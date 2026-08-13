<?php
// Data management functions for SpySee

$dataFile = __DIR__ . '/attendance.json';

function loadData() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        $defaultData = [
            'users' => [
                ['id' => 'admin-001', 'name' => 'Admin User', 'email' => 'admin@spysee.app', 'employee_id' => 'ADM-001', 'role' => 'admin', 'status' => 'active'],
                ['id' => 'staff-001', 'name' => 'Sarah Mthembu', 'email' => 'sarah@spysee.app', 'employee_id' => 'S-101', 'role' => 'staff', 'status' => 'active'],
                ['id' => 'staff-002', 'name' => 'John Adams', 'email' => 'john@spysee.app', 'employee_id' => 'S-102', 'role' => 'staff', 'status' => 'active'],
                ['id' => 'staff-003', 'name' => 'Mary Chen', 'email' => 'mary@spysee.app', 'employee_id' => 'S-103', 'role' => 'staff', 'status' => 'active'],
                ['id' => 'staff-004', 'name' => 'David Okafor', 'email' => 'david@spysee.app', 'employee_id' => 'S-104', 'role' => 'staff', 'status' => 'active'],
            ],
            'active_sessions' => [],
            'attendance_records' => [],
            'qr_codes' => [
                ['id' => 'qr1', 'label' => 'HQ Entrance', 'type' => 'sign-in', 'location' => 'Main Entrance', 'created' => date('Y-m-d'), 'status' => 'active'],
                ['id' => 'qr2', 'label' => 'HQ Exit', 'type' => 'sign-out', 'location' => 'Side Entrance', 'created' => date('Y-m-d'), 'status' => 'active'],
            ]
        ];
        file_put_contents($dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
        return $defaultData;
    }
    return json_decode(file_get_contents($dataFile), true);
}

function saveData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

function getDashboardStats() {
    $data = loadData();
    $onsiteCount = 0;
    $totalEvents = 0;
    $today = date('Y-m-d');
    
    foreach ($data['active_sessions'] as $session) {
        if ($session['status'] === 'signed_in') {
            $onsiteCount++;
        }
    }
    
    foreach ($data['attendance_records'] as $record) {
        if ($record['date'] === $today) {
            $totalEvents++;
        }
    }
    
    return [
        'success' => true,
        'data' => [
            'currentlyOnsite' => $onsiteCount,
            'totalClockedInToday' => $onsiteCount,
            'pendingSync' => 0,
            'totalEventsToday' => $totalEvents
        ]
    ];
}

function getOnsiteStaff() {
    $data = loadData();
    $onsite = [];
    
    foreach ($data['active_sessions'] as $session) {
        if ($session['status'] === 'signed_in') {
            $user = findUserById($data, $session['user_id']);
            if ($user) {
                $onsite[] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'employee_id' => $user['employee_id'],
                    'sign_in_time' => $session['sign_in_time']
                ];
            }
        }
    }
    
    return ['success' => true, 'data' => $onsite];
}

function getRecentActivity() {
    $data = loadData();
    $records = array_slice(array_reverse($data['attendance_records']), 0, 10);
    $activities = [];
    
    foreach ($records as $record) {
        $user = findUserById($data, $record['user_id']);
        if ($user) {
            $activities[] = [
                'id' => $record['id'],
                'name' => $user['name'],
                'action' => $record['type'],
                'timestamp' => $record['timestamp']
            ];
        }
    }
    
    return ['success' => true, 'data' => $activities];
}

function findUserById($data, $userId) {
    foreach ($data['users'] as $user) {
        if ($user['id'] === $userId) {
            return $user;
        }
    }
    return null;
}

