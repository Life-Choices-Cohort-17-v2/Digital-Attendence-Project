<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../data/functions.php';

$connected = false;
$lastSync = '';
$cacheAge = 'Unknown';
$error = '';

try {
    // Test connection by fetching data
    $data = fetchSheetsData();
    
    if ($data && isset($data['success']) && $data['success']) {
        $connected = true;
        
        // Get last sync time from cache
        if (file_exists(CACHE_FILE)) {
            $cached = json_decode(file_get_contents(CACHE_FILE), true);
            if ($cached && isset($cached['fetched_at'])) {
                $lastSync = date('Y-m-d H:i:s', $cached['fetched_at']);
                $age = time() - $cached['fetched_at'];
                if ($age < 60) {
                    $cacheAge = $age . ' seconds ago';
                } elseif ($age < 3600) {
                    $cacheAge = round($age / 60) . ' minutes ago';
                } else {
                    $cacheAge = round($age / 3600) . ' hours ago';
                }
            }
        }
    } else {
        $error = $data['error'] ?? 'Failed to fetch data';
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

echo json_encode([
    'success' => true,
    'connected' => $connected,
    'last_sync' => $lastSync,
    'cache_age' => $cacheAge,
    'error' => $error
]);