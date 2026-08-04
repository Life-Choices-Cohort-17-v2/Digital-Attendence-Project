<?php
namespace Helpers;

class TimeHelper 
{
    /**
     * Checks if a timestamp is within a given cooldown window (in seconds)
     */
    public static function isWithinCooldown(string $lastTimestamp, int $cooldownSeconds = 30): bool 
    {
        $lastTime = strtotime($lastTimestamp);
        $now = time();

        return ($now - $lastTime) < $cooldownSeconds;
    }

    public static function getCurrentTimestamp(): string 
    {
        return date('Y-m-d H:i:s');
    }
}