<?php
namespace Services;

use Models\Attendance;
use Models\User;

class DashboardService
{
    private ?\PDO $pdo;
    private User $userModel;
    private Attendance $attendanceModel;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo;
        if ($this->pdo === null) {
            throw new \Exception('Database connection is required for DashboardService.');
        }
        $this->userModel = new User($this->pdo);
        $this->attendanceModel = new Attendance($this->pdo);
    }

    public function getStats(): array
    {
        $totalEmployees = $this->userModel->countAll();
        $inactiveEmployees = $this->userModel->countInactive();
        $clockedInEmployees = $this->attendanceModel->countClockedIn();
        $clockedIn = $this->attendanceModel->getClockedInUsers();

        return [
            'total_employees' => $totalEmployees,
            'clocked_in_employees' => $clockedInEmployees,
            'inactive_employees' => $inactiveEmployees,
            'clocked_in' => $clockedIn,
        ];
    }

    public function getOnsiteStaff(): array
    {
        return $this->attendanceModel->getClockedInUsers();
    }

    public function getRecentActivity(): array
    {
        return $this->attendanceModel->getRecentRecords(10);
    }
}
