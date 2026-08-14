<?php

namespace Models;

class SyncError
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllSyncErrors(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                se.id,
                se.error_message,
                se.attempts,
                se.resolved,
                ar.type,
                ar.timestamp,
                u.name
            FROM sync_errors se
            JOIN attendance_records ar
                ON se.attendance_id = ar.id
            JOIN users u
                ON ar.user_id = u.id
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}