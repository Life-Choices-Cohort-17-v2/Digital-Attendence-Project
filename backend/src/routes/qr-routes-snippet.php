<?php

/**
 * ADD THESE LINES to backend/src/routes/web.php — do not replace the
 * whole file, it likely already has auth/dashboard routes in it.
 *
 * Adjust the syntax to match whatever router style web.php already uses
 * (this assumes a simple ['METHOD', 'path', callable] style; swap for
 * your actual $router->get(...)/->post(...) calls if different).
 */

use App\Controllers\QRController;
use App\Services\QRService;
use App\Config\Database; // adjust to however Database.php actually exposes a PDO

function buildQRController(): QRController
{
    $pdo = Database::connect(); // <-- ADAPT: use whatever Database.php actually exposes
    return new QRController(new QRService($pdo));
}

// GET  /qr/generate?employee_id=EMP00023[&as=json]
$router->get('/qr/generate', fn() => buildQRController()->generate());

// POST /qr/validate   { "qr_value": "..." }   (optional debug endpoint)
$router->post('/qr/validate', fn() => buildQRController()->validate());

/**
 * INTEGRATION NOTE for whoever owns AttendanceController / AttendanceService:
 *
 * The scan flow is Camera -> QR Validation -> AttendanceController, meaning
 * the browser POSTs the raw scanned string straight to your clock-in route,
 * not to a separate QR endpoint. Inside that controller action:
 *
 *   $qrService = new QRService($pdo);
 *   try {
 *       $qrCode = $qrService->validateScan($rawScannedValue);
 *   } catch (\App\Exceptions\QRException $e) {
 *       // respond with success:false, message: $e->getMessage()
 *       return;
 *   }
 *   // $qrCode->employeeId is now trusted — hand it to AttendanceService
 *   // for the actual clock-in/out business rules.
 */
