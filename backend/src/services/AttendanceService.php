<?php
namespace Services;

use Exception;
use Models\Attendance;
use Models\User;
use Models\QrCode;

class AttendanceService
{
    private \PDO $pdo;
    private User $userModel;
    private Attendance $attendanceModel;
    private QrCode $qrCodeModel;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->attendanceModel = new Attendance($pdo);
        $this->qrCodeModel = new QrCode($pdo);
    }

    public function processScan(string $qrToken, ?string $location = null, ?string $device = 'Mobile'): array
    {
        $qrRecord = $this->qrCodeModel->findByToken($qrToken);
        if (!$qrRecord) {
            throw new Exception('QR code not recognized.');
        }

        if (!empty($qrRecord['revoked_at'])) {
            throw new Exception('This QR code has been revoked.');
        }

        if (!empty($qrRecord['expires_at']) && strtotime($qrRecord['expires_at']) <= time()) {
            throw new Exception('This QR code has expired.');
        }

        if ($qrRecord['status'] !== 'active') {
            throw new Exception('User account is inactive.');
        }

        $lastRecord = $this->attendanceModel->getLastRecordByUserId((int) $qrRecord['user_id']);
        $type = 'sign_in';
        if ($lastRecord && $lastRecord['type'] === 'sign_in') {
            $type = 'sign_out';
        }

        $this->attendanceModel->insertRecord(
            (int) $qrRecord['user_id'],
            $type,
            $location,
            $device,
            $qrToken
        );

        return [
            'user_id' => $qrRecord['user_id'],
            'employee_id' => $qrRecord['employee_id'],
            'name' => $qrRecord['name'],
            'type' => $type,
            'location' => $location,
            'message' => $type === 'sign_in' ? 'Clock in recorded.' : 'Clock out recorded.',
        ];
    }

    public function clockIn(string $employeeId, ?string $location = null, ?string $device = 'Mobile'): array
    {
        $user = $this->userModel->findByEmployeeId($employeeId);
        if (!$user) {
            throw new Exception('Employee not found.');
        }

        if ($user['status'] !== 'active') {
            throw new Exception('User is inactive.');
        }

        $lastRecord = $this->attendanceModel->getLastRecordByUserId((int) $user['id']);
        if ($lastRecord && $lastRecord['type'] === 'sign_in') {
            throw new Exception('User already clocked in.');
        }

        $this->attendanceModel->insertRecord((int) $user['id'], 'sign_in', $location, $device);

        return [
            'employee_id' => $user['employee_id'],
            'name' => $user['name'],
            'type' => 'sign_in',
            'message' => 'Clock-in recorded successfully.',
        ];
    }

    public function clockOut(string $employeeId, ?string $location = null, ?string $device = 'Mobile'): array
    {
        $user = $this->userModel->findByEmployeeId($employeeId);
        if (!$user) {
            throw new Exception('Employee not found.');
        }

        if ($user['status'] !== 'active') {
            throw new Exception('User is inactive.');
        }

        $lastRecord = $this->attendanceModel->getLastRecordByUserId((int) $user['id']);
        if (!$lastRecord || $lastRecord['type'] !== 'sign_in') {
            throw new Exception('User is not currently clocked in.');
        }

        $this->attendanceModel->insertRecord((int) $user['id'], 'sign_out', $location, $device);

        return [
            'employee_id' => $user['employee_id'],
            'name' => $user['name'],
            'type' => 'sign_out',
            'message' => 'Clock-out recorded successfully.',
        ];
    }
}
