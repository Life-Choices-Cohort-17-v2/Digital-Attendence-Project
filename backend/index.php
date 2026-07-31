<?php
/**
 * FRONT CONTROLLER
 * Single entry point for all HTTP requests
 */

// Basic Autoloader for src classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load DB connection config
require_once __DIR__ . '/src/config/Database.php';

// Dispatch Request to Router
require_once __DIR__ . '/src/routes/web.php';