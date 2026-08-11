<?php
/**
 * CENTRAL APPLICATION ROUTER
 */

spl_autoload_register(function ($class) {
    // Standard PSR-4 style mapping relative to src/
    $relPath = str_replace('\\', '/', $class) . '.php';
    
    // 1. Exact match attempt: backend/src/Validators/AttendanceValidator.php
    $file = __DIR__ . '/../' . $relPath;
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // 2. Lowercase folder + exact filename fallback: backend/src/validators/AttendanceValidator.php
    $parts = explode('\\', $class);
    $fileName = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    $mixedFile = __DIR__ . '/../' . $dirPath . '/' . $fileName . '.php';
    if (file_exists($mixedFile)) {
        require_once $mixedFile;
        return;
    }
});

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

$route = preg_replace('#^/backend#', '', $uri);

header('Content-Type: application/json');

switch ($route) {
    case '/':
        echo json_encode(['success' => true, 'message' => 'Digital Attendance System API running']);
        break;

    // --- ATTENDANCE CLOCK ENGINE (Dev 3) ---
    case '/attendance/scan':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo ?? null))->scan();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/attendance/clock-in':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo ?? null))->clockIn();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/attendance/clock-out':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo ?? null))->clockOut();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    // --- DASHBOARD ENDPOINTS (Dev 5) ---
    case '/dashboard/stats':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo ?? null))->stats();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/dashboard/onsite':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo ?? null))->onsite();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    case '/dashboard/recent':
        if ($method === 'GET') {
            (new Controllers\DashboardController($pdo ?? null))->recent();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}