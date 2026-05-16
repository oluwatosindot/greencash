# Step 2: Homepage & Public Pages — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the public-facing marketing site — homepage with live calculator, standalone calculator, contact form, privacy/T&Cs static pages, and custom 404 — plus shared CSS/JS assets.

**Architecture:** Every public page uses the Step 1 layout shells (`header.php` / `footer.php`) with `require_once 'includes/config.php'` at the top. Calculator logic lives in `assets/js/main.js` and is shared between `index.php` and `calculator.php` via identical element IDs. The contact form stores submissions to a new `contact_messages` DB table. `404.php` is self-contained (no config.php — DB failure must not crash the error page).

**Tech Stack:** Procedural PHP 8, PDO/MySQL, Bootstrap 5 (CDN), Bootstrap Icons (CDN), Google Fonts Inter (CDN), vanilla JS.

**Spec:** `docs/superpowers/specs/2026-05-16-step2-public-pages-design.md`

---

## File Map

| Action | File | Responsibility |
|---|---|---|
| Modify | `database/greencash.sql` | Add `contact_messages` table DDL |
| Create | `assets/css/style.css` | CSS custom properties, component styles |
| Create | `assets/js/main.js` | Calculator logic (shared), smooth scroll |
| Create | `index.php` | Homepage: hero, how it works, calculator, trust signals |
| Create | `calculator.php` | Standalone calculator page |
| Create | `contact.php` | Contact form + DB insert handler |
| Create | `privacy-policy.php` | Static POPIA privacy policy |
| Create | `terms-and-conditions.php` | Static loan terms |
| Create | `404.php` | Self-contained custom error page |

---

## Chunk 1: Database + Asset Files

### Task 1: Add `contact_messages` table

**Files:**
- Modify: `database/greencash.sql`

- [ ] **Step 1: Add DDL to greencash.sql**

Append the following before the final `SET FOREIGN_KEY_CHECKS=1;` line in `database/greencash.sql`:

```sql
-- ============================================================
-- CONTACT MESSAGES
-- ============================================================
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

- [ ] **Step 2: Create the table in MySQL**

Run in XAMPP terminal:
```bash
/c/xampp/mysql/bin/mysql.exe -u root greencash -e "
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
```

- [ ] **Step 3: Verify table exists**

```bash
/c/xampp/mysql/bin/mysql.exe -u root greencash -e "SHOW TABLES;"
```

Expected: `contact_messages` appears in the list.

- [ ] **Step 4: Commit**

```bash
git add database/greencash.sql
git commit -m "feat: add contact_messages table to schema"
```

---

### Task 2: Create `assets/css/style.css`

**Files:**
- Create: `assets/css/style.css`

- [ ] **Step 1: Create the assets directory structure**

```bash
mkdir -p /c/xampp/htdocs/greencash/assets/css
mkdir -p /c/xampp/htdocs/greencash/assets/js
```

- [ ] **Step 2: Write style.css**

Create `assets/css/style.css`:

```css
/* ============================================================
   Green Cash — Global Styles
   Bootstrap 5 base. Custom properties reinforce header.php inline vars.
   ============================================================ */

:root {
    --gc-primary:       #2E7D32;
    --gc-primary-light: #66BB6A;
    --gc-primary-dark:  #1B5E20;
    --gc-white:         #ffffff;
    --gc-text:          #212529;
    --gc-muted:         #6c757d;
    --gc-border:        #dee2e6;
    --gc-shadow:        0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ---- Base ---- */
body {
    font-family: 'Inter', sans-serif;
    color: var(--gc-text);
}

/* ---- Buttons ---- */
.btn-gc {
    background-color: var(--gc-primary);
    color: #fff;
    border: none;
    font-weight: 600;
}
.btn-gc:hover,
.btn-gc:focus {
    background-color: var(--gc-primary-dark);
    color: #fff;
}

/* ---- Hero ---- */
.hero {
    background: linear-gradient(135deg, var(--gc-primary) 0%, var(--gc-primary-dark) 100%);
    color: #fff;
    padding: 5rem 0;
}
.hero h1 {
    font-weight: 700;
    font-size: clamp(1.8rem, 4vw, 3rem);
}
.hero p {
    font-size: 1.15rem;
    opacity: 0.9;
}

/* ---- How It Works cards ---- */
.step-card {
    border-top: 4px solid var(--gc-primary);
    border-radius: 8px;
    text-align: center;
    padding: 2rem 1.5rem;
    box-shadow: var(--gc-shadow);
    height: 100%;
}
.step-card .step-icon {
    font-size: 2.5rem;
    color: var(--gc-primary);
    margin-bottom: 1rem;
}
.step-number {
    display: inline-flex;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--gc-primary);
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
}

