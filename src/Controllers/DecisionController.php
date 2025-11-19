<?php
declare(strict_types=1);

namespace SRP\Controllers;

use SRP\Config\Environment;
use SRP\Models\Settings;
use SRP\Models\TrafficLog;
use SRP\Models\Validator;

class DecisionController
{
    public static function handleDecision(): void
    {
        // Auth
        $apiKey = Environment::get('SRP_API_KEY');
        if ($apiKey === '') {
            http_response_code(500);
            exit('{"ok":false,"error":"API key not configured"}');
        }

        $providedKey = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');

        if ($providedKey === '' || !hash_equals($apiKey, $providedKey)) {
            http_response_code(401);
            exit('{"ok":false,"error":"unauthorized"}');
        }

        // Headers & CORS
        self::handleCors();

        header('Content-Type: application/json');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'OPTIONS') {
            exit;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            exit('{"ok":false,"error":"method"}');
        }

        // Input
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === null) {
            $raw = '{}';
        }

        if (strlen($raw) > 10240) {
            http_response_code(413);
            exit('{"ok":false,"error":"Payload too large"}');
        }

        $in = json_decode($raw, true);
        if (!is_array($in)) {
            http_response_code(400);
            exit('{"ok":false,"error":"Invalid JSON"}');
        }

        // Sanitize inputs
        $cid = Validator::sanitizeString($in['click_id'] ?? '', 100);
        $cc  = Validator::sanitizeString($in['country_code'] ?? 'XX', 10);
        $ua  = Validator::sanitizeString($in['user_agent'] ?? '', 500);
        $ip  = Validator::sanitizeString($in['ip_address'] ?? '', 45);
        $lp  = Validator::sanitizeString($in['user_lp'] ?? '', 100);

        $cid = preg_replace('/[^a-zA-Z0-9_-]/', '', $cid);
        $cc  = preg_replace('/[^a-zA-Z]/', '', $cc);
        $lp  = preg_replace('/[^a-zA-Z0-9_-]/', '', $lp);

        $cid = strtoupper($cid);
        $cc  = strtoupper($cc);
        $lp  = strtoupper($lp);

        if ($ip !== '' && !Validator::isValidIp($ip)) {
            http_response_code(400);
            exit('{"ok":false,"error":"Invalid IP address format"}');
        }

        // Device detection
        $device = self::detectDevice($ua);

        // VPN check
        $vpn = self::checkVpn($ip);

        // Fallback URL
        $fallback = '/_meetups/?' . http_build_query([
            'click_id'     => strtolower($cid),
            'country_code' => strtolower($cc),
            'user_agent'   => strtolower($device),
            'ip_address'   => $ip,
            'user_lp'      => strtolower($lp),
        ], '', '&', PHP_QUERY_RFC3986);

        // Decision logic
        $cfg = Settings::get();
        $decision = 'B';
        $target = $fallback;
        $countryAllowed = true;

        if ($cc !== '' && $cc !== 'XX') {
            if (!Validator::isValidCountryCode($cc)) {
                $cc = 'XX';
            } else {
                $countryAllowed = Validator::isCountryAllowed($cc);
            }
        }

        // Auto mute/unmute logic
        $isMuted = false;
        if (!empty($cfg['system_on'])) {
            $currentMinute = (int)(time() / 60);
            $cyclePosition = $currentMinute % 5;

            if ($cyclePosition >= 2) {
                $isMuted = true;
            }
        }

        if (
            !empty($cfg['system_on']) &&
            !$isMuted &&
            $device === 'WAP' &&
            !$vpn &&
            $countryAllowed &&
            isset($cfg['redirect_url']) &&
            filter_var($cfg['redirect_url'], FILTER_VALIDATE_URL)
        ) {
            $decision = 'A';
            $target = rtrim((string)$cfg['redirect_url'], '/');
        }

        // Response
        echo json_encode([
            'ok'       => true,
            'decision' => $decision,
            'target'   => $target,
        ]);

        TrafficLog::create([
            'ip'       => $ip,
            'ua'       => $ua,
            'cid'      => $cid,
            'cc'       => $cc,
            'lp'       => $lp,
            'decision' => $decision,
        ]);

        exit;
    }

    private static function detectDevice(string $ua): string
    {
        $uaLower = strtolower($ua);
        if ($uaLower === 'wap' || $uaLower === 'mobile') {
            return 'WAP';
        } elseif ($uaLower === 'web' || $uaLower === 'desktop') {
            return 'WEB';
        } elseif ($uaLower === 'tablet') {
            return 'TABLET';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $ua)) {
            return 'WAP';
        } elseif (preg_match('/tablet|ipad/i', $ua)) {
            return 'TABLET';
        }

        if (preg_match('~bot|crawl|spider~i', $ua)) {
            return 'BOT';
        }

        return 'WEB';
    }

    private static function checkVpn(string $ip): bool
    {
        if (!Validator::isValidIp($ip)) {
            return false;
        }

        $vpnCheckUrl = "https://blackbox.ipinfo.app/lookup/" . urlencode($ip);
        $ctx = stream_context_create([
            'http' => [
                'timeout'       => 1,
                'ignore_errors' => true,
                'method'        => 'GET',
            ],
            'ssl'  => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $result = @file_get_contents($vpnCheckUrl, false, $ctx);
        if ($result !== false && $result !== '') {
            return trim($result) === 'Y';
        }

        return false;
    }

    private static function handleCors(): void
    {
        $allowedOrigins = [
            'https://trackng.us',
            'https://www.trackng.us',
            'https://api.trackng.us',
        ];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        } elseif (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
    }
}
