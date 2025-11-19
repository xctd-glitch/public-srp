<?php
declare(strict_types=1);

namespace SRP\Middleware;

class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function requireAuth(): void
    {
        self::start();

        if (empty($_SESSION['srp_admin_id'])) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function getCsrfToken(): string
    {
        self::start();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $providedToken): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if ($sessionToken === '' || $providedToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $providedToken);
    }
}
