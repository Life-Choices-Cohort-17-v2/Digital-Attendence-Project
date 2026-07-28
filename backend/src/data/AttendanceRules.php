<?php
namespace App\Data;

use App\Helpers\TimeHelper;

/**
 * ATTENDANCE BUSINESS RULES & GUARDS
 * Owner: Person 3 (Clock Engine Lead)
 */
class AttendanceRules 
{
    /**
     * Validates double-scan / cooldown prevention
     */
    public static function enforceCooldown(?array $activeShift): ?array 
    {
        if (!$activeShift) {
            return null;
        }

        if (TimeHelper::isWithinCooldown($activeShift['clock_in'], 30)) {
            return [
                'allowed' => false,
                'status_code' => 429,
                'message' => 'Cooldown active: Please wait 30 seconds between scans.'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Verifies if employee account status allows clocking
     */
    public static function canUserClock(?array $user): array 
    {
        if (!$user) {
            return [
                'allowed' => false,
                'status_code' => 404,
                'message' => 'Employee badge/ID not found in database.'
            ];
        }

        if (empty($user['is_active'])) {
            return [
                'allowed' => false,
                'status_code' => 403,
                'message' => 'Employee account is inactive. Clock-in denied.'
            ];
        }

        return ['allowed' => true];
    }
}