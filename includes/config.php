<?php
// Force timezone to Africa/Johannesburg so PHP-side date/time calculations
// (OTP expiry, password reset links, log timestamps) match MySQL's NOW() and
// CURRENT_TIMESTAMP. Without this, Afrihost's PHP runs in UTC while MySQL
// runs in SAST, so PHP-calculated expires_at lands 2 hours in the past and
// every OTP is "expired" the moment it's created.
date_default_timezone_set('Africa/Johannesburg');

// Start session with hardened cookie params. Must happen before any output and
// before session_start() — calling these AFTER session_start() is a no-op.
if (session_status() === PHP_SESSION_NONE) {
    // secure cookie flag — only honor HTTPS or X-Forwarded-Proto from a trusted proxy
    $_remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $_trustedProxy = in_array($_remoteAddr, ['127.0.0.1', '::1'], true);
    $_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_trustedProxy && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,           // session cookie, browser-close = invalidate
        'path'     => '/',
        'domain'   => '',          // current host only
        'secure'   => $_isHttps,   // HTTPS-only when serving over HTTPS
        'httponly' => true,        // JS can't read the cookie (XSS hardening)
        'samesite' => 'Lax',       // CSRF hardening; Lax allows top-level GET navigation
    ]);
    ini_set('session.use_strict_mode',  '1'); // reject uninitialized session IDs
    ini_set('session.use_only_cookies', '1'); // don't accept session ID from URL
    session_start();
}

// Authenticated-user idle timeout (30 minutes). Applies once any role is set.
if (!empty($_SESSION['admin_id']) || !empty($_SESSION['user_id']) || !empty($_SESSION['broker_id'])) {
    $idleMax = 30 * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleMax) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Your session expired. Please sign in again.'];
        // Defer the actual redirect to the page that detected it
    }
    $_SESSION['last_activity'] = time();
}

// Derive APP_URL from the request — but ONLY for dev/preview hostnames we explicitly
// trust. Production must set a static APP_URL in env.php so a forged Host header
// can never poison password-reset links, redirects, or asset URLs.
//
// Allowed dev/preview hosts:
//   - localhost / 127.0.0.1 / ::1            (XAMPP)
//   - *.ngrok-free.dev / *.ngrok-free.app / *.ngrok.io  (ngrok preview)
//   - *.trycloudflare.com                    (Cloudflare quick tunnel)
//
// Any other Host header falls through to env.php's static APP_URL, so production
// (e.g. greencash.co.za) only ever uses the canonical domain set in env.
if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $_hostRaw  = $_SERVER['HTTP_HOST'];
    $_hostBare = strtolower(preg_replace('/:\d+$/', '', $_hostRaw));

    $_devHosts        = ['localhost', '127.0.0.1', '::1'];
    $_previewSuffixes = ['.ngrok-free.dev', '.ngrok-free.app', '.ngrok.io', '.trycloudflare.com'];

    $_hostAllowed = in_array($_hostBare, $_devHosts, true);
    if (!$_hostAllowed) {
        foreach ($_previewSuffixes as $_suf) {
            if (str_ends_with($_hostBare, $_suf)) { $_hostAllowed = true; break; }
        }
    }

    if ($_hostAllowed) {
        $_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // Only honor X-Forwarded-Proto when the request reaches us from a trusted
        // local proxy. ngrok's agent runs on this machine and connects to Apache
        // via loopback, so trusting 127.0.0.1 / ::1 here is safe and sufficient.
        $_trustedProxies = ['127.0.0.1', '::1'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', $_trustedProxies, true)) {
            $_scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
        }

        // Base path: works for both /greencash subdirectory and root deploys.
        $_basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if (preg_match('#^(/[^/]+)(/.*)?$#', $_basePath, $m)
            && $m[1] !== '/admin' && $m[1] !== '/includes') {
            $_basePath = $m[1];
        } else {
            $_basePath = '';
        }

        define('APP_URL', $_scheme . '://' . $_hostRaw . $_basePath);
    }
}

