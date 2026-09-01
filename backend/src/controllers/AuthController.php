<?php
namespace Controllers;

use Models\User;
use PDO;
use AuthenticationException;

class AuthController
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function login(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            // Accept employee ID, email, or username
            $identifier = trim(
            $input['employee_id']
            ?? $input['identifier']
            ?? $input['email']
            ?? $input['username']
            ?? ''
        );

            $password = $input['password'] ?? '';
        } else {
            // Form submission
            $identifier = trim(
            $_POST['employee_id']
            ?? $_POST['identifier']
            ?? $_POST['email']
            ?? $_POST['username']
            ?? ''
        );
            $password = $_POST['password'] ?? '';
        }

        try {
            $userModel = new User($this->pdo);

            // Authenticate using employee ID OR email,
            // and password_hash OR staff PIN.
            $user = $userModel->verifyCredentials(
                $identifier,
                $password
            );

            if (!$user) {
                throw new AuthenticationException(
                    'Invalid employee ID/email or PIN/password'
                );
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new AuthenticationException(
                    'Account is inactive. Contact administrator.'
                );
            }

            // Start session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Store relevant user information
            $_SESSION['logged_in']  = true;
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['staff_id']   = $user['employee_id'];
            $_SESSION['name']       = $user['name'];
            $_SESSION['staff_name'] = $user['name'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_type']  = $user['role'];
            $_SESSION['department'] = $user['department'] ?? null;
            $_SESSION['position']   = $user['position'] ?? null;

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

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: /admin-dashboard');
                } else {
                    header('Location: /staff-dashboard');
                }
            }

            exit;

        } catch (AuthenticationException $e) {

            if (str_contains($contentType, 'application/json')) {

                http_response_code(401);
                header('Content-Type: application/json');

                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);

            } else {

                header(
                    'Location: /login?error=' .
                    urlencode($e->getMessage())
                );
            }

            exit;
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (
            str_contains($contentType, 'application/json')
            || $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            header('Content-Type: application/json');

            echo json_encode([
                'success' => true,
                'message' => 'Logged out'
            ]);
        } else {
            header('Location: /login');
        }

        exit;
    }
}