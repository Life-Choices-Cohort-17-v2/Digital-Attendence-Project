<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Test Google Sheets Connection</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .card { background: #fff; border-radius: 10px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { color: #2f6f4f; }
        .success { color: #2f6f4f; background: #e9f4ee; padding: 12px 16px; border-radius: 6px; }
        .error { color: #a3432f; background: #fbeceb; padding: 12px 16px; border-radius: 6px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { background: #2f6f4f; color: #fff; padding: 8px 12px; text-align: left; }
        .data-table td { padding: 6px 12px; border-bottom: 1px solid #eee; }
        .data-table tr:nth-child(even) { background: #fafaf8; }
        pre { background: #f4f4f0; padding: 12px; border-radius: 6px; overflow: auto; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #2f6f4f; color: #fff; text-decoration: none; border-radius: 6px; margin: 4px; }
    </style>
</head>
<body>
    <div class='card'>
        <h1>🔗 Google Sheets Connection Test</h1>
        <p>Testing connection to: <strong>" . APP_SCRIPT_URL . "</strong></p>
    </div>";

// Test 1: Fetch data
echo "<div class='card'>";
echo "<h2>Test 1: Fetching Data</h2>";

$start = microtime(true);
$response = @file_get_contents(APP_SCRIPT_URL);
$time = round((microtime(true) - $start) * 1000, 2);

if ($response === false) {
    echo "<div class='error'>❌ Failed to connect to Google Sheets</div>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Check your internet connection</li>";
    echo "<li>Verify the Apps Script URL is correct</li>";
    echo "<li>Make sure the Apps Script is deployed and published</li>";
    echo "</ul>";
} else {
    echo "<div class='success'>✅ Connected successfully! (${time}ms)</div>";
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "<div class='error'>❌ Invalid JSON response</div>";
        echo "<p>Raw response:</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    } elseif (isset($data['success']) && $data['success'] === true) {
        echo "<div class='success'>✅ Data retrieved successfully!</div>";
        
        if (isset($data['rows']) && is_array($data['rows'])) {
            echo "<p><strong>" . count($data['rows']) . "</strong> rows returned (including header)</p>";
            
            echo "<table class='data-table'>";
            $firstRow = true;
            $cols = 0;
            foreach ($data['rows'] as $row) {
                echo "<tr>";
                if ($firstRow) {
                    echo "<th style='background:#e9f4ee;color:#2f6f4f;'>" . implode("</th><th style='background:#e9f4ee;color:#2f6f4f;'>", array_map('htmlspecialchars', $row)) . "</th>";
                    $cols = count($row);
                    $firstRow = false;
                } else {
                    while (count($row) < $cols) { $row[] = ''; }
                    echo "<td>" . implode("</td><td>", array_map('htmlspecialchars', $row)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>No 'rows' key in response</p>";
        }
    } else {
        echo "<div class='error'>❌ Request failed: " . htmlspecialchars($data['error'] ?? 'Unknown error') . "</div>";
    }
}
echo "</div>";

// Test 2: Send test data
echo "<div class='card'>";
echo "<h2>Test 2: Sending Test Data</h2>";

$testResult = sendToGoogleSheets('TEST-001', 'Test User', 'test');
if ($testResult['success']) {
    echo "<div class='success'>✅ Test data sent successfully!</div>";
    echo "<pre>" . print_r($testResult, true) . "</pre>";
} else {
    echo "<div class='error'>❌ Failed to send test data: " . htmlspecialchars($testResult['error'] ?? 'Unknown error') . "</div>";
}
echo "</div>";

// Test 3: Cache
echo "<div class='card'>";
echo "<h2>Test 3: Cache System</h2>";

$cached = getCachedData();
if ($cached) {
    echo "<div class='success'>✅ Cache exists</div>";
    echo "<p>Cache size: " . round(filesize(CACHE_FILE) / 1024, 2) . " KB</p>";
    echo "<p>Last updated: " . date('Y-m-d H:i:s', filemtime(CACHE_FILE)) . "</p>";
} else {
    echo "<div class='error'>❌ No cache found</div>";
    echo "<p>Attempting to create cache...</p>";
    if (updateCache()) {
        echo "<div class='success'>✅ Cache created!</div>";
    } else {
        echo "<div class='error'>❌ Failed to create cache</div>";
    }
}
echo "</div>";

echo "<div class='card'>";
echo "<p><a href='admin.php' class='btn'>Open Admin Dashboard</a> <a href='index.php' class='btn' style='background:#6b6f76;'>Open Staff App</a></p>";
echo "<p><a href='scan.php?token=test&expires=" . date('Y-m-d\TH:i:s', time() + 30) . "' class='btn'>Test scan.php</a></p>";
echo "</div>";

echo "</body></html>";
?>