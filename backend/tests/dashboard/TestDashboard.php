<?php
require_once __DIR__ . '/../../src/config/DataBase.php';
require_once __DIR__ . '/../../src/models/Attendance.php';
require_once __DIR__ . '/../../src/models/User.php';

use Models\Attendance;
use Models\User;

try {
    $pdo = DataBase::getConnection();

    $attendanceModel = new Attendance($pdo);
    $userModel = new User($pdo);

    echo "=== DASHBOARD METRICS TEST ===\n\n";

    echo "[User Metrics]\n";
    echo "Total Users: " . $userModel->countAll() . "\n";
    echo "Active Users: " . $userModel->countActive() . "\n";
    echo "Inactive Users: " . $userModel->countInactive() . "\n\n";

    echo "[Attendance Metrics]\n";
    echo "Today Present Count: " . $attendanceModel->countTodayPresent() . "\n";
    echo "Currently Onsite Users:\n";
    print_r($attendanceModel->getCurrentlyOnsite());

} catch (Exception $e) {
    echo "Error running test: " . $e->getMessage() . "\n";
}