/* ---- Calculator widget ---- */
.calculator-widget {
    background: #fff;
    border-radius: 12px;
    box-shadow: var(--gc-shadow);
    padding: 2rem;
    max-width: 560px;
    margin: 0 auto;
}
.calculator-widget .calc-result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gc-border);
    font-size: 0.95rem;
}
.calculator-widget .calc-result-row:last-of-type {
    border-bottom: none;
}
.calculator-widget .calc-result-row .label {
    color: var(--gc-muted);
}
.calculator-widget .calc-result-row .value {
    font-weight: 600;
    color: var(--gc-text);
}
.calculator-widget .calc-result-row.total .value {
    color: var(--gc-primary);
    font-size: 1.2rem;
}
.calculator-widget input[type="range"] {
    accent-color: var(--gc-primary);
}

/* ---- Trust signals ---- */
.trust-badge {
    text-align: center;
    padding: 1.5rem 1rem;
}
.trust-badge .trust-icon {
    font-size: 2rem;
    color: var(--gc-primary);
    margin-bottom: 0.5rem;
}
.trust-badge p {
    font-size: 0.85rem;
    color: var(--gc-muted);
    margin: 0;
}

/* ---- Section helpers ---- */
.section-title {
    font-weight: 700;
    color: var(--gc-text);
}
.section-subtitle {
    color: var(--gc-muted);
    max-width: 540px;
    margin: 0 auto;
}

/* ---- 404 page ---- */
.page-404 {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    flex-direction: column;
    padding: 2rem;
}
.page-404 .error-code {
    font-size: clamp(5rem, 15vw, 10rem);
    font-weight: 700;
    color: var(--gc-primary);
    line-height: 1;
}
```

- [ ] **Step 3: Verify file loads in browser**

Open `http://localhost/greencash/` (index.php doesn't exist yet — check the URL directly for the stylesheet):

```
http://localhost/greencash/assets/css/style.css
```

Expected: CSS file contents displayed (not a 404).

- [ ] **Step 4: Commit**

```bash
git add assets/css/style.css
git commit -m "feat: add global stylesheet with CSS custom properties"
```

---

### Task 3: Create `assets/js/main.js`

**Files:**
- Create: `assets/js/main.js`

- [ ] **Step 1: Write main.js**

Create `assets/js/main.js`:

```js
/* ============================================================
   Green Cash — main.js
   Calculator widget logic. Loaded by footer.php on all public pages.
   IMPORTANT: Guard all getElementById calls — this script runs on
   every public page, most of which do not have a calculator widget.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Loan Calculator ----
    const FEE_RATE = 0.15; // 15% flat service fee

    // Null guard: main.js loads on every public page via footer.php.
    // Pages without the calculator widget return null — bail out early.
    // All element lookups must come AFTER this guard.
    const slider = document.getElementById('loanSlider');
    if (!slider) return;

    const elAmount    = document.getElementById('loanAmount');
    const elFee       = document.getElementById('serviceFee');
    const elRepayment = document.getElementById('totalRepayment');
    const elSliderVal = document.getElementById('sliderValue');

    // Format as "R 1,000.00" — regex-based for consistent comma separators.
    // Do NOT use toLocaleString('en-ZA') — SA locale uses spaces, not commas.
    function formatR(n) {
        return 'R ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateCalc() {
        var loan  = parseFloat(slider.value);
        var fee   = loan * FEE_RATE;
        var total = loan + fee;

        if (elSliderVal)  elSliderVal.textContent  = formatR(loan);
        if (elAmount)     elAmount.textContent      = formatR(loan);
        if (elFee)        elFee.textContent         = formatR(fee);
        if (elRepayment)  elRepayment.textContent   = formatR(total);
    }

    slider.addEventListener('input', updateCalc);
    updateCalc(); // populate on page load

    // ---- Smooth scroll for "Check My Rate" CTA on homepage ----
    var checkRateBtn = document.getElementById('checkRate');
    if (checkRateBtn) {
        checkRateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById('calculator');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

});
```

