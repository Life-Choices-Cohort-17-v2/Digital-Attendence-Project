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

    /**
     * Safely reads and decodes the incoming JSON payload.
     * Ensures the returned output is strictly an array.
     */
    private function parseRequestBody(): array
    {
        $rawInput = file_get_contents('php://input');
        
        if (trim($rawInput) === '') {
            return [];
        }

        $decoded = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON payload provided.");
        }

        if (!is_array($decoded)) {
            throw new Exception("JSON payload must be a JSON object.");
        }

        return $decoded;
    }

    public function scan(): void 
    {
        try {
            $input = $this->parseRequestBody();
            $qrCode = AttendanceValidator::validateScanInput($input);
            $result = $this->attendanceService->processScan($qrCode);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clockIn(): void 
    {
        try {
            $input = $this->parseRequestBody();
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
        try {
            $input = $this->parseRequestBody();
            $employeeId = AttendanceValidator::validateClockInput($input);
            $result = $this->attendanceService->clockOut($employeeId);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}