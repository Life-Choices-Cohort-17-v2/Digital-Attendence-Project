
<?php

// --- SHARED SESSION CONFIGURATION ---
$sessionPath = dirname(__DIR__, 3) . '/storage/sessions';

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
 * CENTRAL APPLICATION ROUTER
 * Merged router supporting Auth (Dev 2), Attendance (Dev 3), and Dashboard (Dev 5).
 */

// --- AUTOLOADER ---
spl_autoload_register(function (string $class) {
    // Standard PSR-4 style mapping relative to project root
    $relPath = str_replace('\\', '/', ltrim($class, '\\')) . '.php';

    // 1. Exact match attempt
    $file = __DIR__ . '/../' . $relPath;
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // 2. Lowercase folder + exact filename fallback (e.g., backend/src/validators/AttendanceValidator.php)
    $parts = explode('\\', ltrim($class, '\\'));
    $fileName = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    $mixedFile = __DIR__ . '/../' . $dirPath . '/' . $fileName . '.php';
    if (file_exists($mixedFile)) {
        require_once $mixedFile;
        return;
    }
});

// --- GLOBAL NON-NAMESPACED DEPENDENCIES ---
if (file_exists(__DIR__ . '/../config/DataBase.php')) {
    require_once __DIR__ . '/../config/DataBase.php';
} elseif (file_exists(__DIR__ . '/../config/Database.php')) {
    require_once __DIR__ . '/../config/Database.php';
}

if (file_exists(__DIR__ . '/../exceptions/AuthenticationException.php')) {
    require_once __DIR__ . '/../exceptions/AuthenticationException.php';
}

// --- DATABASE CONNECTION ---
require_once __DIR__ . '/../config/DataBase.php';

try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    $pdo = null;
}

// --- ROUTE & REQUEST PARSING ---
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Ensure $uri defaults to empty string if parse_url returns null or false
$uri = parse_url($requestUri, PHP_URL_PATH) ?? '';

// Dynamic path resolution (supports subfolders or defined BASE_PATH constant)
if (defined('BASE_PATH')) {
    $route = substr($uri, strlen(BASE_PATH));
} else {
    $route = preg_replace('#^/.*?(backend|Digital-Attendence-Project)#i', '', $uri);
}

// Normalize multiple slashes and trailing slashes
$route = preg_replace('#/+#', '/', $route);
$route = str_replace('/index.php', '', $route);
$route = rtrim($route, '/');

if (empty($route)) {
    $route = '/';
}

// Global API response header
header('Content-Type: application/json');

// --- ROUTE DISPATCHER ---
switch ($route) {
    case '/':
        echo json_encode(['success' => true, 'message' => 'Digital Attendance System API running']);
        break;

    // --- AUTHENTICATION ROUTES (Dev 2) ---
    case '/auth/login':
    case '/login':
        if ($method === 'POST') {
            (new Controllers\AuthController($pdo))->login();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/auth/logout':
        if ($method === 'POST') {
            (new Controllers\AuthController($pdo))->logout();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

        case '/test/auth':
    Middleware\AuthMiddleware::handle();

    echo json_encode([
        'success' => true,
        'message' => 'You are authenticated'
    ]);
    break;

    // --- ATTENDANCE ENGINE ROUTES (Dev 3) ---
    case '/attendance/scan':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->scan();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/attendance/clock-in':
    case '/attendance/Spy-in': // Maintained backward compatibility for Dev 2's temporary route
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->clockIn();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/attendance/clock-out':
    case '/attendance/Spy-out': // Maintained backward compatibility for Dev 2's temporary route
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->clockOut();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    // --- DASHBOARD ENDPOINTS (Dev 5) ---
    case '/dashboard/stats':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo))->stats();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/dashboard/onsite':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo))->onsite();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/dashboard/recent':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo))->recent();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

        // --- SETTINGS ENDPOINTS ---
    case '/settings':
        if ($method === 'GET') {
            (new Controllers\SettingsController($pdo))->index();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/sync-errors':
    if ($method === 'GET') {
        (new Controllers\SyncErrorController($pdo))->index(); // <-- Singular matches SyncErrorController.php
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    }
    break;

    // Default 404
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}