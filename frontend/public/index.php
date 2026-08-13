<?php
// Main Router for SpySee System
declare(strict_types=1);

session_start();

// ============================================================
// RATE LIMITING - Prevent API flooding
// ============================================================
function checkRateLimit() {
    $rateLimitKey = 'rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rateLimitFile = sys_get_temp_dir() . '/' . $rateLimitKey . '.json';
    $now = time();
    $limit = 20; // Max 20 requests per minute
    $window = 60;
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        if ($data && isset($data['count']) && isset($data['reset'])) {
            if ($now < $data['reset']) {
                if ($data['count'] >= $limit) {
                    http_response_code(429);
                    echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait.']);
                    exit;
                }
                $data['count']++;
            } else {
                $data = ['count' => 1, 'reset' => $now + $window];
            }
        } else {
            $data = ['count' => 1, 'reset' => $now + $window];
        }
    } else {
        $data = ['count' => 1, 'reset' => $now + $window];
    }
    file_put_contents($rateLimitFile, json_encode($data));
}

// ============================================================
// ROUTER SETUP
// ============================================================
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$baseUrl = $baseUrl === '' ? '' : $baseUrl;

$path = $requestPath;
if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
    $path = substr($path, strlen($baseUrl)) ?: '/';
}
if (str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName)) ?: '/';
}

$path = '/' . trim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================
// STATIC FILE HANDLING - Cached aggressively
// ============================================================
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map'];
$pathInfo = pathinfo($requestPath);

if (isset($pathInfo['extension']) && in_array($pathInfo['extension'], $staticExtensions)) {
    $filePath = __DIR__ . '/assets/' . ltrim($requestPath, '/');
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject', 'map' => 'application/json'
        ];
        if (isset($mimeTypes[$pathInfo['extension']])) {
            header('Content-Type: ' . $mimeTypes[$pathInfo['extension']]);
        }
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($filePath);
        exit;
    }
}

// ============================================================
// LOAD FUNCTIONS
// ============================================================
require_once __DIR__ . '/../data/functions.php';

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function view(string $view, array $data = []): void
{
    extract($data);
    require __DIR__ . '/../src/views/' . $view . '.php';
}

function route_url(string $path = '/'): string
{
    $path = '/' . trim($path, '/');
    return $path === '/' ? '/index.php' : '/index.php' . $path;
}

function asset_url(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . route_url($path));
    exit;
}

function getSessionRole(): ?string {
    return $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? null;
}

// ============================================================
// HANDLE LOGIN
// ============================================================
if ($path === '/login' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $creds = getCredentialsFromSheets();
    
    if ($creds && $creds['success']) {
        $loggedIn = false;
        foreach ($creds['staff'] as $staff) {
            $staffId = $staff['staff_id'] ?? '';
            $staffPin = $staff['pin'] ?? $staff['PIN'] ?? '';
            $staffActive = $staff['active'] ?? $staff['Active'] ?? 'NO';
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
                header('Location: /index.php/staff-dashboard');
                exit;
            }
        }
        foreach ($creds['admins'] as $admin) {
            $adminId = $admin['admin_id'] ?? '';
            $adminPass = $admin['password'] ?? $admin['Password'] ?? '';
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
                header('Location: /index.php/admin-dashboard');
                exit;
            }
        }
        if (!$loggedIn) {
            $_SESSION['login_error'] = '❌ Invalid Staff ID/Admin ID or PIN/Password.';
        }
    } else {
        $_SESSION['login_error'] = '❌ Could not connect to Google Sheets. Check your config.';
    }
    header('Location: /index.php/login');
    exit;
}

// ============================================================
// HANDLE LOGOUT
// ============================================================
if ($path === '/logout') {
    session_destroy();
    header('Location: /index.php/login');
    exit;
}

