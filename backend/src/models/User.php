<?php
namespace Models;

class User
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByEmployeeId(string $employeeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute(['employee_id' => $employeeId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS count FROM users');
        return (int) $stmt->fetchColumn();
    }

    public function countInactive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'inactive'");
        return (int) $stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'active'");
        return (int) $stmt->fetchColumn();
    }
}
