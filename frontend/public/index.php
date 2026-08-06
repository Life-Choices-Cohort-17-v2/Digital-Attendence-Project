<?php
declare(strict_types=1);
/**
 * Main Router – with error reporting & robust base path handling
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

// If SCRIPT_NAME is '/index.php', base is empty; else it's the subdirectory.
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

// ---- Parse request path ----
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
// Remove the base URL prefix if present
if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
    $path = substr($path, strlen($baseUrl)) ?: '/';
}
// Remove script name if present
if (str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName)) ?: '/';
}
$path = '/' . ltrim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Load mock data functions ----
require_once __DIR__ . '/../src/views/staff/functions.php';

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

function require_auth(?string $role = null): array
{
    if (!isset($_SESSION['user_id'])) {
        redirect_to('/login');
    }
    if ($role !== null && ($_SESSION['user_role'] ?? '') !== $role) {
        redirect_to('/login');
    }
    return [
        'id'         => $_SESSION['user_id'],
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'employeeId' => $_SESSION['employee_id'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'staff',
    ];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ---- Handle POST login ----
if ($path === '/login' && $method === 'POST') {
    $identifier = strtolower(trim((string) ($_POST['identifier'] ?? '')));
    $password   = $_POST['password'] ?? '';

    if (($identifier === 'admin@spysee.app' || $identifier === 'admin') && $password === 'admin123') {
        $_SESSION['user_id']     = 'admin-001';
        $_SESSION['user_name']   = 'Admin User';
        $_SESSION['user_email']  = 'admin@spysee.app';
        $_SESSION['user_role']   = 'admin';
        $_SESSION['employee_id'] = 'ADM-001';
        redirect_to('/admin/loading');
    } elseif (($identifier === 'sarah@spysee.app' || $identifier === 'sarah') && $password === 'sarah123') {
        $_SESSION['user_id']     = 'staff-001';
        $_SESSION['user_name']   = 'Sarah Mthembu';
        $_SESSION['user_email']  = 'sarah@spysee.app';
        $_SESSION['user_role']   = 'staff';
        $_SESSION['employee_id'] = 'S-101';
        redirect_to('/loading');
    } elseif (($identifier === 'staff@spysee.app' || $identifier === 'staff') && $password === 'password123') {
        $_SESSION['user_id']     = 'staff-002';
        $_SESSION['user_name']   = 'Demo Staff';
        $_SESSION['user_email']  = 'staff@spysee.app';
        $_SESSION['user_role']   = 'staff';
        $_SESSION['employee_id'] = 'EMP-001';
        redirect_to('/loading');
    } else {
        $_SESSION['login_error'] = 'Invalid credentials. Use admin@spysee.app/admin123 or sarah@spysee.app/sarah123';
        redirect_to('/login');
    }
}

if ($path === '/logout') {
    session_destroy();
    redirect_to('/login');
}

// ---- Password update (demo) ----
if ($path === '/profile/password' && $method === 'POST') {
    $_SESSION['flash'] = ['valid' => true, 'message' => 'Password updated (demo).'];
    redirect_to('/profile');
}

// ---- API routes ----
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json');

    $api = [
        '/api/dashboard-stats.php' => function () { echo json_encode(getDashboardStats()); },
        '/api/onsite-staff.php'    => function () { echo json_encode(getOnsiteStaff()); },
        '/api/recent-activity.php' => function () { echo json_encode(getRecentActivity()); },
        '/api/sign-in.php'         => function () {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(handleClockIn($data));
        },
        '/api/sign-out.php'        => function () {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(handleClockOut($data));
        },
        '/api/user-history.php'    => function () {
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? '';
            echo json_encode(getUserHistory($userId));
        },
        '/api/attendance-logs.php' => function () { echo json_encode(getAllAttendanceLogs()); },
        '/api/users.php'           => function () { echo json_encode(getAllUsers()); },
        '/api/qr-codes.php'        => function () { echo json_encode(getQRCodes()); },
        '/api/generate-qr.php'     => function () {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(generateQRCode($data));
        },
        '/api/revoke-qr.php'       => function () {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(revokeQRCode($data));
        },
    ];

    if (isset($api[$path])) {
        $api[$path]();
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API not found']);
    }
    exit;
}

// ---- Page routes ----
switch ($path) {
    case '/':
    case '/login':
        $title = 'Login | SpySee';
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        view('login', compact('title', 'error'));
        break;

    case '/admin/loading':
        $title = 'Entering Admin Hub... | SpySee';
        $user = require_auth('admin');
        view('admin/loading-dashboard', compact('title', 'user'));
        break;

    case '/loading':
    case '/staff/loading.php':
        $title = 'Entering Hub... | SpySee';
        $user = require_auth('staff');
        view('staff/loading', compact('title', 'user'));
        break;

    // ---- Staff routes ----
    case '/staff-dashboard':
    case '/dashboard.php':
        $title = 'Staff Dashboard | SpySee';
        $user = require_auth('staff');
        view('staff/dashboard', compact('title', 'user'));
        break;

    case '/scan-qr':
    case '/scan-qr.php':
        $title = 'Scan QR Code | SpySee';
        $user = require_auth('staff');
        view('staff/scan-qr', compact('title', 'user'));
        break;

    case '/history':
    case '/history.php':
        $title = 'Attendance History | SpySee';
        $user = require_auth('staff');
        view('staff/history', compact('title', 'user'));
        break;

    case '/calendar':
    case '/calendar.php':
        $title = 'Calendar | SpySee';
        $user = require_auth('staff');
        view('staff/calendar', compact('title', 'user'));
        break;

    case '/profile':
    case '/profile.php':
        $title = 'Profile | SpySee';
        $user = require_auth('staff');
        $flash = get_flash();
        view('staff/profile', compact('title', 'user', 'flash'));
        break;

    // ---- Admin routes ----
    case '/admin-dashboard':
    case '/admin/dashboard.php':
        $title = 'Admin Dashboard | SpySee';
        $user = require_auth('admin');
        view('admin/dashboard', compact('title', 'user'));
        break;

    case '/admin-dashboard/users':
    case '/admin/users.php':
        $title = 'User Management | SpySee';
        $user = require_auth('admin');
        view('admin/users', compact('title', 'user'));
        break;

    case '/admin-dashboard/attendance':
    case '/admin/attendance.php':
        $title = 'Attendance Logs | SpySee';
        $user = require_auth('admin');
        view('admin/attendance', compact('title', 'user'));
        break;

    case '/admin-dashboard/qr-generator':
    case '/admin/qr.php':
        $title = 'QR Generator | SpySee';
        $user = require_auth('admin');
        view('admin/qr', compact('title', 'user'));
        break;

    case '/admin-dashboard/settings':
    case '/admin/settings.php':
        $title = 'Settings | SpySee';
        $user = require_auth('admin');
        view('admin/settings', compact('title', 'user'));
        break;

    default:
        http_response_code(404);
        $title = 'Not Found | SpySee';
        view('404', compact('title'));
        break;
}