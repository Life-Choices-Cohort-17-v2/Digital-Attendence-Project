<?php
namespace Services;

use Models\User;
use Models\Attendance;

class DashboardService {
    private $userModel;
    private $attendanceModel;

    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
    }

    public function getStats() {
        return [
            'currentlyOnsite' => count($this->userModel->getOnsiteStaff()),
            'totalClockedInToday' => $this->attendanceModel->getTodayEvents(),
            'pendingSync' => count($this->attendanceModel->getPendingSync()),
            'totalEventsToday' => $this->attendanceModel->getTodayEvents() // extend if needed
        ];
    }

    public function getOnsiteStaff() {
        $staff = $this->userModel->getOnsiteStaff();
        return array_map(function($u) {
            // get last sign-in time
            $stmt = $this->attendanceModel->db->prepare(
                "SELECT timestamp FROM attendance_records WHERE user_id = ? AND type = 'sign_in' AND DATE(timestamp) = ? ORDER BY timestamp DESC LIMIT 1"
            );
            $stmt->execute([$u['id'], date('Y-m-d')]);
            $time = $stmt->fetch();
            return [
                'id' => $u['id'],
                'name' => $u['name'],
                'role' => $u['position'] ?? $u['department'] ?? 'Staff',
                'sign_in_time' => $time ? $time['timestamp'] : null
            ];
        }, $staff);
    }

    public function getRecentActivity($limit = 10) {
        $stmt = $this->attendanceModel->db->prepare(
            "SELECT a.*, u.name FROM attendance_records a 
             JOIN users u ON a.user_id = u.id 
             ORDER BY a.timestamp DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}