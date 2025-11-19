<?php

declare(strict_types=1);

namespace SRP\Controllers;

use SRP\Models\SrpClient;

/**
 * Landing Controller
 * Handles traffic routing through SRP Decision API
 */
class LandingController
{
    /**
     * Handle traffic routing
     */
    public static function route(): void
    {
        // Initialize SRP client
        $debugMode = (bool) ($_GET['debug'] ?? false);
        $srpClient = new SrpClient(null, null, $debugMode);

        // Get click ID (required)
        $clickId = $_GET['click_id'] ?? 'AUTO_' . uniqid();

        // Get or detect country code
        $countryCode = SrpClient::getCountryCode();

        // Get User Agent
        $userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Detect device type
        $device = SrpClient::detectDevice($userAgentRaw);

        // Allow device override from query parameter
        $deviceOverride = strtolower((string) ($_GET['user_agent'] ?? ''));
        $deviceMapping = ['mobile' => 'wap', 'desktop' => 'web'];
        $deviceOverride = $deviceMapping[$deviceOverride] ?? $deviceOverride;

        if (in_array($deviceOverride, ['wap', 'web', 'bot'], true)) {
            $device = $deviceOverride;
        }

        // Get IP address
        $ipAddress = SrpClient::getClientIP();

        // Get campaign parameter
        $campaign = (string) ($_GET['user_lp'] ?? '');

        // Call SRP Decision API
        $decision = $srpClient->getDecision([
            'click_id' => $clickId,
            'country_code' => $countryCode,
            'user_agent' => $device,
            'ip_address' => $ipAddress,
            'user_lp' => $campaign
        ]);

        // Handle decision
        if ($decision && isset($decision['target'])) {
            $targetUrl = $decision['target'];

            // Set headers
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Location: ' . $targetUrl, true, 302);
            exit;
        }

        // Fallback if API fails
        $fallbackUrl = SrpClient::getFallbackUrl();

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . $fallbackUrl, true, 302);
        exit;
    }

    /**
     * Display landing page information
     */
    public static function index(): void
    {
        require __DIR__ . '/../Views/landing.view.php';
    }
}