- [ ] **Step 2: Verify file loads in browser**

```
http://localhost/greencash/assets/js/main.js
```

Expected: JS file contents displayed (not a 404).

- [ ] **Step 3: Commit**

```bash
git add assets/js/main.js
git commit -m "feat: add calculator JS with null guard and smooth scroll"
```

---

## Chunk 2: Homepage & Calculator

### Task 4: Create `index.php` — Homepage

**Files:**
- Create: `index.php`

- [ ] **Step 1: Write index.php**

Create `index.php`:

```php
<?php
require_once 'includes/config.php';
$page_title = 'Get Your Salary In Advance — Today';
include 'includes/header.php';
?>

<!-- ===================== HERO ===================== -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-3">Get Your Salary In Advance — Today</h1>
                <p class="mb-4">Fast, secure salary advance loans from R1,000 to R10,000. Repaid in 1 month. No hidden fees.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= APP_URL ?>/apply.php" class="btn btn-light btn-lg fw-semibold px-4">
                        <i class="bi bi-pencil-square me-2"></i>Apply Now
                    </a>
                    <a href="#calculator" id="checkRate" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-calculator me-2"></i>Check My Rate
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center pt-4 pt-lg-0">
                <i class="bi bi-cash-stack" style="font-size:8rem; opacity:0.3;"></i>
            </div>
        </div>
    </div>
</section>

<!-- ===================== HOW IT WORKS ===================== -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle mt-2">Get your salary advance in three simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <h5 class="fw-semibold">Apply Online</h5>
                    <p class="text-muted small">Fill in our simple application form in under 5 minutes from your phone or computer.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="bi bi-patch-check"></i></div>
                    <h5 class="fw-semibold">Get Approved</h5>
                    <p class="text-muted small">Our team reviews your application quickly. Most decisions are made within 24 hours.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="bi bi-bank"></i></div>
                    <h5 class="fw-semibold">Receive Funds</h5>
                    <p class="text-muted small">Once approved, funds are transferred directly to your bank account — same day.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CALCULATOR ===================== -->
<section class="py-5 bg-light" id="calculator">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How Much Do You Need?</h2>
            <p class="section-subtitle mt-2">Use our calculator to see your exact repayment amount before you apply.</p>
        </div>

        <div class="calculator-widget">
            <label class="fw-semibold mb-1 d-flex justify-content-between">
                <span>Loan Amount</span>
                <span id="sliderValue" class="text-success fw-bold">R 5,000.00</span>
            </label>
            <input type="range" class="form-range mb-4"
                   id="loanSlider" min="1000" max="10000" step="500" value="5000">

            <div class="calc-result-row">
                <span class="label">Loan Amount</span>
                <span class="value" id="loanAmount">R 5,000.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Service Fee (15%)</span>
                <span class="value" id="serviceFee">R 750.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Loan Term</span>
                <span class="value">1 Month</span>
            </div>
            <div class="calc-result-row total mt-2">
                <span class="label fw-semibold">Total Repayment</span>
                <span class="value" id="totalRepayment">R 5,750.00</span>
            </div>

            <a href="<?= APP_URL ?>/apply.php" class="btn btn-gc w-100 mt-4 py-2">
                <i class="bi bi-pencil-square me-2"></i>Apply Now
            </a>
            <p class="text-center text-muted small mt-2 mb-0">No obligation. Takes less than 5 minutes.</p>
        </div>
    </div>
</section>

<!-- ===================== TRUST SIGNALS ===================== -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-clock-fill"></i></div>
                    <h6 class="fw-semibold mb-1">Fast Approval</h6>
                    <p>Decision within 24 hours of submission</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-shield-check"></i></div>
                    <h6 class="fw-semibold mb-1">Secure &amp; Safe</h6>
                    <p>Your data is encrypted and never shared</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <h6 class="fw-semibold mb-1">NCR Registered</h6>
                    <p>Fully compliant with South African regulations</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Load in browser and verify**

Open `http://localhost/greencash/`

