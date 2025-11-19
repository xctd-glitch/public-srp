# API Integration Guide - SRP Decision API

## Gambaran Umum

Panduan ini menjelaskan cara memanggil Decision API dari hosting/server lain untuk mendapatkan keputusan redirect berdasarkan traffic profile.

## 📋 Table of Contents

1. [Endpoint Information](#endpoint-information)
2. [Authentication](#authentication)
3. [Request Format](#request-format)
4. [Response Format](#response-format)
5. [Integration Examples](#integration-examples)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)
8. [Testing](#testing)

## 🔗 Endpoint Information

### Base URL
```
https://your-srp-domain.com/decision.php
```

### Method
```
POST
```

### Content Type
```
application/json
```

### Authentication
```
X-API-Key header required
```

## 🔐 Authentication

Decision API menggunakan API Key authentication via header.

### Setup API Key

1. **Generate API Key:**
   ```bash
   # Generate secure random key (32 characters)
   openssl rand -hex 32

   # Or use PHP
   php -r "echo bin2hex(random_bytes(32));"
   ```

2. **Add to .env file pada SRP server:**
   ```env
   SRP_API_KEY=your_generated_api_key_here_64_chars
   ```

3. **Restart web server** setelah update .env

4. **Save API Key** di server client Anda (secure storage)

### Security Best Practices

✅ **DO:**
- Store API key in environment variables
- Use HTTPS for all requests
- Rotate API keys periodically
- Monitor API usage

❌ **DON'T:**
- Hardcode API key in source code
- Commit API key to version control
- Share API key via email/chat
- Use HTTP (unencrypted)

## 📤 Request Format

### Headers

```http
POST /decision.php HTTP/1.1
Host: your-srp-domain.com
Content-Type: application/json
X-API-Key: your_api_key_here
User-Agent: YourApp/1.0
```

### Request Body

```json
{
  "click_id": "ABC123XYZ",
  "country_code": "US",
  "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15",
  "ip_address": "203.0.113.45",
  "user_lp": "campaign_name"
}
```

### Request Parameters

| Parameter | Type | Required | Max Length | Description |
|-----------|------|----------|------------|-------------|
| `click_id` | string | Yes | 100 | Unique identifier untuk click tracking |
| `country_code` | string | Yes | 2 | ISO 3166-1 alpha-2 country code (e.g., US, GB, ID) |
| `user_agent` | string | Yes | 500 | Browser user agent string atau simplified ("mobile", "desktop") |
| `ip_address` | string | Yes | 45 | IPv4 or IPv6 address |
| `user_lp` | string | Optional | 100 | Landing page identifier atau campaign name |

### Input Validation

- `click_id`: Alphanumeric, dash, underscore only
- `country_code`: Must be valid ISO country code
- `ip_address`: Must be valid IP format
- All inputs will be sanitized server-side

## 📥 Response Format

### Success Response

```json
{
  "ok": true,
  "decision": "A",
  "target": "https://target-offer.com/path?params=value"
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `ok` | boolean | Always `true` on success |
| `decision` | string | `"A"` = redirect to offer, `"B"` = fallback/safe page |
| `target` | string | Full URL untuk redirect |

### Decision Logic

**Decision "A" (Redirect to Offer)**
- System is ON
- Device is Mobile/WAP
- Country is allowed (based on whitelist/blacklist)
- No VPN detected
- Auto-mute cycle is in unmute phase

**Decision "B" (Fallback/Safe Page)**
- System is OFF
- Device is Desktop/Tablet/Bot
- Country is blocked
- VPN detected
- Auto-mute cycle is in mute phase

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request (invalid JSON or parameters) |
| 401 | Unauthorized (invalid or missing API key) |
| 405 | Method Not Allowed (not POST) |
| 413 | Payload Too Large (>10KB) |
| 500 | Internal Server Error |

### Error Response

```json
{
  "ok": false,
  "error": "unauthorized"
}
```

## 💻 Integration Examples

### 1. PHP (cURL)

#### Basic Implementation

```php
<?php
/**
 * SRP Decision API Client
 *
 * Call this function to get routing decision from SRP
 */
function getSrpDecision(array $params): ?array
{
    $apiUrl = getenv('SRP_API_URL') ?: 'https://your-srp-domain.com/decision.php';
    $apiKey = getenv('SRP_API_KEY') ?: '';

    if (empty($apiKey)) {
        error_log('SRP API Key not configured');
        return null;
    }

    // Validate required parameters
    $required = ['click_id', 'country_code', 'user_agent', 'ip_address'];
    foreach ($required as $field) {
        if (empty($params[$field])) {
            error_log("SRP: Missing required field: {$field}");
            return null;
        }
    }

    // Prepare request
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

/**
 * Example Usage
 */
function handleTrafficRedirect()
{
    // Get visitor information
    $clickId = $_GET['click_id'] ?? uniqid('clk_');
    $countryCode = getVisitorCountry(); // Your GeoIP function
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $campaign = $_GET['campaign'] ?? '';

    // Call SRP Decision API
    $decision = getSrpDecision([
        'click_id' => $clickId,
        'country_code' => $countryCode,
        'user_agent' => $userAgent,
        'ip_address' => $ipAddress,
        'user_lp' => $campaign
    ]);

    if ($decision && isset($decision['target'])) {
        // Log decision (optional)
        error_log("SRP Decision: {$decision['decision']} -> {$decision['target']}");

        // Redirect to target
        header('Location: ' . $decision['target'], true, 302);
        exit;
    }

    // Fallback if API fails
    header('Location: https://your-safe-page.com');
    exit;
}

/**
 * Helper: Get visitor country from IP
 * Replace with your actual GeoIP implementation
 */
function getVisitorCountry(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Example using CloudFlare header
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
    }

    // Example using MaxMind GeoIP2
    if (class_exists('GeoIp2\Database\Reader')) {
        try {
            $reader = new GeoIp2\Database\Reader('/path/to/GeoLite2-Country.mmdb');
            $record = $reader->country($ip);
            return $record->country->isoCode;
        } catch (Exception $e) {
            error_log("GeoIP Error: " . $e->getMessage());
        }
    }

    return 'XX'; // Unknown
}

// Usage in your landing page
handleTrafficRedirect();
```

#### Environment Setup (.env)

```env
SRP_API_URL=https://your-srp-domain.com/decision.php
SRP_API_KEY=your_64_character_api_key_here
```

### 2. PHP (Guzzle HTTP Client)

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SrpClient
{
    private Client $client;
    private string $apiKey;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 5.0,
            'connect_timeout' => 3.0,
            'verify' => true,
        ]);
        $this->apiKey = $apiKey;
    }

    public function getDecision(array $params): ?array
    {
        try {
            $response = $this->client->post('/decision.php', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                    'User-Agent' => 'MyApp/1.0'
                ],
                'json' => $params
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data && isset($data['ok']) && $data['ok']) {
                return $data;
            }

            return null;

        } catch (GuzzleException $e) {
            error_log("SRP API Error: " . $e->getMessage());
            return null;
        }
    }
}

// Usage
$srp = new SrpClient(
    'https://your-srp-domain.com',
    getenv('SRP_API_KEY')
);

$decision = $srp->getDecision([
    'click_id' => 'ABC123',
    'country_code' => 'US',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_lp' => 'campaign1'
]);

if ($decision) {
    header('Location: ' . $decision['target']);
    exit;
}
```

### 3. Node.js (Axios)

```javascript
const axios = require('axios');

/**
 * SRP Decision API Client
 */
class SrpClient {
    constructor(apiUrl, apiKey) {
        this.apiUrl = apiUrl;
        this.apiKey = apiKey;

        this.client = axios.create({
            baseURL: apiUrl,
            timeout: 5000,
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': apiKey,
                'User-Agent': 'MyApp/1.0'
            }
        });
    }

    async getDecision(params) {
        try {
            const response = await this.client.post('/decision.php', params);

            if (response.data && response.data.ok) {
                return response.data;
            }

            return null;

        } catch (error) {
            console.error('SRP API Error:', error.message);
            return null;
        }
    }
}

// Usage Example
async function handleRequest(req, res) {
    const srp = new SrpClient(
        process.env.SRP_API_URL,
        process.env.SRP_API_KEY
    );

    const decision = await srp.getDecision({
        click_id: req.query.click_id || generateUniqueId(),
        country_code: getCountryFromIP(req.ip),
        user_agent: req.headers['user-agent'],
        ip_address: req.ip,
        user_lp: req.query.campaign || ''
    });

    if (decision && decision.target) {
        console.log(`Decision: ${decision.decision} -> ${decision.target}`);
        return res.redirect(302, decision.target);
    }

    // Fallback
    return res.redirect(302, 'https://your-safe-page.com');
}

module.exports = { SrpClient, handleRequest };
```

### 4. Python (Requests)

```python
import requests
import os
import logging
from typing import Optional, Dict

class SrpClient:
    """SRP Decision API Client"""

    def __init__(self, api_url: str, api_key: str):
        self.api_url = api_url
        self.api_key = api_key
        self.timeout = 5

    def get_decision(self, params: Dict) -> Optional[Dict]:
        """
        Get routing decision from SRP

        Args:
            params: Dictionary with click_id, country_code, user_agent, ip_address, user_lp

        Returns:
            Dictionary with decision data or None on error
        """
        try:
            response = requests.post(
                f"{self.api_url}/decision.php",
                json=params,
                headers={
                    'Content-Type': 'application/json',
                    'X-API-Key': self.api_key,
                    'User-Agent': 'MyApp/1.0'
                },
                timeout=self.timeout,
                verify=True
            )

            response.raise_for_status()
            data = response.json()

            if data.get('ok'):
                return data

            logging.error(f"SRP API Error: {data}")
            return None

        except requests.exceptions.RequestException as e:
            logging.error(f"SRP API Request Error: {e}")
            return None
        except ValueError as e:
            logging.error(f"SRP API JSON Error: {e}")
            return None

# Usage Example
def handle_traffic(request):
    """Handle incoming traffic and redirect"""

    srp = SrpClient(
        api_url=os.getenv('SRP_API_URL'),
        api_key=os.getenv('SRP_API_KEY')
    )

    decision = srp.get_decision({
        'click_id': request.args.get('click_id', generate_unique_id()),
        'country_code': get_country_from_ip(request.remote_addr),
        'user_agent': request.headers.get('User-Agent', ''),
        'ip_address': request.remote_addr,
        'user_lp': request.args.get('campaign', '')
    })

    if decision and decision.get('target'):
        logging.info(f"Decision: {decision['decision']} -> {decision['target']}")
        return redirect(decision['target'], code=302)

    # Fallback
    return redirect('https://your-safe-page.com', code=302)
```

### 5. JavaScript (Browser - Fetch API)

```javascript
/**
 * SRP Client for Browser
 * Note: API Key should NOT be exposed in browser!
 * Use a backend proxy instead.
 */
class SrpClient {
    constructor(proxyUrl) {
        this.proxyUrl = proxyUrl; // Your backend proxy URL
    }

    async getDecision(params) {
        try {
            const response = await fetch(this.proxyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data && data.ok) {
                return data;
            }

            return null;

        } catch (error) {
            console.error('SRP API Error:', error);
            return null;
        }
    }
}

// Usage
async function handleRedirect() {
    const srp = new SrpClient('/api/srp-decision'); // Your backend proxy

    const decision = await srp.getDecision({
        click_id: getClickId(),
        country_code: getCountryCode(),
        user_agent: navigator.userAgent,
        ip_address: await getClientIP(),
        user_lp: getCampaignName()
    });

    if (decision && decision.target) {
        window.location.href = decision.target;
    } else {
        window.location.href = 'https://your-safe-page.com';
    }
}
```

### 6. cURL (Command Line)

```bash
#!/bin/bash

# Configuration
API_URL="https://your-srp-domain.com/decision.php"
API_KEY="your_api_key_here"

# Request payload
REQUEST_DATA='{
  "click_id": "TEST123",
  "country_code": "US",
  "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)",
  "ip_address": "203.0.113.45",
  "user_lp": "campaign1"
}'

# Make request
response=$(curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "User-Agent: TestScript/1.0" \
  -d "$REQUEST_DATA" \
  -s -w "\n%{http_code}")

# Parse response
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

echo "HTTP Code: $http_code"
echo "Response: $body"

# Check success
if [ "$http_code" = "200" ]; then
    decision=$(echo "$body" | jq -r '.decision')
    target=$(echo "$body" | jq -r '.target')
    echo "Decision: $decision"
    echo "Target: $target"
else
    echo "Error: Request failed"
    exit 1
fi
```

## ⚠️ Error Handling

### Common Errors

#### 1. 401 Unauthorized

```json
{"ok":false,"error":"unauthorized"}
```

**Causes:**
- Missing X-API-Key header
- Invalid API key
- API key not matching .env

**Solution:**
- Check API key in request header
- Verify API key in SRP .env file
- Regenerate API key if needed

#### 2. 400 Bad Request

```json
{"ok":false,"error":"Invalid JSON"}
```

**Causes:**
- Malformed JSON
- Missing required fields
- Invalid field format

**Solution:**
- Validate JSON syntax
- Check all required fields present
- Verify field formats (IP, country code, etc.)

#### 3. 413 Payload Too Large

```json
{"ok":false,"error":"Payload too large"}
```

**Causes:**
- Request body > 10KB

**Solution:**
- Reduce payload size
- Don't send unnecessary data

#### 4. 500 Internal Server Error

**Causes:**
- Database connection error
- PHP error on SRP server
- Configuration issue

**Solution:**
- Check SRP server logs
- Verify database is running
- Check .env configuration

### Retry Strategy

```php
function getSrpDecisionWithRetry(array $params, int $maxRetries = 3): ?array
{
    $attempt = 0;
    $delay = 1; // seconds

    while ($attempt < $maxRetries) {
        $result = getSrpDecision($params);

        if ($result !== null) {
            return $result;
        }

        $attempt++;
        if ($attempt < $maxRetries) {
            sleep($delay);
            $delay *= 2; // Exponential backoff
        }
    }

    error_log("SRP API: Max retries reached");
    return null;
}
```

## 🎯 Best Practices

### 1. Timeout Configuration

```php
// Set reasonable timeouts
curl_setopt($ch, CURLOPT_TIMEOUT, 5);         // Total timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout
```

### 2. Error Logging

```php
// Log all API errors
error_log(sprintf(
    "SRP API Error [%s]: %s | Params: %s",
    date('Y-m-d H:i:s'),
    $error,
    json_encode($params)
));
```

### 3. Caching (Optional)

```php
function getCachedDecision(array $params): ?array
{
    $cacheKey = 'srp_' . md5(json_encode($params));
    $cached = apcu_fetch($cacheKey);

    if ($cached !== false) {
        return $cached;
    }

    $decision = getSrpDecision($params);

    if ($decision) {
        apcu_store($cacheKey, $decision, 60); // Cache 1 minute
    }

    return $decision;
}
```

### 4. Fallback Strategy

Always have a fallback URL if API fails:

```php
$decision = getSrpDecision($params);

if ($decision && isset($decision['target'])) {
    $redirectUrl = $decision['target'];
} else {
    // Fallback if API fails
    $redirectUrl = 'https://your-safe-page.com';
}

header('Location: ' . $redirectUrl);
exit;
```

### 5. Monitoring

```php
// Track API response times
$startTime = microtime(true);
$decision = getSrpDecision($params);
$duration = microtime(true) - $startTime;

if ($duration > 2.0) {
    error_log("SRP API Slow Response: {$duration}s");
}
```

## 🧪 Testing

### Test Script

Create `test_srp_api.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php'; // If using Composer

// Load your SRP client implementation
require __DIR__ . '/srp_client.php';

// Test cases
$testCases = [
    'Mobile US User' => [
        'click_id' => 'TEST_MOBILE_US_' . time(),
        'country_code' => 'US',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)',
        'ip_address' => '8.8.8.8',
        'user_lp' => 'test_campaign'
    ],
    'Desktop GB User' => [
        'click_id' => 'TEST_DESKTOP_GB_' . time(),
        'country_code' => 'GB',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'ip_address' => '8.8.4.4',
        'user_lp' => 'test_campaign'
    ],
    'Mobile ID User' => [
        'click_id' => 'TEST_MOBILE_ID_' . time(),
        'country_code' => 'ID',
        'user_agent' => 'mobile',
        'ip_address' => '103.10.20.30',
        'user_lp' => 'test_campaign'
    ]
];

echo "Testing SRP Decision API\n";
echo str_repeat('=', 60) . "\n\n";

foreach ($testCases as $name => $params) {
    echo "Test: {$name}\n";
    echo "Params: " . json_encode($params, JSON_PRETTY_PRINT) . "\n";

    $startTime = microtime(true);
    $decision = getSrpDecision($params);
    $duration = round((microtime(true) - $startTime) * 1000, 2);

    if ($decision) {
        echo "✓ Success ({$duration}ms)\n";
        echo "  Decision: {$decision['decision']}\n";
        echo "  Target: {$decision['target']}\n";
    } else {
        echo "✗ Failed ({$duration}ms)\n";
    }

    echo "\n";
}

echo str_repeat('=', 60) . "\n";
echo "Tests completed\n";
```

Run tests:
```bash
php test_srp_api.php
```

### Expected Output

```
Testing SRP Decision API
============================================================

Test: Mobile US User
Params: {
    "click_id": "TEST_MOBILE_US_1234567890",
    "country_code": "US",
    "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)",
    "ip_address": "8.8.8.8",
    "user_lp": "test_campaign"
}
✓ Success (124.56ms)
  Decision: A
  Target: https://target-offer.com/path

Test: Desktop GB User
Params: {
    "click_id": "TEST_DESKTOP_GB_1234567890",
    "country_code": "GB",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
    "ip_address": "8.8.4.4",
    "user_lp": "test_campaign"
}
✓ Success (98.32ms)
  Decision: B
  Target: /_meetups/?click_id=test_desktop_gb_1234567890...

============================================================
Tests completed
```

## 📊 Performance Tips

### 1. Use Keep-Alive

```php
// Enable HTTP Keep-Alive for multiple requests
curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
```

### 2. Use Connection Pooling

For high-volume traffic, consider using persistent connections.

### 3. Async Requests (Node.js)

```javascript
// Process multiple requests concurrently
const decisions = await Promise.all([
    srp.getDecision(params1),
    srp.getDecision(params2),
    srp.getDecision(params3)
]);
```

### 4. CDN/Proxy

Consider using CDN or proxy for geo-distributed requests.

## 🔒 Security Checklist

- [ ] API key stored in environment variables (not hardcoded)
- [ ] Using HTTPS for all requests
- [ ] SSL certificate verification enabled
- [ ] Request timeout configured
- [ ] Error messages don't expose sensitive info
- [ ] API key rotated periodically
- [ ] Access logs monitored for suspicious activity
- [ ] Rate limiting implemented (if needed)

## 📞 Support

If you encounter issues:

1. Check error logs on both servers
2. Verify API key is correct
3. Test with cURL first
4. Check network connectivity
5. Review this documentation

---

**API Version**: 2.0
**Last Updated**: 2025-11-17
**Compatible with**: SRP 2.0+
