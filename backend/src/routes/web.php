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
            $service = new \App\Services\AttendanceService(
                new \App\Models\Attendance($pdo),
                new \App\Models\User($pdo)
            );
            (new \App\Controllers\AttendanceController($service))->scan();
        }
        break;

    case '/attendance/Spy-in':
        if ($method === 'POST') {
            $service = new \App\Services\AttendanceService(
                new \App\Models\Attendance($pdo),
                new \App\Models\User($pdo)
            );
            (new \App\Controllers\AttendanceController($service))->clockIn();
        }
        break;

    case '/attendance/Spy-out':
        if ($method === 'POST') {
            $service = new \App\Services\AttendanceService(
                new \App\Models\Attendance($pdo),
                new \App\Models\User($pdo)
            );
            (new \App\Controllers\AttendanceController($service))->clockOut();
        }
        break;

    // --- PERSON 2: AUTHENTICATION ROUTES ---
    case '/auth/login':
        if ($method === 'POST') {
            (new \App\Controllers\AuthController($pdo))->login();
        }
        break;

    // --- STAFF PORTAL (Person 7) ---
    case '/attendance/history':
        if ($method === 'GET') {
            $service = new \App\Services\AttendanceService(
                new \App\Models\Attendance($pdo),
                new \App\Models\User($pdo)
            );
            (new \App\Controllers\AttendanceController($service))->history();
        }
        break;

    case '/attendance/onsite':
        if ($method === 'GET') {
            $service = new \App\Services\AttendanceService(
                new \App\Models\Attendance($pdo),
                new \App\Models\User($pdo)
            );
            (new \App\Controllers\AttendanceController($service))->onsite();
        }
        break;

    // Default 404
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
        break;
}