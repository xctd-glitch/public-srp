#!/usr/bin/env php
<?php
/**
 * Auto Cleanup Logs - Cron Job Script
 *
 * Purpose: Automatically delete old click logs based on retention period
 * Schedule: Run weekly (recommended: every Sunday at 2 AM)
 *
 * Crontab example:
 * 0 2 * * 0 /usr/bin/php /path/to/public/cron_cleanup_logs.php >> /var/log/srp-cleanup.log 2>&1
 *
 * Or using crontab with cd:
 * 0 2 * * 0 cd /path/to/public && php cron_cleanup_logs.php >> /var/log/srp-cleanup.log 2>&1
 */

declare(strict_types=1);

// Only allow CLI execution
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from command line');
}

require __DIR__ . '/_bootstrap.php';

use function SRP\env;
use function SRP\autoCleanupLogs;
use function SRP\getCleanupStats;

// Script configuration
$dryRun = in_array('--dry-run', $argv ?? [], true);
$verbose = in_array('--verbose', $argv ?? [], true) || in_array('-v', $argv ?? [], true);
$force = in_array('--force', $argv ?? [], true);

// Get retention period from environment
$retentionDays = (int)env('SRP_LOG_RETENTION_DAYS', '7');
if ($retentionDays < 1 || $retentionDays > 365) {
    $retentionDays = 7; // Default to 7 days
}

// Header
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     SRP Auto Cleanup Logs - Weekly Maintenance           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "Retention Period: {$retentionDays} days\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no deletion)" : "LIVE") . "\n\n";

// Get statistics before cleanup
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "BEFORE CLEANUP\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $statsBefore = getCleanupStats();

    echo "Total Logs: " . number_format($statsBefore['total']) . "\n";
    echo "Oldest Log: " . $statsBefore['oldest_days'] . " days ago\n";
    echo "Newest Log: " . $statsBefore['newest_days'] . " days ago\n";
    echo "Est. Size: " . formatBytes($statsBefore['size_estimate']) . "\n\n";

    // Check if cleanup is needed
    if ($statsBefore['total'] === 0) {
        echo "✓ No logs to cleanup.\n";
        exit(0);
    }

    if ($statsBefore['oldest_days'] < $retentionDays && !$force) {
        echo "✓ All logs are within retention period ({$retentionDays} days).\n";
        echo "  Oldest log is only {$statsBefore['oldest_days']} days old.\n";
        echo "  Use --force to cleanup anyway.\n";
        exit(0);
    }

    // Perform cleanup
    if ($dryRun) {
        echo "\n[DRY RUN] Would delete logs older than {$retentionDays} days\n";
        $deleted = 0; // Simulate
    } else {
        echo "\n🗑️  Deleting logs older than {$retentionDays} days...\n";
        $deleted = autoCleanupLogs($retentionDays);
    }

    // Get statistics after cleanup
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "AFTER CLEANUP\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $statsAfter = getCleanupStats();

    echo "Deleted Logs: " . number_format($deleted) . "\n";
    echo "Remaining Logs: " . number_format($statsAfter['total']) . "\n";

    if ($statsAfter['total'] > 0) {
        echo "Oldest Log: " . $statsAfter['oldest_days'] . " days ago\n";
        echo "Newest Log: " . $statsAfter['newest_days'] . " days ago\n";
        echo "Est. Size: " . formatBytes($statsAfter['size_estimate']) . "\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "SUMMARY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $spaceSaved = $statsBefore['size_estimate'] - $statsAfter['size_estimate'];
    $percentDeleted = $statsBefore['total'] > 0 ?
        round(($deleted / $statsBefore['total']) * 100, 2) : 0;

    echo "✓ Cleanup completed successfully\n";
    echo "  Records deleted: " . number_format($deleted) . " ({$percentDeleted}%)\n";
    echo "  Space saved: ~" . formatBytes($spaceSaved) . "\n";
    echo "  Completed: " . date('Y-m-d H:i:s') . "\n";

    if ($verbose) {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "VERBOSE INFO\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Retention Days: {$retentionDays}\n";
        echo "Cutoff Date: " . date('Y-m-d H:i:s', time() - ($retentionDays * 86400)) . "\n";
        echo "Current Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Dry Run: " . ($dryRun ? 'Yes' : 'No') . "\n";
        echo "Force: " . ($force ? 'Yes' : 'No') . "\n";
    }

    echo "\n";
    exit(0);

} catch (Exception $e) {
    echo "\n✗ Error during cleanup: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

/**
 * Format bytes to human readable size
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
