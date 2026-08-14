<?php
namespace Models;

use PDO;

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a user record by email address.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find a user record by primary key ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find a user record by employee string identifier (e.g., EMP001).
     */
    public function findByEmployeeId(string $employeeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute(['employee_id' => $employeeId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Count total registered users in the database.
     */
    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS count FROM users');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count inactive accounts.
     */
    public function countInactive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'inactive'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count active accounts for dashboard metrics.
     */
    public function countActive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'active'");
        return (int) $stmt->fetchColumn();
    }
}