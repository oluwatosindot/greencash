# Pattern: Security Hardening

## .htaccess (root)

```apache
# Disable directory listing
Options -Indexes

# Follow symlinks
Options +FollowSymLinks

# PHP error display (production: off)
php_flag display_errors off
php_flag log_errors on

# URL rewriting
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Custom error pages
ErrorDocument 404 /404.php
ErrorDocument 500 /500.php

# Block access to sensitive files
<FilesMatch "\.(sql|log|md|json|lock|yml|env|gitignore)$">
    Require all denied
</FilesMatch>

# Block access to directories
<FilesMatch "^(includes|config|uploads|vendor|cron|database)">
    Require all denied
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self';"
</IfModule>

# Protect uploads directory — no PHP execution
<Directory "/uploads">
    php_flag engine off
    Options -ExecCGI
    AddHandler cgi-script .php .php3 .php4 .php5 .phtml .pl .py .jsp .asp .htm .shtml .sh .cgi
    <FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|shtml|sh|cgi)$">
        Require all denied
    </FilesMatch>
</Directory>
```

---

## File Upload Security (use on every upload)

```php
function validateAndMoveUpload(array $file, string $uploadDir, string $fieldName): ?array {
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'application/pdf' => 'pdf',
    ];
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // MIME type check (not just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimes[$mime])) {
        return null; // invalid MIME
    }

    $ext = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return null; // invalid extension
    }

    // Generate safe filename
    $safeName   = $fieldName . '_' . uniqid('', true) . '.' . $ext;
    $targetPath = rtrim($uploadDir, '/') . '/' . $safeName;

    // Path traversal protection
    $realUploadDir = realpath($uploadDir);
    $realTarget    = $realUploadDir . DIRECTORY_SEPARATOR . $safeName;
    if (strpos($realTarget, $realUploadDir) !== 0) {
        return null; // path traversal attempt
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return [
        'name' => basename($file['name']),
        'path' => 'uploads/' . $safeName,
        'mime' => $mime,
    ];
}
```

---

## Secure File Serving (serve-document.php)

```php
<?php
require_once 'includes/config.php';
requireAdmin(); // or requireBroker() depending on context

$fileParam = $_GET['file'] ?? '';
if (empty($fileParam)) {
    http_response_code(404);
    exit('File not found.');
}

$uploadDir = realpath(UPLOAD_DIR);
$filePath  = realpath($uploadDir . '/' . basename($fileParam));

// Path traversal guard
if ($filePath === false || strpos($filePath, $uploadDir) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found.');
}

// Verify file belongs to an application the user can access
// (add DB lookup here if needed)

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit();
```

---

## CSRF on AJAX endpoints

```javascript
// Get CSRF token from meta tag in admin-header.php
// Add to admin-header.php: <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// Use in fetch calls:
fetch('/admin/ajax/update-status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        csrf_token: getCsrfToken(),
        application_id: appId,
        status: newStatus,
        notes: notes,
    })
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        // handle success
    }
});
```

---

## cron/cleanup.php

```php
<?php
// Run every 30 minutes via cPanel cron:
// php /home/<user>/public_html/cron/cleanup.php

require_once __DIR__ . '/../includes/config.php';

// Delete expired OTPs only — used=1 records are kept as audit trail until they expire naturally
// DO NOT delete on used=1 alone — removes audit trail and causes race conditions on slow servers
$pdo->exec("DELETE FROM otp_verifications WHERE expires_at < NOW()");

// Delete old rate limit records (older than 2 hours, unlocked)
$pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NULL AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
$pdo->exec("DELETE FROM rate_limits WHERE locked_until IS NOT NULL AND locked_until < NOW()");

// Delete expired password reset tokens (if stored in users table)
$pdo->exec("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW()");

echo '[' . date('Y-m-d H:i:s') . '] Cleanup complete.' . PHP_EOL;
```

---

## .gitignore (place in project root)

```gitignore
# Never commit secrets or generated files
includes/env.php
vendor/
node_modules/

# Uploaded files (keep directory, not contents)
uploads/*
!uploads/.gitkeep

# Logs
*.log
error_log

# OS files
.DS_Store
Thumbs.db
```

---

## Security checklist before going live

- [ ] `includes/env.php` is in `.gitignore`
- [ ] All forms have `<?= csrfField() ?>` and handler calls `verifyCsrf()`
- [ ] All AJAX endpoints call `verifyCsrf()`
- [ ] All admin pages call `requireAdmin()` at top
- [ ] All broker pages call `requireBroker()` at top
- [ ] All DB queries use PDO prepared statements (no string interpolation in SQL)
- [ ] All output uses `htmlspecialchars()` (or the `sanitize()` helper)
- [ ] File uploads validated with MIME check + extension check + realpath check
- [ ] `uploads/` directory has `.htaccess` blocking PHP execution
- [ ] `uploads/` permissions set to 0750
- [ ] `session_regenerate_id(true)` called on every login
- [ ] Rate limiting active for admin login and broker login
- [ ] Security headers set in `.htaccess`
- [ ] HTTPS enforced via `.htaccess` redirect
- [ ] `redirect()` function used for all redirects (strips external URLs)
- [ ] Cron job set up for cleanup.php
