<?php
declare(strict_types=1);

namespace SRP\Controllers;

use SRP\Middleware\Session;

class DashboardController
{
    public static function index(): void
    {
        Session::start();

        if (empty($_SESSION['srp_admin_id'])) {
            header('Location: /login.php');
            exit;
        }

        $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        require __DIR__ . '/../Views/dashboard.view.php';
    }

    public static function landing(): void
    {
        $csrfNonce = bin2hex(random_bytes(16));

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0');
        header(
            'Content-Security-Policy: '
            . "default-src 'self'; "
            . "script-src 'self' 'nonce-{$csrfNonce}' https://cdn.tailwindcss.com; "
            . "style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
        );

        require __DIR__ . '/../Views/landing.view.php';
    }
}