Expected:
- Green navbar loads, no PHP errors
- Hero section visible with green gradient
- How It Works — 3 cards
- Calculator widget: drag slider → all values update live
- Trust signals section
- Footer with copyright
- No 404 errors in browser DevTools console for CSS/JS

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: add homepage with hero, calculator, and trust signals"
```

---

### Task 5: Create `calculator.php` — Standalone Calculator

**Files:**
- Create: `calculator.php`

- [ ] **Step 1: Write calculator.php**

Create `calculator.php`:

```php
<?php
require_once 'includes/config.php';
$page_title = 'Loan Calculator';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="text-center mb-4">
            <h1 class="section-title">Loan Calculator</h1>
            <p class="text-muted mt-2">See exactly what you'll repay before you apply. No surprises.</p>
        </div>

        <div class="calculator-widget">
            <label class="fw-semibold mb-1 d-flex justify-content-between">
                <span>Loan Amount</span>
                <span id="sliderValue" class="text-success fw-bold">R 5,000.00</span>
            </label>
            <input type="range" class="form-range mb-4"
                   id="loanSlider" min="1000" max="10000" step="500" value="5000">

            <div class="calc-result-row">
                <span class="label">Loan Amount</span>
                <span class="value" id="loanAmount">R 5,000.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Service Fee (15%)</span>
                <span class="value" id="serviceFee">R 750.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Loan Term</span>
                <span class="value">1 Month</span>
            </div>
            <div class="calc-result-row total mt-2">
                <span class="label fw-semibold">Total Repayment</span>
                <span class="value" id="totalRepayment">R 5,750.00</span>
            </div>

            <a href="<?= APP_URL ?>/apply.php" class="btn btn-gc w-100 mt-4 py-2">
                <i class="bi bi-pencil-square me-2"></i>Apply Now
            </a>
            <p class="text-center text-muted small mt-2 mb-0">
                Borrow R1,000–R10,000 · 1 month term · 15% flat fee
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Load in browser and verify**

Open `http://localhost/greencash/calculator.php`

Expected:
- Centred calculator widget with correct horizontal padding (header.php opens `<main class="container py-4">` — no extra container needed in this file)
- Drag slider → values update (same JS powering this as homepage)
- No JS errors in console

- [ ] **Step 3: Commit**

```bash
git add calculator.php
git commit -m "feat: add standalone calculator page"
```

---

## Chunk 3: Contact, Static Pages & 404

### Task 6: Create `contact.php` — Contact Form

**Files:**
- Create: `contact.php`

- [ ] **Step 1: Write contact.php**

Create `contact.php`:

