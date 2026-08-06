<?php
// ============================================================
// FILE: frontend/data/functions.php
// DATA MANAGEMENT - Google Sheets Integration (LIVE MODE)
// ============================================================

// Load Google Sheets config
require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

// ============================================================
// DASHBOARD DATA (DIRECT FROM SHEETS - NO CACHE)
// ============================================================

function getDashboardStats() {
    // ALWAYS fetch fresh data
    $data = fetchSheetsData(); 
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        // If fetch fails, try cache as fallback
        $data = getCachedData();
        if (!$data || !isset($data['rows'])) {
            return [
                'success' => true,
                'data' => [
                    'currentlyOnsite' => 0,
                    'totalClockedInToday' => 0,
                    'pendingSync' => 0,
                    'totalEventsToday' => 0
                ]
            ];
        }
    }
    
    $rows = $data['rows'] ?? [];
    
    // Remove header row
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows);
        }
    }
    
    // Process rows - get latest status for each staff
    $staffStatus = [];
    $today = date('Y-m-d');
    $todayCheckins = 0;
    
    foreach ($rows as $row) {
        if (isset($row[1]) && !empty($row[1])) {
            $staffId = trim($row[1]);
            $status = str_replace('Check-', '', $row[3] ?? '');
            $statusLower = strtolower(trim($status));
            $timestamp = $row[0] ?? date('Y-m-d H:i:s');
            
            // Store latest status (last occurrence wins)
            $staffStatus[$staffId] = [
                'name' => $row[2] ?? 'Unknown',
                'status' => $statusLower,
                'timestamp' => $timestamp
            ];
        }
    }
    
    $onsiteCount = 0;
    foreach ($staffStatus as $sid => $staff) {
        if ($staff['status'] === 'in') {
            $onsiteCount++;
        }
        // Check if today's check-in
        $datePart = substr($staff['timestamp'], 0, 10);
        if ($datePart === $today && $staff['status'] === 'in') {
            $todayCheckins++;
        }
    }
    
    return [
        'success' => true,
        'data' => [
            'currentlyOnsite' => $onsiteCount,
            'totalClockedInToday' => $todayCheckins,
            'pendingSync' => 0,
            'totalEventsToday' => $todayCheckins
        ]
    ];
}

function getOnsiteStaff() {
    // ALWAYS fetch fresh data directly - ignore cache
    $data = fetchSheetsData(); 
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        // If fetch fails, try cache as fallback
        $data = getCachedData();
        if (!$data || !isset($data['rows'])) {
            return ['success' => true, 'data' => []];
        }
    }
    
    $rows = $data['rows'] ?? [];
    
    // Remove header row
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows);
        }
    }
    
    // Process rows - get latest status for each staff
    $staffStatus = [];
    
    // Process ALL rows to get latest status
    foreach ($rows as $row) {
        if (isset($row[1]) && !empty($row[1])) {
            $staffId = trim($row[1]);
            $status = str_replace('Check-', '', $row[3] ?? '');
            $statusLower = strtolower(trim($status));
            
            // Store latest status (last occurrence wins)
            $staffStatus[$staffId] = [
                'name' => $row[2] ?? 'Unknown',
                'status' => $statusLower,
                'last_action' => $row[0] ?? date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Build onsite list - only those currently "in"
    $onsite = [];
    foreach ($staffStatus as $sid => $staff) {
        if ($staff['status'] === 'in') {
            $onsite[] = [
                'id' => $sid,
                'name' => $staff['name'],
                'role' => 'Staff',
                'employee_id' => $sid,
                'sign_in_time' => $staff['last_action'],
                'status' => 'signed_in'
            ];
        }
    }
    
    return ['success' => true, 'data' => $onsite];
}

function getRecentActivity() {
    $data = fetchSheetsData(); 
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        return ['success' => true, 'data' => []];
    }
    
    $rows = $data['rows'] ?? [];
    
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows);
        }
    }
    
    $activities = [];
    $count = 0;
    
    foreach (array_reverse($rows) as $row) {
        if ($count >= 10) break;
        if (isset($row[1]) && isset($row[2]) && isset($row[3])) {
            $activities[] = [
                'id' => uniqid(),
                'name' => $row[2] ?? 'Unknown',
                'action' => str_replace('Check-', '', $row[3] ?? ''),
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s')
            ];
            $count++;
        }
    }
    
    return ['success' => true, 'data' => $activities];
}

// ============================================================
// CLOCK IN/OUT (UPDATES GOOGLE SHEETS)
// ============================================================