// env.php's static APP_URL applies when:
//   - CLI scripts (cron, php -l) — no HTTP_HOST to derive from
//   - Production hostnames not on the allowlist — canonical URL prevents
//     Host header injection from poisoning generated links
require_once __DIR__ . '/env.php';

// Application constants
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'R');
define('MIN_LOAN_AMOUNT', 1000);
define('MAX_LOAN_AMOUNT', 8000);
define('LOAN_TERM_MONTHS', 1);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ABSPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

// PDO Database Connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Service temporarily unavailable.');
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Sanitize user input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency as "R 1,000.00"
 */
function formatCurrency(float $amount): string {
    return CURRENCY . ' ' . number_format($amount, 2);
}

/**
 * Internal redirect only — strips external URLs
 */
function redirect(string $url): void {
    if (preg_match('#^https?://#i', $url)) {
        $parsedApp = parse_url(APP_URL, PHP_URL_HOST);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== $parsedApp) {
            $url = '/';
        }
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Set a flash message (shown once on next page)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate CSRF token and return hidden input
 */
function csrfField(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Verify CSRF token — call at top of every POST handler.
 * Token is session-scoped and rotated after each successful verification.
 */
function verifyCsrf(): void {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
    unset($_SESSION['csrf_token']);
}

/**
 * Require admin — redirect to login if not authenticated
 */
function requireAdmin(): void {
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_role'])) {
        redirect('/login.php');
    }
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

/**
 * Require broker — redirect to broker login if not authenticated
 */
function requireBroker(): void {
    if (empty($_SESSION['broker_id'])) {
        redirect('/broker-portal-login.php');
    }
}

/**
 * Check if current user is broker
 */
function isBroker(): bool {
    return !empty($_SESSION['broker_id']);
}

/**
 * Generate unique application reference number.
 * Format: GC-YYYYMMDD-XXXXXX (6 uppercase hex chars — authoritative format).
 * Wrap callers in a retry loop (max 3) catching PDO error code 23000 on collision.
 */
function generateReference(): string {
    return 'GC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Generate broker code.
 * Format: GCBR + 6 random uppercase hex chars (e.g. GCBRA3F2K1)
 */
function generateBrokerCode(): string {
    return 'GCBR' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Log admin action
 */
function logAdminAction(PDO $pdo, int $adminId, string $action, string $details = ''): void {
    $ip = getClientIp();
    $stmt = $pdo->prepare(
        "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$adminId, $action, $details, $ip]);
}

/**
 * Log broker action
 */
function logBrokerAction(PDO $pdo, ?int $brokerId, string $action, string $details = ''): void {
    $ip = getClientIp();
    $stmt = $pdo->prepare(
        "INSERT INTO broker_activity_log (broker_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$brokerId, $action, $details, $ip]);
}

/**
 * Get client IP (handles Cloudflare + proxy headers)
 */
function getClientIp(): string {
    // Only trust proxy headers when the request actually came through a known proxy.
    // On Afrihost shared hosting (no CDN in front by default), trust nothing but REMOTE_ADDR.
    // If you put Cloudflare in front later, extend $trustedProxyRanges with the CF IPs.
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $trustedProxies = ['127.0.0.1', '::1']; // loopback only (ngrok agent uses this)
    if (in_array($remote, $trustedProxies, true)) {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For can contain a chain — first entry is the original client
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
    }
    return $remote;
}

/**
 * Returns true if identifier is currently rate-limited / locked out, false if OK.
 */
function checkRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): bool {
    $stmt = $pdo->prepare(
        "SELECT attempts, locked_until FROM rate_limits
         WHERE identifier = ? AND attempt_type = ? LIMIT 1"
    );
    $stmt->execute([$identifier, $type]);
    $row = $stmt->fetch();

    if (!$row) return false;

    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        return true; // still locked
    }

    return false;
}

/**
 * Increment attempt counter; lock identifier once maxAttempts is reached.
 * Single atomic upsert — ON DUPLICATE KEY UPDATE.
 * NOTE: MySQL evaluates IF() against the pre-increment value of `attempts`.
 * So `attempts + 1 >= $maxAttempts` correctly locks on the Nth attempt
 * (e.g. old=9, 9+1=10 >= 10 → lock fires on the 10th attempt as intended).
 */
function incrementRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): void {
    $pdo->prepare(
        "INSERT INTO rate_limits (identifier, attempt_type, attempts, last_attempt_at)
         VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
            attempts        = attempts + 1,
            last_attempt_at = NOW(),
            locked_until    = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)"
    )->execute([$identifier, $type, $maxAttempts, $lockMinutes]);
}

/**
 * Reset rate limit counter and lock on successful login.
 */
function resetRateLimit(PDO $pdo, string $identifier, string $type): void {
    $pdo->prepare(
        "UPDATE rate_limits SET attempts = 0, locked_until = NULL
         WHERE identifier = ? AND attempt_type = ?"
    )->execute([$identifier, $type]);
}

/**
 * Generate a cryptographically random 6-digit OTP string (zero-padded).
 */
function generateOTP(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send application confirmation email (stub — replaced by PHPMailer in Step 7).
 */
function sendApplicationConfirmation(array $data): void {
    error_log(sprintf(
        'GREENCASH DEV CONFIRMATION: ref=%s name=%s email=%s amount=%.2f',
        $data['reference_number'],
        $data['first_name'],
        $data['email'],
        $data['loan_amount']
    ));
}

/**
 * Email a 6-digit OTP code to a user as part of the login flow.
 * Returns true on send, false on failure. Always also writes the OTP to error_log
 * so a developer can recover it during testing if mail is broken.
 */
function sendOtpEmail(string $recipient, string $otp, int $minutesValid = 10): bool {
    // SECURITY: never log OTPs in production. Anyone with read access to the PHP error
    // log (other shared-hosting tenants, sysadmins, log-aggregators) could harvest 2FA
    // codes in real time, defeating the whole second-factor. Gate behind APP_ENV='local'
    // so the convenience exists ONLY when developers are running locally on XAMPP.
    if (defined('APP_ENV') && APP_ENV === 'local') {
        error_log("GREENCASH DEV OTP for {$recipient}: {$otp}");
    }

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        error_log('sendOtpEmail aborted: invalid recipient ' . $recipient);
        return false;
    }

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $scrub = fn($v) => preg_replace('/[\r\n]+/', ' ', (string) $v);
    $appName = defined('APP_NAME') ? APP_NAME : 'GreenCash';
    $appUrl  = defined('APP_URL') ? APP_URL : '';
    $codeHtml = $h($otp);

    // SECURITY: keep the OTP OUT of the Subject line. Subject is visible in inbox
    // previews, lock-screen notifications, mail-server logs, and bounce reports —
    // any of which can leak the code to people other than the intended recipient.
    // The code lives in the HTML body only.
    $subject = $scrub("Your {$appName} sign-in code");

    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f6f8f4;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0c1410">
<div style="max-width:520px;margin:0 auto;padding:24px">
  <div style="background:#121C14;color:#fff;padding:28px 24px;border-radius:14px 14px 0 0;text-align:center">
    <div style="color:#f4c020;font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px">' . $h($appName) . ' &middot; Sign-in code</div>
    <h1 style="margin:0;font-size:18px;font-weight:600;color:#cfe0d3">Use this code to finish signing in</h1>
  </div>

  <div style="background:#fff;padding:32px 24px;border-radius:0 0 14px 14px;border:1px solid #e2e8de;border-top:0;text-align:center">
    <div style="display:inline-block;background:#f6f8f4;border:2px dashed #1aa636;border-radius:14px;padding:18px 28px;margin:6px 0 14px">
      <span style="font-family:Consolas,Menlo,monospace;font-size:38px;font-weight:700;color:#0f7a26;letter-spacing:.18em">' . $codeHtml . '</span>
    </div>
    <p style="margin:14px 0 0;color:#5e6b62;font-size:14px;line-height:1.5">
      This code expires in <strong>' . (int) $minutesValid . ' minutes</strong>.<br>
      It can only be used once.
    </p>
    <div style="margin-top:26px;padding:14px 18px;background:#fffbe6;border-left:3px solid #f4c020;border-radius:0 10px 10px 0;text-align:left;font-size:13px;color:#1a241d">
      <strong>If you did not request this code:</strong> someone may have your password. Sign in and change it immediately, then email <a href="mailto:info@greencash.co.za" style="color:#0f7a26">info@greencash.co.za</a>.
    </div>
  </div>

  <p style="text-align:center;margin:18px 0 0;color:#999;font-size:11px">Sent automatically by ' . $h($appName) . ' &middot; ' . $h($appUrl) . '</p>
</div>
</body></html>';

    $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@greencash.co.za';
    $fromName = $scrub(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $appName);

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: GreenCash/1.0';
    $headers[] = 'X-Priority: 1'; // Mark as high so it doesn't sit in inbox
    $headers[] = 'X-Auto-Response-Suppress: All';

    // RFC 2047-encode the subject so any non-ASCII (em dash, accents, emoji) survives
    // SMTP transfer without becoming mojibake like "â€"".
    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');

    $sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));

    if (!$sent) {
        // Log the delivery failure but NEVER the OTP value itself — see APP_ENV check above.
        error_log("sendOtpEmail FAILED for {$recipient} — mail() returned false. Check SMTP config.");
    }
    return $sent;
}

