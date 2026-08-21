<?php
// ============================================================
// FILE: backend/src/config/GoogleSheets.php
// FIXED - With Credential Caching + NO DUPLICATE SEND
// ============================================================

define('APP_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyFHp5ETpwFXVwyvxQNj1izgYkqOXsqsRCQS77wdT-qVwklILGSHmZbZHwZXRDeKOgT/exec');
define('CACHE_FILE', __DIR__ . '/../../storage/cache/sheets_cache.json');
define('CREDENTIALS_CACHE_FILE', __DIR__ . '/../../storage/cache/credentials_cache.json');

// ============================================================
// SESSION HELPERS
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

function getUserRole() {
    return $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? null;
}

// ============================================================
// HTTP HELPERS - FIXED WITH NO DUPLICATE FALLBACK
// ============================================================

function httpGet($url) {
    error_log("🌐 HTTP GET: " . $url);
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("🌐 HTTP GET response code: " . $httpCode);
        if ($error) {
            error_log("🌐 HTTP GET error: " . $error);
        }
        
        if ($response !== false && $httpCode === 200) {
            error_log("🌐 HTTP GET success, response length: " . strlen($response));
            return $response;
        }
    }
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        error_log("🌐 HTTP GET failed with file_get_contents");
        $error = error_get_last();
        if ($error) {
            error_log("🌐 Error: " . json_encode($error));
        }
    }
    return $response;
}

function httpPost($url, $payload) {
    error_log("📤 HTTP POST: " . $url);
    error_log("📤 Payload: " . $payload);
    
    $headers = [
        'Content-Type: text/plain;charset=utf-8',
        'Content-Length: ' . strlen($payload),
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
    
    // 🛡️ FIX: Only try CURL - NO FALLBACK to prevent duplicates
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("📤 HTTP POST response code: " . $httpCode);
        if ($error) {
            error_log("📤 HTTP POST error: " . $error);
        }
        
        // 🛡️ CRITICAL FIX: Even on HTTP error (400, 500), return the response if we got one
        // Don't fallback to a second request
        if ($response !== false) {
            error_log("📤 HTTP POST got response with code " . $httpCode . ": " . substr($response, 0, 200));
            return $response;
        }
        
        // Only return false if CURL completely failed (no response at all)
        error_log("📤 HTTP POST: CURL failed completely, no response");
        return false;
    }
    
    // 🛡️ FIX: Only use fallback if CURL doesn't exist (very rare)
    error_log("📤 HTTP POST: CURL not available, using fallback (this should not happen in normal environments)");
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $payload,
            'timeout' => 15
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("📤 HTTP POST failed with file_get_contents");
        $error = error_get_last();
        if ($error) {
            error_log("📤 Error: " . json_encode($error));
        }
        return false;
    }
    
    error_log("📤 HTTP POST success (fallback), response: " . substr($response, 0, 200));
    return $response;
}

// ============================================================
// 🛡️ CREDENTIALS WITH CACHING - Prevents rate limiting
// ============================================================

