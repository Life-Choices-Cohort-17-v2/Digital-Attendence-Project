<?php
namespace Models;

use DataBase;
use PDO;

class User {
    /**
     * Find a user by email.
     *
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email): ?array {
        $pdo = DataBase::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, employee_id, name, email, password_hash, role, department, position, status
            FROM users
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Verify password.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}