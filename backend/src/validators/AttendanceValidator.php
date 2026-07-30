<?php

namespace Validators;

use Exception;

class AttendanceValidator
{
    public static function validateScanInput(array $input): string
    {
        if (!isset($input['qr_code'])) {
            throw new Exception('QR code is required.');
        }

        $qrCode = trim($input['qr_code']);

        if ($qrCode === '') {
            throw new Exception('QR code cannot be empty.');
        }

        return $qrCode;
    }
}