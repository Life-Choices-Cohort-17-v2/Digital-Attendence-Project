<?php

require __DIR__ . '/../../src/autoload.php';

use App\Controllers\QRController;
use App\Exceptions\QRException;
use App\Models\QRCode;
use App\Services\QRService;

$failures = 0;
$passed = 0;

function check(string $label, bool $condition, int &$passed, int &$failures): void
{
    if ($condition) {
        $passed++;
        echo "  PASS  {$label}\n";
    } else {
        $failures++;
        echo "  FAIL  {$label}\n";
    }
}

echo "== Autoloader ==\n";
check('App\Models\QRCode loads via autoloader', class_exists(QRCode::class), $passed, $failures);
check('App\Services\QRService loads via autoloader', class_exists(QRService::class), $passed, $failures);
check('App\Controllers\QRController loads via autoloader', class_exists(QRController::class), $passed, $failures);

echo "\n== Setting up in-memory SQLite DB (real SQL, not mocked) ==\n";
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// SQLite-flavoured version of migrations/001_create_qr_codes_table.sql
$pdo->exec('
    CREATE TABLE qr_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id TEXT NOT NULL,
        token TEXT NOT NULL UNIQUE,
        issued_at TEXT NOT NULL,
        expires_at TEXT NULL,
        revoked_at TEXT NULL
    )
');
check('qr_codes table created', true, $passed, $failures);

echo "\n== QRCode model (real inserts/selects) ==\n";
$qrService = new QRService($pdo);

$issued = $qrService->issueFor('EMP00023');
check('issueFor() persists a row and returns a QRCode', $issued->employeeId === 'EMP00023', $passed, $failures);
check('token is 32 hex chars', preg_match('/^[a-f0-9]{32}$/', $issued->token) === 1, $passed, $failures);

$found = QRCode::findByToken($pdo, $issued->token);
check('findByToken() retrieves the same row back', $found !== null && $found->token === $issued->token, $passed, $failures);
check('freshly issued code is valid', $found->isValid(), $passed, $failures);

echo "\n== QRService::validateScan() ==\n";
$validated = $qrService->validateScan($issued->token);
check('validateScan() accepts a valid token', $validated->employeeId === 'EMP00023', $passed, $failures);

try {
    $qrService->validateScan('not-a-real-token');
    check('validateScan() rejects malformed content', false, $passed, $failures);
} catch (QRException $e) {
    check('validateScan() rejects malformed content', true, $passed, $failures);
}

try {
    $qrService->validateScan(str_repeat('a', 32)); // right shape, not in DB
    check('validateScan() rejects a well-formed but unknown token', false, $passed, $failures);
} catch (QRException $e) {
    check('validateScan() rejects a well-formed but unknown token (' . $e->getMessage() . ')', true, $passed, $failures);
}

echo "\n== Revocation ==\n";
$found->revoke();
try {
    $qrService->validateScan($issued->token);
    check('validateScan() rejects a revoked token', false, $passed, $failures);
} catch (QRException $e) {
    check('validateScan() rejects a revoked token (' . $e->getMessage() . ')', true, $passed, $failures);
}

echo "\n== Expiry ==\n";
$expiring = $qrService->issueFor('EMP00099', -10); // already expired 10s ago
try {
    $qrService->validateScan($expiring->token);
    check('validateScan() rejects an expired token', false, $passed, $failures);
} catch (QRException $e) {
    check('validateScan() rejects an expired token (' . $e->getMessage() . ')', true, $passed, $failures);
}

echo "\n== QRService::renderPng() ==\n";
$png = $qrService->renderPng($issued->token);
check('renderPng() returns a valid PNG', substr($png, 0, 8) === "\x89PNG\r\n\x1a\n", $passed, $failures);

echo "\n== QRController instantiates cleanly ==\n";
$controller = new QRController($qrService);
check('QRController constructs without error', $controller instanceof QRController, $passed, $failures);

echo "\n----------------------------------------\n";
echo "Passed: {$passed}, Failed: {$failures}\n";
exit($failures > 0 ? 1 : 0);
