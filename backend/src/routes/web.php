<?php
/**
 * CENTRAL APPLICATION ROUTER
 */

spl_autoload_register(function ($class) {
    $relPath = str_replace('\\', '/', $class) . '.php';
    $file = __DIR__ . '/../' . $relPath;
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    $parts = explode('\\', $class);
    $fileName = array_pop($parts);
    $dirPath = strtolower(implode('/', $parts));
    $fallback = __DIR__ . '/../' . $dirPath . '/' . $fileName . '.php';
    if (file_exists($fallback)) {
        require_once $fallback;
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

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}
