<?php
/**
 * Environment template — copy to env.php on the server, then fill in real values.
 * env.php is gitignored. Never commit secrets.
 *
 * For Afrihost shared hosting, see DEPLOY.md for where to find each value
 * inside cPanel (DB credentials, SMTP host, mailbox passwords, etc.).
 */

// === Database (cPanel → MySQL Databases) ===
// Afrihost prefixes DB and user names with your cPanel username, e.g. "youracct_greencash".
define('DB_HOST', 'localhost');                       // Always localhost on cPanel shared hosting
define('DB_NAME', 'youracct_greencash');              // Replace "youracct" with your cPanel prefix
define('DB_USER', 'youracct_gc_app');                 // The MySQL user you created in cPanel
define('DB_PASS', 'STRONG_PASSWORD_FROM_CPANEL');     // Set when creating the user

// === Application ===
define('APP_URL', 'https://greencash.co.za');         // No trailing slash. https only after SSL is active.
define('APP_NAME', 'GreenCash');

// === Mail (SMTP) ===
// Afrihost provides SMTP at mail.greencash.co.za once the mailbox is created
// in cPanel → Email Accounts. Use TLS on port 587 (or SSL on 465).
define('MAIL_HOST', 'mail.greencash.co.za');
define('MAIL_PORT', 587);
define('MAIL_USER', 'noreply@greencash.co.za');
define('MAIL_PASS', 'MAILBOX_PASSWORD_FROM_CPANEL');
define('MAIL_FROM_NAME', 'GreenCash');
define('MAIL_FROM_EMAIL', 'noreply@greencash.co.za');

// === Notification + business email addresses ===
define('ADMIN_EMAIL',       'admin@greencash.co.za');   // New-application notifications go here
define('PARTNERSHIP_EMAIL', 'partners@greencash.co.za'); // Employers band CTA → mailto:

// === WhatsApp click-to-chat (floating button) ===
// International format, NO leading + or spaces. South Africa: 27 + dropped leading 0.
define('WHATSAPP_NUMBER',          '27785177961');
define('WHATSAPP_PREFILL_MESSAGE', "Hi GreenCash — I'd like to know more about a salary advance.");

// === Optional: NCR registration ===
// Uncomment AFTER you receive the NCR registration number. While commented out,
// the footer's "Responsible lending" disclosure block is hidden — no fake claims shown.
// define('NCR_NUMBER', 'NCRCPXXXX');

// === Environment marker ===
// 'production' on Afrihost, 'local' on XAMPP. Some code paths log more verbosely in 'local'.
define('APP_ENV', 'production');
