<?php
// index.php (entry point)
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/config/DataBase.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic Autoloader for src classes
spl_autoload_register(function ($class) {
    // Check direct path first
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    // Check lowercase folder fallback
    $fileLowercase = __DIR__ . '/src/' . strtolower(str_replace('\\', '/', $class)) . '.php';
    if (file_exists($fileLowercase)) {
        require_once $fileLowercase;
    }
});

// Load DB connection config
require_once __DIR__ . '/src/config/DataBase.php';

$routes = require __DIR__ . '/src/routes/web.php';
$routeKey = $method . ' ' . $path;

if (!isset($routes[$routeKey])) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

list($controllerName, $action) = $routes[$routeKey];
$controllerClass = "Controllers\\$controllerName";
if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo json_encode(['error' => 'Controller not found']);
    exit;
}

$controller = new $controllerClass();
if (!method_exists($controller, $action)) {
    http_response_code(500);
    echo json_encode(['error' => 'Action not found']);
    exit;
}

// Call the action
$controller->$action();