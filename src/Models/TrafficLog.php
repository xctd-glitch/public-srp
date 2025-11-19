<?php
declare(strict_types=1);

namespace SRP\Models;

use SRP\Config\Database;

class TrafficLog
{
    public static function create(array $data): void
    {
        $ip = htmlspecialchars(substr((string)($data['ip'] ?? ''), 0, 45), ENT_QUOTES, 'UTF-8');
        $ua = htmlspecialchars(substr((string)($data['ua'] ?? ''), 0, 500), ENT_QUOTES, 'UTF-8');
        $cid = htmlspecialchars(substr((string)($data['cid'] ?? ''), 0, 100), ENT_QUOTES, 'UTF-8');
        $cc = htmlspecialchars(substr((string)($data['cc'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8');
        $lp = htmlspecialchars(substr((string)($data['lp'] ?? ''), 0, 100), ENT_QUOTES, 'UTF-8');
        $decision = in_array($data['decision'] ?? '', ['A', 'B'], true) ? $data['decision'] : '';

        if ($decision === '') {
            throw new \InvalidArgumentException('Invalid decision value');
        }

        $conn = Database::getConnection();
        $sql = <<<SQL
INSERT INTO logs (ts, ip, ua, click_id, country_code, user_lp, decision)
VALUES (UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)
SQL;

        $stmt = $conn->prepare($sql);
        $cidParam = $cid !== '' ? $cid : null;
        $ccParam = $cc !== '' ? $cc : null;
        $lpParam = $lp !== '' ? $lp : null;
        $stmt->bind_param('ssssss', $ip, $ua, $cidParam, $ccParam, $lpParam, $decision);
        $stmt->execute();
        $stmt->close();
    }

    public static function getAll(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        $conn = Database::getConnection();
        $sql = sprintf('SELECT * FROM logs ORDER BY id DESC LIMIT %d', $limit);
        $logs = [];
        $result = $conn->query($sql);
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            $result->free();
        }

        return $logs;
    }

    public static function clearAll(): int
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare('DELETE FROM logs');
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();

        return $count;
    }

    public static function autoCleanup(int $retentionDays = 7): int
    {
        $retentionDays = max(1, min(365, $retentionDays));
        $cutoffTimestamp = time() - ($retentionDays * 86400);

        $conn = Database::getConnection();
        $stmt = $conn->prepare('DELETE FROM logs WHERE ts < ?');
        $stmt->bind_param('i', $cutoffTimestamp);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();

        return $count;
    }

    public static function getStats(): array
    {
        $conn = Database::getConnection();
        $result = $conn->query('SELECT COUNT(*) as total, MIN(ts) as oldest, MAX(ts) as newest FROM logs');
        $row = $result ? $result->fetch_assoc() : null;

        if ($result) {
            $result->free();
        }

        if (!$row || $row['total'] == 0) {
            return [
                'total' => 0,
                'oldest_days' => 0,
                'newest_days' => 0,
                'size_estimate' => 0
            ];
        }

        return [
            'total' => (int)$row['total'],
            'oldest_days' => $row['oldest'] ? (int)((time() - $row['oldest']) / 86400) : 0,
            'newest_days' => $row['newest'] ? (int)((time() - $row['newest']) / 86400) : 0,
            'size_estimate' => (int)$row['total'] * 500
        ];
    }
}