function handleClockIn($data) {
    $userData = loadData();
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    // Check if already Signed in
    foreach ($userData['active_sessions'] as $session) {
        if ($session['user_id'] === $userId && $session['status'] === 'signed_in') {
            return ['success' => false, 'message' => 'Already Signed in'];
        }
    }
    
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    
    // Add to active sessions
    $userData['active_sessions'][] = [
        'user_id' => $userId,
        'status' => 'signed_in',
        'sign_in_time' => $now
    ];
    
    // Add attendance record
    $recordId = uniqid();
    $userData['attendance_records'][] = [
        'id' => $recordId,
        'user_id' => $userId,
        'type' => 'sign-in',
        'timestamp' => $now,
        'date' => $today,
        'location' => $data['location'] ?? 'Office'
    ];
    
    saveData($userData);
    
    return ['success' => true, 'message' => 'Signed in successfully', 'timestamp' => $now];
}

function handleClockOut($data) {
    $userData = loadData();
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        return ['success' => false, 'message' => 'User not identified'];
    }
    
    // Find and update active session
    $sessionFound = false;
    foreach ($userData['active_sessions'] as $key => $session) {
        if ($session['user_id'] === $userId && $session['status'] === 'signed_in') {
            $userData['active_sessions'][$key]['status'] = 'signed_out';
            $userData['active_sessions'][$key]['sign_out_time'] = date('Y-m-d H:i:s');
            $sessionFound = true;
            break;
        }
    }
    
    if (!$sessionFound) {
        return ['success' => false, 'message' => 'Not currently Signed in'];
    }
    
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    
    // Add attendance record
    $userData['attendance_records'][] = [
        'id' => uniqid(),
        'user_id' => $userId,
        'type' => 'sign-out',
        'timestamp' => $now,
        'date' => $today,
        'location' => $data['location'] ?? 'Office'
    ];
    
    saveData($userData);
    
    return ['success' => true, 'message' => 'Sign Out successfully', 'timestamp' => $now];
}

function getUserHistory($userId) {
    $data = loadData();
    $records = [];
    
    foreach ($data['attendance_records'] as $record) {
        if ($record['user_id'] === $userId) {
            $records[] = $record;
        }
    }
    
    return ['success' => true, 'data' => array_reverse($records)];
}

function getAllAttendanceLogs() {
    $data = loadData();
    $logs = [];
    
    foreach (array_reverse($data['attendance_records']) as $record) {
        $user = findUserById($data, $record['user_id']);
        if ($user) {
            $logs[] = [
                'id' => $record['id'],
                'staff' => $user['name'],
                'type' => $record['type'],
                'timestamp' => $record['timestamp'],
                'date' => $record['date'],
                'location' => $record['location'] ?? 'Office',
                'sync' => 'synced'
            ];
        }
    }
    
    return ['success' => true, 'data' => $logs];
}

function getAllUsers() {
    $data = loadData();
    return ['success' => true, 'data' => $data['users']];
}

function getQRCodes() {
    $data = loadData();
    $activeQrs = array_filter($data['qr_codes'], function($qr) {
        return $qr['status'] === 'active';
    });
    return ['success' => true, 'data' => array_values($activeQrs)];
}

function generateQRCode($data) {
    $qrData = loadData();
    $newQR = [
        'id' => 'qr_' . uniqid(),
        'label' => $data['label'] ?? 'New QR Code',
        'type' => $data['type'] ?? 'sign-in',
        'location' => $data['location'] ?? 'Office',
        'created' => date('Y-m-d'),
        'status' => 'active'
    ];
    $qrData['qr_codes'][] = $newQR;
    saveData($qrData);
    return ['success' => true, 'data' => $newQR];
}

function revokeQRCode($data) {
    $qrData = loadData();
    $qrId = $data['qr_id'] ?? null;
    foreach ($qrData['qr_codes'] as $key => $qr) {
        if ($qr['id'] === $qrId) {
            $qrData['qr_codes'][$key]['status'] = 'revoked';
            saveData($qrData);
            return ['success' => true];
        }
    }
    return ['success' => false, 'message' => 'QR code not found'];
}