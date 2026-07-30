<?php
namespace Models;

class Attendance 
{
    private ?\PDO $pdo;

    // Simulated in-memory session clock states
    private static array $mockClockedIn = [];
    private static array $lastScanTime = [];

    public function __construct(?\PDO $pdo = null) 
    {
        $this->pdo = $pdo;
    }

    public function isClockedIn(string $employeeId): bool 
    {
        return self::$mockClockedIn[$employeeId] ?? false;
    }

    public function getLastClockTime(string $employeeId): ?string 
    {
        return self::$lastScanTime[$employeeId] ?? null;
    }

    public function recordClockIn(string $employeeId, string $timestamp): bool 
    {
        self::$mockClockedIn[$employeeId] = true;
        self::$lastScanTime[$employeeId] = $timestamp;
        return true;
    }

    public function recordClockOut(string $employeeId, string $timestamp): bool 
    {
        self::$mockClockedIn[$employeeId] = false;
        self::$lastScanTime[$employeeId] = $timestamp;
        return true;
    }
}