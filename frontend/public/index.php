<?php
declare(strict_types=1);
/**
 * Main Router – HYBRID SYSTEM
 * - Login: Database (users table)
 * - Attendance history: Database (attendance_records)
 * - Dashboard: Google Sheets (cache)
 * - QR Scanner: Google Sheets
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---- Custom session save path ----
// --- SHARED SESSION CONFIGURATION ---
$sessionPath = dirname(__DIR__, 2) . '/storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);
session_name('INsite_SESSION');

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// ---- Determine base URL dynamically ----
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDir  = rtrim(dirname($scriptName), '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

// ---- Parse request path ----
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
    $path = substr($path, strlen($baseUrl)) ?: '/';
}
if (str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName)) ?: '/';
}
$path = '/' . ltrim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Load functions ----
require_once __DIR__ . '/../data/functions.php';
require_once __DIR__ . '/../../backend/src/config/DataBase.php';
require_once __DIR__ . '/../../backend/src/models/User.php';

// ---- Helper functions ----
function route_url(string $path = '/'): string
{
    global $baseUrl;
    $path = '/' . ltrim($path, '/');
    return ($baseUrl ?: '') . ($path === '/' ? '' : $path);
}

function asset_url(string $path): string
{
    global $baseUrl;
    return ($baseUrl ?: '') . '/assets/' . ltrim($path, '/');
}

function view(string $view, array $data = []): void
{
    extract($data);
    require __DIR__ . '/../src/views/' . $view . '.php';
}

function redirect_to(string $path): never
{
    header('Location: ' . route_url($path));
    exit;
}

/**
 * Live lookup of optional profile fields (department, position) straight
 * from the database, keyed off the authenticated session's user id.
 *
 * This reuses the backend's existing DataBase config class as-is (read only,
 * no changes made to it) so the Staff Profile page always reflects the real
 * users table instead of anything hardcoded. Safe even if the department/
 * position columns don't exist yet — falls back to null.
 */
function get_live_profile_fields(int $userId): array
{
    static $cache = [];
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    $fields = ['department' => null, 'position' => null];

    try {
        $dbConfigPath = dirname(__DIR__, 2) . '/backend/src/config/DataBase.php';
        if (file_exists($dbConfigPath)) {
            require_once $dbConfigPath;
            $pdo = DataBase::getConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $fields['department'] = $row['department'] ?? null;
                $fields['position']   = $row['position'] ?? null;
            }
        }
    } catch (\Throwable $e) {
        // DB unreachable or columns not present yet — keep the null defaults.
    }

    $cache[$userId] = $fields;
    return $fields;
}

