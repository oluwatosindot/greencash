# Step 2: Homepage & Public Pages — Design Spec

**Date:** 2026-05-16
**Status:** Approved
**Approach:** Option B — Pages + stub asset files

---

## Overview

Build the public-facing marketing site: homepage with live calculator, standalone calculator page, contact form (DB-backed), two static pages, and a custom 404. Also create the shared CSS and JS asset files already linked by `header.php` and `footer.php`.

---

## Files to Create

```
greencash/
├── index.php                        (homepage)
├── calculator.php                   (standalone calculator)
├── contact.php                      (contact form — stores to DB)
├── privacy-policy.php               (static)
├── terms-and-conditions.php         (static)
├── 404.php                          (custom error page — self-contained)
└── assets/
    ├── css/
    │   └── style.css                (CSS variables, base styles)
    └── js/
        └── main.js                  (calculator logic, shared)
```

---

## Database Addition

Add `contact_messages` table to `database/greencash.sql` (append before closing `SET FOREIGN_KEY_CHECKS=1`) and run it in MySQL as part of Step 2 deliverables:

```sql
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Local Development — Asset URL Requirement

`header.php` and `footer.php` link assets using `APP_URL` (e.g. `<?= APP_URL ?>/assets/css/style.css`). For assets to resolve correctly during local development, `includes/env.php` **must** define:

```php
define('APP_URL', 'http://localhost/greencash');
```

This is already set in the Step 1 `env.php`. Do not change it to the production URL until deployment. The done condition test will fail silently if `APP_URL` points to the production domain while files only exist locally.

---

## Page Architecture

Every page follows this pattern:
```php
<?php
require_once 'includes/config.php';
$page_title = 'Page Title';
include 'includes/header.php';
// page content
include 'includes/footer.php';
```

No auth guards on any Step 2 pages — all are public. Exception: `404.php` is self-contained (see below).

---

## Calculator Logic

**Fee structure:** 15% flat fee on loan amount.

```
fee = loan_amount * 0.15
total_repayment = loan_amount + fee
term = 1 month (fixed)
```

Example: R5,000 loan → R750 fee → R5,750 total repayment.

**Slider config:**
- Min: R1,000
- Max: R10,000
- Step: R500
- Default: R5,000

---

## `assets/js/main.js`

Single responsibility: power the calculator widget on both `index.php` and `calculator.php`.

```js
document.addEventListener('DOMContentLoaded', function () {
    const FEE_RATE = 0.15; // 15% flat fee

    const slider = document.getElementById('loanSlider');
    // REQUIRED: null-guard all element lookups.
    // main.js is loaded by footer.php on EVERY public page.
    // On pages without a calculator widget (contact, privacy, T&Cs),
    // getElementById returns null — calling .addEventListener() on null
    // throws an uncaught TypeError. Always guard:
    if (!slider) return;

    const elAmount     = document.getElementById('loanAmount');
    const elFee        = document.getElementById('serviceFee');
    const elRepayment  = document.getElementById('totalRepayment');

    function updateCalc() {
        const loan = parseFloat(slider.value);
        const fee  = loan * FEE_RATE;
        elAmount.textContent    = formatR(loan);
        elFee.textContent       = formatR(fee);
        elRepayment.textContent = formatR(loan + fee);
    }

    function formatR(n) {
        return 'R ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    slider.addEventListener('input', updateCalc);
    updateCalc(); // populate on load

    // Smooth scroll for "Check My Rate" CTA on homepage
    const checkRateBtn = document.getElementById('checkRate');
    if (checkRateBtn) {
        checkRateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('calculator')?.scrollIntoView({ behavior: 'smooth' });
        });
    }
});
```

Both pages use identical HTML element IDs (`#loanSlider`, `#loanAmount`, `#serviceFee`, `#totalRepayment`).

---

## `assets/css/style.css`

Defines CSS custom properties and component styles:

| Class/Variable | Purpose |
|---|---|
| `:root` vars | `--gc-primary`, `--gc-primary-light`, `--gc-primary-dark`, `--gc-white`, `--gc-text`, `--gc-muted`, `--gc-border`, `--gc-shadow` |
| `.hero` | Green gradient bg (`#2E7D32` → `#1B5E20`), white text, `padding: 5rem 0` |
| `.step-card` | "How It Works" card — border-top green accent, centered icon |
| `.calculator-widget` | White card, `box-shadow: var(--gc-shadow)`, rounded corners |
| `.trust-badge` | Icon + label, muted text, centered |
| `.btn-gc` | Green button (`background: var(--gc-primary)`, white text) |

---

## `index.php` — Homepage

### Sections (top to bottom)

**1. Hero**
- Green gradient background (`.hero`)
- Headline: *"Get Your Salary In Advance — Today"*
- Subtext: *"Fast, secure salary advance loans from R1,000 to R10,000. Repaid in 1 month."*
- Two CTAs: **Apply Now** (`/apply.php`) and **Check My Rate** (`id="checkRate"`, smooth scroll to `#calculator`)

**2. How It Works** — 3 Bootstrap cards in a row
- Apply Online — fill in the form in minutes
- Get Approved — decision within 24 hours
- Receive Funds — money in your account same day

