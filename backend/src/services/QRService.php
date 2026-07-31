<?php

namespace App\Services;

use App\Exceptions\QRException;
use App\Models\QRCode;
use PDO;
use RuntimeException;

/**
 * backend/src/services/QRService.php
 *
 * Owns: "Generate/validate QR codes" from the role brief.
 * validateScan() is the "QR Validation" step in Camera -> QR Validation ->
 * AttendanceController — whoever owns AttendanceController calls this,
 * then acts on the returned employee id.
 */
final class QRService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $pixelsPerModule = 8
    ) {
    }

    public function issueFor(string $employeeId, ?int $ttlSeconds = null): QRCode
    {
        return QRCode::issueFor($this->pdo, $employeeId, $ttlSeconds);
    }

    /**
     * @throws QRException
     */
    public function validateScan(string $rawValue): QRCode
    {
        $token = trim($rawValue);

        if ($token === '') {
            throw QRException::emptyValue();
        }

        if (!ctype_xdigit($token) || strlen($token) !== 32) {
            throw QRException::malformed();
        }

        $qrCode = QRCode::findByToken($this->pdo, $token);
        if ($qrCode === null) {
            throw QRException::notFound();
        }

        if ($qrCode->isRevoked()) {
            throw QRException::revoked();
        }

        if ($qrCode->isExpired()) {
            throw QRException::expired();
        }

        return $qrCode;
    }

    public function renderPng(string $token): string
    {
        if (!function_exists('imagecreate')) {
            throw new RuntimeException('The GD extension is required to render QR PNGs.');
        }
        if (!class_exists('QRCode')) {
            require_once __DIR__ . '/../../vendor/phpqrcode/qrlib.php';
        }


        $matrix = \QRCode::getMinimumQRCode($token, QR_ERROR_CORRECT_LEVEL_M);
        $moduleCount = $matrix->getModuleCount();
        $size = $moduleCount * $this->pixelsPerModule;
        $quietZone = $this->pixelsPerModule * 4;
        $imageSize = $size + ($quietZone * 2);

        $image = imagecreate($imageSize, $imageSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $imageSize, $imageSize, $white);

        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount; $col++) {
                if ($matrix->isDark($row, $col)) {
                    $x = $quietZone + ($col * $this->pixelsPerModule);
                    $y = $quietZone + ($row * $this->pixelsPerModule);
                    imagefilledrectangle(
                        $image,
                        $x,
                        $y,
                        $x + $this->pixelsPerModule - 1,
                        $y + $this->pixelsPerModule - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function renderPngDataUri(string $token): string
    {
        return 'data:image/png;base64,' . base64_encode($this->renderPng($token));
    }
}
