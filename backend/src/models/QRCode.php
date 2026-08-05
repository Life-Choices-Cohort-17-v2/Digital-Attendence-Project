<?php
namespace Models;

class QrCode
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.*, u.id AS user_id, u.employee_id, u.name, u.email, u.status
            FROM qr_codes q
            JOIN users u ON u.employee_id = q.employee_id
            WHERE q.token = :token
            LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $record = $stmt->fetch();
        return $record ?: null;
    }
}
