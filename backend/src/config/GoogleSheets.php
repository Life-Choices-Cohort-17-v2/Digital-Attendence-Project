<?php
// ============================================================
// FILE: backend/src/config/GoogleSheets.php
// OPTIMIZED - Faster timeouts & immediate cache updates
// ============================================================

define('APP_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbz5r1-cfdlPAK7eFOdf4OcDi764oVx_Uh54Uo32x-UPQHD7IMJrnwcpp3mAZ7ycVIpB/exec');
define('CACHE_FILE', __DIR__ . '/../../storage/cache/sheets_cache.json');

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
// HTTP HELPER - OPTIMIZED WITH SHORT TIMEOUTS
// ============================================================

function httpGet($url) {
    // Try cURL first (faster)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response !== false && $httpCode === 200) {
            return $response;
        }
    }
    
    // Fallback to file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ]
    ]);
    return @file_get_contents($url, false, $context);
}

function httpPost($url, $payload) {
    $options = [
        'http' => [
            'header'  => "Content-Type: text/plain;charset=utf-8\r\n",
            'method'  => 'POST',
            'content' => $payload,
            'timeout' => 2
        ]
    ];
    $context = stream_context_create($options);
    return @file_get_contents($url, false, $context);
}

// ============================================================
// GOOGLE SHEETS API
// ============================================================

function getCredentialsFromSheets() {
    $response = httpGet(APP_SCRIPT_URL . '?action=getCredentials');
    if ($response === false) {
        $response = httpGet(APP_SCRIPT_URL);
        if ($response === false) {
            return ['success' => false, 'error' => 'Failed to fetch credentials'];
        }
    }
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    return $data;
}

function fetchSheetsData() {
    $response = httpGet(APP_SCRIPT_URL);
    if ($response === false) return ['success' => false, 'error' => 'Failed to fetch'];
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    return $data;
}

function sendToGoogleSheets($staffId, $name, $method = 'QR', $token = null, $expires = null) {
    $payload = ['staff_id' => $staffId, 'name' => $name, 'method' => $method];
    if ($token) $payload['token'] = $token;
    if ($expires) $payload['expires'] = $expires;

    $response = httpPost(APP_SCRIPT_URL, json_encode($payload));
    if ($response === false) return ['success' => false, 'error' => 'Failed to connect'];
    return json_decode($response, true);
}

function sendAsyncToGoogleSheets($staffId, $name, $method = 'QR', $token = null, $expires = null) {
    $url = APP_SCRIPT_URL;
    $payload = json_encode([
        'staff_id' => $staffId,
        'name' => $name,
        'method' => $method,
        'token' => $token,
        'expires' => $expires
    ]);
    
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'start /B C:\xampp\php\php.exe -r "' . 
               '$options = [\'http\' => [\'header\' => \'Content-Type: text/plain;charset=utf-8\\r\\n\', \'method\' => \'POST\', \'content\' => \'' . addslashes($payload) . '\', \'timeout\' => 2]];' .
               '$context = stream_context_create($options);' .
               '@file_get_contents(\'' . $url . '\', false, $context);" 2>nul';
        pclose(popen($cmd, 'r'));
    } else {
        $cmd = 'php -r "' .
               '$options = [\'http\' => [\'header\' => \'Content-Type: text/plain;charset=utf-8\\r\\n\', \'method\' => \'POST\', \'content\' => \'' . addslashes($payload) . '\', \'timeout\' => 2]];' .
               '$context = stream_context_create($options);' .
               '@file_get_contents(\'' . $url . '\', false, $context);" > /dev/null 2>&1 &';
        exec($cmd);
    }
}

// ============================================================
// CACHE FUNCTIONS - WITH IMMEDIATE UPDATE
// ============================================================

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
        $dir = dirname(CACHE_FILE);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents(CACHE_FILE, json_encode([
            'data' => $data,
            'fetched_at' => time()
        ]));
        return true;
    }
    return false;
}

// ============================================================
// STATUS FUNCTIONS - WITH INSTANT LOCAL UPDATE
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