function handleClockIn($data) {
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $staffName = $data['name'] ?? $_SESSION['user_name'] ?? 'Staff';
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    // Get current status from cache
    $currentStatus = getStatusFromCache($userId);
    
    if ($currentStatus === 'in') {
        return ['success' => false, 'message' => 'Already Signed in'];
    }
    
    // Update local cache
    updateLocalStatus($userId, 'in', $staffName);
    
    // Send to Google Sheets (async - non-blocking)
    sendAsyncToGoogleSheets($userId, $staffName, 'web');
    
    return ['success' => true, 'message' => 'Signed in successfully', 'timestamp' => date('Y-m-d H:i:s')];
}

function handleClockOut($data) {
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $staffName = $data['name'] ?? $_SESSION['user_name'] ?? 'Staff';
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    // Get current status from cache
    $currentStatus = getStatusFromCache($userId);
    
    if ($currentStatus === 'out') {
        return ['success' => false, 'message' => 'Not currently Signed in'];
    }
    
    // Update local cache
    updateLocalStatus($userId, 'out', $staffName);
    
    // Send to Google Sheets (async - non-blocking)
    sendAsyncToGoogleSheets($userId, $staffName, 'web');
    
    return ['success' => true, 'message' => 'Signed out successfully', 'timestamp' => date('Y-m-d H:i:s')];
}

// ============================================================
// USER HISTORY (FROM GOOGLE SHEETS)
// ============================================================

function getUserHistory($userId) {
    $data = fetchSheetsData(); 
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        return ['success' => true, 'data' => []];
    }
    
    $rows = $data['rows'] ?? [];
    
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows); 
        }
    }
    
    $records = [];
    foreach ($rows as $row) {
        if (isset($row[1]) && $row[1] === $userId) {
            $records[] = [
                'id' => uniqid(),
                'user_id' => $row[1],
                'type' => str_replace('Check-', '', $row[3] ?? ''),
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s'),
                'date' => isset($row[0]) ? substr($row[0], 0, 10) : date('Y-m-d'),
                'location' => $row[4] ?? 'Office'
            ];
        }
    }
    
    return ['success' => true, 'data' => array_reverse($records)];
}

function getAllAttendanceLogs() {
    $data = fetchSheetsData(); 
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        return ['success' => true, 'data' => []];
    }
    
    $rows = $data['rows'] ?? [];
    
    if (!empty($rows) && is_array($rows[0])) {
        $firstRow = array_values($rows[0]);
        $headerCheck = implode(' ', array_slice($firstRow, 0, 3));
        if (stripos($headerCheck, 'Timestamp') !== false || stripos($headerCheck, 'Staff') !== false) {
            array_shift($rows); 
        }
    }
    
    $logs = [];
    foreach (array_reverse($rows) as $row) {
        if (isset($row[1]) && isset($row[2]) && isset($row[3])) {
            $logs[] = [
                'id' => uniqid(),
                'staff' => $row[2] ?? 'Unknown',
                'type' => str_replace('Check-', '', $row[3] ?? ''),
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s'),
                'date' => isset($row[0]) ? substr($row[0], 0, 10) : date('Y-m-d'),
                'location' => $row[4] ?? 'Office',
                'sync' => 'synced'
            ];
        }
    }
    
    return ['success' => true, 'data' => $logs];
}

// ============================================================
// USER MANAGEMENT (FROM GOOGLE SHEETS - READ ONLY)
// ============================================================

function getAllUsers() {
    $creds = getCredentialsFromSheets();
    if (!$creds || !$creds['success']) {
        return ['success' => false, 'data' => []];
    }
    
    $users = [];
    foreach ($creds['staff'] as $staff) {
        $users[] = [
            'id' => $staff['staff_id'],
            'name' => $staff['name'],
            'email' => strtolower($staff['staff_id']) . '@spysee.app',
            'employee_id' => $staff['staff_id'],
            'role' => 'staff',
            'status' => $staff['active'] === 'YES' ? 'active' : 'inactive'
        ];
    }
    
    foreach ($creds['admins'] as $admin) {
        $users[] = [
            'id' => $admin['admin_id'],
            'name' => $admin['name'],
            'email' => strtolower($admin['admin_id']) . '@spysee.app',
            'employee_id' => $admin['admin_id'],
            'role' => 'admin',
            'status' => 'active'
        ];
    }
    
    return ['success' => true, 'data' => $users];
}

function findUserById($data, $userId) {
    $creds = getCredentialsFromSheets();
    if ($creds && $creds['success']) {
        foreach ($creds['staff'] as $staff) {
            if ($staff['staff_id'] === $userId) {
                return [
                    'id' => $staff['staff_id'],
                    'name' => $staff['name'],
                    'role' => 'staff',
                    'employee_id' => $staff['staff_id']
                ];
            }
        }
        foreach ($creds['admins'] as $admin) {
            if ($admin['admin_id'] === $userId) {
                return [
                    'id' => $admin['admin_id'],
                    'name' => $admin['name'],
                    'role' => 'admin',
                    'employee_id' => $admin['admin_id']
                ];
            }
        }
    }
    return null;
}