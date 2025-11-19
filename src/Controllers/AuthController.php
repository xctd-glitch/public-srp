<?php
declare(strict_types=1);

namespace SRP\Controllers;

use SRP\Config\Environment;
use SRP\Middleware\Session;

class AuthController
{
    public static function login(): void
    {
        Session::start();

        $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;
        $errorMessage = null;

        // Redirect authenticated admins to dashboard
        if (!empty($_SESSION['srp_admin_id'])) {
            header('Location: /index.php');
            exit;
        }

        $adminUser = trim(Environment::get('SRP_ADMIN_USER'));
        $adminHash = trim(Environment::get('SRP_ADMIN_PASSWORD_HASH'));
        $adminPlain = Environment::get('SRP_ADMIN_PASSWORD');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errorMessage = self::handleLoginAttempt($csrfToken, $adminUser, $adminHash, $adminPlain);
        }

        require __DIR__ . '/../Views/login.view.php';
    }

    public static function logout(): void
    {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login.php');
            exit;
        }

        $csrfSession = (string)($_SESSION['csrf_token'] ?? '');
        $csrfProvided = (string)($_POST['csrf_token'] ?? '');

        if ($csrfSession === '' || $csrfProvided === '' || !hash_equals($csrfSession, $csrfProvided)) {
            http_response_code(400);
            exit('Invalid session token.');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'] ?? '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        session_destroy();

        header('Location: /login.php');
        exit;
    }

    private static function handleLoginAttempt(
        string $csrfToken,
        string $adminUser,
        string $adminHash,
        string $adminPlain
    ): ?string {
        // Rate limiting
        $rateLimitKey = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = ['count' => 0, 'time' => time()];
        }

        $attempts = &$_SESSION[$rateLimitKey];
        if (time() - $attempts['time'] > 900) {
            $attempts = ['count' => 0, 'time' => time()];
        }

        if ($attempts['count'] >= 5) {
            $waitTime = 900 - (time() - $attempts['time']);
            return "Too many login attempts. Please try again in " . ceil($waitTime / 60) . " minutes.";
        }

        $attempts['count']++;

        $providedToken = (string)($_POST['csrf_token'] ?? '');
        if ($csrfToken === '' || !hash_equals($csrfToken, $providedToken)) {
            return 'Invalid session token. Please refresh the page and try again.';
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

        if ($adminUser === '' || ($adminHash === '' && $adminPlain === '')) {
            return 'Admin credentials have not been configured yet.';
        }

        if (!hash_equals($adminUser, $username)) {
            return 'Invalid credentials provided.';
        }

        $passwordOk = false;
        if ($adminHash !== '') {
            $passwordOk = password_verify($password, $adminHash);
        }
        if (!$passwordOk && $adminPlain !== '') {
            $passwordOk = hash_equals($adminPlain, $password);
        }

        if ($passwordOk) {
            unset($_SESSION[$rateLimitKey]);
            session_regenerate_id(true);
            $_SESSION['srp_admin_id'] = $adminUser;

            if ($remember) {
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), [
                    'expires'  => time() + 60 * 60 * 24 * 30,
                    'path'     => $params['path'] ?? '/',
                    'domain'   => $params['domain'] ?? '',
                    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            }

            header('Location: /index.php');
            exit;
        }

        return 'Invalid credentials provided.';
    }
}
