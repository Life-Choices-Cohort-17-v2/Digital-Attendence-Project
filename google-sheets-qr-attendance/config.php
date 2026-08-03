<?php
// ============================================================
// CONFIGURATION FILE (No Hardcoded Creds)
// ============================================================

// Google Apps Script Web App URL - REPLACE WITH YOUR NEW URL
define('APP_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbxPfeLqirw101Yna88aLoRSAHlgAax9dziyBPO7DMT27CdJxVPxP5cAUDQ-xnXQk2d5/exec');

// QR Code Refresh Interval (seconds)
define('QR_REFRESH_INTERVAL', 30);

// Cache File Location
define('CACHE_FILE', __DIR__ . '/.sheets_cache.json');

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isStaff() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php?tab=admin');
        exit;
    }
}

function sendToGoogleSheets($staffId, $name, $method = 'QR', $token = null, $expires = null) {
    $payload = ['staff_id' => $staffId, 'name' => $name, 'method' => $method];
    if ($token) $payload['token'] = $token;
    if ($expires) $payload['expires'] = $expires;

    $options = [
        'http' => [
            'header'  => "Content-Type: text/plain;charset=utf-8\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'timeout' => 30
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents(APP_SCRIPT_URL, false, $context);
    if ($response === false) return ['success' => false, 'error' => 'Failed to connect'];
    return json_decode($response, true);
}

function fetchSheetsData() {
    $response = @file_get_contents(APP_SCRIPT_URL);
    if ($response === false) return ['success' => false, 'error' => 'Failed to fetch'];
    return json_decode($response, true);
}

function getCachedData() {
    if (file_exists(CACHE_FILE)) {
        $cached = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cached && isset($cached['data'])) return $cached['data'];
    }
    return null;
}

function updateCache() {
    $data = fetchSheetsData();
    if ($data && isset($data['success']) && $data['success']) {
        file_put_contents(CACHE_FILE, json_encode(['data' => $data, 'fetched_at' => time()]));
        return true;
    }
    return false;
}
?>