<?php
require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

echo "<h1>🔍 Google Sheets Debug</h1>";

// 1. Check APP_SCRIPT_URL
echo "<h2>1. APP_SCRIPT_URL</h2>";
echo "<pre>" . APP_SCRIPT_URL . "</pre>";

// 2. Test fetch
echo "<h2>2. Testing fetchSheetsData()</h2>";
$data = fetchSheetsData();
echo "<pre>";
print_r($data);
echo "</pre>";

// 3. Test sending a record
echo "<h2>3. Testing sendToGoogleSheets()</h2>";
$result = sendToGoogleSheets('TEST-' . time(), 'Debug Test', 'web', 'debug_' . time(), date('Y-m-d H:i:s'));
echo "<pre>";
print_r($result);
echo "</pre>";

// 4. Check cache file
echo "<h2>4. Cache file</h2>";
if (file_exists(CACHE_FILE)) {
    echo "✅ Cache exists at: " . CACHE_FILE . "<br>";
    $cache = json_decode(file_get_contents(CACHE_FILE), true);
    echo "Last fetched: " . date('Y-m-d H:i:s', $cache['fetched_at'] ?? time()) . "<br>";
    echo "Rows in cache: " . count($cache['data']['rows'] ?? []) . "<br>";
    echo "Last 2 rows:<br>";
    $rows = array_slice($cache['data']['rows'] ?? [], -2);
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
} else {
    echo "❌ No cache file at: " . CACHE_FILE . "<br>";
}

// 5. Check PHP error log path
echo "<h2>5. PHP Error Log</h2>";
echo "<p>Check: C:\xampp\php\logs\php_error_log</p>";