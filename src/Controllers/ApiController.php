<?php
declare(strict_types=1);

namespace SRP\Controllers;

use SRP\Middleware\Session;
use SRP\Models\Settings;
use SRP\Models\TrafficLog;

class ApiController
{
    public static function handleDataRequest(): void
    {
        Session::start();

        if (empty($_SESSION['srp_admin_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_THROW_ON_ERROR);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        self::handleCors();

        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'OPTIONS') {
            exit;
        }

        try {
            switch ($method) {
                case 'GET':
                    self::getData();
                    break;
                case 'POST':
                    self::requireCsrfToken();
                    self::postData();
                    break;
                case 'DELETE':
                    self::requireCsrfToken();
                    self::deleteLogs();
                    break;
                default:
                    throw new \RuntimeException('Method Not Allowed', 405);
            }
        } catch (\Throwable $e) {
            $statusCode = $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            http_response_code($statusCode);

            $errorMsg = 'An error occurred';
            if ($statusCode === 405) {
                $errorMsg = 'Method Not Allowed';
            } elseif ($statusCode === 400) {
                $errorMsg = 'Bad Request';
            }

            echo json_encode(['ok' => false, 'error' => $errorMsg], JSON_THROW_ON_ERROR);
            exit;
        }
    }

    private static function getData(): never
    {
        echo json_encode([
            'ok'   => true,
            'cfg'  => Settings::get(),
            'logs' => TrafficLog::getAll(50),
        ], JSON_THROW_ON_ERROR);

        exit;
    }

    private static function postData(): never
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            $raw = '';
        }

        if (strlen($raw) > 10240) {
            http_response_code(413);
            echo json_encode(['ok' => false, 'error' => 'Payload too large'], JSON_THROW_ON_ERROR);
            exit;
        }

        try {
            $data = json_decode($raw ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload'], JSON_THROW_ON_ERROR);
            exit;
        }

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload'], JSON_THROW_ON_ERROR);
            exit;
        }

        if (!isset($data['system_on']) || !isset($data['redirect_url'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing required fields'], JSON_THROW_ON_ERROR);
            exit;
        }

        try {
            Settings::update(
                (bool)($data['system_on']),
                (string)($data['redirect_url']),
                (string)($data['country_filter_mode'] ?? 'all'),
                (string)($data['country_filter_list'] ?? '')
            );
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid input: ' . $e->getMessage()], JSON_THROW_ON_ERROR);
            exit;
        }

        echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
        exit;
    }

    private static function deleteLogs(): never
    {
        $count = TrafficLog::clearAll();
        echo json_encode(['ok' => true, 'deleted' => $count], JSON_THROW_ON_ERROR);
        exit;
    }

    private static function requireCsrfToken(): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if ($sessionToken === '' || !hash_equals($sessionToken, $providedToken)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token'], JSON_THROW_ON_ERROR);
            exit;
        }
    }

    private static function handleCors(): void
    {
        $allowedOrigins = [
            'https://localhost',
            'http://localhost',
            'http://localhost:8000',
            'http://localhost:3000',
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '') {
            $isAllowed = false;
            foreach ($allowedOrigins as $allowed) {
                if (str_starts_with($origin, $allowed)) {
                    $isAllowed = true;
                    break;
                }
            }

            if ($isAllowed) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        $allowedHeaders = 'Content-Type, X-CSRF-Token, X-Requested-With';

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, DELETE');
            header('Access-Control-Allow-Headers: ' . $allowedHeaders);
        }
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);
    }
}
