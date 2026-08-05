<?php
namespace Controllers;

require_once __DIR__ . '/../validators/AttendanceValidator.php';

use Services\AttendanceService;
use Validators\AttendanceValidator;
use Exception;

class AttendanceController
{
    private AttendanceService $attendanceService;

    public function __construct(?\PDO $pdo = null)
    {
        $this->attendanceService = new AttendanceService($pdo);
    }

    public function scan(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $qrToken = AttendanceValidator::validateScanInput($input);
            $location = $input['location'] ?? null;
            $device = $input['device'] ?? 'Mobile';
            $result = $this->attendanceService->processScan($qrToken, $location, $device);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clockIn(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $employeeId = AttendanceValidator::validateClockInput($input);
            $location = $input['location'] ?? null;
            $device = $input['device'] ?? 'Mobile';
            $result = $this->attendanceService->clockIn($employeeId, $location, $device);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clockOut(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $employeeId = AttendanceValidator::validateClockInput($input);
            $location = $input['location'] ?? null;
            $device = $input['device'] ?? 'Mobile';
            $result = $this->attendanceService->clockOut($employeeId, $location, $device);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
