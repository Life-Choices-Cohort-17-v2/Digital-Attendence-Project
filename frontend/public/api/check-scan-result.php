<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['qr_result'])) {
    $result = $_SESSION['qr_result'];
    unset($_SESSION['qr_result']);
    echo json_encode([
        'success' => true,
        'name' => $result['name'] ?? '',
        'action' => $result['action'] ?? '',
        'location' => $result['location'] ?? 'HQ',
        'timestamp' => $result['timestamp'] ?? date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(['success' => false]);
}