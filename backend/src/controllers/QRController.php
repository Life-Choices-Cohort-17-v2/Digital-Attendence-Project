<?php

namespace App\Controllers;

use App\Exceptions\QRException;
use App\Services\QRService;
use PDO;

/**
 * backend/src/controllers/QRController.php
 *
 * Admin-facing: issue a new QR badge for an employee.
 *   GET  /qr/generate?employee_id=EMP00023            -> PNG image
 *   GET  /qr/generate?employee_id=EMP00023&as=json     -> JSON + base64 data URI
 *
 * NOTE ON THE SCAN SIDE: this controller does NOT expose a "/qr/scan"
 * endpoint. Per the flow (Camera -> QR Validation -> AttendanceController),
 * the scanned value gets POSTed straight to whatever endpoint
 * AttendanceController owns (e.g. POST /attendance/clock-in), and that
 * controller calls QRService::validateScan() itself before recording
 * anything. See the integration note in routes/web.php below.
 */
final class QRController
{
    private QRService $qrService;
    public function __construct(private readonly PDO $pdo)
    {
        $this->qrService = new QRService($this->pdo);
    }

    public function generate(): void
    {
        $employeeId = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '';

        if ($employeeId === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'employee_id query parameter is required.']);
            return;
        }

        try {
            $qrCode = $this->qrService->issueFor($employeeId);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Could not generate QR code.']);
            return;
        }

        if (($_GET['as'] ?? '') === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'employee_id' => $employeeId,
                'qr_token' => $qrCode->token,
                'image_data_uri' => $this->qrService->renderPngDataUri($qrCode->token),
            ]);
            return;
        }

        header('Content-Type: image/png');
        echo $this->qrService->renderPng($qrCode->token);
    }

    /**
     * Optional standalone "check this code" endpoint — handy for debugging
     * from Postman/curl without going through the full attendance flow.
     * POST /qr/validate  { "qr_value": "..." }
     */
    public function validate(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $rawValue = is_array($input) ? (string) ($input['qr_value'] ?? '') : '';

        try {
            $qrCode = $this->qrService->validateScan($rawValue);
            echo json_encode([
                'success' => true,
                'employee_id' => $qrCode->employeeId,
                'issued_at' => $qrCode->issuedAt,
                'message' => 'QR code is valid and active.',
            ]);
        } catch (QRException $e) {
            http_response_code(200); // still a well-formed response, just success:false
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
