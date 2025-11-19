<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use SRP\Middleware\Session;
use SRP\Models\ImonetizeitClient;

Session::start();

// Check authentication
if (empty($_SESSION['srp_admin_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_THROW_ON_ERROR);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed'], JSON_THROW_ON_ERROR);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        $raw = '';
    }

    $data = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON payload');
    }

    $action = $data['action'] ?? '';

    if ($action === '') {
        throw new InvalidArgumentException('Missing action parameter');
    }

    $client = new ImonetizeitClient();
    $result = null;

    switch ($action) {
        case 'offers':
            $limit = (int)($data['limit'] ?? 50);
            $result = $client->getOffers($limit);
            break;

        case 'smartlinks':
            $result = $client->getSmartlinks();
            break;

        case 'leads':
            $start = $data['start_date'] ?? '';
            $end = $data['end_date'] ?? '';
            if ($start === '' || $end === '') {
                throw new InvalidArgumentException('Missing start_date or end_date');
            }
            $result = $client->getLeads($start, $end);
            break;

        case 'clicks':
            $start = $data['start_date'] ?? '';
            $end = $data['end_date'] ?? '';
            if ($start === '' || $end === '') {
                throw new InvalidArgumentException('Missing start_date or end_date');
            }
            $result = $client->getClicks($start, $end);
            break;

        case 'stats_daily':
            $date = $data['date'] ?? '';
            if ($date === '') {
                throw new InvalidArgumentException('Missing date parameter');
            }
            $result = $client->getStatsDaily($date);
            break;

        case 'stats_range':
            $start = $data['start_date'] ?? '';
            $end = $data['end_date'] ?? '';
            if ($start === '' || $end === '') {
                throw new InvalidArgumentException('Missing start_date or end_date');
            }
            $result = $client->getStatsRange($start, $end);
            break;

        case 'balance':
            $result = $client->getBalance();
            break;

        case 'points':
            $result = $client->getPoints();
            break;

        default:
            throw new InvalidArgumentException('Unknown action: ' . $action);
    }

    if ($result === null) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'API request failed'], JSON_THROW_ON_ERROR);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'data' => $result
    ], JSON_THROW_ON_ERROR);

} catch (RuntimeException $e) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Service configuration error: ' . $e->getMessage()
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Bad request: ' . $e->getMessage()
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Internal server error'
    ], JSON_THROW_ON_ERROR);
}
