<?php
// ============================================================
// FILE: frontend/data/functions.php
// HYBRID: Database for users + Google Sheets for attendance
// ============================================================

require_once __DIR__ . '/../../backend/src/config/DataBase.php';
require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

// ============================================================
// GET ALL USERS - FROM DATABASE
// ============================================================

function getAllUsersFromDatabase() {
    try {
        $pdo = DataBase::getConnection();
        $stmt = $pdo->query("
            SELECT id, employee_id, name, email, role, status, created_at 
            FROM users 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Database error in getAllUsersFromDatabase: " . $e->getMessage());
        return [];
    }
}

function getAllUsers() {
    $users = getAllUsersFromDatabase();
    return ['success' => true, 'data' => $users];
}

function createUser(array $input): array {
    $employeeId = trim($input['employee_id'] ?? '');
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = trim($input['role'] ?? 'staff');

    if ($employeeId === '' || $name === '' || $email === '') {
        return ['success' => false, 'message' => 'Employee ID, name, and email are required'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Enter a valid email address'];
    }
    if (!in_array($role, ['staff', 'admin'], true)) {
        return ['success' => false, 'message' => 'Role must be staff or admin'];
    }

    try {
        $userModel = new Models\User(DataBase::getConnection());
        if ($userModel->findByEmployeeId($employeeId)) {
            return ['success' => false, 'message' => 'Employee ID already exists'];
        }
        if ($userModel->findByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
        if (!$userModel->create([
            'employee_id' => $employeeId,
            'name' => $name,
            'email' => $email,
            'passwords' => $tempPassword,
            'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
        ])) {
            return ['success' => false, 'message' => 'Failed to create user'];
        }

        return ['success' => true, 'message' => 'User created', 'temp_password' => $tempPassword];
    } catch (Throwable $e) {
        error_log('Database error creating user: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to create user'];
    }
}

function updateUser(int $id, array $input): array {
    $employeeId = trim($input['employee_id'] ?? '');
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = trim($input['role'] ?? 'staff');

    if ($employeeId === '' || $name === '' || $email === '') {
        return ['success' => false, 'message' => 'Employee ID, name, and email are required'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Enter a valid email address'];
    }
    if (!in_array($role, ['staff', 'admin'], true)) {
        return ['success' => false, 'message' => 'Role must be staff or admin'];
    }

    try {
        $userModel = new Models\User(DataBase::getConnection());
        if (!$userModel->findById($id)) {
            return ['success' => false, 'message' => 'User not found'];
        }
        $existingEmployee = $userModel->findByEmployeeId($employeeId);
        if ($existingEmployee && (int) $existingEmployee['id'] !== $id) {
            return ['success' => false, 'message' => 'Employee ID already exists'];
        }
        $existingEmail = $userModel->findByEmail($email);
        if ($existingEmail && (int) $existingEmail['id'] !== $id) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $updated = $userModel->update($id, [
            'employee_id' => $employeeId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);
        return $updated
            ? ['success' => true, 'message' => 'User updated']
            : ['success' => false, 'message' => 'Failed to update user'];
    } catch (Throwable $e) {
        error_log('Database error updating user: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to update user'];
    }
}

function deleteUser(int $id): array {
    try {
        $userModel = new Models\User(DataBase::getConnection());
        if (!$userModel->findById($id)) {
            return ['success' => false, 'message' => 'User not found'];
        }
        return $userModel->delete($id)
            ? ['success' => true, 'message' => 'User deleted']
            : ['success' => false, 'message' => 'Failed to delete user'];
    } catch (Throwable $e) {
        error_log('Database error deleting user: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to delete user'];
    }
}

function updateOwnPassword(int $id, array $input): array {
    $currentPassword = (string) ($input['current_password'] ?? '');
    $newPassword = (string) ($input['new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '') {
        return ['success' => false, 'message' => 'Current and new passwords are required'];
    }
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    if ($currentPassword === $newPassword) {
        return ['success' => false, 'message' => 'New password must be different from the current password'];
    }

    try {
        $userModel = new Models\User(DataBase::getConnection());
        if (!$userModel->updatePassword($id, $currentPassword, $newPassword)) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        return ['success' => true, 'message' => 'Password updated successfully'];
    } catch (Throwable $e) {
        error_log('Database error updating password: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to update password'];
    }
}

// ============================================================
// STAFF STATUS - FROM GOOGLE SHEETS (CACHE)
// ============================================================

function getAllStaffWithStatus() {
    $data = getCachedData();
    
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
    
    return ['success' => true, 'data' => array_values($staffStatus)];
}

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
// CLOCK IN/OUT - INSTANT STATUS UPDATE
// ============================================================

function handleClockIn($data) {
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $staffName = $data['name'] ?? $_SESSION['user_name'] ?? 'Staff';
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    $currentStatus = getStaffStatus($userId);
    
    if ($currentStatus === 'in') {
        return ['success' => false, 'message' => 'Already Signed in'];
    }
    
    updateLocalStatus($userId, 'in', $staffName);
    sendToGoogleSheets($userId, $staffName, 'web');
    
    return [
        'success' => true, 
        'message' => 'Signed in successfully', 
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'in'
    ];
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
    
    updateLocalStatus($userId, 'out', $staffName);
    sendToGoogleSheets($userId, $staffName, 'web');
    
    return [
        'success' => true, 
        'message' => 'Signed out successfully', 
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'out'
    ];
}

// ============================================================
// DASHBOARD FUNCTIONS - FROM GOOGLE SHEETS (CACHE)
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
            $type = strtolower(trim($status)) === 'in' ? 'sign-in' : 'sign-out';
            $method = isset($row[4]) && !empty($row[4]) ? $row[4] : 'QR';
            
            $records[] = [
                'id' => uniqid(),
                'user_id' => $row[1],
                'type' => $type,
                'timestamp' => $row[0] ?? date('Y-m-d H:i:s'),
                'date' => isset($row[0]) ? substr($row[0], 0, 10) : date('Y-m-d'),
                'location' => $row[5] ?? 'Office',
                'method' => $method
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