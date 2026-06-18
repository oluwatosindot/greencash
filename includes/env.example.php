<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'greencash');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Application
define('APP_URL', 'https://www.greencash.co.za');
define('APP_NAME', 'Green Cash');

// Mail (SMTP)
define('MAIL_HOST', 'smtp.yourdomain.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'noreply@greencash.co.za');
define('MAIL_PASS', 'your_mail_password');
define('MAIL_FROM_NAME', 'Green Cash');
define('MAIL_FROM_EMAIL', 'noreply@greencash.co.za');

// Admin notification email
define('ADMIN_EMAIL', 'admin@greencash.co.za');

// Partnership enquiries (Employers band CTA on index.php)
define('PARTNERSHIP_EMAIL', 'partners@greencash.co.za');

// WhatsApp click-to-chat (floating button on every customer page)
// International format, NO leading + or spaces (e.g. SA mobile: '27821234567').
define('WHATSAPP_NUMBER', '27785177961');
define('WHATSAPP_PREFILL_MESSAGE', "Hi GreenCash — I'd like to know more about a salary advance.");
