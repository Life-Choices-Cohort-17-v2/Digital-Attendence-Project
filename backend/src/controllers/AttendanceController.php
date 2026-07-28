<?php
namespace App\Controllers;

use App\Validators\AttendanceValidator;
use App\Services\AttendanceService;

/**
 * ATTENDANCE CONTROLLER
 * Owner: Person 3 (Clock Engine Lead)
 */
class AttendanceController 
{
    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService) 
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * POST /attendance/scan
     * Smart Endpoint for Person 4's QR Scanner
     */
    public function scan(): void 
    {
        $this->setJsonHeaders();

        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // 1. Validate Input Payload
        $validation = AttendanceValidator::validateScanInput($rawInput);
        if (!$validation['is_valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        // 2. Process Scan via Engine Service
        $result = $this->attendanceService->processScan(
            $validation['employee_id'],
            $validation['location']
        );

        // 3. Respond
        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    /**
     * POST /attendance/clock-in
     */
    public function clockIn(): void 
    {
        $this->setJsonHeaders();
        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validation = AttendanceValidator::validateScanInput($rawInput);
        if (!$validation['is_valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        // Direct Force Clock-In
        $result = $this->attendanceService->processScan($validation['employee_id'], $validation['location']);
        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    /**
     * POST /attendance/clock-out
     */
    public function clockOut(): void 
    {
        $this->setJsonHeaders();
        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validation = AttendanceValidator::validateScanInput($rawInput);
        if (!$validation['is_valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        // Direct Force Clock-Out
        $result = $this->attendanceService->processScan($validation['employee_id'], $validation['location']);
        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    private function setJsonHeaders(): void 
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}