<?php

declare(strict_types=1);

namespace SRP\Models;

use RuntimeException;
use Throwable;

/**
 * Imonetizeit API Client – Complete Edition
 * PHP 8.3 – PSR-12 – Secure – No debug trash
 */
final class ImonetizeitClient
{
    private const AUTH_URL   = 'https://api.imonetizeit.com/v1/auth/session';
    private string $cacheFile;

    private string $clientId;
    private string $apiKey;

    public function __construct()
    {
        $this->cacheFile = __DIR__ . '/../../logs/imonetizeit-session.json';

        $this->clientId = getenv('IMONETIZEIT_CLIENT_ID') ?: '';
        $this->apiKey   = getenv('IMONETIZEIT_API_KEY') ?: '';

        if ($this->clientId === '' || $this->apiKey === '') {
            throw new RuntimeException('ENV IMONETIZEIT_CLIENT_ID / IMONETIZEIT_API_KEY tidak ditemukan');
        }
    }

    /* =========================================================================
       PUBLIC REQUEST WRAPPER
    ========================================================================= */

    public function request(string $method, string $url, array $payload = []): ?array
    {
        $session = $this->getSession();
        if ($session === null || empty($session['session_token'])) {
            return null;
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $session['session_token']
        ];

        $response = $this->curl($method, $url, $payload, $headers);
        if ($response === null) {
            return null;
        }

        if (isset($response['error']) && str_contains(strtolower((string)$response['error']), 'expired')) {
            $session = $this->createSession();
            if ($session === null) {
                return null;
            }

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $session['session_token']
            ];

            return $this->curl($method, $url, $payload, $headers);
        }

        return $response;
    }

    /* =========================================================================
       API FUNCTIONS – SIAP TARIK
    ========================================================================= */

    public function getOffers(int $limit = 50): ?array
    {
        return $this->request('GET', 'https://api.imonetizeit.com/v1/offers', [
            'limit' => max(1, min($limit, 200))
        ]);
    }

    public function getSmartlinks(): ?array
    {
        return $this->request('GET', 'https://api.imonetizeit.com/v1/smartlinks');
    }

    public function getLeads(string $start, string $end): ?array
    {
        if (!$this->isValidDate($start) || !$this->isValidDate($end)) {
            return null;
        }

        return $this->request('GET', 'https://api.imonetizeit.com/v1/leads', [
            'start_date' => $start,
            'end_date'   => $end
        ]);
    }

    public function getClicks(string $start, string $end): ?array
    {
        if (!$this->isValidDate($start) || !$this->isValidDate($end)) {
            return null;
        }

        return $this->request('GET', 'https://api.imonetizeit.com/v1/clicks', [
            'start_date' => $start,
            'end_date'   => $end
        ]);
    }

    public function getStatsDaily(string $date): ?array
    {
        if (!$this->isValidDate($date)) {
            return null;
        }

        return $this->request('GET', 'https://api.imonetizeit.com/v1/stats/daily', [
            'date' => $date
        ]);
    }

    public function getStatsRange(string $start, string $end): ?array
    {
        if (!$this->isValidDate($start) || !$this->isValidDate($end)) {
            return null;
        }

        return $this->request('GET', 'https://api.imonetizeit.com/v1/stats/range', [
            'start_date' => $start,
            'end_date'   => $end
        ]);
    }

    public function getBalance(): ?array
    {
        return $this->request('GET', 'https://api.imonetizeit.com/v1/balance');
    }

    public function getPoints(): ?array
    {
        return $this->request('GET', 'https://api.imonetizeit.com/v1/points');
    }

    /* =========================================================================
       SESSION HANDLING
    ========================================================================= */

    private function getSession(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return $this->createSession();
        }

        $content = file_get_contents($this->cacheFile);
        if ($content === false) {
            return $this->createSession();
        }

        $json = json_decode($content, true);
        if (!is_array($json)) {
            return $this->createSession();
        }

        if (time() - ($json['time'] ?? 0) > 60) {
            return $this->createSession();
        }

        return $json['data'] ?? null;
    }

    private function createSession(): ?array
    {
        $payload = [
            'client_id' => $this->clientId,
            'api_key'   => $this->apiKey
        ];

        $response = $this->curl('POST', self::AUTH_URL, $payload, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if ($response === null) {
            return null;
        }

        // Ensure logs directory exists
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->cacheFile, json_encode([
            'time' => time(),
            'data' => $response
        ], JSON_UNESCAPED_SLASHES));

        return $response;
    }

    /* =========================================================================
       INTERNAL UTILITIES
    ========================================================================= */

    private function curl(string $method, string $url, array $data, array $headers): ?array
    {
        $method = strtoupper($method);

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_SLASHES);
        }

        if ($method === 'GET' && !empty($data)) {
            $options[CURLOPT_URL] = $url . '?' . http_build_query($data);
        }

        curl_setopt_array($ch, $options);

        try {
            $resp = curl_exec($ch);
            if ($resp === false) {
                error_log('cURL error: ' . curl_error($ch));
                curl_close($ch);
                return null;
            }

            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($code < 200 || $code >= 300) {
                error_log('HTTP error: ' . $code);
                return null;
            }

            $json = json_decode((string)$resp, true);
            return is_array($json) ? $json : null;
        } catch (Throwable $e) {
            error_log('Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function isValidDate(string $date): bool
    {
        return (bool)preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date);
    }
}
