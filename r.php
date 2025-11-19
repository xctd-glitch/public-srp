<?php
declare(strict_types=1);

function getSrpDecision(array $params): ? array
{
    $apiUrl = getenv('SRP_API_URL') ?: 'https://trackng.us/decision.php';
    $apiKey = getenv('SRP_API_KEY') ?: '6a6964658b009bc92d359e0d5e7b85957e78e1fddbab456b469da5df65bebf79';

    if (empty($apiKey)) {
        error_log('SRP API Key not configured');
        return null;
    }

    $required = ['click_id', 'country_code', 'user_agent', 'ip_address'];
    foreach ($required as $field) {
        if (empty($params[$field])) {
            error_log("SRP: Missing required field: {$field}");
            return null;
        }
    }

    $ch = curl_init($apiUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
            'User-Agent: MyApp/1.0'
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
        error_log("SRP API Error: {$error}");
        return null;
    }

    if ($httpCode !== 200) {
        error_log("SRP API HTTP {$httpCode}: {$response}");
        return null;
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['ok']) || !$data['ok']) {
        error_log("SRP API Invalid Response: {$response}");
        return null;
    }

    return $data;
}

function handleTrafficRedirect()
{
    $originalQueryString = $_SERVER['QUERY_STRING'] ?? '';

    $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;

    $clickId = $_GET['click_id'] ?? '';
    $countryCode = strtoupper((string) ($_GET['country_code'] ?? 'XX'));
    $userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgentOverride = strtolower((string) ($_GET['user_agent'] ?? ''));

    $deviceToSend = 'web';

    if (preg_match('~bot|crawl|spider|facebook|whatsapp~i', $userAgentRaw)) {
        $deviceToSend = 'BOT';
    } else {
        if (preg_match('/tablet|ipad/i', $userAgentRaw)) {
            $deviceToSend = 'wap';
        } elseif (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgentRaw)) {
            $deviceToSend = 'wap';
        }

        $normalizedOverride = ['mobile' => 'wap', 'desktop' => 'web'];
        $userAgentOverride = $normalizedOverride[$userAgentOverride] ?? $userAgentOverride;

        if (in_array($userAgentOverride, ['wap', 'web'], true)) {
            $deviceToSend = $userAgentOverride;
        }
    }

    $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_TRUE_CLIENT_IP']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? null;

    if ($ipAddress === null && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ipAddress = $ip;
                break;
            }
        }
    }

    if ($ipAddress === null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? ($_GET['ip_address'] ?? '');
    }

    $campaign = (string) ($_GET['user_lp'] ?? '');

    $decision = getSrpDecision([
        'click_id' => $clickId,
        'country_code' => $countryCode,
        'user_agent' => $deviceToSend,
        'ip_address' => $ipAddress,
        'user_lp' => $campaign
    ]);

    if ($decision && isset($decision['target'])) {
        error_log("SRP Decision: {$decision['decision']} -> {$decision['target']}");

        header('Location: ' . $decision['target'], true, 302);
        exit;
    }

    $fallbackUrl = $baseUrl . '/_meetups/' . ($originalQueryString !== '' ? '?' . $originalQueryString : '');

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: ' . $fallbackUrl, true, 302);
    exit;
}

handleTrafficRedirect();