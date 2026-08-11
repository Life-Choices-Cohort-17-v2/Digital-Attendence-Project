<?php
namespace App\Controllers;

use App\Validators\AttendanceValidator;
use App\Services\AttendanceService;

/**
 * ATTENDANCE CONTROLLER
 * Owner: Person 3 (Clock Engine Lead)
 * Extra methods for Person 7 (Staff Portal)
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
     */
    public function scan(): void 
    {
        $this->setJsonHeaders();

        $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $validation = AttendanceValidator::validateScanInput($rawInput);
        if (!$validation['is_valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        $result = $this->attendanceService->processScan(
            $validation['employee_id'],
            $validation['location']
        );

        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    /**
     * POST /attendance/clock-in  (or /attendance/Spy-in)
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

        $result = $this->attendanceService->processScan($validation['employee_id'], $validation['location']);
        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    /**
     * POST /attendance/clock-out  (or /attendance/Spy-out)
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

        $result = $this->attendanceService->processScan($validation['employee_id'], $validation['location']);
        http_response_code($result['status_code'] ?? 200);
        echo json_encode($result);
    }

    // =========================================================
    // PERSON 7 – Staff Portal endpoints
    // =========================================================

    /**
     * GET /attendance/history
     * Returns records for the currently logged-in user only.
     * Optional: ?today=1
     */
    public function history(): void 
    {
        $this->setJsonHeaders();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $userId    = (int) $_SESSION['user_id'];   // never trust browser user_id
        $todayOnly = isset($_GET['today']) && $_GET['today'] == '1';

        try {
            $records = $this->attendanceService->getHistoryForUser($userId, $todayOnly);

            // Ensure each record has a convenient "date" field
            foreach ($records as &$r) {
                if (!isset($r['date']) && isset($r['timestamp'])) {
                    $r['date'] = substr($r['timestamp'], 0, 10);
                }
            }

            echo json_encode(['data' => $records]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * GET /attendance/onsite
     * Returns staff currently signed in
     */
    public function onsite(): void 
    {
        $this->setJsonHeaders();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        try {
            $staff = $this->attendanceService->getOnsiteStaff();
            echo json_encode(['data' => $staff]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    // =========================================================

    private function setJsonHeaders(): void 
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}