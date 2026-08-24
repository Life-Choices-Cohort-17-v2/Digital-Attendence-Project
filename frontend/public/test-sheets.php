<?php
// ============================================================
// TEST: Google Sheets Connection Debug
// ============================================================

require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

echo "<h1>🔍 Google Sheets Debug Test</h1>";

// 1. Check if APP_SCRIPT_URL is set
echo "<h2>1. Apps Script URL</h2>";
echo "<pre>" . APP_SCRIPT_URL . "</pre>";
echo "<br>";

// 2. Test sending a record directly
echo "<h2>2. Testing sendToGoogleSheets()</h2>";
$testStaffId = 'TEST-' . date('His');
$testName = 'Debug Test';
$result = sendToGoogleSheets($testStaffId, $testName, 'web', 'debug_' . time(), date('Y-m-d H:i:s'));

echo "<pre>";
print_r($result);
echo "</pre>";
echo "<br>";

// 3. Check cache file
echo "<h2>3. Cache File Status</h2>";
if (file_exists(CACHE_FILE)) {
    echo "✅ Cache exists at: " . CACHE_FILE . "<br>";
    $cache = json_decode(file_get_contents(CACHE_FILE), true);
    echo "Last fetched: " . date('Y-m-d H:i:s', $cache['fetched_at'] ?? time()) . "<br>";
    echo "Rows in cache: " . count($cache['data']['rows'] ?? []) . "<br>";
    
    // Show last 3 rows
    $rows = array_slice($cache['data']['rows'] ?? [], -3);
    echo "<h4>Last 3 rows in cache:</h4>";
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
} else {
    echo "❌ No cache file at: " . CACHE_FILE . "<br>";
}
echo "<br>";

// 4. Check PHP error log location
echo "<h2>4. PHP Error Log</h2>";
echo "<p>Check: <code>C:\xampp\php\logs\php_error_log</code></p>";
echo "<p>Or run: <code>tail -f C:\xampp\php\logs\php_error_log</code></p>";
echo "<br>";

// 5. Test fetch data
echo "<h2>5. Testing fetchSheetsData()</h2>";
$fetchResult = fetchSheetsData();
echo "<pre>";
print_r($fetchResult);
echo "</pre>";