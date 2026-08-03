<?php
/**
 * CENTRAL APPLICATION ROUTER
 * Fixed path resolution and subfolder support.
 */

// --- SIMPLE AUTOLOADER ---
spl_autoload_register(function (string $class) {
    $class = ltrim($class, '\\');
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// --- GLOBAL CLASSES (not namespaced) ---
require_once __DIR__ . '/../config/DataBase.php';
require_once __DIR__ . '/../exceptions/AuthenticationException.php';

// --- DATABASE CONNECTION ---
$pdo = DataBase::getConnection();

// --- ROUTING ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Strip the base path (e.g., /insite/Digital-Attendence-Project/backend) so we get /auth/login
$route = substr($uri, strlen(BASE_PATH));

// Also remove any trailing index.php if present (shouldn't happen, but safe)
$route = str_replace('/index.php', '', $route);

switch ($route) {
    // --- PERSON 3: ATTENDANCE CLOCK ENGINE ROUTES (stubs) ---
    case '/attendance/scan':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->scan();
        }
        break;

    case '/attendance/Spy-in':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->clockIn();
        }
        break;

    case '/attendance/Spy-out':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->clockOut();
        }
        break;

    // --- PERSON 2: AUTHENTICATION ROUTES ---
    case '/auth/login':
        if ($method === 'POST') {
            (new Controllers\AuthController($pdo))->login();
        }
        break;

    case '/auth/logout':
        if ($method === 'POST') {
            (new Controllers\AuthController($pdo))->logout();
        }
        break;

    // Optional: support form action="/login"
    case '/login':
        if ($method === 'POST') {
            (new Controllers\AuthController($pdo))->login();
        }
        break;

    // Default 404
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}