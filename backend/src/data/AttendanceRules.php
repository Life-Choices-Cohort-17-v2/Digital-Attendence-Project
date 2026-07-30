<?php
namespace Data;

use Helpers\TimeHelper;
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

        self::enforceCooldown($lastClockTime, 30);
    }

    /**
     * Rules specific to clocking OUT
     */
// AttendanceRules.php
        public static function canClockOut(?array $employee, bool $isClockedIn, ?string $lastClockTime): void 
        {
            self::validateEmployee($employee);

            if (!$isClockedIn) {
                throw new Exception("Employee is not currently clocked in.");
            }

            // 0 or short delay so clocking out after clocking in isn't blocked
            self::enforceCooldown($lastClockTime, 0); 
        }

    /**
     * Enforces a cooldown against double-scans or rapid retries
     */
    public static function enforceCooldown(
            ?string $lastActionTime, 
            int $cooldownSeconds = 30
        ): void {
            if (!$lastActionTime) {
                return;
            }

            $elapsed = time() - strtotime($lastActionTime);
            if ($elapsed < $cooldownSeconds) {
                $remaining = $cooldownSeconds - $elapsed;
                throw new Exception("Please wait {$remaining} seconds before repeating this action.");
            }
        }
    }
