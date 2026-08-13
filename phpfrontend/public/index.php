<?php
// Main Router for Clock-It System
declare(strict_types=1);

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

// Load data functions
require_once __DIR__ . '/../data/functions.php';

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

function redirect_to(string $path): never
{
    header('Location: ' . route_url($path));
    exit;
}

// Handle login
if ($path === '/login' && $method === 'POST') {
    $identifier = strtolower(trim((string) ($_POST['identifier'] ?? '')));
    $password = $_POST['password'] ?? '';
    
    if (($identifier === 'admin@clockit.app' || $identifier === 'admin') && $password === 'admin123') {
        $_SESSION['user_id'] = 'admin-001';
        $_SESSION['user_name'] = 'Admin User';
        $_SESSION['user_email'] = 'admin@clockit.app';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['employee_id'] = 'ADM-001';
        redirect_to('/admin-dashboard');
    } elseif (($identifier === 'sarah@clockit.app' || $identifier === 'sarah') && $password === 'sarah123') {
        $_SESSION['user_id'] = 'staff-001';
        $_SESSION['user_name'] = 'Sarah Mthembu';
        $_SESSION['user_email'] = 'sarah@clockit.app';
        $_SESSION['user_role'] = 'staff';
        $_SESSION['employee_id'] = 'S-101';
        redirect_to('/staff-dashboard');
    } elseif (($identifier === 'staff@clockit.app' || $identifier === 'staff') && $password === 'password123') {
        $_SESSION['user_id'] = 'staff-002';
        $_SESSION['user_name'] = 'Demo Staff';
        $_SESSION['user_email'] = 'staff@clockit.app';
        $_SESSION['user_role'] = 'staff';
        $_SESSION['employee_id'] = 'EMP-001';
        redirect_to('/staff-dashboard');
    } else {
        $_SESSION['login_error'] = 'Invalid credentials. Use admin@clockit.app/admin123 or sarah@clockit.app/sarah123';
        redirect_to('/login');
    }
}

if ($path === '/logout') {
    session_destroy();
    redirect_to('/login');
}

// API Routes
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json');
    
    if ($path === '/api/dashboard-stats.php') {
        echo json_encode(getDashboardStats());
        exit;
    }
    if ($path === '/api/onsite-staff.php') {
        echo json_encode(getOnsiteStaff());
        exit;
    }
    if ($path === '/api/recent-activity.php') {
        echo json_encode(getRecentActivity());
        exit;
    }
    if ($path === '/api/clock-in.php') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(handleClockIn($data));
        exit;
    }
    if ($path === '/api/clock-out.php') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(handleClockOut($data));
        exit;
    }
    if ($path === '/api/user-history.php') {
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? '';
        echo json_encode(getUserHistory($userId));
        exit;
    }
    if ($path === '/api/attendance-logs.php') {
        echo json_encode(getAllAttendanceLogs());
        exit;
    }
    if ($path === '/api/users.php') {
        echo json_encode(getAllUsers());
        exit;
    }
    if ($path === '/api/qr-codes.php') {
        echo json_encode(getQRCodes());
        exit;
    }
    if ($path === '/api/generate-qr.php') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(generateQRCode($data));
        exit;
    }
    if ($path === '/api/revoke-qr.php') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(revokeQRCode($data));
        exit;
    }
}

// Page Routes
switch ($path) {
    case '/':
    case '/login':
        $title = 'Login | Clock-It';
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        view('login', compact('title', 'error'));
        break;

    case '/staff-dashboard':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
            redirect_to('/login');
        }
        $title = 'Staff Dashboard | Clock-It';
        $user = [
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'employeeId' => $_SESSION['employee_id'],
            'role' => $_SESSION['user_role']
        ];
        view('staff/dashboard', compact('title', 'user'));
        break;

    case '/scan-qr':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
            redirect_to('/login');
        }
        $title = 'Scan QR Code | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('staff/scan-qr', compact('title', 'user'));
        break;

    case '/history':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
            redirect_to('/login');
        }
        $title = 'Attendance History | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('staff/history', compact('title', 'user'));
        break;

    case '/calendar':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
            redirect_to('/login');
        }
        $title = 'Calendar | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('staff/calendar', compact('title', 'user'));
        break;

    case '/profile':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
            redirect_to('/login');
        }
        $title = 'Profile | Clock-It';
        $user = [
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'employeeId' => $_SESSION['employee_id'],
            'role' => $_SESSION['user_role']
        ];
        view('staff/profile', compact('title', 'user'));
        break;

    case '/admin-dashboard':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirect_to('/login');
        }
        $title = 'Admin Dashboard | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('admin/dashboard', compact('title', 'user'));
        break;

    case '/admin-dashboard/users':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirect_to('/login');
        }
        $title = 'User Management | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('admin/users', compact('title', 'user'));
        break;

    case '/admin-dashboard/attendance':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirect_to('/login');
        }
        $title = 'Attendance Logs | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('admin/attendance', compact('title', 'user'));
        break;

    case '/admin-dashboard/qr-generator':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirect_to('/login');
        }
        $title = 'QR Generator | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('admin/qr', compact('title', 'user'));
        break;

    case '/admin-dashboard/settings':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirect_to('/login');
        }
        $title = 'Settings | Clock-It';
        $user = ['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']];
        view('admin/settings', compact('title', 'user'));
        break;

    default:
        http_response_code(404);
        $title = 'Not Found | Clock-It';
        view('404', compact('title'));
        break;
}