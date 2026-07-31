<?php

/**
 * backend/src/autoload.php
 * Skip this entirely if the project already has composer/vendor/autoload.php
 * with an App\ PSR-4 mapping — just make sure it covers these folders.
 *
 * Maps App\Services\QRService -> src/services/QRService.php (lowercasing
 * the namespace segment to match your lowercase folder names).
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $segments = explode('\\', $relative);
    $className = array_pop($segments);
    $folders = array_map('strtolower', $segments);

    $path = __DIR__ . '/' . implode('/', $folders) . '/' . $className . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require_once __DIR__ . '/../vendor/qrcodegenerator/qrcode.php';
