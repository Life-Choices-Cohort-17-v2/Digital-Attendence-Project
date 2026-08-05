<?php
namespace Controllers;

use Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct(?\PDO $pdo = null)
    {
        $this->authService = new AuthService($pdo);
    }

    public function login(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        header('Content-Type: application/json');

        if (empty($payload['email']) || empty($payload['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
            return;
        }

        $result = $this->authService->login($payload['email'], $payload['password']);
        if (!$result['success']) {
            http_response_code(401);
        }

        echo json_encode($result);
    }

    public function logout(): void
    {
        header('Content-Type: application/json');
        $this->authService->logout();
        echo json_encode(['success' => true, 'message' => 'Logged out.']);
    }
}
