<?php
namespace Controllers;

use Models\User;
use PDO;

class UserController {
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
    }

    /**
     * GET /users - list all users
     */
    public function index(): void {
        header('Content-Type: application/json');
        try {
            $userModel = new User($this->pdo);
            $users = $userModel->getAllUsers();
            echo json_encode(['success' => true, 'data' => $users]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /users - create a new user
     */
    public function store(): void {
        header('Content-Type: application/json');

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $employeeId = trim($input['employee_id'] ?? '');
        $name       = trim($input['name'] ?? '');
        $email      = trim($input['email'] ?? '');
        $role       = trim($input['role'] ?? 'staff');

        if ($employeeId === '' || $name === '' || $email === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'employee_id, name, and email are required']);
            return;
        }

        try {
            $userModel = new User($this->pdo);

            // Don't allow duplicate employee_id or email
            if ($userModel->findByEmployeeId($employeeId)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Employee ID already exists']);
                return;
            }
            if ($userModel->findByEmail($email)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                return;
            }

            // Generate a default password (staff use a PIN, admins use a hashed password)
            $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);

            $created = $userModel->create([
                'employee_id'   => $employeeId,
                'name'          => $name,
                'email'         => $email,
                'passwords'     => $tempPassword,
                'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
                'role'          => $role,
                'status'        => 'active',
            ]);

            if ($created) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'User created',
                    'temp_password' => $tempPassword // show once so admin can share it
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}