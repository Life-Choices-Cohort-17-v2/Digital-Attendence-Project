<?php
/**
 * Mock data provider – ensures all functions return { success, data } structure.
 */

// Global mock data (persistent across requests)
$mockData = [
    'users' => [
        ['id' => 'admin-001', 'name' => 'Admin User', 'email' => 'admin@spysee.app', 'role' => 'admin', 'employeeId' => 'ADM-001'],
        ['id' => 'staff-001', 'name' => 'Sarah Mthembu', 'email' => 'sarah@spysee.app', 'role' => 'staff', 'employeeId' => 'S-101'],
        ['id' => 'staff-002', 'name' => 'Demo Staff', 'email' => 'staff@spysee.app', 'role' => 'staff', 'employeeId' => 'EMP-001'],
    ],
    'onsiteStaff' => [], // will be populated on first call
    'dashboardStats' => [],
    'recentActivity' => [],
    'userHistory' => [],
    'attendanceLogs' => [],
    'qrCodes' => [],
];

function getOnsiteStaff(): array {
    global $mockData;
    if (empty($mockData['onsiteStaff'])) {
        // Always seed with Sarah for demo
        $mockData['onsiteStaff'][] = [
            'id'           => 'staff-001',
            'name'         => 'Sarah Mthembu',
            'role'         => 'staff',
            'sign_in_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ];
    }
    return ['success' => true, 'data' => $mockData['onsiteStaff']];
}

function getDashboardStats(): array {
    $onsite = getOnsiteStaff();
    $count = count($onsite['data']);
    return [
        'success' => true,
        'data' => [
            'currentlyOnsite'     => $count,
            'totalClockedInToday' => $count + 5,
            'pendingSync'         => 2,
            'totalEventsToday'    => $count * 2 + 10,
        ]
    ];
}

function getRecentActivity(): array {
    global $mockData;
    if (empty($mockData['recentActivity'])) {
        $mockData['recentActivity'] = [
            ['id' => 1, 'name' => 'Sarah Mthembu', 'action' => 'sign-in', 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes'))],
            ['id' => 2, 'name' => 'Admin User', 'action' => 'sign-out', 'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes'))],
        ];
    }
    return ['success' => true, 'data' => $mockData['recentActivity']];
}

function getUserHistory(string $userId): array {
    global $mockData;
    if (!isset($mockData['userHistory'][$userId])) {
        $mockData['userHistory'][$userId] = [
            ['id' => 1, 'date' => date('Y-m-d', strtotime('-1 day')), 'sign_in' => '08:00 AM', 'sign_out' => '05:00 PM', 'status' => 'present'],
            ['id' => 2, 'date' => date('Y-m-d', strtotime('-2 days')), 'sign_in' => '08:30 AM', 'sign_out' => '05:30 PM', 'status' => 'present'],
        ];
    }
    return ['success' => true, 'data' => $mockData['userHistory'][$userId]];
}

function getAllAttendanceLogs(): array {
    global $mockData;
    if (empty($mockData['attendanceLogs'])) {
        $mockData['attendanceLogs'] = [
            ['id' => 1, 'staff' => 'Sarah Mthembu', 'type' => 'sign-in', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'date' => date('Y-m-d'), 'location' => 'Office', 'sync' => 'synced'],
            ['id' => 2, 'staff' => 'Demo Staff', 'type' => 'sign-out', 'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'date' => date('Y-m-d'), 'location' => 'Office', 'sync' => 'synced'],
        ];
    }
    return ['success' => true, 'data' => $mockData['attendanceLogs']];
}

function getAllUsers(): array {
    global $mockData;
    return ['success' => true, 'data' => $mockData['users']];
}

function getQRCodes(): array {
    global $mockData;
    if (empty($mockData['qrCodes'])) {
        $mockData['qrCodes'] = [
            ['id' => 'qr1', 'label' => 'HQ Entrance', 'type' => 'sign-in', 'location' => 'Main Entrance', 'created' => date('Y-m-d'), 'status' => 'active'],
        ];
    }
    return ['success' => true, 'data' => $mockData['qrCodes']];
}

// ---- Placeholder action functions ----
function handleClockIn(array $data): array {
    return ['success' => true, 'message' => 'Clock-in simulated.', 'timestamp' => date('Y-m-d H:i:s')];
}
function handleClockOut(array $data): array {
    return ['success' => true, 'message' => 'Clock-out simulated.', 'timestamp' => date('Y-m-d H:i:s')];
}
function generateQRCode(array $data): array {
    return ['success' => true, 'message' => 'QR code generated simulated.'];
}
function revokeQRCode(array $data): array {
    return ['success' => true, 'message' => 'QR code revoked simulated.'];
}