<?php
require_once 'config.php';
session_start();

// --- QR Code Parameters ---
$token = $_GET['token'] ?? '';
$expires = $_GET['expires'] ?? '';
$staffId = $_GET['staff_id'] ?? '';
$name = $_GET['name'] ?? '';
$method = $_GET['method'] ?? 'QR';

// --- Validate Token ---
if (empty($token) || empty($expires)) {
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid QR</title>
    <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}.card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}.error{color:#a3432f;font-size:48px;margin-bottom:12px;}</style>
    </head><body><div class="card"><div class="error">❌</div><h2>Invalid QR Code</h2><p style="color:#6b6f76;">This QR code is missing required data. Please try again.</p></div></body></html>');
    exit;
}

// --- Check Expiry ---
$expireTime = strtotime($expires);
if ($expireTime < time()) {
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Expired</title>
    <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}.card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}.error{color:#a3432f;font-size:48px;margin-bottom:12px;}</style>
    </head><body><div class="card"><div class="error">⏰</div><h2>QR Code Expired</h2><p style="color:#6b6f76;">This QR code has expired. Please request a new one from the admin screen.</p></div></body></html>');
    exit;
}

// --- Store QR data in session ---
$_SESSION['qr_scan'] = [
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method
];

// --- Check Login Status ---
if (isStaff()) {
    processScan();
    exit;
}

if (isAdmin()) {
    $_SESSION['message'] = '❌ Admins cannot clock in/out via QR.';
    $_SESSION['message_type'] = 'error';
    header('Location: admin.php');
    exit;
}

// --- Not logged in → redirect to login ---
$_SESSION['redirect_after_login'] = 'scan.php?' . http_build_query([
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method
]);
header('Location: login.php?redirect=scan');
exit;

// ============================================================
// PROCESS SCAN - INSTANT RESPONSE
// ============================================================
function processScan() {
    global $token, $expires, $staffId, $name, $method;
    
    $loggedInStaffId = $_SESSION['staff_id'] ?? '';
    $loggedInStaffName = $_SESSION['staff_name'] ?? '';
    
    // Validate staff matches QR
    if (!empty($staffId) && $staffId !== 'QR_SCAN' && $staffId !== $loggedInStaffId) {
        $_SESSION['message'] = '❌ This QR code is for a different staff member.';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
    
    $useStaffId = !empty($staffId) && $staffId !== 'QR_SCAN' ? $staffId : $loggedInStaffId;
    $useStaffName = !empty($name) && $name !== 'QR_Scan' ? $name : $loggedInStaffName;
    
    // --- STEP 1: Get current status from CACHE (INSTANT) ---
    $currentStatus = getStatusFromCache($useStaffId);
    
    // Determine new status
    $newStatus = $currentStatus === 'in' ? 'Check-out' : 'Check-in';
    $newStatusDisplay = $currentStatus === 'in' ? 'Check-out' : 'Check-in';
    
    // --- STEP 2: Update LOCAL CACHE immediately (INSTANT) ---
    updateLocalStatus($useStaffId, $newStatusDisplay);
    
    // --- STEP 3: Send success message to user (INSTANT) ---
    $_SESSION['message'] = "✅ {$useStaffName} — " . $newStatusDisplay . " successful!";
    $_SESSION['message_type'] = 'success';
    
    // --- STEP 4: Send to Google Sheets in the BACKGROUND (async) ---
    // We'll use a non-blocking call
    sendAsyncToGoogleSheets($useStaffId, $useStaffName, $method, $token, $expires);
    
    unset($_SESSION['qr_scan']);
    header('Location: index.php');
    exit;
}

// ============================================================
// GET STATUS FROM CACHE (INSTANT)
// ============================================================
function getStatusFromCache($staffId) {
    $cacheFile = CACHE_FILE;
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && isset($cached['data'])) {
            $rows = $cached['data']['rows'] ?? [];
            array_shift($rows);
            
            $latestStatus = 'out';
            $latestTimestamp = '';
            
            foreach ($rows as $row) {
                if (isset($row[1]) && $row[1] === $staffId) {
                    if (empty($latestTimestamp) || $row[0] > $latestTimestamp) {
                        $latestTimestamp = $row[0];
                        $status = str_replace('Check-', '', $row[3]);
                        $latestStatus = strtolower($status);
                    }
                }
            }
            return $latestStatus;
        }
    }
    return 'out';
}

// ============================================================
// UPDATE LOCAL CACHE (INSTANT)
// ============================================================
function updateLocalStatus($staffId, $newStatus) {
    $cacheFile = CACHE_FILE;
    $data = null;
    
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && isset($cached['data'])) {
            $data = $cached['data'];
        }
    }
    
    if ($data === null) {
        // If no cache, fetch from Sheets once
        $response = @file_get_contents(APP_SCRIPT_URL);
        if ($response !== false) {
            $data = json_decode($response, true);
        }
    }
    
    if ($data && isset($data['rows'])) {
        // Find and update the status for this staff member
        $rows = $data['rows'];
        $found = false;
        
        foreach ($rows as $index => $row) {
            if (isset($row[1]) && $row[1] === $staffId) {
                // Update the status in the cached data
                $row[3] = 'Check-' . $newStatus;
                $row[0] = date('Y-m-d H:i:s');
                $rows[$index] = $row;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Add new entry if not found
            $rows[] = [
                date('Y-m-d H:i:s'),
                $staffId,
                $_SESSION['staff_name'] ?? 'Staff',
                'Check-' . $newStatus,
                'QR'
            ];
        }
        
        $data['rows'] = $rows;
        file_put_contents($cacheFile, json_encode([
            'data' => $data,
            'fetched_at' => time()
        ]));
    }
}

// ============================================================
// SEND TO GOOGLE SHEETS IN BACKGROUND (NON-BLOCKING)
// ============================================================
function sendAsyncToGoogleSheets($staffId, $name, $method, $token, $expires) {
    // Use a background HTTP request
    $url = APP_SCRIPT_URL;
    $payload = json_encode([
        'staff_id' => $staffId,
        'name' => $name,
        'method' => $method,
        'token' => $token,
        'expires' => $expires
    ]);
    
    // Use a background process
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows: Use start /B to run in background
        $cmd = 'start /B C:\xampp\php\php.exe -r "' . 
               '$options = [\'http\' => [\'header\' => \'Content-Type: text/plain;charset=utf-8\\r\\n\', \'method\' => \'POST\', \'content\' => \'' . addslashes($payload) . '\']];' .
               '$context = stream_context_create($options);' .
               '@file_get_contents(\'' . $url . '\', false, $context);"';
        pclose(popen($cmd, 'r'));
    } else {
        // Linux/Mac: Use exec with &
        $cmd = 'php -r "' .
               '$options = [\'http\' => [\'header\' => \'Content-Type: text/plain;charset=utf-8\\r\\n\', \'method\' => \'POST\', \'content\' => \'' . addslashes($payload) . '\']];' .
               '$context = stream_context_create($options);' .
               '@file_get_contents(\'' . $url . '\', false, $context);" > /dev/null 2>&1 &';
        exec($cmd);
    }
}
?>