```php
<?php
require_once 'includes/config.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Collect raw trimmed values
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Preserve values for re-render on error
    $old = compact('name', 'email', 'phone', 'subject', 'message');

    // Validate
    if (empty($name))                              $errors['name']    = 'Name is required.';
    elseif (mb_strlen($name) > 100)               $errors['name']    = 'Name must be 100 characters or less.';

    if (empty($email))                             $errors['email']   = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    elseif (mb_strlen($email) > 255)              $errors['email']   = 'Email must be 255 characters or less.';

    if (!empty($phone) && mb_strlen($phone) > 20) $errors['phone']   = 'Phone must be 20 characters or less.';

    if (empty($subject))                           $errors['subject'] = 'Subject is required.';
    elseif (mb_strlen($subject) > 255)            $errors['subject'] = 'Subject must be 255 characters or less.';

    if (empty($message))                           $errors['message'] = 'Message is required.';
    elseif (mb_strlen($message) > 5000)           $errors['message'] = 'Message must be 5000 characters or less.';

    if (empty($errors)) {
        // Store — NOTE: email stored raw (not sanitize()) to preserve the address
        $stmt = $pdo->prepare(
            "INSERT INTO contact_messages (name, email, phone, subject, message, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            sanitize($name),
            $email,
            !empty($phone) ? sanitize($phone) : null,
            sanitize($subject),
            sanitize($message),
            getClientIp(),
        ]);

        setFlash('success', 'Thank you! We\'ll be in touch shortly.');
        redirect('contact.php');
    }
}

$page_title = 'Contact Us';
include 'includes/header.php';
?>

<div class="row g-5">
    <!-- Contact Form -->
    <div class="col-md-7">
        <h1 class="section-title mb-1">Contact Us</h1>
        <p class="text-muted mb-4">Have a question? Fill in the form and we'll get back to you.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">Please fix the errors below and try again.</div>
        <?php endif; ?>

        <form method="POST" action="contact.php" novalidate>
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" maxlength="100"
                       class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['name'] ?? '') ?>">
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" maxlength="255"
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label>
                <input type="tel" name="phone" maxlength="20"
                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['phone']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                <input type="text" name="subject" maxlength="255"
                       class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['subject'] ?? '') ?>">
                <?php if (isset($errors['subject'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['subject']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                <textarea name="message" rows="5" maxlength="5000"
                          class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"><?= sanitize($old['message'] ?? '') ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['message']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-gc px-4 py-2">
                <i class="bi bi-send me-2"></i>Send Message
            </button>
        </form>
    </div>

    <!-- Contact Details -->
    <div class="col-md-4 offset-md-1">
        <h5 class="fw-semibold mb-3">Get In Touch</h5>
        <ul class="list-unstyled">
            <li class="mb-3 d-flex gap-3">
                <i class="bi bi-telephone-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Phone</div>
                    <div class="text-muted">+27 (0) 10 000 0000</div>
                </div>
            </li>
            <li class="mb-3 d-flex gap-3">
                <i class="bi bi-envelope-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Email</div>
                    <div class="text-muted">admin@greencash.co.za</div>
                </div>
            </li>
            <li class="d-flex gap-3">
                <i class="bi bi-clock-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Business Hours</div>
                    <div class="text-muted">Mon–Fri, 8am–5pm</div>
                </div>
            </li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Load in browser**

Open `http://localhost/greencash/contact.php`

Expected: Two-column layout, form renders with no errors.

- [ ] **Step 3: Test validation**

Submit form with empty fields.

Expected: Red validation errors appear under each required field. Page re-renders with values repopulated (except on success redirect).

- [ ] **Step 4: Test successful submission**

Fill all fields with valid data, submit.

Expected: Green flash message "Thank you! We'll be in touch shortly." at top of page.

- [ ] **Step 5: Verify DB row was stored**

```bash
/c/xampp/mysql/bin/mysql.exe -u root greencash -e "SELECT id, name, email, subject, ip_address, created_at FROM contact_messages ORDER BY id DESC LIMIT 1\G"
```

Expected: Row appears with correct name, email, subject, and ip_address.

- [ ] **Step 6: Commit**

```bash
git add contact.php
git commit -m "feat: add contact form with DB storage and validation"
```

---

### Task 7: Create Static Pages

**Files:**
- Create: `privacy-policy.php`
- Create: `terms-and-conditions.php`

- [ ] **Step 1: Write privacy-policy.php**

Create `privacy-policy.php`:

