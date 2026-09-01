<?php
namespace Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // No logged-in session
        if (!isset($_SESSION['user_id'])) {
            self::deny();
        }

        try {
            // Get the database connection
            $pdo = \DataBase::getConnection();

            // Check the user's CURRENT status in the database
            $stmt = $pdo->prepare(
                'SELECT id, status FROM users WHERE id = :id LIMIT 1'
            );

            $stmt->execute([
                'id' => $_SESSION['user_id']
            ]);

            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // User no longer exists or has been deactivated
            if (!$user || $user['status'] !== 'active') {
                $_SESSION = [];

                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();

                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }

                session_destroy();

                self::deny();
            }

        } catch (\Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'success' => false,
                'error' => 'Authentication check failed'
            ]);

            exit;
        }
    }

    private static function deny(): void
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (
            str_contains($contentType, 'application/json') ||
            str_contains($accept, 'application/json')
        ) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);

            exit;
        }

        header('Location: /login');
        exit;
    }
}