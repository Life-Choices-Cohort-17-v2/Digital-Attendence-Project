<?php
namespace Services;

use Models\User;

class AuthService
{
    private \PDO $pdo;
    private User $userModel;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive.'];
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        return ['success' => true, 'user' => [
            'id' => $user['id'],
            'employee_id' => $user['employee_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->userModel->findById((int) $_SESSION['user_id']);
    }
}
