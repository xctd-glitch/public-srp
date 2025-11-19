<?php
declare(strict_types=1);

namespace SRP\Models;

use SRP\Config\Database;

class Settings
{
    public static function get(): array
    {
        $conn = Database::getConnection();
        $result = $conn->query(
            'SELECT redirect_url, system_on, country_filter_mode, country_filter_list, updated_at FROM settings WHERE id = 1'
        );
        $row = $result ? $result->fetch_assoc() : null;
        if ($result) {
            $result->free();
        }

        return $row ?: self::getDefaults();
    }

    public static function update(bool $on, string $url, string $filterMode = 'all', string $filterList = ''): void
    {
        $safeUrl = self::validateUrl($url);

        if (!in_array($filterMode, ['all', 'whitelist', 'blacklist'], true)) {
            throw new \InvalidArgumentException('Invalid country filter mode');
        }

        $countries = [];
        if ($filterList !== '') {
            $parts = explode(',', $filterList);
            foreach ($parts as $code) {
                $code = strtoupper(trim($code));
                if ($code !== '' && Validator::isValidCountryCode($code)) {
                    $countries[] = $code;
                }
            }
        }
        $cleanList = implode(',', array_unique($countries));

        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            'UPDATE settings
               SET system_on = ?,
                   redirect_url = ?,
                   country_filter_mode = ?,
                   country_filter_list = ?,
                   updated_at = UNIX_TIMESTAMP()
             WHERE id = 1'
        );
        $onValue = $on ? 1 : 0;
        $stmt->bind_param('isss', $onValue, $safeUrl, $filterMode, $cleanList);
        $stmt->execute();
        $stmt->close();
    }

    public static function getCountryFilter(): array
    {
        $cfg = self::get();
        $list = $cfg['country_filter_list'] !== '' ? explode(',', $cfg['country_filter_list']) : [];
        return [
            'mode' => $cfg['country_filter_mode'] ?? 'all',
            'list' => array_map('strtoupper', $list),
        ];
    }

    private static function getDefaults(): array
    {
        return [
            'redirect_url' => '',
            'system_on' => 0,
            'country_filter_mode' => 'all',
            'country_filter_list' => '',
            'updated_at' => 0,
        ];
    }

    private static function validateUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid redirect_url format');
        }

        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || $parsed['scheme'] !== 'https') {
            throw new \InvalidArgumentException('Redirect URL must use HTTPS');
        }

        if (!isset($parsed['host']) || !preg_match('/^[a-z0-9.-]+$/i', $parsed['host'])) {
            throw new \InvalidArgumentException('Invalid redirect_url host');
        }

        if (strlen($url) > 2048) {
            throw new \InvalidArgumentException('Redirect URL too long');
        }

        return $url;
    }
}
