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
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $qrCode = AttendanceValidator::validateScanInput($input);
            
            // If your flow is scan -> instant clock-in:
            $result = $this->attendanceService->clockInByQr($qrCode);
            
            // OR if your flow is scan -> return status check:
            // $result = $this->attendanceService->processScan($qrCode);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clockIn(): void 
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $employeeId = AttendanceValidator::validateClockInput($input);
            $result = $this->attendanceService->clockIn($employeeId);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clockOut(): void 
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $employeeId = AttendanceValidator::validateClockInput($input);
            $result = $this->attendanceService->clockOut($employeeId);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}