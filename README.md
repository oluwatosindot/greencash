# GreenCash

**Salary advance loans for salaried South Africans.** Apply online, 100% digital, available 24 hours.

🌐 **Production:** https://greencash.co.za

---

## Stack

| Layer | Tech |
|---|---|
| Server | PHP 8.2 (procedural), Apache, mod_rewrite |
| Database | MySQL 8 (PDO with prepared statements) |
| Customer-facing UI | Vanilla HTML + CSS + JS |
| Admin / Broker portals | Bootstrap 5 |
| Mail | PHPMailer / native `mail()` |
| Hosting | Afrihost shared cPanel |

No build step, no Node toolchain — the customer-facing site is plain files served by Apache.

---

## Local development

You need PHP 8.2+, MySQL, and Apache. The easiest path is [XAMPP](https://www.apachefriends.org/) for Windows/Mac.

1. Clone into your Apache `htdocs/` (or document root):
   ```bash
   git clone https://github.com/oluwatosindot/greencash.git
   cd greencash
   ```

2. Create your local environment file:
   ```bash
   cp includes/env.example.php includes/env.php
   ```
   Edit `includes/env.php` with your local DB credentials and `APP_URL`.

3. Import the schema:
   ```bash
   mysql -u root greencash < database/greencash.sql
   ```
   (Create the DB first: `CREATE DATABASE greencash CHARACTER SET utf8mb4;`)

4. Visit `http://localhost/greencash/` in a browser.

---

## Project layout

```
greencash/
├── index.php                  Landing page (hero, calculator-replacement, products, employers, apply, FAQ)
├── apply.php                  Multi-step loan application POST handler
├── login.php, verify-otp.php  Admin/broker authentication
├── track-application.php      Self-service application status lookup
├── contact.php                Public contact form + complaints procedure
├── partnership-enquiry.php    Employers band modal POST handler
├── application-submitted.php  Post-submission confirmation
├── privacy-policy.php
├── terms-and-conditions.php
├── 404.php, 500.php
├── admin/                     Admin panel (Bootstrap 5)
├── assets/                    css/, js/, img/ (logos, favicons, OG card)
├── includes/                  Shared header/footer + config.php + env.php
├── cron/                      Scheduled jobs (rate-limit cleanup, etc.)
├── database/greencash.sql     Full schema for fresh installs
├── uploads/                   Applicant docs (blocked from HTTP; served via serve-document.php)
└── serve-document.php         Authenticated proxy for uploads/
```

---

## Deployment

See **[DEPLOY.md](DEPLOY.md)** for the step-by-step Afrihost cPanel walkthrough: MySQL database, code upload via Git Version Control, env.php, mailboxes, Let's Encrypt SSL, HTTPS redirect, permissions.

---

## Contact

- Customer enquiries: info@greencash.co.za
- Partnership enquiries: info@greencash.co.za
- WhatsApp: +27 78 517 7961

---

© GreenCash. All rights reserved.
