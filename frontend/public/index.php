<?php
// Main Router for SpySee System
declare(strict_types=1);

session_start();

// Force no cache for development
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ============================================================
// ROUTER SETUP
// ============================================================
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$baseUrl = $baseUrl === '' ? '' : $baseUrl;

if (str_starts_with($requestPath, $scriptName)) {
    $path = substr($requestPath, strlen($scriptName)) ?: '/';
} elseif ($baseUrl !== '' && str_starts_with($requestPath, $baseUrl)) {
    $path = substr($requestPath, strlen($baseUrl)) ?: '/';
} else {
    $path = $requestPath;
}

$path = '/' . trim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
    $path = $path === '/' ? '/' : $path;
    return ($GLOBALS['baseUrl'] ?? '') . '/index.php' . ($path === '/' ? '' : $path);
}

function asset_url(string $path): string
{
    return ($GLOBALS['baseUrl'] ?? '') . '/assets/' . ltrim($path, '/');
}

function api_url(string $path = ''): string
{
    $path = '/' . trim($path, '/');
    return ($GLOBALS['baseUrl'] ?? '') . '/index.php/api' . ($path === '/' ? '' : $path);
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
// HANDLE LOGIN - SUPPORTS ALL STAFF AND ADMINS
// ============================================================
if ($path === '/login' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $creds = getCredentialsFromSheets();
    
    if ($creds && $creds['success']) {
        $loggedIn = false;
        
        // Check ALL Staff - STF-001 to STF-005
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
        
        // Check ALL Admins - ADMIN_001, ADMIN_002
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
// API ROUTES - CLEAN SWITCH
// ============================================================
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json');
    
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
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
            break;
    }
    exit;
}

// ============================================================
// PAGE ROUTES - CLEAN SWITCH
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
        if (!isLoggedIn()) {
            redirect_to('/login');
            break;
        }
        
        $role = getSessionRole();
        if ($role === 'admin') {
            redirect_to('/admin-dashboard');
            break;
        }
        if ($role !== 'staff') {
            redirect_to('/login');
            break;
        }
        
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
        if (!isLoggedIn() || getSessionRole() !== 'staff') {
            redirect_to('/login');
            break;
        }
        $title = 'Scan QR Code | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('staff/scan-qr', compact('title', 'user'));
        break;

    case '/history':
        if (!isLoggedIn() || getSessionRole() !== 'staff') {
            redirect_to('/login');
            break;
        }
        $title = 'Attendance History | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('staff/history', compact('title', 'user'));
        break;

    case '/calendar':
        if (!isLoggedIn() || getSessionRole() !== 'staff') {
            redirect_to('/login');
            break;
        }
        $title = 'Calendar | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('staff/calendar', compact('title', 'user'));
        break;

    case '/profile':
        if (!isLoggedIn() || getSessionRole() !== 'staff') {
            redirect_to('/login');
            break;
        }
        $title = 'Profile | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'email' => $_SESSION['user_email'] ?? '',
            'employeeId' => $_SESSION['staff_id'] ?? 'STF-001',
            'role' => 'staff'
        ];
        view('staff/profile', compact('title', 'user'));
        break;

    case '/admin-dashboard':
        if (!isLoggedIn() || getSessionRole() !== 'admin') {
            redirect_to('/login');
            break;
        }
        $title = 'Admin Dashboard | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'ADMIN-001',
            'name' => $_SESSION['staff_name'] ?? 'Admin',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('admin/dashboard', compact('title', 'user'));
        break;

    case '/admin-dashboard/users':
        if (!isLoggedIn() || getSessionRole() !== 'admin') {
            redirect_to('/login');
            break;
        }
        $title = 'User Management | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'ADMIN-001',
            'name' => $_SESSION['staff_name'] ?? 'Admin',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('admin/users', compact('title', 'user'));
        break;

    case '/admin-dashboard/attendance':
        if (!isLoggedIn() || getSessionRole() !== 'admin') {
            redirect_to('/login');
            break;
        }
        $title = 'Attendance Logs | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'ADMIN-001',
            'name' => $_SESSION['staff_name'] ?? 'Admin',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('admin/attendance', compact('title', 'user'));
        break;

    case '/admin-dashboard/qr':
    case '/admin-dashboard/qr-generator':
    case '/admin-dashboard/qr-display':
        if (!isLoggedIn() || getSessionRole() !== 'admin') {
            redirect_to('/login');
            break;
        }
        $title = 'QR Terminal | SpySee';
        view('admin/qr', compact('title'));
        break;

    case '/admin-dashboard/settings':
        if (!isLoggedIn() || getSessionRole() !== 'admin') {
            redirect_to('/login');
            break;
        }
        $title = 'Settings | SpySee';
        $user = [
            'id' => $_SESSION['staff_id'] ?? 'ADMIN-001',
            'name' => $_SESSION['staff_name'] ?? 'Admin',
            'email' => $_SESSION['user_email'] ?? ''
        ];
        view('admin/settings', compact('title', 'user'));
        break;

    default:
        http_response_code(404);
        $title = 'Not Found | SpySee';
        view('404', compact('title'));
        break;
}