<?php

declare(strict_types=1);

namespace SRP\Models;

/**
 * SRP Decision API Client
 * Handles all interactions with the SRP Decision API
 */
class SrpClient
{
    private string $apiUrl;
    private string $apiKey;
    private bool $debugMode;

    public function __construct(?string $apiUrl = null, ?string $apiKey = null, bool $debugMode = false)
    {
        $this->apiUrl = $apiUrl ?? (getenv('SRP_API_URL') ?: 'https://trackng.us/decision.php');
        $this->apiKey = $apiKey ?? (getenv('SRP_API_KEY') ?: '');
        $this->debugMode = $debugMode;
    }

    /**
     * Get routing decision from SRP API
     */
    public function getDecision(array $params): ?array
    {
        if (empty($this->apiKey)) {
            $this->debugLog('API key not configured');
            return null;
        }

        // Validate required parameters
        $required = ['click_id', 'country_code', 'user_agent', 'ip_address'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                $this->debugLog("Missing required field: {$field}");
                return null;
            }
        }

        $this->debugLog('Calling SRP API', $params);

        // Initialize cURL
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'User-Agent: SRP-Client/1.0'
            ],
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->debugLog('cURL error', ['error' => $error]);
            return null;
        }

        if ($httpCode !== 200) {
            $this->debugLog('HTTP error', ['code' => $httpCode, 'response' => $response]);
            return null;
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['ok']) || !$data['ok']) {
            $this->debugLog('Invalid API response', ['response' => $response]);
            return null;
        }

        $this->debugLog('API response received', [
            'decision' => $data['decision'] ?? 'unknown',
            'target' => substr($data['target'] ?? '', 0, 50)
        ]);

        return $data;
    }

    /**
     * Get real client IP address (CloudFlare aware)
     */
    public static function getClientIP(): string
    {
        // Priority order for IP detection
        $ipSources = [
            'HTTP_CF_CONNECTING_IP',  // CloudFlare
            'HTTP_TRUE_CLIENT_IP',     // Enterprise proxies
            'HTTP_X_REAL_IP',          // Nginx proxy
            'HTTP_X_FORWARDED_FOR',    // Standard proxy
            'REMOTE_ADDR'              // Direct connection
        ];

        foreach ($ipSources as $source) {
            if (!empty($_SERVER[$source])) {
                $ip = $_SERVER[$source];

                // Handle X-Forwarded-For (can be comma-separated list)
                if ($source === 'HTTP_X_FORWARDED_FOR' && str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    foreach ($ips as $potentialIP) {
                        $potentialIP = trim($potentialIP);
                        // Skip private/reserved IPs
                        if (filter_var($potentialIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                            return $potentialIP;
                        }
                    }
                } else {
                    return trim($ip);
                }
            }
        }

        // Fallback
        return $_GET['ip_address'] ?? '0.0.0.0';
    }

    /**
     * Detect device type from User Agent
     */
    public static function detectDevice(string $userAgent): string
    {
        // Check for bots first
        if (preg_match('~bot|crawl|spider|facebook|whatsapp|telegram~i', $userAgent)) {
            return 'bot';
        }

        // Check for tablets
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'wap'; // SRP treats tablets as mobile
        }

        // Check for mobile devices
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'wap';
        }

        // Default to desktop
        return 'web';
    }

    /**
     * Get country code from CloudFlare or GeoIP
     */
    public static function getCountryCode(): string
    {
        // CloudFlare provides country code
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
        }

        // Fallback from query parameter
        if (!empty($_GET['country_code'])) {
            return strtoupper((string) $_GET['country_code']);
        }

        return 'XX';
    }

    /**
     * Build fallback URL with original parameters
     */
    public static function getFallbackUrl(string $fallbackPath = '/_meetups/'): string
    {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO']
            ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;

        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        return $baseUrl . $fallbackPath . ($queryString !== '' ? '?' . $queryString : '');
    }

    /**
     * Debug log helper
     */
    private function debugLog(string $message, array $context = []): void
    {
        if (!$this->debugMode) {
            return;
        }

        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        error_log("[SRP Debug] {$message}{$contextStr}");
    }
}