```php
<?php
require_once 'includes/config.php';
$page_title = 'Privacy Policy';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="section-title mb-1">Privacy Policy</h1>
        <p class="text-muted mb-4">Last updated: <?= date('F Y') ?></p>

        <div class="card border-0 shadow-sm p-4">
            <h5>1. Information We Collect</h5>
            <p>We collect personal information including your full name, South African ID number, email address, phone number, physical address, employment details, salary information, and financial documents (payslips, bank statements) when you apply for a salary advance loan.</p>

            <h5 class="mt-4">2. How We Use Your Information</h5>
            <p>Your information is used solely for processing your loan application, communicating with you about your application status, and complying with our regulatory obligations as a registered credit provider.</p>

            <h5 class="mt-4">3. POPIA Compliance</h5>
            <p>Green Cash processes personal information in compliance with the Protection of Personal Information Act 4 of 2013 (POPIA). You have the right to access, correct, and request deletion of your personal information. To exercise these rights, contact us at <a href="mailto:admin@greencash.co.za">admin@greencash.co.za</a>.</p>

            <h5 class="mt-4">4. Data Sharing</h5>
            <p>We do not sell or share your personal information with third parties except where required by law or necessary to process your loan application (e.g., credit bureau checks).</p>

            <h5 class="mt-4">5. Data Retention</h5>
            <p>We retain your personal information for a minimum of 5 years after your last interaction with us, as required by the National Credit Act. You may request deletion of your information subject to these legal requirements.</p>

            <h5 class="mt-4">6. Security</h5>
            <p>We implement industry-standard security measures including encrypted data storage and transmission to protect your personal information.</p>

            <h5 class="mt-4">7. Contact</h5>
            <p>For privacy-related queries, contact our Information Officer at <a href="mailto:admin@greencash.co.za">admin@greencash.co.za</a>.</p>

            <div class="alert alert-warning mt-4 small">
                <strong>Legal notice:</strong> This is placeholder privacy policy text. Have this document reviewed and approved by a legal professional before go-live.
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Write terms-and-conditions.php**

Create `terms-and-conditions.php`:

```php
<?php
require_once 'includes/config.php';
$page_title = 'Terms & Conditions';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="section-title mb-1">Terms &amp; Conditions</h1>
        <p class="text-muted mb-4">Last updated: <?= date('F Y') ?></p>

        <div class="card border-0 shadow-sm p-4">
            <h5>1. Loan Product</h5>
            <p>Green Cash offers one loan product: a Salary Advance loan for permanently employed South African citizens and residents.</p>

            <h5 class="mt-4">2. Loan Amount &amp; Term</h5>
            <ul>
                <li>Minimum loan: <strong>R1,000</strong></li>
                <li>Maximum loan: <strong>R10,000</strong></li>
                <li>Loan term: <strong>1 calendar month</strong></li>
                <li>Repayment date: Your next salary date as declared on the application</li>
            </ul>

            <h5 class="mt-4">3. Fees &amp; Costs</h5>
            <ul>
                <li>Service fee: <strong>15% of the loan amount</strong> (flat, once-off)</li>
                <li>Example: A loan of R5,000 carries a service fee of R750. Total repayment: R5,750.</li>
                <li>No hidden fees. No early settlement penalties.</li>
            </ul>

            <h5 class="mt-4">4. Eligibility</h5>
            <p>Applicants must be:</p>
            <ul>
                <li>South African citizen or permanent resident with a valid SA ID</li>
                <li>Permanently employed (not self-employed or contract workers, unless approved)</li>
                <li>18 years of age or older</li>
                <li>Able to demonstrate affordability</li>
            </ul>

            <h5 class="mt-4">5. Repayment</h5>
            <p>The full repayment amount (loan + service fee) is due on your next salary date. Failure to repay may result in additional charges and adverse credit bureau listings.</p>

            <h5 class="mt-4">6. NCR Registration</h5>
            <p>Green Cash is a registered credit provider in terms of the National Credit Act 34 of 2005. NCR Registration Number: [PLACEHOLDER].</p>

            <h5 class="mt-4">7. Governing Law</h5>
            <p>These terms are governed by the laws of the Republic of South Africa.</p>

            <div class="alert alert-warning mt-4 small">
                <strong>Legal notice:</strong> This is placeholder T&amp;C text. Have this document reviewed and approved by a legal professional before go-live.
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 3: Verify both pages load**

- `http://localhost/greencash/privacy-policy.php` — Expected: HTTP 200, content displays
- `http://localhost/greencash/terms-and-conditions.php` — Expected: HTTP 200, content displays

- [ ] **Step 4: Commit**

```bash
git add privacy-policy.php terms-and-conditions.php
git commit -m "feat: add privacy policy and terms & conditions static pages"
```

---

