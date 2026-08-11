<?php
// ============================================================
// FILE: frontend/data/functions.php
// OPTIMIZED - INSTANT STATUS UPDATES
// ============================================================

require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

// ============================================================
// GET ALL STAFF WITH CURRENT STATUS (FROM CACHE - INSTANT!)
// ============================================================

function getAllStaffWithStatus() {
    $data = getCachedData(); // Use cache directly - no fetch delay
    
    if (!$data || !isset($data['rows'])) {
        return ['success' => true, 'data' => []];
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
    $latestTimestamp = [];
    
    foreach ($rows as $row) {
        if (isset($row[1]) && !empty($row[1])) {
            $staffId = trim($row[1]);
            $timestamp = $row[0] ?? date('Y-m-d H:i:s');
            
            // Skip if this timestamp is older than what we have
            if (isset($latestTimestamp[$staffId]) && $timestamp <= $latestTimestamp[$staffId]) {
                continue;
            }
            
            $statusRaw = $row[3] ?? '';
            $status = str_replace('Check-', '', $statusRaw);
            $statusLower = strtolower(trim($status));
            
            if ($statusLower === 'in' || $statusLower === 'out') {
                $staffStatus[$staffId] = [
                    'id' => $staffId,
                    'staff_id' => $staffId,
                    'employee_id' => $staffId,
                    'name' => $row[2] ?? 'Unknown',
                    'status' => $statusLower,
                    'last_action' => $timestamp
                ];
                $latestTimestamp[$staffId] = $timestamp;
            }
        }
    }
    
    // Get all users from credentials
    $allUsers = getAllUsersFromSheets();
    foreach ($allUsers as $user) {
        $userId = $user['id'];
        if (!isset($staffStatus[$userId])) {
            $staffStatus[$userId] = [
                'id' => $userId,
                'staff_id' => $userId,
                'employee_id' => $userId,
                'name' => $user['name'],
                'status' => 'out',
                'last_action' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    return ['success' => true, 'data' => array_values($staffStatus)];
}

// ============================================================
// GET ONSITE STAFF - INSTANT FROM CACHE
// ============================================================

function getOnsiteStaff() {
    $allStaff = getAllStaffWithStatus();
    if (!$allStaff['success']) {
        return ['success' => true, 'data' => []];
    }
    
    $onsite = [];
    foreach ($allStaff['data'] as $staff) {
        if ($staff['status'] === 'in') {
            $onsite[] = [
                'id' => $staff['id'],
                'staff_id' => $staff['staff_id'],
                'employee_id' => $staff['employee_id'],
                'name' => $staff['name'],
                'role' => 'Staff',
                'sign_in_time' => $staff['last_action'],
                'status' => 'signed_in'
            ];
        }
    }
    
    return ['success' => true, 'data' => $onsite];
}

// ============================================================
// GET STAFF STATUS - INSTANT FROM CACHE
// ============================================================

function getStaffStatus($userId) {
    $allStaff = getAllStaffWithStatus();
    if (!$allStaff['success']) {
        return 'out';
    }
    
    foreach ($allStaff['data'] as $staff) {
        if ($staff['id'] === $userId || $staff['staff_id'] === $userId || $staff['employee_id'] === $userId) {
            return $staff['status'];
        }
    }
    
    return 'out';
}

// ============================================================
// GET ALL USERS FROM GOOGLE SHEETS
// ============================================================

function getAllUsersFromSheets() {
    $creds = getCredentialsFromSheets();
    if (!$creds || !$creds['success']) {
        return [];
    }
    
    $users = [];
    
    foreach ($creds['staff'] as $staff) {
        $users[] = [
            'id' => $staff['staff_id'] ?? $staff['Staff_ID'] ?? '',
            'name' => $staff['name'] ?? $staff['Name'] ?? 'Unknown',
            'email' => strtolower($staff['staff_id'] ?? '') . '@spysee.app',
            'employee_id' => $staff['staff_id'] ?? $staff['Staff_ID'] ?? '',
            'role' => 'staff',
            'status' => isset($staff['active']) && strtoupper($staff['active']) === 'YES' ? 'active' : 'inactive'
        ];
    }
    
    foreach ($creds['admins'] as $admin) {
        $users[] = [
            'id' => $admin['admin_id'] ?? $admin['Admin_ID'] ?? '',
            'name' => $admin['name'] ?? $admin['Name'] ?? 'Unknown',
            'email' => strtolower($admin['admin_id'] ?? '') . '@spysee.app',
            'employee_id' => $admin['admin_id'] ?? $admin['Admin_ID'] ?? '',
            'role' => 'admin',
            'status' => 'active'
        ];
    }
    
    return $users;
}

// ============================================================
// CLOCK IN/OUT - INSTANT STATUS UPDATE
// ============================================================

function handleClockIn($data) {
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $staffName = $data['name'] ?? $_SESSION['user_name'] ?? 'Staff';
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    // Check current status from cache
    $currentStatus = getStaffStatus($userId);
    
    if ($currentStatus === 'in') {
        return ['success' => false, 'message' => 'Already Signed in'];
    }
    
    // INSTANT: Update local cache immediately
    updateLocalStatus($userId, 'in', $staffName);
    
    // INSTANT: Return success to user (no waiting)
    $result = [
        'success' => true, 
        'message' => 'Signed in successfully', 
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'in'
    ];
    
    // BACKGROUND: Send to Google Sheets (non-blocking)
    sendAsyncToGoogleSheets($userId, $staffName, 'web');
    
    return $result;
}

function handleClockOut($data) {
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $staffName = $data['name'] ?? $_SESSION['user_name'] ?? 'Staff';
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    $currentStatus = getStaffStatus($userId);
    
    if ($currentStatus === 'out') {
        return ['success' => false, 'message' => 'Not currently Signed in'];
    }
    
    // INSTANT: Update local cache immediately
    updateLocalStatus($userId, 'out', $staffName);
    
    // INSTANT: Return success
    $result = [
        'success' => true, 
        'message' => 'Signed out successfully', 
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'out'
    ];
    
    // BACKGROUND: Send to Google Sheets
    sendAsyncToGoogleSheets($userId, $staffName, 'web');
    
    return $result;
}

// ============================================================
// DASHBOARD FUNCTIONS
// ============================================================

function getDashboardStats() {
    $allStaff = getAllStaffWithStatus();
    
    $onsiteCount = 0;
    $todayCheckins = 0;
    $today = date('Y-m-d');
    
    foreach ($allStaff['data'] as $staff) {
        if ($staff['status'] === 'in') {
            $onsiteCount++;
        }
        $datePart = substr($staff['last_action'], 0, 10);
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

function getRecentActivity() {
    $data = getCachedData();
    
    if (!$data || !isset($data['rows'])) {
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
            $status = str_replace('Check-', '', $row[3] ?? '');
            $activities[] = [
                'id' => uniqid(),
                'name' => $row[2] ?? 'Unknown',
                'action' => strtolower(trim($status)) === 'in' ? 'sign-in' : 'sign-out',
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s')
            ];
            $count++;
        }
    }
    
    return ['success' => true, 'data' => $activities];
}

// ============================================================
// USER HISTORY
// ============================================================

function getUserHistory($userId) {
    $data = getCachedData();
    
    if (!$data || !isset($data['rows'])) {
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
            $status = str_replace('Check-', '', $row[3] ?? '');
            $records[] = [
                'id' => uniqid(),
                'user_id' => $row[1],
                'type' => strtolower(trim($status)) === 'in' ? 'sign-in' : 'sign-out',
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s'),
                'date' => isset($row[0]) ? substr($row[0], 0, 10) : date('Y-m-d'),
                'location' => $row[4] ?? 'Office'
            ];
        }
    }
    
    return ['success' => true, 'data' => array_reverse($records)];
}

function getAllAttendanceLogs() {
    $data = getCachedData();
    
    if (!$data || !isset($data['rows'])) {
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
            $status = str_replace('Check-', '', $row[3] ?? '');
            $logs[] = [
                'id' => uniqid(),
                'staff' => $row[2] ?? 'Unknown',
                'type' => strtolower(trim($status)) === 'in' ? 'sign-in' : 'sign-out',
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
// USER MANAGEMENT
// ============================================================

function getAllUsers() {
    $users = getAllUsersFromSheets();
    return ['success' => true, 'data' => $users];
}

function findUserById($userId) {
    $users = getAllUsersFromSheets();
    foreach ($users as $user) {
        if ($user['id'] === $userId || $user['employee_id'] === $userId) {
            return $user;
        }
    }
    return null;
}