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

    public static function validateClockInput(array $input): string
    {
        if (!isset($input['employee_id'])) {
            throw new Exception('Employee ID is required.');
        }

        $employeeId = trim($input['employee_id']);

        if ($employeeId === '') {
            throw new Exception('Employee ID cannot be empty.');
        }

        return $employeeId;
    }
}