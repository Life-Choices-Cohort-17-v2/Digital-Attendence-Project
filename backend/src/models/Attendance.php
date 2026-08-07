<?php
namespace Models;

use PDO;

class Attendance 
{
    private PDO $pdo;

    public function __construct(PDO $pdo) 
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetch the most recent attendance record for a user.
     * Uses 'id DESC' for sequential safety over timestamp collision.
     */
    public function getLatestRecord(int $userId): ?array 
    {
        $query = "
            SELECT * 
            FROM attendance_records 
            WHERE user_id = :user_id 
            ORDER BY id DESC 
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return $record ?: null;
    }

    /**
     * Check if user is currently clocked in.
     */
    public function isClockedIn(int $userId): bool 
    {
        $latest = $this->getLatestRecord($userId);
        if (!$latest) {
            return false;
        }

        return $latest['type'] === 'sign_in';
    }

    /**
     * Get the timestamp of the last recorded clock action.
     */
    public function getLastClockTime(int $userId): ?string 
    {
        $latest = $this->getLatestRecord($userId);
        return $latest['timestamp'] ?? null;
    }

    /**
     * Insert a new record into attendance_records.
     * Lets MySQL handle auto-generating timestamp and created_at defaults.
     */
    public function createRecord(
        int $userId, 
        string $type, 
        ?string $location = null, 
        string $device = 'Mobile', 
        ?string $qrCode = null
    ): bool {
        $query = "
            INSERT INTO attendance_records 
            (user_id, type, location, device, qr_code, sync_status) 
            VALUES 
            (:user_id, :type, :location, :device, :qr_code, 'pending')
        ";

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            ':user_id'  => $userId,
            ':type'     => $type, // 'sign_in' or 'sign_out'
            ':location' => $location,
            ':device'   => $device,
            ':qr_code'  => $qrCode
        ]);
    }
}