// ============================================================
// API ROUTES - With Rate Limiting
// ============================================================
if ($path === '/api' || str_starts_with($path, '/api/')) {
    // Apply rate limiting to API calls
    checkRateLimit();
    
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=5');
    
    $apiPath = str_replace('.php', '', $path);
    
    switch ($apiPath) {
        case '/api/dashboard-stats':
            echo json_encode(getDashboardStats());
            break;
        case '/api/onsite-staff':
            echo json_encode(getOnsiteStaff());
            break;
        case '/api/all-staff':
            echo json_encode(getAllStaffWithStatus());
            break;
        case '/api/recent-activity':
            echo json_encode(getRecentActivity());
            break;
        case '/api/sign-in':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(handleClockIn($data));
            break;
        case '/api/sign-out':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(handleClockOut($data));
            break;
        case '/api/user-history':
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? '';
            echo json_encode(getUserHistory($userId));
            break;
        case '/api/attendance-logs':
            echo json_encode(getAllAttendanceLogs());
            break;
        case '/api/users':
            echo json_encode(getAllUsers());
            break;
        case '/api/check-scan-result':
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
            break;
        case '/api/test-sheets-connection':
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
            break;
        case '/api/clear-cache':
            try {
                if (file_exists(CACHE_FILE)) unlink(CACHE_FILE);
                updateCache();
                echo json_encode(['success' => true, 'message' => 'Cache cleared and refreshed']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
        case '/api/refresh-data':
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
            break;
        case '/api/settings':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            switch ($action) {
                case 'update_session_timeout':
                    $timeout = intval($input['timeout'] ?? 30);
                    if ($timeout < 5) $timeout = 5;
                    if ($timeout > 1440) $timeout = 1440;
                    ini_set('session.gc_maxlifetime', $timeout * 60);
                    echo json_encode(['success' => true, 'message' => 'Session timeout updated to ' . $timeout . ' minutes']);
                    break;
                default:
                    echo json_encode(['success' => false, 'error' => 'Unknown action']);
            }
            break;
        case '/api/clear-old-data':
            $input = json_decode(file_get_contents('php://input'), true);
            $days = intval($input['days'] ?? 90);
            try {
                $data = getCachedData();
                if (!$data || !isset($data['rows'])) {
                    echo json_encode(['success' => true, 'deleted' => 0]);
                    break;
                }
                $rows = $data['rows'] ?? [];
                $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
                $deleted = 0;
                $newRows = [];
                $header = [];
                if (!empty($rows) && is_array($rows[0])) {
                    $firstRow = array_values($rows[0]);
                    $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
                    if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
                        $header = array_shift($rows);
                    }
                }
                foreach ($rows as $row) {
                    if (isset($row[0])) {
                        try {
                            $date = new DateTime($row[0]);
                            if ($date < new DateTime($cutoff)) {
                                $deleted++;
                                continue;
                            }
                        } catch (Exception $e) {}
                    }
                    $newRows[] = $row;
                }
                if (!empty($header)) array_unshift($newRows, $header);
                $data['rows'] = $newRows;
                file_put_contents(CACHE_FILE, json_encode([
                    'data' => $data,
                    'fetched_at' => time()
                ]));
                echo json_encode(['success' => true, 'deleted' => $deleted, 'remaining' => count($newRows)]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
        default:
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
        if (!isLoggedIn()) { redirect_to('/login'); break; }
        $role = getSessionRole();
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
        if (!isLoggedIn() || getSessionRole() !== 'staff') { redirect_to('/login'); break; }
        $title = 'Scan QR Code | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/scan-qr', compact('title', 'user'));
        break;
    case '/history':
        if (!isLoggedIn() || getSessionRole() !== 'staff') { redirect_to('/login'); break; }
        $title = 'Attendance History | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/history', compact('title', 'user'));
        break;
    case '/calendar':
        if (!isLoggedIn() || getSessionRole() !== 'staff') { redirect_to('/login'); break; }
        $title = 'Calendar | SpySee';
        $user = ['id' => $_SESSION['staff_id'] ?? 'STF-001', 'name' => $_SESSION['staff_name'] ?? 'Staff'];
        view('staff/calendar', compact('title', 'user'));
        break;
    case '/profile':
        if (!isLoggedIn() || getSessionRole() !== 'staff') { redirect_to('/login'); break; }
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
        if (!isLoggedIn() || getSessionRole() !== 'admin') { redirect_to('/login'); break; }
        $title = 'Admin Dashboard | SpySee';
        view('admin/dashboard', compact('title'));
        break;
    case '/admin-dashboard/users':
        if (!isLoggedIn() || getSessionRole() !== 'admin') { redirect_to('/login'); break; }
        $title = 'User Management | SpySee';
        view('admin/users', compact('title'));
        break;
    case '/admin-dashboard/attendance':
        if (!isLoggedIn() || getSessionRole() !== 'admin') { redirect_to('/login'); break; }
        $title = 'Attendance Logs | SpySee';
        view('admin/attendance', compact('title'));
        break;
    case '/admin-dashboard/qr':
    case '/admin-dashboard/qr-generator':
    case '/admin-dashboard/qr-display':
        if (!isLoggedIn() || getSessionRole() !== 'admin') { redirect_to('/login'); break; }
        $title = 'QR Terminal | SpySee';
        view('admin/qr', compact('title'));
        break;
    case '/admin-dashboard/settings':
        if (!isLoggedIn() || getSessionRole() !== 'admin') { redirect_to('/login'); break; }
        $title = 'Settings | SpySee';
        view('admin/settings', compact('title'));
        break;
    default:
        http_response_code(404);
        $title = 'Not Found | SpySee';
        view('404', compact('title'));
        break;
}