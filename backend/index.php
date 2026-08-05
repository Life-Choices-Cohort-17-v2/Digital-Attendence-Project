<?php
/**
 * FRONT CONTROLLER
 * Single entry point for all HTTP requests
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/config/DataBase.php';

// Basic Autoloader for src classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    $fileLowercase = __DIR__ . '/src/' . strtolower(str_replace('\\', '/', $class)) . '.php';
    if (file_exists($fileLowercase)) {
        require_once $fileLowercase;
    }
});

// Create the actual database connection
$pdo = Config\Database::getInstance()->getPdo();

// Dispatch Request to Router
require_once __DIR__ . '/src/routes/web.php';
