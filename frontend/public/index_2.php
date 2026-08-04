<?php
// FRONTEND-ONLY ROUTER
// No Composer, no vendor folder, no backend models/controllers, no PHPUnit needed.
// This file only provides sample data so the PHP pages can display in the browser.

declare(strict_types=1);

$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

session_start();

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

$staffUser = [
    'id' => 'staff-001',
    'name' => 'Will Mxabanisi',
    'email' => 'staff@clockit.app',
    'employeeId' => 'EMP-001',
    'role' => 'staff',
];

$adminUser = [
    'id' => 'admin-001',
    'name' => 'Demo Admin',
    'email' => 'admin@clockit.app',
    'employeeId' => 'ADM-001',
    'role' => 'admin',
];

$users = [
    $staffUser,
    ['id' => 'staff-002', 'name' => 'Anele Mokoena', 'email' => 'anele@clockit.app', 'employeeId' => 'EMP-002', 'role' => 'staff'],
    ['id' => 'staff-003', 'name' => 'Lihle Dlamini', 'email' => 'lihle@clockit.app', 'employeeId' => 'EMP-003', 'role' => 'staff'],
    $adminUser,
];

$events = [
    ['userName' => 'Demo Staff', 'type' => 'clock-in', 'timestamp' => date('Y-m-d') . ' 08:00'],
    ['userName' => 'Anele Mokoena', 'type' => 'clock-in', 'timestamp' => date('Y-m-d') . ' 08:15'],
    ['userName' => 'Lihle Dlamini', 'type' => 'clock-out', 'timestamp' => date('Y-m-d') . ' 16:02'],
];

$stats = [
    'currentlyOnsite' => 2,
    'totalStaffToday' => 3,
    'pendingSync' => 0,
    'totalEvents' => count($events),
];

$onsiteStaff = [
    ['name' => 'Demo Staff', 'employeeId' => 'EMP-001', 'clockedInAt' => '08:00'],
    ['name' => 'Anele Mokoena', 'employeeId' => 'EMP-002', 'clockedInAt' => '08:15'],
];

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

// FIXED: Now uses dynamic $path parameter instead of hardcoding '/dashboard.php'
function redirect_to(string $path): never
{
    header('Location: ' . route_url($path));
    exit;
}

function login_as(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
}

// Frontend-only login simulation: no real authentication.
if ($path === '/login' && $method === 'POST') {
    $identifier = strtolower(trim((string) ($_POST['identifier'] ?? '')));
    // Redirect Staff/CEO to /loading instead of directly to /staff-dashboard
    redirect_to(str_contains($identifier, 'admin') ? '/admin-dashboard' : '/loading');
}

if ($path === '/logout') {
    redirect_to('/');
}

if ($path === '/profile/password' && $method === 'POST') {
    $_SESSION['flash'] = ['valid' => true, 'message' => 'Frontend demo: password form submitted successfully.'];
    redirect_to('/profile');
}

switch ($path) {
    case '/':
    case '/login':
        $title = 'Login | Clock-It';
        view('login', compact('title'));
        break;

    // ADDED: Loading route for staff login sequence
    case '/loading':
    case '/loading.php':
    case '/staff/loading.php':
        $title = 'Entering Hub... | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/loading', compact('title', 'user'));
        break;

    case '/staff-dashboard':
    case '/dashboard.php':
        $title = 'Staff Dashboard | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/dashboard', compact('title', 'user', 'stats', 'events'));
        break;

    case '/scan-qr':
    case '/scan-qr.php':
        $title = 'Scan QR | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/scan-qr', compact('title', 'user'));
        break;

    case '/history':
    case '/history.php':
        $title = 'History | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/history', compact('title', 'user', 'events'));
        break;

    case '/calendar':
    case '/calendar.php':
        $title = 'Calendar | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/calendar', compact('title', 'user'));
        break;

    case '/profile':
    case '/profile.php':
        $title = 'Profile | Clock-It';
        $user = $staffUser;
        login_as($user);
        view('staff/profile', compact('title', 'user'));
        break;

    case '/admin-dashboard':
    case '/admin/dashboard.php':
        $title = 'Admin Dashboard | Clock-It';
        $user = $adminUser;
        login_as($user);
        view('admin/dashboard', compact('title', 'user', 'stats', 'events', 'onsiteStaff'));
        break;

    case '/admin-dashboard/users':
    case '/admin/users.php':
        $title = 'Users | Clock-It';
        $user = $adminUser;
        login_as($user);
        view('admin/users', compact('title', 'user', 'users'));
        break;

    case '/admin-dashboard/attendance':
    case '/admin/attendance.php':
        $title = 'Attendance | Clock-It';
        $user = $adminUser;
        login_as($user);
        view('admin/attendance', compact('title', 'user', 'events'));
        break;

    case '/admin-dashboard/qr-generator':
    case '/admin/qr.php':
        $title = 'QR Generator | Clock-It';
        $user = $adminUser;
        login_as($user);
        view('admin/qr', compact('title', 'user'));
        break;

    case '/admin-dashboard/settings':
    case '/admin/settings.php':
        $title = 'Settings | Clock-It';
        $user = $adminUser;
        login_as($user);
        view('admin/settings', compact('title', 'user'));
        break;

    default:
        http_response_code(404);
        $title = 'Not Found | Clock-It';
        view('404', compact('title'));
        break;
}