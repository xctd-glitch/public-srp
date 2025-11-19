<?php

declare(strict_types=1);

namespace SRP\Models;

/**
 * Environment Configuration Model
 * Manages .env file configuration without manual file editing
 */
class EnvConfig
{
    private static string $envFilePath;

    /**
     * Initialize with env file path
     */
    private static function init(): void
    {
        if (!isset(self::$envFilePath)) {
            self::$envFilePath = dirname(__DIR__, 2) . '/.env';
        }
    }

    /**
     * Get all environment configuration
     */
    public static function getAll(): array
    {
        self::init();

        $config = [
            // Database
            'DB_HOST' => getenv('DB_HOST') ?: 'localhost',
            'DB_NAME' => getenv('DB_NAME') ?: '',
            'DB_USER' => getenv('DB_USER') ?: '',
            'DB_PASS' => getenv('DB_PASS') ?: '',

            // SRP API
            'SRP_API_URL' => getenv('SRP_API_URL') ?: 'https://trackng.us/decision.php',
            'SRP_API_KEY' => getenv('SRP_API_KEY') ?: '',

            // iMonetizeIt API
            'IMONETIZEIT_CLIENT_ID' => getenv('IMONETIZEIT_CLIENT_ID') ?: '13290',
            'IMONETIZEIT_API_KEY' => getenv('IMONETIZEIT_API_KEY') ?: 'ff8cc8dc0da16083b86c0450d359b9458157778b53b18aac5ecdbc8077022f07',

            // Application
            'APP_ENV' => getenv('APP_ENV') ?: 'production',
            'APP_DEBUG' => getenv('APP_DEBUG') ?: 'false',

            // Session
            'SESSION_LIFETIME' => getenv('SESSION_LIFETIME') ?: '3600',

            // Rate Limiting
            'RATE_LIMIT_ATTEMPTS' => getenv('RATE_LIMIT_ATTEMPTS') ?: '5',
            'RATE_LIMIT_WINDOW' => getenv('RATE_LIMIT_WINDOW') ?: '900',
        ];

        return $config;
    }

