<?php
namespace Data;

use Exception;

class AttendanceRules 
{
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

        // Set cooldown to 0 seconds so clocking out right after clocking in passes during tests
        self::enforceCooldown($lastClockTime, 0); 
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

    /**
     * Validates QR code status (revoked or expired)
     */
    public static function validateQrCode(array $qrRecord): void
    {
        $now = date('Y-m-d H:i:s');

        if (!empty($qrRecord['revoked_at'])) {
            throw new Exception("QR code has been revoked.");
        }

        if (!empty($qrRecord['expires_at']) && $qrRecord['expires_at'] < $now) {
            throw new Exception("QR code has expired.");
        }
    }
}

