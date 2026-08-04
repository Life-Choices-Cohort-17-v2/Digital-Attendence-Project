<?php
namespace Middleware;

use Services\AuthService;

class AuthMiddleware {
    public static function requireRole($role) {
        $auth = new AuthService();
        if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== $role) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public static function requireLogin() {
        $auth = new AuthService();
        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
    }
}