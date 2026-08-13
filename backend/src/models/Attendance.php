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
     * Check if a user is currently clocked in.
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
     * Get the timestamp of the user's last attendance action.
     */
    public function getLastClockTime(int $userId): ?string
    {
        $latest = $this->getLatestRecord($userId);

        return $latest['timestamp'] ?? null;
    }

    /**
     * Insert a new attendance record.
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
            ':type'     => $type,
            ':location' => $location,
            ':device'   => $device,
            ':qr_code'  => $qrCode
        ]);
    }


    // =========================================================
    // DASHBOARD METHODS — Dev 5 integration
    // =========================================================

    /**
     * Count users who are currently clocked in.
     *
     * A user is considered clocked in when their latest
     * attendance record is a sign_in.
     */
    public function countClockedIn(): int
    {
        $stmt = $this->pdo->query(
            "
            SELECT COUNT(*) AS count
            FROM (
                SELECT user_id, MAX(id) AS last_id
                FROM attendance_records
                GROUP BY user_id
            ) latest
            JOIN attendance_records ar
                ON ar.id = latest.last_id
            WHERE ar.type = 'sign_in'
            "
        );

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get users who are currently clocked in.
     */
    public function getClockedInUsers(): array
    {
        $stmt = $this->pdo->query(
            "
            SELECT
                u.id AS user_id,
                u.employee_id,
                u.name,
                ar.timestamp AS last_clock_time,
                ar.location
            FROM attendance_records ar
            JOIN users u
                ON u.id = ar.user_id
            JOIN (
                SELECT user_id, MAX(id) AS last_id
                FROM attendance_records
                GROUP BY user_id
            ) latest
                ON latest.user_id = ar.user_id
                AND latest.last_id = ar.id
            WHERE ar.type = 'sign_in'
            ORDER BY ar.timestamp DESC
            "
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent attendance activity.
     */
    public function getRecentRecords(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                ar.id,
                ar.user_id,
                u.employee_id,
                u.name,
                ar.type,
                ar.timestamp,
                ar.location,
                ar.device,
                ar.sync_status
            FROM attendance_records ar
            JOIN users u
                ON u.id = ar.user_id
            ORDER BY ar.timestamp DESC
            LIMIT :limit
            "
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // =========================================================
    // EXISTING DASHBOARD QUERY
    // =========================================================

    public function countTodayPresent(): int
    {
        $query = "
            SELECT COUNT(DISTINCT user_id)
            FROM attendance_records
            WHERE DATE(timestamp) = CURDATE()
            AND type = 'sign_in'
        ";

        return (int) $this->pdo->query($query)->fetchColumn();
    }

    /**
     * Return users whose latest attendance action is sign_in.
     */
    public function getCurrentlyOnsite(): array
    {
        $query = "
            SELECT a1.*
            FROM attendance_records a1
            INNER JOIN (
                SELECT user_id, MAX(id) AS max_id
                FROM attendance_records
                GROUP BY user_id
            ) a2
                ON a1.id = a2.max_id
            WHERE a1.type = 'sign_in'
        ";

        return $this->pdo->query($query)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}