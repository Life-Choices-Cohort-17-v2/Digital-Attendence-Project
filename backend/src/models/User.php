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
     * Find user by employee_id (staff) 
     * Supports both STF-001 and ADMIN_001 formats
     */
    public function findByEmployeeId(string $employeeId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users 
            WHERE employee_id = :employee_id 
            LIMIT 1
        ');
        $stmt->execute(['employee_id' => $employeeId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find user by email address
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find user by primary key ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Verify user credentials (password or PIN)
     */
public function verifyCredentials(string $identifier, string $password): ?array
{
    // Try employee ID first
    $user = $this->findByEmployeeId($identifier);

    // If not found, try email
    if (!$user) {
        $user = $this->findByEmail($identifier);
    }

    // User does not exist
    if (!$user) {
        return null;
    }

    // Check hashed password
    if (
        !empty($user['password_hash']) &&
        password_verify($password, $user['password_hash'])
    ) {
        return $user;
    }

    // Check staff PIN/password
    if (
        isset($user['passwords']) &&
        $user['passwords'] !== null &&
        hash_equals((string) $user['passwords'], $password)
    ) {
        return $user;
    }

    return null;
}

    /**
     * Count total users
     */
    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS count FROM users');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count active users
     */
    public function countActive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'active'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count inactive users
     */
    public function countInactive(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'inactive'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all users
     */
    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, employee_id, name, email, role, status, created_at 
            FROM users 
            ORDER BY name ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new user
     */
    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (employee_id, name, email, passwords, password_hash, role, status, department, position)
            VALUES (:employee_id, :name, :email, :passwords, :password_hash, :role, :status, :department, :position)
        ');
        return $stmt->execute([
            'employee_id'   => $data['employee_id'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'passwords'     => $data['passwords'] ?? null,
            'password_hash' => $data['password_hash'] ?? null,
            'role'          => $data['role'] ?? 'staff',
            'status'        => $data['status'] ?? 'active',
            'department'    => $data['department'] ?? null,
            'position'      => $data['position'] ?? null,
        ]);
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        $allowed = ['name', 'email', 'role', 'status', 'department', 'position'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (isset($data['password'])) {
            $fields[] = "passwords = :passwords";
            $params['passwords'] = $data['password'];
        }
        
        if (isset($data['password_hash'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password_hash'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET " . implode(', ', $fields) . "
            WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }

        $currentMatches = false;
        if (!empty($user['password_hash'])) {
            $currentMatches = password_verify($currentPassword, $user['password_hash']);
        }
        if (!$currentMatches && isset($user['passwords'])) {
            $currentMatches = hash_equals((string) $user['passwords'], $currentPassword);
        }
        if (!$currentMatches) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET passwords = :passwords, password_hash = :password_hash WHERE id = :id');
        return $stmt->execute([
            'passwords' => $newPassword,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    public function countActiveAdmins(): int
{
    $stmt = $this->pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'"
    );

    return (int) $stmt->fetchColumn();
}

       /**
     * Delete (soft delete) user
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = 'inactive' WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Reactivate an inactive user
     */
    public function reactivate(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = 'active' WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}