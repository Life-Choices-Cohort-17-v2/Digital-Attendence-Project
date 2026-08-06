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

        // Verify employee exists and account status
        AttendanceRules::validateEmployee($employee);

        // Verify QR token expiration and revocation
        AttendanceRules::validateQrCode($employee);

        $employeeId = $employee['employee_id'];
        $isClockedIn = $this->attendanceModel->isClockedIn($employeeId);

        return [
            'employee_id' => $employeeId,
            'name' => $employee['name'],
            'status' => $isClockedIn ? 'CLOCKED_IN' : 'CLOCKED_OUT'
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

        // Non-blocking handoff to Google Sheets (Person 6)
        if (class_exists('\Services\GoogleSheetsService')) {
            try {
                $sheetsService = new \Services\GoogleSheetsService();
                $sheetsService->syncRecord($employeeId, 'CLOCKED_IN', $now);
            } catch (\Throwable $e) {
                error_log("Google Sheets sync failed: " . $e->getMessage());
            }
        }

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
        AttendanceRules::validateEmployee($employee);
        AttendanceRules::validateQrCode($employee);

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

        // Non-blocking handoff to Google Sheets (Person 6)
        if (class_exists('\Services\GoogleSheetsService')) {
            try {
                $sheetsService = new \Services\GoogleSheetsService();
                $sheetsService->syncRecord($employeeId, 'CLOCKED_OUT', $now);
            } catch (\Throwable $e) {
                error_log("Google Sheets sync failed: " . $e->getMessage());
            }
        }

        return [
            'action' => 'CLOCKED_OUT',
            'message' => "Clock-out recorded successfully for {$employee['name']}.",
            'employee_id' => $employeeId,
            'timestamp' => $now
        ];
    }
}