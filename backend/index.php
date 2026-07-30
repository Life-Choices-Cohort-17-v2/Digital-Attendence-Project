<?php
/**
 * FRONT CONTROLLER
 * Single entry point for all HTTP requests
 */

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

// Dispatch Request to Router
require_once __DIR__ . '/src/routes/web.php';