**3. Live Calculator Widget** (`id="calculator"`)
- Range slider R1,000–R10,000, step R500, default R5,000 (`id="loanSlider"`)
- Displays: Loan Amount (`id="loanAmount"`), Service Fee (`id="serviceFee"`), Total Repayment (`id="totalRepayment"`), Term: 1 Month (fixed label)
- **Apply Now** CTA button below result

**4. Trust Signals** — 3 items in a row
- Fast Approval (bi-clock icon)
- Secure & Safe (bi-shield-check icon)
- NCR Registered (bi-patch-check icon)

---

## `calculator.php` — Standalone Calculator

- Page heading: *"Loan Calculator"*
- Brief description: *"See exactly what you'll repay before you apply."*
- Same calculator widget HTML as homepage — identical element IDs (`#loanSlider`, `#loanAmount`, `#serviceFee`, `#totalRepayment`)
- **Apply Now** button below result
- No other content — clean, focused page

---

## `contact.php` — Contact Form

### Layout
Two-column Bootstrap grid (col-md-7 form / col-md-4 contact details) on desktop, stacked on mobile.

### Form Fields
| Field | Type | Required | Max Length |
|---|---|---|---|
| Name | text | Yes | 100 |
| Email | email | Yes | 255 |
| Phone | tel | No | 20 |
| Subject | text | Yes | 255 |
| Message | textarea (5 rows) | Yes | 5000 |
| CSRF token | hidden | Yes | — |

### POST Handler (top of file, before HTML output)

```
1. verifyCsrf()
2. For each field: trim($_POST['field'] ?? '')
3. Validate:
   - name: required, max 100 chars
   - email: required, filter_var(FILTER_VALIDATE_EMAIL), max 255 chars
     NOTE: store the raw trimmed email — do NOT apply sanitize() to email
     before DB insert (htmlspecialchars corrupts stored address)
   - phone: optional, max 20 chars if provided
   - subject: required, max 255 chars
   - message: required, max 5000 chars
   - Apply sanitize() to name, subject, message (for display-safe storage)
4. On validation fail: re-render form with $errors array, repopulate fields
   (csrfField() generates a fresh token automatically since verifyCsrf() rotated it)
5. On pass: INSERT to contact_messages (name, email, phone, subject, message, ip_address)
6. setFlash('success', 'Thank you! We\'ll be in touch shortly.') → redirect('contact.php')
```

**Rate limiting note:** No IP rate limiting is implemented on this form in Step 2. This is a known gap — add IP-based rate limiting (max 3 submissions/hour using `rate_limits` table) to the Step 10 security hardening checklist.

### Right Column — Contact Details
- Phone: (placeholder number)
- Email: admin@greencash.co.za
- Business hours: Mon–Fri, 8am–5pm

---

## `privacy-policy.php` — Static Page

Standard SA fintech privacy policy covering:
- What data is collected (personal, financial, documents)
- How it is used (loan processing, communication)
- POPIA compliance statement
- Data retention and deletion rights
- Contact details for privacy queries

No DB queries. No forms. Placeholder text — must be reviewed by legal before go-live.

---

## `terms-and-conditions.php` — Static Page

Loan terms covering:
- Loan product: Salary Advance only
- Amount range: R1,000–R10,000
- Term: 1 calendar month
- Fee: 15% of loan amount (flat)
- Eligibility requirements (employed, SA ID)
- Repayment obligations
- Default consequences
- NCR registration statement

No DB queries. No forms. Placeholder text — must be reviewed by legal before go-live.

---

## `404.php`

**Self-contained HTML — do NOT include `header.php`, `footer.php`, or `config.php`.**

Reason: `config.php` opens a PDO database connection immediately on load. If the DB is unavailable (a plausible condition when errors are occurring), including `config.php` will call `die('Service temporarily unavailable.')` — replacing the 404 page with a blank crash. The 404 page must survive a DB outage.

`ErrorDocument 404 /404.php` is already configured in `.htaccess` (Step 1 deliverable — confirmed present).

Structure:
- `http_response_code(404)` at top
- Centred layout, full viewport height, Bootstrap 5 via CDN
- Large "404" display text
- Heading: *"Page Not Found"*
- Subtext: *"The page you're looking for doesn't exist or has been moved."*
- Two buttons: **Go Home** (`/`) and **Apply Now** (`/apply.php`)

---

## Done Condition

1. `contact_messages` table exists in MySQL — verify with `SHOW TABLES`
2. All 6 pages load at `http://localhost/greencash/` with no PHP errors in the error log
3. `assets/css/style.css` and `assets/js/main.js` return HTTP 200 (no 404 in browser console)
4. Calculator updates live on slider drag on both `index.php` and `calculator.php`
5. Contact form POST stores a row in `contact_messages` — verify in phpMyAdmin
6. Navigating to a non-existent URL (e.g. `http://localhost/greencash/nonexistent`) serves `404.php`

---

## Local → Production Notes

- `env.php` `APP_URL` must be updated from `http://localhost/greencash` to `https://www.greencash.co.za` before deploy
- Update phone number and contact details in `contact.php` before deploy
- Privacy policy and T&Cs placeholder text must be reviewed by legal before go-live
- 15% fee rate is hardcoded in `main.js` — update `FEE_RATE` constant if rate changes
- Add contact form IP rate limiting in Step 10
