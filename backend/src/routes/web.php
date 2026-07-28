<?php
/**
 * CENTRAL APPLICATION ROUTER
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Standardize route path
$route = str_replace('/backend', '', $uri);

switch ($route) {
    // --- PERSON 3: ATTENDANCE CLOCK ENGINE ROUTES ---
    case '/attendance/scan':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->scan();
        }
        break;

    case '/attendance/clock-in':
        if ($method === 'POST') {
            (new Controllers\AttendanceController($pdo))->clockIn();
        }
        break;

    case '/attendance/clock-out':
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

    // Default 404
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}