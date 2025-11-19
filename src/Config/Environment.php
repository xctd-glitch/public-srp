<?php
declare(strict_types=1);

namespace SRP\Config;

class Environment
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $baseDir = dirname(__DIR__, 2);
        $baseFile = $baseDir . '/.env';
        self::loadEnvFile($baseFile, false);

        $envName = getenv('SRP_ENV') ?: ($_ENV['SRP_ENV'] ?? '');
        $envName = trim((string)$envName);
        if ($envName !== '') {
            $namedFile = sprintf('%s/.env.%s', $baseDir, $envName);
            self::loadEnvFile($namedFile, true);
        }

        $explicitFile = getenv('SRP_ENV_FILE') ?: ($_ENV['SRP_ENV_FILE'] ?? '');
        $explicitFile = trim((string)$explicitFile);
        if ($explicitFile !== '') {
            $path = str_contains($explicitFile, '/') ? $explicitFile : sprintf('%s/%s', $baseDir, $explicitFile);
            self::loadEnvFile($path, true);
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default ?? '';
        }

        return (string)$value;
    }

    private static function loadEnvFile(string $path, bool $override = false): void
    {
        static $fileLoadedKeys = [];

        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $value = trim($value);
            $hasSystemValue = getenv($key) !== false && !isset($fileLoadedKeys[$key]);

            if ($hasSystemValue) {
                continue;
            }

            if (!$override && isset($fileLoadedKeys[$key])) {
                continue;
            }

            $fileLoadedKeys[$key] = true;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