function require_auth(?string $role = null): array
{
    if (!isset($_SESSION['user_id'])) {
        redirect_to('/login');
    }
    if ($role !== null && ($_SESSION['user_role'] ?? '') !== $role) {
        redirect_to('/login');
    }
    $profileExtras = get_live_profile_fields((int) $_SESSION['user_id']);
    return [
        'id'         => $_SESSION['user_id'],
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'employeeId' => $_SESSION['employee_id'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'staff',
        'department' => $profileExtras['department'],
        'position'   => $profileExtras['position'],
    ];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Staff Attendance History — read from MySQL attendance_records.
 *
 * The history page expects:
 *   { success: true, data: [ { id, date, timestamp, type, location, method, sync } ] }
 *
 * DB stores type as sign_in / sign_out. The page groups on sign-in / sign-out.
 */
function get_user_history_from_db($userId): array
{
    if ($userId === null || $userId === '') {
        return ['success' => false, 'message' => 'Authentication required'];
    }

    try {
        $pdo = DataBase::getConnection();
        $stmt = $pdo->prepare(
            "SELECT id, type, timestamp, location, device, sync_status
             FROM attendance_records
             WHERE user_id = :user_id
             ORDER BY timestamp DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $history = array_map(static function (array $row): array {
            $timestamp = $row['timestamp'] ?? null;
            $type = $row['type'] ?? '';

            if ($type === 'sign_in') {
                $mappedType = 'sign-in';
            } elseif ($type === 'sign_out') {
                $mappedType = 'sign-out';
            } else {
                $mappedType = $type;
            }

            return [
                'id'        => $row['id'],
                'date'      => $timestamp ? date('Y-m-d', strtotime($timestamp)) : null,
                'timestamp' => $timestamp,
                'type'      => $mappedType,
                'location'  => $row['location'] ?? 'Office',
                'method'    => $row['device'] ?? 'QR',
                'sync'      => $row['sync_status'] ?? 'pending',
            ];
        }, $rows);

        return ['success' => true, 'data' => $history];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================
// HANDLE LOGIN - DATABASE (HYBRID)
// ============================================================
if ($path === '/login' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $pdo = DataBase::getConnection();
        $userModel = new Models\User($pdo);

        // Find user by employee_id (works for both staff and admins)
        $user = $userModel->findByEmployeeId($username);

        if (!$user) {
            $_SESSION['login_error'] = '❌ Invalid Staff ID/Admin ID or PIN/Password.';
            redirect_to('/login');
        }

        // Check password or PIN
        $valid = false;

        // For admins: check password_hash
        if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            $valid = true;
        }

        // For staff: check 'passwords' column (PIN)
        if (isset($user['passwords']) && $user['passwords'] === $password) {
            $valid = true;
        }

        if (!$valid) {
            $_SESSION['login_error'] = '❌ Invalid Staff ID/Admin ID or PIN/Password.';
            redirect_to('/login');
        }

        if ($user['status'] !== 'active') {
            $_SESSION['login_error'] = '❌ Account is inactive. Contact administrator.';
            redirect_to('/login');
        }

        // Set session
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_type'] = $user['role'];
        $_SESSION['staff_id'] = $user['employee_id'];
        $_SESSION['staff_name'] = $user['name'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['employee_id'] = $user['employee_id'];

        if (!empty($_SESSION['redirect_after_login'])) {
            $redirectUrl = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirectUrl);
            exit;
        }

        if ($user['role'] === 'admin') {
            redirect_to('/admin-dashboard');
        } else {
            redirect_to('/staff-dashboard');
        }

    } catch (Exception $e) {
        $_SESSION['login_error'] = '❌ Database error: ' . $e->getMessage();
        redirect_to('/login');
    }
}

// ============================================================
// HANDLE LOGOUT
// ============================================================
if ($path === '/logout') {
    session_destroy();
    redirect_to('/login');
}

// ============================================================
// API ROUTES - HYBRID (Database + Google Sheets)
// ============================================================
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $apiPath = str_replace('.php', '', $path);

    $apiMap = [
        // ---- DATABASE APIS ----
        '/api/users' => function() {
            echo json_encode(getAllUsers());
        },

        // ---- GOOGLE SHEETS APIS (KEEP THESE) ----
        '/api/dashboard-stats' => function() {
            echo json_encode(getDashboardStats());
        },
        '/api/onsite-staff' => function() {
            echo json_encode(getOnsiteStaff());
        },
        '/api/all-staff' => function() {
            echo json_encode(getAllStaffWithStatus());
        },
        '/api/recent-activity' => function() {
            echo json_encode(getRecentActivity());
        },
        '/api/sign-in' => function() {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(handleClockIn($data));
        },
        '/api/sign-out' => function() {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(handleClockOut($data));
        },
        '/api/user-history' => function () {
            // SECURITY: always use the authenticated session's user id.
            // A user_id passed in the query string is intentionally ignored
            // so one employee can never request another employee's history.
            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                return;
            }

            $result = get_user_history_from_db($userId);
            if (empty($result['success'])) {
                http_response_code(500);
            }
            echo json_encode($result);
        },
        '/api/attendance-logs' => function() {
            echo json_encode(getAllAttendanceLogs());
        },
        '/api/check-scan-result' => function() {
            if (isset($_SESSION['qr_result'])) {
                $result = $_SESSION['qr_result'];
                unset($_SESSION['qr_result']);
                echo json_encode([
                    'success' => true,
                    'name' => $result['name'] ?? '',
                    'action' => $result['action'] ?? '',
                    'location' => $result['location'] ?? 'HQ',
                    'timestamp' => $result['timestamp'] ?? date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
        },
        '/api/test-sheets-connection' => function() {
            $connected = false;
            $lastSync = '';
            $cacheAge = 'Unknown';
            $error = '';
            try {
                $data = fetchSheetsData();
                if ($data && isset($data['success']) && $data['success']) {
                    $connected = true;
                    if (file_exists(CACHE_FILE)) {
                        $cached = json_decode(file_get_contents(CACHE_FILE), true);
                        if ($cached && isset($cached['fetched_at'])) {
                            $lastSync = date('Y-m-d H:i:s', $cached['fetched_at']);
                            $age = time() - $cached['fetched_at'];
                            if ($age < 60) $cacheAge = $age . ' seconds ago';
                            elseif ($age < 3600) $cacheAge = round($age / 60) . ' minutes ago';
                            else $cacheAge = round($age / 3600) . ' hours ago';
                        }
                    }
                } else {
                    $error = $data['error'] ?? 'Failed to fetch data';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            echo json_encode([
                'success' => true,
                'connected' => $connected,
                'last_sync' => $lastSync,
                'cache_age' => $cacheAge,
                'error' => $error
            ]);
        },
        '/api/clear-cache' => function() {
            try {
                if (file_exists(CACHE_FILE)) unlink(CACHE_FILE);
                updateCache();
                echo json_encode(['success' => true, 'message' => 'Cache cleared and refreshed']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        },
        '/api/refresh-data' => function() {
            try {
                $result = updateCache();
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Data refreshed successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to refresh data']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        },
    ];

    if (isset($apiMap[$apiPath])) {
        $apiMap[$apiPath]();
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API endpoint not found: ' . $apiPath]);
    }
    exit;
}

// ============================================================
// PAGE ROUTES
// ============================================================
switch ($path) {
    case '/':
    case '/login':
        $title = 'Login | SpySee';
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        view('login', compact('title', 'error'));
        break;

    case '/staff-dashboard':
    case '/dashboard.php':
        $title = 'Staff Dashboard | SpySee';
        $user = require_auth('staff');
        view('staff/dashboard', compact('title', 'user'));
        break;

    case '/scan-qr':
        $title = 'Scan QR Code | SpySee';
        $user = require_auth('staff');
        view('staff/scan-qr', compact('title', 'user'));
        break;

    case '/history':
        $title = 'Attendance History | SpySee';
        $user = require_auth('staff');
        view('staff/history', compact('title', 'user'));
        break;

    case '/calendar':
        $title = 'Calendar | SpySee';
        $user = require_auth('staff');
        view('staff/calendar', compact('title', 'user'));
        break;

    case '/profile':
        $title = 'Profile | SpySee';
        $user = require_auth('staff');
        view('staff/profile', compact('title', 'user'));
        break;

    case '/admin-dashboard':
        $user = require_auth('admin');
        $title = 'Admin Dashboard | SpySee';
        view('admin/dashboard', compact('title', 'user'));
        break;

    case '/admin-dashboard/users':
        $user = require_auth('admin');
        $title = 'User Management | SpySee';
        view('admin/users', compact('title', 'user'));
        break;

    case '/admin-dashboard/attendance':
        $user = require_auth('admin');
        $title = 'Attendance Logs | SpySee';
        view('admin/attendance', compact('title', 'user'));
        break;

    case '/admin-dashboard/qr':
    case '/admin-dashboard/qr-generator':
        $user = require_auth('admin');
        $title = 'QR Terminal | SpySee';
        view('admin/qr', compact('title', 'user'));
        break;

    case '/admin-dashboard/settings':
        $user = require_auth('admin');
        $title = 'Settings | SpySee';
        view('admin/settings', compact('title', 'user'));
        break;

    default:
        http_response_code(404);
        $title = 'Not Found | SpySee';
        view('404', compact('title'));
        break;
}