    /**
     * Update environment configuration
     */
    public static function update(array $newConfig): bool
    {
        self::init();

        try {
            // Read current .env file
            $envContent = '';
            if (file_exists(self::$envFilePath)) {
                $envContent = file_get_contents(self::$envFilePath);
            }

            // Parse existing env file
            $envVars = self::parseEnvFile($envContent);

            // Merge with new config
            foreach ($newConfig as $key => $value) {
                // Validate key
                if (!self::isValidEnvKey($key)) {
                    continue;
                }

                // Update or add
                $envVars[$key] = $value;
            }

            // Write back to file
            $newContent = self::buildEnvContent($envVars);

            // Backup old file
            if (file_exists(self::$envFilePath)) {
                copy(self::$envFilePath, self::$envFilePath . '.backup');
            }

            // Write new content
            if (file_put_contents(self::$envFilePath, $newContent) === false) {
                throw new \RuntimeException('Failed to write .env file');
            }

            // Update current environment
            foreach ($newConfig as $key => $value) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }

            return true;

        } catch (\Throwable $e) {
            error_log("EnvConfig update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test database connection
     */
    public static function testDatabaseConnection(string $host, string $database, string $username, string $password): array
    {
        try {
            $mysqli = new \mysqli($host, $username, $password, $database);

            if ($mysqli->connect_error) {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $mysqli->connect_error
                ];
            }

            $mysqli->close();

            return [
                'success' => true,
                'message' => 'Connection successful'
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test SRP API connection
     */
    public static function testSrpConnection(string $apiUrl, string $apiKey): array
    {
        try {
            $ch = curl_init($apiUrl);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-Key: ' . $apiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'click_id' => 'TEST_' . time(),
                    'country_code' => 'XX',
                    'user_agent' => 'web',
                    'ip_address' => '127.0.0.1',
                    'user_lp' => 'test'
                ]),
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'message' => 'cURL error: ' . $error
                ];
            }

            if ($httpCode === 401 || $httpCode === 403) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed. Check API key.'
                ];
            }

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => "HTTP error: $httpCode"
                ];
            }

            $data = json_decode($response, true);

            if (!$data) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON response'
                ];
            }

            return [
                'success' => true,
                'message' => 'API connection successful',
                'response' => $data
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse .env file content
     */
    private static function parseEnvFile(string $content): array
    {
        $vars = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes
                $value = trim($value, '"\'');

                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * Build .env file content from array
     */
    private static function buildEnvContent(array $vars): string
    {
        $content = "# ===================================================================\n";
        $content .= "# SRP Application Environment Configuration\n";
        $content .= "# Last updated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "# ===================================================================\n\n";

        // Database section
        $content .= "# Database Configuration\n";
        $content .= "DB_HOST=" . ($vars['DB_HOST'] ?? 'localhost') . "\n";
        $content .= "DB_NAME=" . ($vars['DB_NAME'] ?? '') . "\n";
        $content .= "DB_USER=" . ($vars['DB_USER'] ?? '') . "\n";
        $content .= "DB_PASS=" . ($vars['DB_PASS'] ?? '') . "\n\n";

        // SRP API section
        $content .= "# SRP API Configuration\n";
        $content .= "SRP_API_URL=" . ($vars['SRP_API_URL'] ?? 'https://trackng.us/decision.php') . "\n";
        $content .= "SRP_API_KEY=" . ($vars['SRP_API_KEY'] ?? '') . "\n\n";

        // Application section
        $content .= "# Application Configuration\n";
        $content .= "APP_ENV=" . ($vars['APP_ENV'] ?? 'production') . "\n";
        $content .= "APP_DEBUG=" . ($vars['APP_DEBUG'] ?? 'false') . "\n\n";

        // Session section
        $content .= "# Session Configuration\n";
        $content .= "SESSION_LIFETIME=" . ($vars['SESSION_LIFETIME'] ?? '3600') . "\n\n";

        // Rate limiting section
        $content .= "# Rate Limiting Configuration\n";
        $content .= "RATE_LIMIT_ATTEMPTS=" . ($vars['RATE_LIMIT_ATTEMPTS'] ?? '5') . "\n";
        $content .= "RATE_LIMIT_WINDOW=" . ($vars['RATE_LIMIT_WINDOW'] ?? '900') . "\n\n";

        // Any other vars not in standard sections
        $standardKeys = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'SRP_API_URL', 'SRP_API_KEY',
            'APP_ENV', 'APP_DEBUG',
            'SESSION_LIFETIME',
            'RATE_LIMIT_ATTEMPTS', 'RATE_LIMIT_WINDOW'
        ];

        $otherVars = array_diff_key($vars, array_flip($standardKeys));
        if (!empty($otherVars)) {
            $content .= "# Other Configuration\n";
            foreach ($otherVars as $key => $value) {
                $content .= "$key=$value\n";
            }
        }

        return $content;
    }

    /**
     * Validate environment key
     */
    private static function isValidEnvKey(string $key): bool
    {
        // Allow only alphanumeric and underscore
        return (bool) preg_match('/^[A-Z_][A-Z0-9_]*$/', $key);
    }

    /**
     * Get configuration groups for UI
     */
    public static function getConfigGroups(): array
    {
        $all = self::getAll();

        return [
            'database' => [
                'label' => 'Database Configuration',
                'icon' => 'database',
                'fields' => [
                    'DB_HOST' => [
                        'label' => 'Database Host',
                        'type' => 'text',
                        'value' => $all['DB_HOST'],
                        'placeholder' => 'localhost'
                    ],
                    'DB_NAME' => [
                        'label' => 'Database Name',
                        'type' => 'text',
                        'value' => $all['DB_NAME'],
                        'placeholder' => 'srp_database'
                    ],
                    'DB_USER' => [
                        'label' => 'Database Username',
                        'type' => 'text',
                        'value' => $all['DB_USER'],
                        'placeholder' => 'root'
                    ],
                    'DB_PASS' => [
                        'label' => 'Database Password',
                        'type' => 'password',
                        'value' => $all['DB_PASS'],
                        'placeholder' => '••••••••'
                    ]
                ]
            ],
            'srp_api' => [
                'label' => 'SRP API Configuration',
                'icon' => 'api',
                'fields' => [
                    'SRP_API_URL' => [
                        'label' => 'API URL',
                        'type' => 'url',
                        'value' => $all['SRP_API_URL'],
                        'placeholder' => 'https://trackng.us/decision.php'
                    ],
                    'SRP_API_KEY' => [
                        'label' => 'API Key',
                        'type' => 'password',
                        'value' => $all['SRP_API_KEY'],
                        'placeholder' => 'Enter your API key'
                    ]
                ]
            ],
            'application' => [
                'label' => 'Application Settings',
                'icon' => 'settings',
                'fields' => [
                    'APP_ENV' => [
                        'label' => 'Environment',
                        'type' => 'select',
                        'value' => $all['APP_ENV'],
                        'options' => [
                            'development' => 'Development',
                            'staging' => 'Staging',
                            'production' => 'Production'
                        ]
                    ],
                    'APP_DEBUG' => [
                        'label' => 'Debug Mode',
                        'type' => 'select',
                        'value' => $all['APP_DEBUG'],
                        'options' => [
                            'true' => 'Enabled',
                            'false' => 'Disabled'
                        ]
                    ],
                    'SESSION_LIFETIME' => [
                        'label' => 'Session Lifetime (seconds)',
                        'type' => 'number',
                        'value' => $all['SESSION_LIFETIME'],
                        'placeholder' => '3600'
                    ]
                ]
            ],
            'security' => [
                'label' => 'Security Settings',
                'icon' => 'shield',
                'fields' => [
                    'RATE_LIMIT_ATTEMPTS' => [
                        'label' => 'Rate Limit Attempts',
                        'type' => 'number',
                        'value' => $all['RATE_LIMIT_ATTEMPTS'],
                        'placeholder' => '5'
                    ],
                    'RATE_LIMIT_WINDOW' => [
                        'label' => 'Rate Limit Window (seconds)',
                        'type' => 'number',
                        'value' => $all['RATE_LIMIT_WINDOW'],
                        'placeholder' => '900'
                    ]
                ]
            ]
        ];
    }
}
