<?php
// Login throttling shared across PHP sessions and clients, backed by MySQL.
// Import database/login_rate_limits.sql before deploying this change.
function locoAuthRateKeys(string $identifier): array {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return [
        'ip' => hash('sha256', 'ip:' . $ip),
        'account' => hash('sha256', 'account:' . strtolower(trim($identifier))),
    ];
}
function locoAuthRateLimited(PDO $pdo, string $identifier): bool {
    $keys = locoAuthRateKeys($identifier);
    $q = $pdo->prepare(
        'SELECT key_hash, failures, (window_started_at >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE) AS active_window
         FROM login_rate_limits WHERE key_hash IN (:ip_key, :account_key)'
    );
    $q->execute([':ip_key' => $keys['ip'], ':account_key' => $keys['account']]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!(int)$row['active_window']) continue;
        $threshold = hash_equals($row['key_hash'], $keys['ip']) ? 40 : 8;
        if ((int)$row['failures'] >= $threshold) return true;
    }
    return false;
}
function locoAuthRecordFailure(PDO $pdo, string $identifier): void {
    $q = $pdo->prepare(
        'INSERT INTO login_rate_limits (key_hash, failures, window_started_at)
         VALUES (:key_hash, 1, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
           failures = IF(window_started_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,
                         1, LEAST(failures + 1, 65535)),
           window_started_at = IF(window_started_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,
                                  UTC_TIMESTAMP(), window_started_at)'
    );
    foreach (locoAuthRateKeys($identifier) as $key) {
        $q->execute([':key_hash' => $key]);
    }
}
function locoAuthClearAccountFailures(PDO $pdo, string $identifier): void {
    $keys = locoAuthRateKeys($identifier);
    $q = $pdo->prepare('DELETE FROM login_rate_limits WHERE key_hash = :key_hash');
    $q->execute([':key_hash' => $keys['account']]);
}