### Task 8: Create `404.php` — Custom Error Page

**Files:**
- Create: `404.php`

- [ ] **Step 1: Write 404.php**

Create `404.php`. This file is **self-contained** — no `config.php`, no layout shells.

```php
<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — Green Cash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gc-primary: #2E7D32; }
        body { font-family: 'Inter', sans-serif; }
        .page-404 {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex-direction: column;
            padding: 2rem;
        }
        .error-code {
            font-size: clamp(5rem, 15vw, 10rem);
            font-weight: 700;
            color: var(--gc-primary);
            line-height: 1;
        }
    </style>
</head>
<body>
<div class="page-404">
    <div class="error-code">404</div>
    <h1 class="h3 fw-semibold mt-3 mb-2">Page Not Found</h1>
    <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
    <div class="d-flex gap-3 flex-wrap justify-content-center">
        <a href="/" class="btn btn-success px-4">
            <i class="bi bi-house me-2"></i>Go Home
        </a>
        <a href="/apply.php" class="btn btn-outline-success px-4">
            <i class="bi bi-pencil-square me-2"></i>Apply Now
        </a>
    </div>
</div>
</body>
</html>
```

- [ ] **Step 2: Verify 404 page via direct URL**

Open `http://localhost/greencash/404.php`

Expected: Large green "404", heading, two buttons, no PHP errors.

- [ ] **Step 3: Verify 404 page via .htaccess routing**

Open `http://localhost/greencash/this-page-does-not-exist`

Expected: Same 404 page served (via `ErrorDocument 404 /404.php` in `.htaccess`).

- [ ] **Step 4: Commit**

```bash
git add 404.php
git commit -m "feat: add self-contained 404 error page"
```

---

## Chunk 4: Final Verification & Push

### Task 9: Full Done Condition Verification

- [ ] **Step 1: Verify all 6 pages load cleanly**

Open each URL and confirm HTTP 200, no PHP warnings in XAMPP error log (`/c/xampp/apache/logs/error.log`):

| URL | Expected |
|---|---|
| `http://localhost/greencash/` | Homepage loads |
| `http://localhost/greencash/calculator.php` | Calculator loads |
| `http://localhost/greencash/contact.php` | Contact form loads |
| `http://localhost/greencash/privacy-policy.php` | Privacy policy loads |
| `http://localhost/greencash/terms-and-conditions.php` | T&Cs load |
| `http://localhost/greencash/this-does-not-exist` | 404 page served |

- [ ] **Step 2: Verify assets load (no 404 in DevTools)**

Open browser DevTools → Network tab, reload `http://localhost/greencash/`

Expected: `style.css` and `main.js` both return 200, not 404.

- [ ] **Step 3: Verify calculator on both pages**

On `index.php` and `calculator.php`: drag slider to R3,000.

Expected:
- Loan Amount: `R 3,000.00`
- Service Fee: `R 450.00`
- Total Repayment: `R 3,450.00`

- [ ] **Step 4: Verify contact form stores to DB**

Submit contact form with test data. Verify in MySQL:

```bash
/c/xampp/mysql/bin/mysql.exe -u root greencash -e "SELECT * FROM contact_messages ORDER BY id DESC LIMIT 1\G"
```

Expected: Row present with correct fields.

- [ ] **Step 5: Check XAMPP error log for PHP warnings**

```bash
tail -20 /c/xampp/apache/logs/error.log
```

Expected: No PHP warnings or errors from any of the 6 pages.

- [ ] **Step 6: Final commit and push**

```bash
git add -A
git status
git commit -m "feat: Step 2 complete — homepage, calculator, contact, static pages, 404"
git push origin staging
```

---

## Summary

| Task | Files | Status |
|---|---|---|
| 1 | `database/greencash.sql` — add contact_messages | |
| 2 | `assets/css/style.css` | |
| 3 | `assets/js/main.js` | |
| 4 | `index.php` | |
| 5 | `calculator.php` | |
| 6 | `contact.php` | |
| 7 | `privacy-policy.php`, `terms-and-conditions.php` | |
| 8 | `404.php` | |
| 9 | Full verification + push | |
