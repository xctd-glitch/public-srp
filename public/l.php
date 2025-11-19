<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

use RuntimeException;

$clientId = '13290';
$apiKey   = 'ff8cc8dc0da16083b86c0450d359b9458157778b53b18aac5ecdbc8077022f07';

if (empty($clientId) || empty($apiKey)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Missing IMT credentials'
    ]);
    exit;
}

$start = date('Y-m-d', strtotime('today')) ?? '';
$end   = date('Y-m-d', strtotime('today')) ?? '';

if (!isValidDate($start) || !isValidDate($end)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid start_date or end_date format. Use Y-m-d.'
    ]);
    exit;
}

try {
    $token = getAccessToken($clientId, $apiKey);
    $data  = getStats($start, $end, $token);

    echo json_encode([
        'ok' => true,
        'data' => $data
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}

function isValidDate(string $date): bool
{
    if ($date === '') {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

function getAccessToken(string $clientId, string $apiKey): string
{
    $payload = json_encode([
        'client_id' => $clientId,
        'api_key'   => $apiKey
    ], JSON_THROW_ON_ERROR);

    $ch = curl_init('https://api.imonetizeit.com/v1/auth/session');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FAILONERROR => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Login request failed: ' . curl_error($ch));
    }

    curl_close($ch);

    $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    if (empty($json['access_token'])) {
        throw new RuntimeException('Access token not found in response');
    }

    return $json['access_token'];
}

function getStats(string $start, string $end, string $token): array
{
    $url = sprintf(
        'https://api.imonetizeit.com/v1/statistics/sm?start_date=%s&end_date=%s&access_token=%s',
        rawurlencode($start),
        rawurlencode($end),
        rawurlencode($token)
    );

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FAILONERROR => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Stats request failed: ' . curl_error($ch));
    }

    curl_close($ch);

    $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    $result = [];

    if (!empty($json['data']) && is_array($json['data'])) {
        $id = 1;
        foreach ($json['data'] as $row) {
            $result[] = [
                'id'        => $id++,
                'smartlink' => $row['smartlink'] ?? '',
                'visits'    => $row['visits'] ?? 0,
                'unique'    => $row['unigue'] ?? 0,
                'clicks'    => $row['clicks'] ?? 0,
                'leads'     => $row['leads'] ?? 0,
                'payout'    => '$' . number_format((float)($row['payouts'] ?? 0), 2)
            ];
        }
    }

    return $result;
}
