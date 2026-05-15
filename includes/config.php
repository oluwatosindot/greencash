<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment
require_once __DIR__ . '/env.php';

// Application constants
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'R');
define('MIN_LOAN_AMOUNT', 1000);
define('MAX_LOAN_AMOUNT', 10000);
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
    // Block open redirects
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
 * Design note: Green Cash uses a session-scoped CSRF token (one token per session, not per request).
 * This is intentional for simplicity and matches the CocoFinance reference pattern.
 * The token is regenerated on login (session_regenerate_id), which is the primary protection.
 * After verifyCsrf() succeeds, rotate the token for state-changing operations:
 *   unset($_SESSION['csrf_token']); // forces csrfField() to generate a new one next render
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
    // Rotate token after successful verification
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
 * Generate unique application reference number
 * Format: GC-YYYYMMDD-XXXXXX (e.g. GC-20260508-A3F2K1)
 *
 * Collision note: 6 hex chars = ~16M combinations per day. Sufficient for low-to-medium volume.
 * The `reference_number` column has a UNIQUE KEY, so a collision causes a PDO exception.
 * Wrap inserts using this in a retry loop:
 *
 *   $inserted = false;
 *   for ($i = 0; $i < 3; $i++) {
 *       try {
 *           $ref = generateReference();
 *           $stmt->execute([$ref, ...]); // insert with reference_number
 *           $inserted = true;
 *           break;
 *       } catch (PDOException $e) {
 *           if ($e->getCode() !== '23000') throw $e; // only retry on duplicate key
 *       }
 *   }
 *   if (!$inserted) throw new RuntimeException('Could not generate unique reference.');
 */
function generateReference(): string {
    return 'GC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Generate broker code (no dashes)
 * Format: GCBR + 6 random chars (e.g. GCBRA3F2K1)
 */
function generateBrokerCode(): string {
    return 'GCBR' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Log admin action
 */
function logAdminAction(PDO $pdo, int $adminId, string $action, string $details = ''): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare(
        "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$adminId, $action, $details, $ip]);
}

/**
 * Log broker action
 */
function logBrokerAction(PDO $pdo, ?int $brokerId, string $action, string $details = ''): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare(
        "INSERT INTO broker_activity_log (broker_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$brokerId, $action, $details, $ip]);
}

/**
 * Get client IP (handles proxies)
 */
function getClientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim(explode(',', $_SERVER[$key])[0]);
        }
    }
    return 'unknown';
}