<?php
// ============================================================
// FILE: frontend/public/scan.php
// QR SCAN PROCESSOR - Handles QR code scanning for clock in/out
// ============================================================

// Load Google Sheets functions from backend
require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';
session_start();

// --- Helper Functions ---
function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function route_url($path) {
    return '/index.php' . ($path === '/' ? '' : $path);
}

// --- QR Code Parameters ---
$token = $_GET['token'] ?? '';
$expires = $_GET['expires'] ?? '';
$staffId = $_GET['staff_id'] ?? '';
$name = $_GET['name'] ?? '';
$method = $_GET['method'] ?? 'QR';
$location = $_GET['location'] ?? 'HQ Entrance';

// --- Validate Token ---
if (empty($token) || empty($expires)) {
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid QR</title>
    <style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}
        .card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}
        .error{color:#a3432f;font-size:48px;margin-bottom:12px;}
        .btn{display:inline-block;padding:10px 20px;background:#2f6f4f;color:#fff;text-decoration:none;border-radius:8px;margin-top:16px;border:none;cursor:pointer;}
        .btn:hover{background:#3a8a5f;}
    </style>
    </head><body><div class="card"><div class="error">❌</div><h2>Invalid QR Code</h2><p style="color:#6b6f76;">This QR code is missing required data. Please try again.</p>
    <a href="' . route_url('/staff-dashboard') . '" class="btn">Return to Dashboard</a>
    </div></body></html>');
    exit;
}

// --- Check Expiry ---
$expireTime = strtotime($expires);
if ($expireTime < time()) {
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Expired</title>
    <style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}
        .card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}
        .error{color:#a3432f;font-size:48px;margin-bottom:12px;}
        .btn{display:inline-block;padding:10px 20px;background:#2f6f4f;color:#fff;text-decoration:none;border-radius:8px;margin-top:16px;border:none;cursor:pointer;}
        .btn:hover{background:#3a8a5f;}
    </style>
    </head><body><div class="card"><div class="error">⏰</div><h2>QR Code Expired</h2><p style="color:#6b6f76;">This QR code has expired. Please request a new one from the admin screen.</p>
    <a href="' . route_url('/staff-dashboard') . '" class="btn">Return to Dashboard</a>
    </div></body></html>');
    exit;
}

// --- Store QR data in session ---
$_SESSION['qr_scan'] = [
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method,
    'location' => $location
];

// --- Check Login Status ---
if (isStaff()) {
    processScan();
    exit;
}

if (isAdmin()) {
    $_SESSION['message'] = '❌ Admins cannot clock in/out via QR.';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . route_url('/admin-dashboard'));
    exit;
}

// --- Not logged in → redirect to login ---
$_SESSION['redirect_after_login'] = '/scan.php?' . http_build_query([
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method,
    'location' => $location
]);
header('Location: ' . route_url('/login'));
exit;

// ============================================================
// PROCESS SCAN - INSTANT RESPONSE
// ============================================================
function processScan() {
    global $token, $expires, $staffId, $name, $method, $location;
    
    $loggedInStaffId = $_SESSION['staff_id'] ?? '';
    $loggedInStaffName = $_SESSION['staff_name'] ?? '';
    
    // Validate staff matches QR
    if (!empty($staffId) && $staffId !== 'QR_SCAN' && $staffId !== $loggedInStaffId) {
        $_SESSION['message'] = '❌ This QR code is for a different staff member.';
        $_SESSION['message_type'] = 'error';
        header('Location: ' . route_url('/staff-dashboard'));
        exit;
    }
    
    $useStaffId = !empty($staffId) && $staffId !== 'QR_SCAN' ? $staffId : $loggedInStaffId;
    $useStaffName = !empty($name) && $name !== 'QR_Scan' ? $name : $loggedInStaffName;
    
    // --- STEP 1: Get current status from CACHE (INSTANT) ---
    $currentStatus = getStatusFromCache($useStaffId);
    
    // Determine new status
    $newStatusDisplay = $currentStatus === 'in' ? 'Check-out' : 'Check-in';
    $actionDisplay = $currentStatus === 'in' ? 'Sign out' : 'Sign in';
    
    // --- STEP 2: Update LOCAL CACHE immediately (INSTANT) ---
    updateLocalStatus($useStaffId, $newStatusDisplay, $useStaffName);
    
    // --- STEP 3: Send success message to user (INSTANT) ---
    // Store the result for display on the dashboard
    $_SESSION['qr_result'] = [
        'success' => true,
        'action' => $actionDisplay,
        'name' => $useStaffName,
        'location' => $location,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // --- STEP 4: Send to Google Sheets in the BACKGROUND (async - non-blocking) ---
    // Person 6: External service call that doesn't block the user
    sendAsyncToGoogleSheets($useStaffId, $useStaffName, $method, $token, $expires);
    
    unset($_SESSION['qr_scan']);
    
    // Redirect back to staff dashboard with success message
    header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
    exit;
}