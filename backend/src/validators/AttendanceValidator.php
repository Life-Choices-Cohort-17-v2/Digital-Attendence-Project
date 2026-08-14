<?php

namespace Validators;

use Exception;

class AttendanceValidator
{
    /**
     * Parses raw JSON string and validates syntax.
     */
    public static function parseJson(string $rawInput): array
    {
        if (trim($rawInput) === '') {
            return [];
        }

        $decoded = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON payload provided.");
        }

        return $decoded ?? [];
    }

    /**
     * Validates scan payload and returns QR code.
     */
    public static function validateScanInput(array $input): string
    {
        if (!isset($input['qr_code'])) {
            throw new Exception("QR code is required.");
        }

        $qrCode = trim($input['qr_code']);

        if ($qrCode === '') {
            throw new Exception("QR code cannot be empty.");
        }

        return $qrCode;
    }

    /**
     * Validates clock payload and returns employee ID.
     */
    public static function validateClockInput(array $input): string
    {
        if (!isset($input['employee_id'])) {
            throw new Exception("Employee ID is required.");
        }

        $employeeId = trim($input['employee_id']);

        if ($employeeId === '') {
            throw new Exception("Employee ID cannot be empty.");
        }

        return $employeeId;
    }
}