<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use SRP\Middleware\Session;
use SRP\Models\ImonetizeitClient;

Session::start();

// Check authentication
if (empty($_SESSION['srp_admin_id'])) {
    respondJson(401, ['ok' => false, 'error' => 'Unauthorized']);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method !== 'POST') {
    respondJson(405, ['ok' => false, 'error' => 'Method Not Allowed']);
}

try {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw === '' ? '{}' : $raw, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON payload');
    }

    $action = trim((string)($data['action'] ?? ''));

    if ($action === '') {
        throw new InvalidArgumentException('Missing action parameter');
    }

    $client = new ImonetizeitClient();

    $result = match ($action) {
        'offers'      => $client->getOffers(normalizeLimit($data['limit'] ?? null)),
        'smartlinks'  => $client->getSmartlinks(),
        'leads'       => $client->getLeads(...validateDateRange($data)),
        'clicks'      => $client->getClicks(...validateDateRange($data)),
        'stats_daily' => $client->getStatsDaily(validateDateParam($data, 'date')),
        'stats_range' => $client->getStatsRange(...validateDateRange($data)),
        'balance'     => $client->getBalance(),
        'points'      => $client->getPoints(),
        default       => throw new InvalidArgumentException('Unknown action: ' . $action),
    };

    if ($result === null) {
        respondJson(502, ['ok' => false, 'error' => 'API request failed']);
    }

    respondJson(200, [
        'ok' => true,
        'data' => $result
    ]);
} catch (RuntimeException $e) {
    respondJson(503, [
        'ok' => false,
        'error' => 'Service configuration error: ' . $e->getMessage()
    ]);
} catch (InvalidArgumentException $e) {
    respondJson(400, [
        'ok' => false,
        'error' => 'Bad request: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respondJson(500, [
        'ok' => false,
        'error' => 'Internal server error'
    ]);
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function normalizeLimit(mixed $limit): int
{
    $limit = filter_var($limit, FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 50,
            'min_range' => 1,
        ],
    ]);

    return max(1, min((int)$limit, 200));
}

function validateDateParam(array $data, string $key): string
{
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        throw new InvalidArgumentException('Missing ' . $key . ' parameter');
    }

    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
        throw new InvalidArgumentException('Invalid ' . $key . ' format, expected YYYY-MM-DD');
    }

    return $value;
}

function validateDateRange(array $data): array
{
    $start = validateDateParam($data, 'start_date');
    $end = validateDateParam($data, 'end_date');

    return [$start, $end];
}