/**
 * Email the full application details to the loans inbox (LOANS_EMAIL) so the team can
 * triage applications by email until the admin portal is built.
 *
 * Uses PHP's mail() — works on Afrihost shared hosting via local sendmail without
 * any vendor dependencies. Returns true on send, false on failure (also logged).
 */
function sendApplicationToLoans(array $data, array $docs = []): bool {
    if (!defined('LOANS_EMAIL') || LOANS_EMAIL === '') {
        error_log('sendApplicationToLoans skipped: LOANS_EMAIL not configured');
        return false;
    }

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $fmtR = fn($v) => 'R ' . number_format((float) $v, 2, '.', ' ');

    $statusLabels = [
        'employed' => 'Permanent / full-time',
        'contract' => 'Contract',
        'self_employed' => 'Self-employed',
    ];
    $durationLabels = [
        'less_3m' => 'Less than 3 months',
        '3_6m'    => '3 – 6 months',
        '6_12m'   => '6 – 12 months',
        '1_2y'    => '1 – 2 years',
        '2_5y'    => '2 – 5 years',
        '5y_plus' => '5+ years',
    ];
    $empStatus  = $statusLabels[$data['employment_status'] ?? ''] ?? ($data['employment_status'] ?? '—');
    $empTenure  = $durationLabels[$data['employment_duration'] ?? ''] ?? ($data['employment_duration'] ?? '—');

    $totalExpenses = (float) ($data['rent'] ?? 0) + (float) ($data['food'] ?? 0)
                   + (float) ($data['transport'] ?? 0) + (float) ($data['other_expenses'] ?? 0);
    $disposable    = (float) ($data['salary_amount'] ?? 0) - $totalExpenses;

    $docList = '';
    if (!empty($docs)) {
        $docList = '<ul style="margin:0;padding-left:20px">';
        foreach ($docs as $d) {
            $docList .= '<li>' . $h(ucwords(str_replace('_', ' ', $d['type'] ?? ''))) . ': <code>' . $h($d['name'] ?? '') . '</code></li>';
        }
        $docList .= '</ul>';
    } else {
        $docList = '<p style="color:#888;font-style:italic">No documents attached.</p>';
    }

    $appUrl    = defined('APP_URL') ? APP_URL : '';
    $appName   = defined('APP_NAME') ? APP_NAME : 'GreenCash';
    $reference = $h($data['reference_number'] ?? '—');
    // Scrub CR/LF from any user-controlled value that lands in a header or the
    // subject — prevents email header injection (Bcc/Cc/etc.).
    $scrub = fn($v) => preg_replace('/[\r\n]+/', ' ', (string) $v);
    $subject   = sprintf(
        '[%s] New loan application — %s %s — %s',
        $scrub($data['reference_number'] ?? '—'),
        $scrub($data['first_name'] ?? ''),
        $scrub($data['last_name'] ?? ''),
        $fmtR($data['loan_amount'] ?? 0)
    );

    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f6f8f4;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0c1410">
<div style="max-width:680px;margin:0 auto;padding:24px">
  <div style="background:#0c1410;color:#fff;padding:24px;border-radius:14px 14px 0 0">
    <div style="color:#f4c020;font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px">New loan application</div>
    <h1 style="margin:0;font-size:22px;font-weight:700">' . $reference . '</h1>
    <p style="margin:6px 0 0;color:#cfe0d3;font-size:14px">' . $h(date('l, j F Y \a\t H:i')) . '</p>
  </div>

  <div style="background:#fff;padding:24px;border-radius:0 0 14px 14px;border:1px solid #e2e8de;border-top:0">

    <table style="width:100%;border-collapse:collapse;margin-bottom:18px">
      <tr>
        <td style="padding:8px 0;color:#5e6b62;font-size:13px;width:40%">Loan amount</td>
        <td style="padding:8px 0;font-weight:700;font-size:17px;color:#1aa636">' . $fmtR($data['loan_amount'] ?? 0) . '</td>
      </tr>
      <tr>
        <td style="padding:8px 0;color:#5e6b62;font-size:13px">Repayment due</td>
        <td style="padding:8px 0">' . $h(date('j F Y', strtotime($data['repayment_date'] ?? $data['next_payday_date'] ?? 'now'))) . '</td>
      </tr>
    </table>

    <h2 style="margin:18px 0 8px;font-size:15px;color:#0f7a26;padding-bottom:6px;border-bottom:2px solid #e2e8de">Applicant</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <tr><td style="padding:4px 0;color:#5e6b62;width:40%">Name</td><td><strong>' . $h(trim(($data['first_name'] ?? '') . ' ' . ($data['other_names'] ?? '') . ' ' . ($data['last_name'] ?? ''))) . '</strong></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">SA ID number</td><td>' . $h($data['id_number'] ?? '') . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Email</td><td><a href="mailto:' . $h($data['email'] ?? '') . '" style="color:#0f7a26">' . $h($data['email'] ?? '') . '</a></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Mobile</td><td><a href="tel:' . $h(preg_replace('/\s+/', '', $data['phone'] ?? '')) . '" style="color:#0f7a26">' . $h($data['phone'] ?? '') . '</a></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Address</td><td>' . $h(trim(($data['address'] ?? '') . ', ' . ($data['city'] ?? '') . ', ' . ($data['province'] ?? '') . ' ' . ($data['zip_code'] ?? ''), ', ')) . '</td></tr>
    </table>

    <h2 style="margin:22px 0 8px;font-size:15px;color:#0f7a26;padding-bottom:6px;border-bottom:2px solid #e2e8de">Employment</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <tr><td style="padding:4px 0;color:#5e6b62;width:40%">Status</td><td>' . $h($empStatus) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Employer</td><td><strong>' . $h($data['employer_name'] ?? '') . '</strong></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Job title</td><td>' . $h($data['job_title'] ?? '') . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Tenure</td><td>' . $h($empTenure) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Employer contact</td><td>' . $h($data['employer_contact'] ?? '') . '</td></tr>
    </table>

    <h2 style="margin:22px 0 8px;font-size:15px;color:#0f7a26;padding-bottom:6px;border-bottom:2px solid #e2e8de">Affordability</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <tr><td style="padding:4px 0;color:#5e6b62;width:40%">Net monthly salary</td><td><strong>' . $fmtR($data['salary_amount'] ?? 0) . '</strong></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Next payday</td><td>' . $h(date('j F Y', strtotime($data['next_payday_date'] ?? 'now'))) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Rent</td><td>' . $fmtR($data['rent'] ?? 0) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Food</td><td>' . $fmtR($data['food'] ?? 0) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Transport</td><td>' . $fmtR($data['transport'] ?? 0) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62">Other expenses</td><td>' . $fmtR($data['other_expenses'] ?? 0) . '</td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62;border-top:1px solid #e2e8de;padding-top:8px"><strong>Total expenses</strong></td><td style="padding-top:8px;border-top:1px solid #e2e8de"><strong>' . $fmtR($totalExpenses) . '</strong></td></tr>
      <tr><td style="padding:4px 0;color:#5e6b62"><strong>Disposable income</strong></td><td><strong style="color:' . ($disposable >= (float) ($data['loan_amount'] ?? 0) * 1.15 ? '#1aa636' : '#d33') . '">' . $fmtR($disposable) . '</strong></td></tr>
    </table>

    <h2 style="margin:22px 0 8px;font-size:15px;color:#0f7a26;padding-bottom:6px;border-bottom:2px solid #e2e8de">Documents uploaded</h2>
    ' . $docList . '

    <div style="margin-top:24px;padding:14px 18px;background:#f6f8f4;border-radius:10px;font-size:13px;color:#5e6b62">
      <strong style="color:#0c1410">Next steps:</strong> review the documents in the admin portal (when available) or request copies directly from the applicant. Stored in the database with application ID <code>' . $h($data['application_id'] ?? '?') . '</code>.
    </div>

  </div>

  <p style="text-align:center;margin:18px 0 0;color:#999;font-size:11px">Sent automatically by ' . $h($appName) . ' &middot; ' . $h($appUrl) . '</p>
</div>
</body></html>';

    $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@greencash.co.za';
    $fromName = $scrub(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $appName);
    // Reply-To: only accept if it passes filter_var AND has no CR/LF (defence in depth)
    $replyToRaw = $scrub($data['email'] ?? $from);
    $replyTo = filter_var($replyToRaw, FILTER_VALIDATE_EMAIL) ? $replyToRaw : $from;

    // Build multipart MIME so we can attach the applicant's docs (ID, payslip, bank
    // statements). Each file capped at 8 MB; total email capped at 20 MB to stay under
    // most mail-server limits.
    $boundary = 'gc_' . bin2hex(random_bytes(16));
    $maxFileBytes  = 8 * 1024 * 1024;
    $maxTotalBytes = 20 * 1024 * 1024;

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'X-Mailer: GreenCash/1.0';
    $headers[] = 'X-Priority: 3';

    // Part 1 — the HTML body
    $eol = "\r\n";
    $mailBody  = '--' . $boundary . $eol;
    $mailBody .= 'Content-Type: text/html; charset=UTF-8' . $eol;
    $mailBody .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
    $mailBody .= $body . $eol . $eol;

    // Parts 2..N — each uploaded document as base64 attachment. Resolve each $doc['path']
    // (stored as 'uploads/filename.ext') to an absolute path inside UPLOAD_DIR; reject
    // anything that path-traverses or sits outside the uploads directory.
    $uploadDirReal = realpath(UPLOAD_DIR);
    // Defence-in-depth: a trailing separator on the prefix prevents the
    // "/home/x/uploads" prefix matching "/home/x/uploads-evil/..." paths.
    $uploadPrefix  = rtrim($uploadDirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $allowedExts   = ['pdf', 'jpg', 'jpeg', 'png'];
    $totalAttachedBytes = 0;
    $attachmentNotes = [];

    foreach ($docs as $d) {
        $relPath = $d['path'] ?? '';
        if ($relPath === '') continue;

        // Reject path traversal + absolute paths BEFORE concatenating with ABSPATH.
        if (strpos($relPath, '..') !== false
            || str_starts_with($relPath, '/')
            || str_starts_with($relPath, '\\')
            || preg_match('#^[a-zA-Z]:[/\\\\]#', $relPath)) {
            $attachmentNotes[] = 'Suspicious path rejected: ' . $relPath;
            continue;
        }

        // Extension allowlist — same set the uploader enforces.
        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $attachmentNotes[] = 'Non-allowlisted extension: ' . $relPath;
            continue;
        }

        $absPath = realpath(ABSPATH . $relPath);
        if (!$absPath
            || !is_readable($absPath)
            || strncmp($absPath, $uploadPrefix, strlen($uploadPrefix)) !== 0) {
            $attachmentNotes[] = 'Could not attach: ' . ($d['name'] ?? $relPath);
            continue;
        }
        $size = filesize($absPath);
        if ($size === false || $size > $maxFileBytes) {
            $attachmentNotes[] = 'Skipped (too large): ' . ($d['name'] ?? basename($absPath));
            continue;
        }
        if ($totalAttachedBytes + $size > $maxTotalBytes) {
            $attachmentNotes[] = 'Skipped (email size cap reached): ' . ($d['name'] ?? basename($absPath));
            continue;
        }
        $fileBytes = file_get_contents($absPath);
        if ($fileBytes === false) continue;
        $totalAttachedBytes += $size;

        // MIME type — finfo if available, else a sensible fallback by extension
        $mimeType = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $absPath);
            finfo_close($finfo);
            if ($detected) $mimeType = $detected;
        } else {
            $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
            $mimeType = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? $mimeType;
        }

        // Use the original filename (sanitized — strip CR/LF/quotes) so the recipient sees
        // "ID_BobSmith.pdf" rather than the safeName "id_document_679...pdf".
        $displayName = preg_replace('/[\r\n"\\\\]/', '_', $d['name'] ?? basename($absPath));

        $mailBody .= '--' . $boundary . $eol;
        $mailBody .= 'Content-Type: ' . $mimeType . '; name="' . $displayName . '"' . $eol;
        $mailBody .= 'Content-Transfer-Encoding: base64' . $eol;
        $mailBody .= 'Content-Disposition: attachment; filename="' . $displayName . '"' . $eol . $eol;
        $mailBody .= chunk_split(base64_encode($fileBytes)) . $eol;
    }

    $mailBody .= '--' . $boundary . '--' . $eol;

    // RFC 2047-encode the subject so the em-dash separator survives SMTP transfer.
    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');

    $sent = @mail(LOANS_EMAIL, $subject, $mailBody, implode($eol, $headers));

    if (!$sent) {
        error_log(sprintf(
            'sendApplicationToLoans FAILED ref=%s — mail() returned false. SMTP likely not configured (XAMPP local) or rejected by server.',
            $data['reference_number'] ?? '?'
        ));
    }
    if (!empty($attachmentNotes)) {
        error_log('sendApplicationToLoans attachment notes for ref=' . ($data['reference_number'] ?? '?') . ': ' . implode('; ', $attachmentNotes));
    }

    return $sent;
}

