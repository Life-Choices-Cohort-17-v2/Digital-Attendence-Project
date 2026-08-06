<?php
// frontend/public/test-sheets.php
// Debug Google Sheets Connection

require_once __DIR__ . '/../data/functions.php';

echo "<h1>🔍 Google Sheets Debug</h1>";

// 1. Check APP_SCRIPT_URL
echo "<h2>1. APP_SCRIPT_URL</h2>";
if (defined('APP_SCRIPT_URL')) {
    echo "<p style='background:#eee; padding:10px; word-break:break-all;'>" . APP_SCRIPT_URL . "</p>";
} else {
    echo "<p style='color:red;'>❌ APP_SCRIPT_URL is not defined!</p>";
}

// 2. Test file_get_contents directly
echo "<h2>2. Testing file_get_contents()</h2>";
$start = microtime(true);
$response = @file_get_contents(APP_SCRIPT_URL);
$time = round((microtime(true) - $start) * 1000, 2);

if ($response === false) {
    echo "<p style='color:red;'>❌ Failed to connect! (${time}ms)</p>";
    echo "<p>Possible reasons:</p>";
    echo "<ul>";
    echo "<li>URL is incorrect or missing /exec</li>";
    echo "<li>Apps Script is not published</li>";
    echo "<li>Your PHP cannot make outgoing HTTPS requests</li>";
    echo "<li>Firewall or network issue</li>";
    echo "</ul>";
    
    // Show error details
    $error = error_get_last();
    if ($error) {
        echo "<p style='color:red;'>Error: " . htmlspecialchars($error['message']) . "</p>";
    }
} else {
    echo "<p style='color:green;'>✅ Connected successfully! (${time}ms)</p>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p style='color:green;'>✅ Data retrieved! Rows: " . count($data['rows'] ?? []) . "</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    } else {
        echo "<p style='color:orange;'>⚠️ Response received but not valid JSON or success=false</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
}

// 3. Test with CURL (if available)
echo "<h2>3. Testing with CURL</h2>";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, APP_SCRIPT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $curlResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: <strong>{$httpCode}</strong></p>";
    
    if ($curlResponse !== false && $httpCode === 200) {
        echo "<p style='color:green;'>✅ CURL successful!</p>";
        $data = json_decode($curlResponse, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p style='color:green;'>✅ Valid response! Rows: " . count($data['rows'] ?? []) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ CURL failed: " . $curlError . "</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ CURL not available</p>";
}

// 4. Show the cached data (if any)
echo "<h2>4. Cached Data</h2>";
$cached = getCachedData();
if ($cached && isset($cached['rows'])) {
    echo "<p style='color:green;'>✅ Cache exists with " . count($cached['rows']) . " rows.</p>";
    echo "<pre>";
    print_r(array_slice($cached['rows'], 0, 5));
    echo "</pre>";
} else {
    echo "<p style='color:orange;'>⚠️ No cache found.</p>";
}
?>