#!/usr/bin/env php
<?php
declare(strict_types=1);

// Change to project root directory
chdir(dirname(__DIR__));

require_once __DIR__ . '/../src/bootstrap.php';

use SRP\Models\TrafficLog;

// Parse command line arguments
$retentionDays = isset($argv[1]) ? (int) $argv[1] : 7;

if ($retentionDays < 1) {
    echo "Error: Retention days must be at least 1\n";
    exit(1);
}

// Log start
$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] SRP Cron: Starting log cleanup (retention: {$retentionDays} days)\n";

try {
    // Auto cleanup old logs
    $deleted = TrafficLog::autoCleanup($retentionDays);

    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] SRP Cron: Successfully deleted {$deleted} old log entries\n";

    exit(0);

} catch (\Throwable $e) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] SRP Cron: Error - {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";

    exit(1);
}