/**
 * Send a partnership enquiry email to PARTNERSHIP_EMAIL (info@greencash.co.za).
 * Triggered by the "Become a partner" modal on index.php.
 */
function sendPartnershipEnquiry(array $data): bool {
    if (!defined('PARTNERSHIP_EMAIL') || PARTNERSHIP_EMAIL === '') {
        error_log('sendPartnershipEnquiry skipped: PARTNERSHIP_EMAIL not configured');
        return false;
    }

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    // Scrub CR/LF from any user-controlled value placed in a header or the
    // subject — prevents email header injection.
    $scrub = fn($v) => preg_replace('/[\r\n]+/', ' ', (string) $v);
    $appName = defined('APP_NAME') ? APP_NAME : 'GreenCash';
    $appUrl  = defined('APP_URL') ? APP_URL : '';

    // Use the company name if provided, otherwise fall back to the contact person's name so
    // the subject line is still meaningful (and you can scan the inbox).
    $subjectName = trim($scrub($data['company'] ?? '')) !== ''
        ? trim($scrub($data['company']))
        : trim($scrub($data['contact_name'] ?? 'New enquiry'));
    $subject = '[Partnership enquiry] ' . $subjectName;
    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f6f8f4;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0c1410">
<div style="max-width:600px;margin:0 auto;padding:24px">
  <div style="background:linear-gradient(135deg,#0a5a1c,#0f7a26);color:#fff;padding:24px;border-radius:14px 14px 0 0">
    <div style="color:#f4c020;font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px">New partnership enquiry</div>
    <h1 style="margin:0;font-size:20px;font-weight:700">' . $h($data['company'] ?? '') . '</h1>
    <p style="margin:6px 0 0;color:#cfe0d3;font-size:14px">' . $h(date('l, j F Y \a\t H:i')) . '</p>
  </div>

  <div style="background:#fff;padding:24px;border-radius:0 0 14px 14px;border:1px solid #e2e8de;border-top:0">
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <tr><td style="padding:6px 0;color:#5e6b62;width:38%">Company</td><td><strong>' . $h($data['company'] ?? '') . '</strong></td></tr>
      <tr><td style="padding:6px 0;color:#5e6b62">Contact person</td><td>' . $h($data['contact_name'] ?? '') . '</td></tr>
      <tr><td style="padding:6px 0;color:#5e6b62">Email</td><td><a href="mailto:' . $h($data['email'] ?? '') . '" style="color:#0f7a26">' . $h($data['email'] ?? '') . '</a></td></tr>
      <tr><td style="padding:6px 0;color:#5e6b62">Phone</td><td><a href="tel:' . $h(preg_replace('/\s+/', '', $data['phone'] ?? '')) . '" style="color:#0f7a26">' . $h($data['phone'] ?? '') . '</a></td></tr>
      ' . (!empty($data['employees']) ? '<tr><td style="padding:6px 0;color:#5e6b62">Employees</td><td>' . $h($data['employees']) . '</td></tr>' : '') . '
    </table>

    ' . (!empty($data['message']) ? '
    <h2 style="margin:22px 0 8px;font-size:15px;color:#0f7a26;padding-bottom:6px;border-bottom:2px solid #e2e8de">Message</h2>
    <p style="margin:0;line-height:1.6;color:#1a241d;background:#f6f8f4;padding:14px 18px;border-radius:10px;border-left:3px solid #f4c020;font-style:italic">' . nl2br($h($data['message'])) . '</p>
    ' : '') . '

    <div style="margin-top:24px;padding:14px 18px;background:#f6f8f4;border-radius:10px;font-size:13px;color:#5e6b62">
      Reply directly to this email to reach <strong>' . $h($data['contact_name'] ?? '') . '</strong>.
    </div>
  </div>

  <p style="text-align:center;margin:18px 0 0;color:#999;font-size:11px">Sent automatically by ' . $h($appName) . ' &middot; ' . $h($appUrl) . '</p>
</div>
</body></html>';

    $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@greencash.co.za';
    $fromName = $scrub(defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : $appName);
    $replyToRaw = $scrub($data['email'] ?? $from);
    $replyTo = filter_var($replyToRaw, FILTER_VALIDATE_EMAIL) ? $replyToRaw : $from;

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'X-Mailer: GreenCash/1.0';

    // RFC 2047-encode the subject so non-ASCII characters survive SMTP transfer.
    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');

    $sent = @mail(PARTNERSHIP_EMAIL, $subject, $body, implode("\r\n", $headers));

    if (!$sent) {
        error_log('sendPartnershipEnquiry FAILED for company=' . ($data['company'] ?? '?'));
    }

    return $sent;
}
