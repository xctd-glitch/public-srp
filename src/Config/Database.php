<?php
declare(strict_types=1);

namespace SRP\Config;

use mysqli;
use mysqli_sql_exception;

class Database
{
    private static ?mysqli $connection = null;
    private static bool $bootstrapped = false;

    public static function getConnection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $host = $_ENV['SRP_DB_HOST'] ?? '127.0.0.1';
        $user = $_ENV['SRP_DB_USER'] ?? 'root';
        $pass = $_ENV['SRP_DB_PASS'] ?? '';
        $name = $_ENV['SRP_DB_NAME'] ?? 'srp';
        $port = (int)($_ENV['SRP_DB_PORT'] ?? 3306);
        $socket = $_ENV['SRP_DB_SOCKET'] ?? '';

        try {
            self::$connection = mysqli_init();
            if (self::$connection === false) {
                throw new mysqli_sql_exception('Failed to initialize MySQLi');
            }

            self::$connection->real_connect(
                $host,
                $user,
                $pass,
                $name,
                $port,
                $socket !== '' ? $socket : null
            );
            self::$connection->set_charset('utf8mb4');

            if (!self::$bootstrapped) {
                self::initializeSchema();
                self::$bootstrapped = true;
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            exit('DB init failed');
        }

        return self::$connection;
    }

    private static function initializeSchema(): void
    {
        $conn = self::$connection;

        $conn->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  redirect_url VARCHAR(2048) NOT NULL DEFAULT '',
  system_on TINYINT(1) NOT NULL DEFAULT 0,
  country_filter_mode ENUM('all','whitelist','blacklist') NOT NULL DEFAULT 'all',
  country_filter_list TEXT NOT NULL,
  updated_at INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );

        $conn->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ts INT UNSIGNED NOT NULL,
  ip VARCHAR(45) NOT NULL,
  ua VARCHAR(500) NOT NULL,
  click_id VARCHAR(100) NULL,
  country_code VARCHAR(10) NULL,
  user_lp VARCHAR(100) NULL,
  decision ENUM('A','B') NOT NULL,
  INDEX idx_logs_ts (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );

        $stmt = $conn->prepare(
            "INSERT INTO settings (id, redirect_url, system_on, country_filter_mode, country_filter_list, updated_at)
             VALUES (1, '', 0, 'all', '', UNIX_TIMESTAMP())
             ON DUPLICATE KEY UPDATE id = id"
        );
        $stmt->execute();
        $stmt->close();
    }
}
