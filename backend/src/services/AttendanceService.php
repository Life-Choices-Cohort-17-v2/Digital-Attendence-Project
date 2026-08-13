<?php
namespace Services;

use PDO;
use Exception;
use Models\User;
use Models\Attendance;
use Data\AttendanceRules;
use Helpers\TimeHelper;

class AttendanceService 
{
    private User $userModel;
    private Attendance $attendanceModel;

    public function __construct(PDO $pdo) 
    {
        $this->userModel = new User($pdo);
        $this->attendanceModel = new Attendance($pdo);
    }

    /**
     * Process a raw QR code scan to identify the employee and current status.
     */
    public function processScan(string $qrCode): array
{
    $employeeId = AttendanceRules::validateQrToken($qrCode);

    $user = $this->userModel->findByEmployeeId($employeeId);

    AttendanceRules::validateEmployee($user);

    $userId = (int) $user['id'];
    $isClockedIn = $this->attendanceModel->isClockedIn($userId);

    return [
        'employee_id' => $user['employee_id'],
        'user_id'     => $userId,
        'name'        => $user['name'],
        'status'      => $isClockedIn ? 'CLOCKED_IN' : 'CLOCKED_OUT'
    ];
}

    public function clockIn(
    string $employeeId,
    ?string $location = null,
    string $device = 'Mobile',
    ?string $qrCode = null
): array 
{
    $user = $this->userModel->findByEmployeeId($employeeId);

    AttendanceRules::validateEmployee($user);

    $userId = (int) $user['id'];
    $isClockedIn = $this->attendanceModel->isClockedIn($userId);
    $lastClockTime = $this->attendanceModel->getLastClockTime($userId);

    AttendanceRules::canClockIn($user, $isClockedIn, $lastClockTime);

    $this->attendanceModel->createRecord(
        $userId,
        'sign_in',
        $location,
        $device,
        $qrCode
    );

    $now = TimeHelper::getCurrentTimestamp();

    // Temporary inline sync trigger
    if (class_exists('\Services\GoogleSheetsService')) {
        try {
            $sheetsService = new \Services\GoogleSheetsService();
            $sheetsService->syncRecord($employeeId, 'CLOCKED_IN', $now);
        } catch (\Throwable $e) {
            error_log(
                "Google Sheets sync non-blocking error: " . $e->getMessage()
            );
        }
    }

    return [
        'action'      => 'CLOCKED_IN',
        'message'     => "Clock-in recorded successfully for {$user['name']}.",
        'employee_id' => $employeeId,
        'timestamp'   => $now
    ];
}

public function clockInByQr(
    string $qrCode,
    ?string $location = null,
    string $device = 'Mobile'
): array 
{
    $employeeId = AttendanceRules::validateQrToken($qrCode);

    return $this->clockIn(
        $employeeId,
        $location,
        $device,
        $qrCode
    );
}

    public function clockOut(string $employeeId, ?string $location = null, string $device = 'Mobile', ?string $qrCode = null): array 
    {
        $user = $this->userModel->findByEmployeeId($employeeId);
        AttendanceRules::validateEmployee($user);

        $userId = (int) $user['id'];
        $isClockedIn = $this->attendanceModel->isClockedIn($userId);
        $lastClockTime = $this->attendanceModel->getLastClockTime($userId);

        AttendanceRules::canClockOut($user, $isClockedIn, $lastClockTime);

        $this->attendanceModel->createRecord($userId, 'sign_out', $location, $device, $qrCode);
        $now = TimeHelper::getCurrentTimestamp();

        // Temporary inline sync trigger (Dev 6 scope) — will be decoupled to event listener post-demo
        if (class_exists('\Services\GoogleSheetsService')) {
            try {
                $sheetsService = new \Services\GoogleSheetsService();
                $sheetsService->syncRecord($employeeId, 'CLOCKED_OUT', $now);
            } catch (\Throwable $e) {
                error_log("Google Sheets sync non-blocking error: " . $e->getMessage());
            }
        }

        return [
            'action'      => 'CLOCKED_OUT',
            'message'     => "Clock-out recorded successfully for {$user['name']}.",
            'employee_id' => $employeeId,
            'timestamp'   => $now
        ];
    }
}