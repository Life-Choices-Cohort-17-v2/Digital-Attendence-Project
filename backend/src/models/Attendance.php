<?php
namespace Models;

class Attendance
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insertRecord(int $userId, string $type, ?string $location = null, ?string $device = 'Mobile', ?string $qrCode = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance_records (user_id, type, timestamp, location, device, qr_code, sync_status) VALUES (:user_id, :type, NOW(), :location, :device, :qr_code, :sync_status)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'location' => $location,
            'device' => $device,
            'qr_code' => $qrCode,
            'sync_status' => 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getLastRecordByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM attendance_records WHERE user_id = :user_id ORDER BY timestamp DESC, id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $record = $stmt->fetch();
        return $record ?: null;
    }

    public function getClockedInUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id AS user_id, u.employee_id, u.name, ar.timestamp AS last_clock_time, ar.location
            FROM attendance_records ar
            JOIN users u ON u.id = ar.user_id
            JOIN (
                SELECT user_id, MAX(id) AS last_id
                FROM attendance_records
                GROUP BY user_id
            ) latest ON latest.user_id = ar.user_id AND latest.last_id = ar.id
            WHERE ar.type = "sign_in"
            ORDER BY ar.timestamp DESC'
        );
        return $stmt->fetchAll();
    }

    public function countClockedIn(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS count
            FROM (
                SELECT user_id, MAX(id) AS last_id
                FROM attendance_records
                GROUP BY user_id
            ) latest
            JOIN attendance_records ar ON ar.id = latest.last_id
            WHERE ar.type = "sign_in"'
        );
        return (int) $stmt->fetchColumn();
    }

    public function getRecentRecords(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ar.id, ar.user_id, u.employee_id, u.name, ar.type, ar.timestamp, ar.location, ar.device, ar.sync_status
            FROM attendance_records ar
            JOIN users u ON u.id = ar.user_id
            ORDER BY ar.timestamp DESC
            LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getHistoryForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, type, timestamp, location, device, sync_status
            FROM attendance_records
            WHERE user_id = :user_id
            ORDER BY timestamp DESC
            LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