function getCredentialsFromSheets($forceRefresh = false) {
    error_log("🔑 Getting credentials from sheets (forceRefresh: " . ($forceRefresh ? 'true' : 'false') . ")");
    
    if (!$forceRefresh && file_exists(CREDENTIALS_CACHE_FILE)) {
        $cached = json_decode(file_get_contents(CREDENTIALS_CACHE_FILE), true);
        if ($cached && isset($cached['fetched_at']) && (time() - $cached['fetched_at']) < 300) {
            error_log("🔑 Using cached credentials (age: " . (time() - $cached['fetched_at']) . "s)");
            return $cached['data'];
        }
    }
    
    $response = httpGet(APP_SCRIPT_URL . '?action=getCredentials');
    if ($response === false) {
        $response = httpGet(APP_SCRIPT_URL);
        if ($response === false) {
            if (file_exists(CREDENTIALS_CACHE_FILE)) {
                $cached = json_decode(file_get_contents(CREDENTIALS_CACHE_FILE), true);
                if ($cached && isset($cached['data'])) {
                    error_log("🔑 Using stale cached credentials as fallback");
                    return $cached['data'];
                }
            }
            return ['success' => false, 'error' => 'Failed to fetch credentials'];
        }
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    
    $dir = dirname(CREDENTIALS_CACHE_FILE);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(CREDENTIALS_CACHE_FILE, json_encode([
        'data' => $data,
        'fetched_at' => time()
    ]));
    
    error_log("🔑 Credentials cached successfully");
    return $data;
}

// ============================================================
// GOOGLE SHEETS API
// ============================================================

function fetchSheetsData() {
    error_log("📥 Fetching sheets data");
    $response = httpGet(APP_SCRIPT_URL);
    if ($response === false) {
        error_log("📥 Fetch failed");
        return ['success' => false, 'error' => 'Failed to fetch'];
    }
    $data = json_decode($response, true);
    if (!$data) {
        error_log("📥 Invalid JSON response");
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    error_log("📥 Fetch success, rows: " . count($data['rows'] ?? []));
    return $data;
}

// ============================================================
// 🛡️ CRITICAL FIX: sendToGoogleSheets - ONLY ONE CALL PER SCAN
// ============================================================

function sendToGoogleSheets($staffId, $name, $method = 'QR', $token = null, $expires = null) {
    error_log("📤 SEND TO GOOGLE SHEETS (ONCE)");
    error_log("📤 Staff: {$staffId}, Name: {$name}, Method: {$method}");
    
    $payload = ['staff_id' => $staffId, 'name' => $name, 'method' => $method];
    if ($token) $payload['token'] = $token;
    if ($expires) $payload['expires'] = $expires;
    
    $jsonPayload = json_encode($payload);
    error_log("📤 JSON Payload: " . $jsonPayload);
    
    // 🛡️ FIX: Only attempt once - no retries
    $response = httpPost(APP_SCRIPT_URL, $jsonPayload);
    
    if ($response === false) {
        error_log("❌ Google Sheets connection failed!");
        // Don't retry - just log and continue
        return ['success' => false, 'error' => 'Failed to connect'];
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        error_log("❌ Invalid response from Google Sheets: " . $response);
        return ['success' => false, 'error' => 'Invalid response from server'];
    }
    
    error_log("📥 Google Sheets response: " . json_encode($result));
    return $result;
}

// ============================================================
// 🛡️ FIXED: sendAsyncToGoogleSheets - DOES NOT DUPLICATE
// ============================================================

function sendAsyncToGoogleSheets($staffId, $name, $method = 'QR', $token = null, $expires = null) {
    error_log("📤 Async send to Google Sheets: {$staffId} ({$name})");
    // 🛡️ CRITICAL FIX: Do NOT send duplicate - just log and return
    error_log("📤 Async send skipped - use sendToGoogleSheets() for actual sending");
    return ['success' => true, 'message' => 'Async send skipped to prevent duplicates'];
}

// ============================================================
// CACHE FUNCTIONS
// ============================================================

function getCachedData() {
    if (file_exists(CACHE_FILE)) {
        $cached = json_decode(file_get_contents(CACHE_FILE), true);
        if ($cached && isset($cached['data'])) return $cached['data'];
    }
    return null;
}

function updateCache() {
    error_log("🔄 Updating cache");
    $data = fetchSheetsData();
    if ($data && isset($data['success']) && $data['success']) {
        $dir = dirname(CACHE_FILE);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents(CACHE_FILE, json_encode([
            'data' => $data,
            'fetched_at' => time()
        ]));
        error_log("🔄 Cache updated successfully");
        return true;
    }
    error_log("🔄 Cache update failed");
    return false;
}

// ============================================================
// STATUS FUNCTIONS
// ============================================================

function getStatusFromCache($staffId) {
    $data = getCachedData();
    if (!$data) return 'out';
    
    $rows = $data['rows'] ?? [];
    if (!empty($rows) && is_array($rows[0]) && strpos(implode('', $rows[0]), 'Timestamp') !== false) {
        array_shift($rows);
    }
    
    $latestStatus = 'out';
    $latestTimestamp = '';
    
    foreach ($rows as $row) {
        if (isset($row[1]) && $row[1] === $staffId) {
            if (empty($latestTimestamp) || $row[0] > $latestTimestamp) {
                $latestTimestamp = $row[0];
                $status = str_replace('Check-', '', $row[3] ?? '');
                $latestStatus = strtolower($status);
            }
        }
    }
    return $latestStatus;
}

function updateLocalStatus($staffId, $newStatus, $staffName = 'Staff') {
    error_log("🔄 Updating local status: {$staffId} -> {$newStatus}");
    
    $data = getCachedData();
    
    if (!$data) {
        $data = fetchSheetsData();
        if (!$data || !isset($data['success']) || !$data['success']) {
            $data = ['rows' => []];
        }
    }
    
    $rows = $data['rows'] ?? [];
    
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows);
        }
    }
    
    $found = false;
    foreach ($rows as $index => $row) {
        if (isset($row[1]) && $row[1] === $staffId) {
            $row[0] = date('Y-m-d H:i:s');
            $row[3] = 'Check-' . $newStatus;
            $rows[$index] = $row;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $rows[] = [
            date('Y-m-d H:i:s'),
            $staffId,
            $staffName,
            'Check-' . $newStatus,
            'web'
        ];
    }
    
    $data['rows'] = $rows;
    file_put_contents(CACHE_FILE, json_encode([
        'data' => $data,
        'fetched_at' => time()
    ]));
    
    error_log("🔄 Local status updated, rows: " . count($rows));
}

function getStaffListFromCache() {
    $data = getCachedData();
    if (!$data) return [];
    
    $rows = $data['rows'] ?? [];
    if (!empty($rows) && is_array($rows[0]) && strpos(implode('', $rows[0]), 'Timestamp') !== false) {
        array_shift($rows);
    }
    
    $staffList = [];
    $seen = [];
    
    foreach ($rows as $row) {
        if (isset($row[1]) && !isset($seen[$row[1]])) {
            $seen[$row[1]] = true;
            $staffList[] = [
                'staff_id' => $row[1],
                'name' => $row[2] ?? 'Unknown',
                'status' => strtolower(str_replace('Check-', '', $row[3] ?? ''))
            ];
        }
    }
    return $staffList;
}