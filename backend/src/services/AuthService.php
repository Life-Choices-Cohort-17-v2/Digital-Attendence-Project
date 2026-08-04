<?php
namespace Services;

use Models\User;

class AuthService {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($identifier, $password) {
        $user = $this->userModel->findByEmail($identifier);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account inactive'];
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];
        return ['success' => true, 'user' => $user];
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        return $this->userModel->find($_SESSION['user_id']);
    }
}