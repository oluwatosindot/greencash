<?php
// Run every 30 minutes via cPanel cron:
// php /home/<user>/public_html/cron/cleanup.php

require_once __DIR__ . '/../includes/config.php';

// Delete expired OTPs — used=1 records kept until natural expiry for audit trail
$pdo->exec("DELETE FROM otp_verifications WHERE expires_at < NOW()");

// Delete old unlocked rate limit records (older than 2 hours)
$pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NULL AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");

// Delete expired rate limit locks
$pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NOT NULL AND locked_until < NOW()");

// Nullify expired password reset tokens
$pdo->exec("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW()");

echo '[' . date('Y-m-d H:i:s') . '] Cleanup complete.' . PHP_EOL;
