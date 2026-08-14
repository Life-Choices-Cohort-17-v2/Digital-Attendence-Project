<?php
declare(strict_types=1);
/**
 * Main Router – Google Sheets Integration
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---- Custom session save path ----
$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}
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

// ---- Load Google Sheets functions ----
require_once __DIR__ . '/../data/functions.php';

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

// ============================================================
// HANDLE LOGIN - GOOGLE SHEETS (WORKING)
// ============================================================
if ($path === '/login' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $creds = getCredentialsFromSheets();
    
    $loggedIn = false;
    
    if ($creds && is_array($creds) && isset($creds['success']) && $creds['success'] === true) {
        
        // Check STAFF credentials
        if (isset($creds['staff']) && is_array($creds['staff'])) {
            foreach ($creds['staff'] as $staff) {
                $staffId = $staff['staff_id'] ?? '';
                $staffPin = $staff['pin'] ?? '';
                $staffActive = $staff['active'] ?? 'NO';
                
                if ($staffId === $username && $staffPin === $password && strtoupper($staffActive) === 'YES') {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'staff';
                    $_SESSION['user_role'] = 'staff';
                    $_SESSION['staff_id'] = $staffId;
                    $_SESSION['staff_name'] = $staff['name'] ?? 'Staff';
                    $_SESSION['user_id'] = $staffId;
                    $_SESSION['user_name'] = $staff['name'] ?? 'Staff';
                    $_SESSION['employee_id'] = $staffId;
                    $loggedIn = true;
                    
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                    redirect_to('/staff-dashboard');
                }
            }
        }
        
        // Check ADMIN credentials
        if (isset($creds['admins']) && is_array($creds['admins'])) {
            foreach ($creds['admins'] as $admin) {
                $adminId = $admin['admin_id'] ?? '';
                $adminPass = $admin['password'] ?? '';
                
                if ($adminId === $username && $adminPass === $password) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['staff_id'] = $adminId;
                    $_SESSION['staff_name'] = $admin['name'] ?? 'Admin';
                    $_SESSION['user_id'] = $adminId;
                    $_SESSION['user_name'] = $admin['name'] ?? 'Admin';
                    $_SESSION['employee_id'] = $adminId;
                    $loggedIn = true;
                    
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                    redirect_to('/admin-dashboard');
                }
            }
        }
    } else {
        $errorMsg = isset($creds['error']) ? $creds['error'] : 'Unknown error';
        $_SESSION['login_error'] = '❌ Could not connect to Google Sheets: ' . $errorMsg;
        redirect_to('/login');
    }
    
    if (!$loggedIn) {
        $_SESSION['login_error'] = '❌ Invalid Staff ID/Admin ID or PIN/Password.';
    }
    redirect_to('/login');
}

// ============================================================
// HANDLE LOGOUT
// ============================================================
if ($path === '/logout') {
    session_destroy();
    redirect_to('/login');
}

// ============================================================
// API ROUTES - GOOGLE SHEETS DATA
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
        '/api/user-history' => function() {
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? '';
            echo json_encode(getUserHistory($userId));
        },
        '/api/attendance-logs' => function() {
            echo json_encode(getAllAttendanceLogs());
        },
        '/api/users' => function() {
            echo json_encode(getAllUsers());
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
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { redirect_to('/login'); break; }
        $role = $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? null;
        if ($role === 'admin') { redirect_to('/admin-dashboard'); break; }
        if ($role !== 'staff') { redirect_to('/login'); break; }
        $title = 'Staff Dashboard | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? $_SESSION['user_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? '',
            'employeeId' => $_SESSION['staff_id'] ?? $_SESSION['employee_id'] ?? 'STF-001',
            'role' => 'staff'
        ];
        view('staff/dashboard', compact('title', 'user'));
        break;
        
    case '/scan-qr':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') { redirect_to('/login'); break; }
        $title = 'Scan QR Code | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/scan-qr', compact('title', 'user'));
        break;
        
    case '/history':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') { redirect_to('/login'); break; }
        $title = 'Attendance History | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/history', compact('title', 'user'));
        break;
        
    case '/calendar':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') { redirect_to('/login'); break; }
        $title = 'Calendar | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/calendar', compact('title', 'user'));
        break;
        
    case '/profile':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') { redirect_to('/login'); break; }
        $title = 'Profile | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? '',
            'employeeId' => $_SESSION['staff_id'] ?? 'STF-001'
        ];
        view('staff/profile', compact('title', 'user'));
        break;
        
    case '/admin-dashboard':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') { redirect_to('/login'); break; }
        $title = 'Admin Dashboard | SpySee';
        view('admin/dashboard', compact('title'));
        break;
        
    case '/admin-dashboard/users':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') { redirect_to('/login'); break; }
        $title = 'User Management | SpySee';
        view('admin/users', compact('title'));
        break;
        
    case '/admin-dashboard/attendance':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') { redirect_to('/login'); break; }
        $title = 'Attendance Logs | SpySee';
        view('admin/attendance', compact('title'));
        break;
        
    case '/admin-dashboard/qr':
    case '/admin-dashboard/qr-generator':
    case '/admin-dashboard/qr-display':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') { redirect_to('/login'); break; }
        $title = 'QR Terminal | SpySee';
        view('admin/qr', compact('title'));
        break;
        
    case '/admin-dashboard/settings':
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') { redirect_to('/login'); break; }
        $title = 'Settings | SpySee';
        view('admin/settings', compact('title'));
        break;
        
    default:
        http_response_code(404);
        $title = 'Not Found | SpySee';
        view('404', compact('title'));
        break;
}