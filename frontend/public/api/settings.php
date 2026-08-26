<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'update_session_timeout':
        $timeout = intval($input['timeout'] ?? 30);
        if ($timeout < 5) $timeout = 5;
        if ($timeout > 1440) $timeout = 1440;
        
        // Update session timeout
        ini_set('session.gc_maxlifetime', $timeout * 60);
        
        echo json_encode([
            'success' => true,
            'message' => 'Session timeout updated to ' . $timeout . ' minutes'
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action'
        ]);
        break;
}