<?php
namespace Services;

use Exception;
use Models\Employee;
use Models\Attendance;
use Data\AttendanceRules;
use Helpers\TimeHelper;

class AttendanceService 
{
    private Employee $employeeModel;
    private Attendance $attendanceModel;

    public function __construct(?\PDO $pdo = null) 
    {
        $this->employeeModel = new Employee($pdo);
        $this->attendanceModel = new Attendance($pdo);
    }

    public function processScan(string $qrCode): array 
    {
        $employee = $this->employeeModel->findByQrCode($qrCode);
        AttendanceRules::validateEmployee($employee);

        $isClockedIn = $this->attendanceModel->isClockedIn($employee['employee_id']);

        return [
            'employee' => $employee,
            'current_status' => $isClockedIn ? 'clocked_in' : 'clocked_out'
        ];
    }

    public function clockIn(string $employeeId): array 
    {
        $employee = $this->employeeModel->findById($employeeId);
        $isClockedIn = $this->attendanceModel->isClockedIn($employeeId);
        $lastClockTime = $this->attendanceModel->getLastClockTime($employeeId);

        // Modular Rule Guard
        AttendanceRules::canClockIn($employee, $isClockedIn, $lastClockTime);

        $now = TimeHelper::getCurrentTimestamp();
        $this->attendanceModel->recordClockIn($employeeId, $now);

        return [
            'action' => 'CLOCKED_IN',
            'message' => "Clock-in recorded successfully for {$employee['name']}.",
            'employee_id' => $employeeId,
            'timestamp' => $now
        ];
    }

    public function clockInByQr(string $qrCode): array 
    {
        $employee = $this->employeeModel->findByQrCode($qrCode);
        if (!$employee) {
            throw new Exception("Employee not found for QR code.");
        }

        // Standardized key to 'employee_id'
        return $this->clockIn($employee['employee_id']); 
    }

    public function clockOut(string $employeeId): array 
    {
        $employee = $this->employeeModel->findById($employeeId);
        $isClockedIn = $this->attendanceModel->isClockedIn($employeeId);
        $lastClockTime = $this->attendanceModel->getLastClockTime($employeeId);

        // Modular Rule Guard
        AttendanceRules::canClockOut($employee, $isClockedIn, $lastClockTime);

        $now = TimeHelper::getCurrentTimestamp();
        $this->attendanceModel->recordClockOut($employeeId, $now);

        return [
            'action' => 'CLOCKED_OUT',
            'message' => "Clock-out recorded successfully for {$employee['name']}.",
            'employee_id' => $employeeId,
            'timestamp' => $now
        ];
    }
}