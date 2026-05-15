<?php
// Cron cleanup task — runs every 30 minutes via cPanel cron:
// php /home/<user>/public_html/cron/cleanup.php

require_once __DIR__ . '/../includes/config.php';

try {
    // Delete expired OTPs only — used=1 records are kept as audit trail until they expire naturally
    // DO NOT delete on used=1 alone — removes audit trail and causes race conditions on slow servers
    $pdo->exec("DELETE FROM otp_verifications WHERE expires_at < NOW()");

    // Delete old rate limit records (older than 2 hours, unlocked)
    $pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NULL AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
    $pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NOT NULL AND locked_until < NOW()");

    // Delete expired password reset tokens (if stored in users table)
    $pdo->exec("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW()");

    echo '[' . date('Y-m-d H:i:s') . '] Cleanup complete.' . PHP_EOL;
} catch (PDOException $e) {
    error_log('Cleanup failed: ' . $e->getMessage());
    echo '[' . date('Y-m-d H:i:s') . '] Cleanup failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
