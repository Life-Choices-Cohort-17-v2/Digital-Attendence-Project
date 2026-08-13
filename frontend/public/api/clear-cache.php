<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../data/functions.php';

try {
    // Delete cache file
    if (file_exists(CACHE_FILE)) {
        unlink(CACHE_FILE);
    }
    
    // Force refresh cache
    $result = updateCache();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared and refreshed'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}