<?php
namespace Data;

use Exception;

class AttendanceRules 
{
    /**
     * Validates QR token structure and extracts the targeted employee ID.
     * Supports formats like 'QR-USER001-A1B2' or 'QR-EMP001-A1B2'.
     */
    public static function validateQrToken(string $qrCode): string 
    {
        if (empty($qrCode) || !str_starts_with($qrCode, 'QR-')) {
            throw new Exception("Invalid QR token format.");
        }

        $parts = explode('-', $qrCode);
        if (count($parts) < 3) {
            throw new Exception("Malformed QR token structure.");
        }

        $employeeTag = $parts[1]; 
        $numericId = preg_replace('/[^0-9]/', '', $employeeTag);

        if (empty($numericId)) {
            throw new Exception("Invalid employee identifier in QR token.");
        }

        return 'EMP' . str_pad($numericId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Checks if the employee is known and active
     */
    public static function validateEmployee(?array $employee): void 
    {
        if (!$employee) {
            throw new Exception("Employee badge or ID not found.");
        }

        if (($employee['status'] ?? '') !== 'active') {
            throw new Exception("Employee account is inactive. Action denied.");
        }
    }

    /**
     * Rules specific to clocking IN
     */
    public static function canClockIn(?array $employee, bool $isClockedIn, ?string $lastClockTime): void 
    {
        self::validateEmployee($employee);

        if ($isClockedIn) {
            throw new Exception("Employee is already clocked in.");
        }

        // Apply cooldown only if there was a previous action
        if ($lastClockTime !== null) {
            self::enforceCooldown($lastClockTime, 30);
        }
    }

    /**
     * Rules specific to clocking OUT
     */
    public static function canClockOut(?array $employee, bool $isClockedIn, ?string $lastClockTime): void 
    {
        self::validateEmployee($employee);

        if (!$isClockedIn) {
            throw new Exception("Employee is not currently clocked in.");
        }
    }

    /**
     * Enforces a cooldown against double-scans or rapid retries
     */
    public static function enforceCooldown(?string $lastActionTime, int $cooldownSeconds = 30): void 
    {
        if (!$lastActionTime || $cooldownSeconds <= 0) {
            return;
        }

        $elapsed = time() - strtotime($lastActionTime);
        if ($elapsed < $cooldownSeconds) {
            $remaining = $cooldownSeconds - $elapsed;
            throw new Exception("Please wait {$remaining} seconds before repeating this action.");
        }
    }
}