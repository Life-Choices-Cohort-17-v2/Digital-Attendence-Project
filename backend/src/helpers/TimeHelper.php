<?php
namespace App\Helpers;

/**
 * TIME & SHIFT CALCULATIONS
 * Owner: Person 3 (Clock Engine Lead)
 */
class TimeHelper 
{
    /**
     * Calculates duration in seconds between two timestamps
     */
    public static function calculateDurationInSeconds(string $startTime, string $endTime): int 
    {
        return strtotime($endTime) - strtotime($startTime);
    }

    /**
     * Formats shift duration into human-readable format (e.g., "8h 15m")
     */
    public static function formatDuration(int $seconds): string 
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$hours}h {$minutes}m";
    }

    /**
     * Checks if a scan occurred within a cooldown window (in seconds)
     */
    public static function isWithinCooldown(string $lastTimestamp, int $cooldownSeconds = 30): bool 
    {
        $lastTime = strtotime($lastTimestamp);
        $now = time();

        return ($now - $lastTime) < $cooldownSeconds;
    }
}