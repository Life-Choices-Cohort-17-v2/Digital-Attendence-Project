<?php
namespace Controllers;

use Models\User;
use PDO;
use AuthenticationException;

class AuthController {
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
    }

    public function login(): void {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? $input['username'] ?? '';  // accept both
            $password = $input['password'] ?? '';
        } else {
            // Form submission – the login form sends "identifier" (the email)
            $email = $_POST['identifier'] ?? $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
        }

        try {
            $userModel = new User($this->pdo);
            $user = $userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new AuthenticationException('Invalid email or password');
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new AuthenticationException('Account is inactive. Contact administrator.');
            }

            // Start session and store relevant user info
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['position']   = $user['position'];

            if (str_contains($contentType, 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'employee_id' => $user['employee_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                // Redirect based on role (optional)
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard');
                } else {
                    header('Location: /staff/dashboard');
                }
            }
            exit;
        } catch (AuthenticationException $e) {
            if (str_contains($contentType, 'application/json')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            } else {
                header('Location: /login?error=' . urlencode($e->getMessage()));
            }
            exit;
        }
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json') || $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Logged out']);
        } else {
            header('Location: /login');
        }
        exit;
    }
}