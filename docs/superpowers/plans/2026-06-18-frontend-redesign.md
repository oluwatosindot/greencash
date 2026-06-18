# Frontend Redesign Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the artifact template's design system byte-for-byte onto the customer-facing PHP pages — single-page `index.php` with hero+calc+how+products+employers+apply+FAQ, shared dark nav/footer, restyled auth/track/legal pages. Admin and broker portals stay on Bootstrap and are untouched.

**Architecture:** Drop Bootstrap from `includes/header.php`/`includes/footer.php` (customer shell only). Move artifact tokens/components into `assets/css/style.css` (rewritten) and behavior into `assets/js/main.js` (rewritten). Each customer page keeps its existing PHP top/bottom and POST handlers; only the body markup is rewritten. `apply.php` loses its UI (now lives inline on index.php) and becomes a POST handler + duplicate-guard GET state. `admin/` and `broker-*` shells are not touched.

**Tech Stack:** PHP 8.2 (XAMPP), MySQL via PDO, vanilla HTML/CSS/JS (no framework), Sora + Plus Jakarta Sans from Google Fonts CDN. No test framework exists — verification is `curl` + `grep` based smoke checks plus manual browser verification on `http://localhost/greencash/`.

**Source spec:** `docs/superpowers/specs/2026-06-18-frontend-redesign-design.md`
**Source artifact:** `c:\Users\user\Downloads\e3543a85-efd2-44f2-916b-02fa57c5aeeb.html` (814 lines, the design reference — copy CSS/markup verbatim from this file where indicated)

**Branch:** Currently on `staging`. Do all work here. No worktree needed (the user has been working on `staging` for the wider GreenCash build).

**Verification approach:** This project has no test framework. Each task ends with a smoke check: `curl -s -o /dev/null -w "%{http_code}" http://localhost/greencash/<page>` for status, and `curl -s http://localhost/greencash/<page> | grep -q '<expected-marker>'` for content. After the visible-impact tasks (CSS, header, index), also open `http://localhost/greencash/` in a browser and visually compare against the artifact opened from the Downloads path. Apache must be running (it is — verified in the prior session); MySQL must be running for any page that does a DB query (login, track, apply POST).

---

## Chunk 1: Foundation (CSS + JS + config constant)

This chunk establishes the design system. Every subsequent chunk references the classes defined here. After this chunk, the site will look **broken** because the new classes exist but no page is using them yet — that's fine, the visible work starts in Chunk 3. Don't pause for visual review here.

### Task 1.1: Rewrite `assets/css/style.css` (tokens + base)

**Files:**
- Modify: `assets/css/style.css` (currently 155 lines — full rewrite)

- [ ] **Step 1: Replace the file with the artifact's `<style>` block, tokens through `.btn-block`**

Read `c:\Users\user\Downloads\e3543a85-efd2-44f2-916b-02fa57c5aeeb.html` lines 29–72 and copy verbatim into `assets/css/style.css`. That covers `:root` tokens, base resets, typography (`h1-h4`, `.display`), `.wrap`, and the entire `.btn` family (`.btn`, `.btn-primary`, `.btn-yellow`, `.btn-ghost`, `.btn-block`).

- [ ] **Step 2: Append the two new component classes that aren't in the artifact**

Add after the `.btn-block` rule:

```css
/* Ghost button on a dark background (e.g. hero secondary CTA) */
.btn-ghost--on-dark{color:#fff;border-color:rgba(255,255,255,.35)}
.btn-ghost--on-dark:hover{color:var(--yellow);border-color:var(--yellow)}

/* Flash messages (replaces Bootstrap .alert) */
.flash{
  position:relative;display:flex;align-items:flex-start;gap:12px;
  background:var(--paper-2);border:1px solid var(--line);border-left:4px solid var(--green);
  border-radius:12px;padding:14px 44px 14px 18px;margin-bottom:14px;
  font-size:14.5px;color:var(--ink);box-shadow:var(--shadow-sm);
}
.flash--success{border-left-color:var(--green)}
.flash--error{border-left-color:#d33}
.flash--warning{border-left-color:var(--yellow)}
.flash--info{border-left-color:var(--green-deep)}
.flash .x{
  position:absolute;top:10px;right:10px;
  width:26px;height:26px;border:none;background:transparent;
  border-radius:50%;cursor:pointer;font-size:18px;color:var(--muted);
}
.flash .x:hover{background:var(--paper);color:var(--ink)}
```

- [ ] **Step 3: Verify the file compiles (no syntax errors break the cascade)**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/assets/css/style.css
```

Expected: `200`. Then `curl -s http://localhost/greencash/assets/css/style.css | grep -c '\-\-green:#1aa636'` should return `1`.

- [ ] **Step 4: Commit**

```bash
git add assets/css/style.css
git commit -m "style: replace style.css base with artifact tokens, btn system, flash component"
```

### Task 1.2: Append the rest of the artifact CSS to `assets/css/style.css`

**Files:**
- Modify: `assets/css/style.css`

- [ ] **Step 1: Append all artifact component CSS verbatim**

Read `c:\Users\user\Downloads\e3543a85-efd2-44f2-916b-02fa57c5aeeb.html` lines 73–283 and append verbatim to `assets/css/style.css`. Covers: header/nav/brand/navlinks/burger, hero/eyebrow/trust-row, calc-card/slider-group/input range/calc-out, strip, section.block/sec-head/kicker, grid-3/card, steps/step, band, faq/q, apply/form-shell/steps-bar/form-body/fstep/fgrid/field/check/form-nav/upload/success/ref-no, footer/foot-grid/foot-brand/foot-col/foot-contact/foot-bottom, and the two `@media` blocks.

- [ ] **Step 2: Verify file size + key markers**

```bash
wc -l C:/xampp/htdocs/greencash/assets/css/style.css
curl -s http://localhost/greencash/assets/css/style.css | grep -c 'class\|grid-3\|calc-card\|form-shell'
```

Expected: ~280 lines total. Curl grep should hit several artifact-class names.

- [ ] **Step 3: Commit**

```bash
git add assets/css/style.css
git commit -m "style: port full artifact component CSS (hero, calc, cards, forms, footer)"
```

### Task 1.3: Rewrite `assets/js/main.js`

**Files:**
- Modify: `assets/js/main.js` (currently 55 lines — full rewrite)

- [ ] **Step 1: Replace with the feature-detected script**

Replace the entire file with:

```js
// Year (footer)
const yr = document.getElementById('yr');
if (yr) yr.textContent = new Date().getFullYear();

// Flash dismisser
document.querySelectorAll('.flash .x').forEach(btn =>
  btn.addEventListener('click', () => btn.closest('.flash').remove())
);

// Mobile nav burger
const burger = document.getElementById('burger');
const navlinks = document.getElementById('navlinks');
if (burger && navlinks) {
  burger.addEventListener('click', () => navlinks.classList.toggle('open'));
  navlinks.querySelectorAll('a').forEach(a =>
    a.addEventListener('click', () => navlinks.classList.remove('open'))
  );
}

// Calculator (hero card on index.php)
const amt = document.getElementById('amt');
if (amt) {
  const amtVal = document.getElementById('amtVal');
  const oPrincipal = document.getElementById('oPrincipal');
  const oFees = document.getElementById('oFees');
  const oTotal = document.getElementById('oTotal');
  const FEE_RATE = 0.15; // matches includes/config.php / apply.php backend
  const fmt = n => 'R ' + Math.round(n).toLocaleString('en-ZA');
  function paintSlider(el){
    const min = +el.min, max = +el.max;
    const p = ((el.value - min) / (max - min)) * 100;
    el.style.setProperty('--p', p + '%');
  }
  function calc(){
    const a = +amt.value;
    const fee = a * FEE_RATE;
    amtVal.textContent = fmt(a);
    if (oPrincipal) oPrincipal.textContent = fmt(a);
    if (oFees) oFees.textContent = fmt(fee);
    if (oTotal) oTotal.textContent = fmt(a + fee);
    paintSlider(amt);
  }
  amt.addEventListener('input', calc);
  calc();
}

// Multi-step apply form (index.php #apply)
const loanForm = document.getElementById('loanForm');
if (loanForm) {
  const steps = [...loanForm.querySelectorAll('.fstep')];
  const bar = [...document.querySelectorAll('#stepsBar .sb')];
  let cur = 0;

  function goto(i){
    steps.forEach(s => s.classList.remove('active'));
    steps[i].classList.add('active');
    bar.forEach((b, bi) => {
      b.classList.toggle('active', bi === i);
      b.classList.toggle('done', bi < i);
    });
    cur = i;
    document.getElementById('apply').scrollIntoView({behavior: 'smooth'});
  }

  function validateStep(i){
    let ok = true;
    steps[i].querySelectorAll('[required]').forEach(inp => {
      const field = inp.closest('.field') || inp.closest('.check');
      let bad = (inp.type === 'checkbox') ? !inp.checked : !inp.value.trim();
      if (inp.name === 'id_number' && inp.value && !/^\d{13}$/.test(inp.value.trim())) bad = true;
      if (inp.type === 'email' && inp.value && !/^[^@]+@[^@]+\.[^@]+$/.test(inp.value)) bad = true;
      if (field) field.classList.toggle('invalid', bad);
      if (bad) ok = false;
    });
    return ok;
  }

  loanForm.querySelectorAll('[data-next]').forEach(b =>
    b.addEventListener('click', () => { if (validateStep(cur)) goto(cur + 1); })
  );
  loanForm.querySelectorAll('[data-prev]').forEach(b =>
    b.addEventListener('click', () => goto(cur - 1))
  );

  // File list display
  ['id_document', 'payslip', 'bank_statement'].forEach(name => {
    const inp = loanForm.querySelector(`input[name="${name}"]`);
    if (!inp) return;
    inp.addEventListener('change', () => {
      const label = inp.closest('.field').querySelector('.file-chosen');
      if (label) label.textContent = inp.files.length ? inp.files[0].name : '';
    });
  });

  // Sync calculator amount into Step 3's loan_amount input
  const loanAmount = loanForm.querySelector('input[name="loan_amount"]');
  document.querySelectorAll('a[href$="#apply"]').forEach(a => {
    a.addEventListener('click', () => {
      if (loanAmount && amt && !loanAmount.value) loanAmount.value = amt.value;
    });
  });
  // NOTE: No submit handler — the form posts to apply.php for real backend handling.
}

// FAQ accordion
document.querySelectorAll('.q button').forEach(btn => {
  btn.addEventListener('click', () => {
    const q = btn.parentElement;
    const ans = q.querySelector('.ans');
    const open = q.classList.contains('open');
    document.querySelectorAll('.q').forEach(x => {
      x.classList.remove('open');
      x.querySelector('.ans').style.maxHeight = null;
    });
    if (!open) {
      q.classList.add('open');
      ans.style.maxHeight = ans.scrollHeight + 'px';
    }
  });
});
```

- [ ] **Step 2: Verify it loads without errors**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/assets/js/main.js
```

Expected: `200`. Open browser DevTools console on any page that loads main.js — no `ReferenceError` or `SyntaxError`.

- [ ] **Step 3: Commit**

```bash
git add assets/js/main.js
git commit -m "feat: replace main.js with feature-detected nav/calc/form/faq controllers"
```

### Task 1.4: Add `PARTNERSHIP_EMAIL` constant to env files

**Files:**
- Modify: `includes/env.php`
- Modify: `includes/env.example.php`

- [ ] **Step 1: Add the constant**

Append to both `env.php` (after `ADMIN_EMAIL`) and `env.example.php` (matching position):

```php
// Partnership enquiries (Employers band CTA on index.php)
define('PARTNERSHIP_EMAIL', 'partners@greencash.co.za');
```

- [ ] **Step 2: Verify constant is loaded**

```bash
curl -s "http://localhost/greencash/index.php" -o /dev/null -w "%{http_code}\n"
```

Expected: `200` (page still loads — constant is defined before any include uses it).

- [ ] **Step 3: Commit**

```bash
git add includes/env.php includes/env.example.php
git commit -m "feat: add PARTNERSHIP_EMAIL constant for employers band CTA"
```

---

## Chunk 2: Shared shells (`includes/header.php`, `includes/footer.php`)

After this chunk, **every customer page will render with the new shell** — but the page bodies (still using old Bootstrap classes from chunks before this redesign) will look broken. That's intentional and gets fixed page-by-page in Chunks 3–7. Don't ship without Chunks 3–7.

### Task 2.1: Rewrite `includes/header.php`

**Files:**
- Modify: `includes/header.php` (currently 64 lines — full rewrite)

- [ ] **Step 1: Replace the file**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
</head>
<body>

<header>
    <div class="wrap nav">
        <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="brand">
            <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/logo01.jpeg" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" style="height:42px">
        </a>
        <nav class="navlinks" id="navlinks">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Loans</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php">Track</a>
        </nav>
        <div class="nav-cta">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" class="btn btn-ghost btn-ghost--on-dark">Sign in</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-yellow">Apply now</a>
            <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<?php $flash = getFlash(); if ($flash):
    $type = in_array($flash['type'], ['success','error','warning','info'], true) ? $flash['type'] : 'info';
?>
<div class="wrap" style="padding-top:18px">
    <div class="flash flash--<?= $type ?>">
        <span><?= sanitize($flash['message']) ?></span>
        <button class="x" type="button" aria-label="Dismiss">×</button>
    </div>
</div>
<?php endif; ?>

<main>
```

**Note:** the `<main>` is now empty (no `class="container py-4"`). Pages that need a max-width wrapper add their own `<div class="wrap">…</div>`.

- [ ] **Step 2: Verify the header renders + flash works**

```bash
curl -s http://localhost/greencash/ | grep -c 'class="brand"'
curl -s http://localhost/greencash/ | grep -c 'class="navlinks"'
curl -s http://localhost/greencash/ | grep -c 'logo01.jpeg'
```

Expected: each returns `1`.

- [ ] **Step 3: Commit**

```bash
git add includes/header.php
git commit -m "feat: rewrite header.php with dark sticky nav, logo image, vanilla flash markup"
```

### Task 2.2: Rewrite `includes/footer.php`

**Files:**
- Modify: `includes/footer.php` (currently 29 lines — full rewrite)

- [ ] **Step 1: Replace the file**

```php
</main>

<footer>
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-brand">
                <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/logo01.jpeg" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" style="height:48px">
                <p>GreenCash gives salaried South Africans fast, fair access to the salary they've already earned. Apply online, any time.</p>
            </div>
            <div class="foot-col">
                <h4>Company</h4>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Our loans</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            </div>
            <div class="foot-col">
                <h4>Legal</h4>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php">Terms &amp; Conditions</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/privacy-policy.php">Privacy Policy (POPIA)</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php#pre-agreement">Pre-agreement disclosure</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php#complaints">Complaints procedure</a>
            </div>
            <div class="foot-col foot-contact">
                <h4>Contact</h4>
                <b>Odyssey Mall, Ballito</b>
                KwaZulu-Natal, South Africa<br>
                <b style="margin-top:8px">Open 24 hours</b>
                +27 (0)32 000 0000<br>
                hello@greencash.co.za
            </div>
        </div>
        <div class="foot-bottom">
            <div class="legal"><b>Responsible lending:</b> [NCR registration number — insert before launch]. Lending subject to affordability assessment. Representative cost of credit, interest and fees disclosed before acceptance, in compliance with the National Credit Act 34 of 2005. <i>Placeholder copy — confirm with compliance.</i></div>
            <div>© <span id="yr"></span> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>. All rights reserved.</div>
        </div>
    </div>
</footer>

<script src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/main.js" defer></script>
</body>
</html>
```

- [ ] **Step 2: Verify**

```bash
curl -s http://localhost/greencash/ | grep -c 'foot-grid'
curl -s http://localhost/greencash/ | grep -c 'Responsible lending'
curl -s http://localhost/greencash/ | grep -c 'main.js'
```

Expected: each returns `1`. The Bootstrap JS CDN line should no longer appear: `curl -s http://localhost/greencash/ | grep -c 'bootstrap.bundle.min.js'` should be `0`.

- [ ] **Step 3: Commit**

```bash
git add includes/footer.php
git commit -m "feat: rewrite footer.php with dark 4-column grid, drop Bootstrap CDN"
```

---

## Chunk 3: `index.php` (single-page artifact shape)

Largest chunk. Build the page section by section so each step is reviewable and any single section can be reverted if it breaks. After Task 3.1, the home page will be visibly broken (old markup inside a new shell) — keep going.

### Task 3.1: Strip the old index.php body, add hero + calculator

**Files:**
- Modify: `index.php` (currently 136 lines — replace body between `<?php include header ?>` and `<?php include footer ?>`)

- [ ] **Step 1: Replace the file body**

Keep the PHP top exactly as it is (`require_once 'includes/config.php'; $page_title = …; include 'includes/header.php';`), but **change `$page_title`** to `'Your Salary Boost'` (matches artifact title intent). Replace all body markup with this new hero + calculator:

```php
<?php
require_once 'includes/config.php';
$page_title = 'Your Salary Boost';
include 'includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero" id="home">
    <div class="wrap">
        <div>
            <span class="eyebrow">● Available 24 hours · 100% online</span>
            <h1>Get the salary you've <span class="hl">already earned</span> — today.</h1>
            <p class="lead">GreenCash gives salaried South Africans fast, fair access to a portion of their earned pay. No queues, no paperwork mountains. Apply in minutes and get a decision quickly.</p>
            <div class="hero-actions">
                <a href="#apply" class="btn btn-primary">Apply for cash →</a>
                <a href="#how" class="btn btn-ghost btn-ghost--on-dark">See how it works</a>
            </div>
            <div class="trust-row">
                <div class="ti"><b>R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?>–R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?></b>Loan range</div>
                <div class="ti"><b>24 hrs</b>Always open</div>
                <div class="ti"><b>Minutes</b>To apply</div>
            </div>
        </div>

        <!-- Calculator -->
        <div class="calc-card" id="calc">
            <h3>How much do you need?</h3>
            <p class="sub">Move the slider for an instant estimate.</p>
            <div class="slider-group">
                <label>Loan amount <span class="val" id="amtVal">R <?= number_format(5000, 0, '.', ' ') ?></span></label>
                <input type="range" id="amt" min="<?= (int) MIN_LOAN_AMOUNT ?>" max="<?= (int) MAX_LOAN_AMOUNT ?>" step="500" value="5000">
            </div>
            <div class="calc-out">
                <div class="calc-row"><span>Principal</span><span id="oPrincipal">R 5 000</span></div>
                <div class="calc-row"><span>Service fee (15%)</span><span id="oFees">R 750</span></div>
                <div class="calc-row total"><span>Total repayable</span><span id="oTotal">R 5 750</span></div>
            </div>
            <a href="#apply" class="btn btn-primary btn-block">Apply for this amount</a>
            <p class="calc-note">Estimate only. Final cost shown before you accept, in line with the National Credit Act.</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
```

(The footer include is temporary so the page renders during the build-out. We'll add sections above it across Tasks 3.2–3.7.)

- [ ] **Step 2: Verify**

Browser: open `http://localhost/greencash/`. Expect dark nav with logo, dark hero with green-to-yellow gradient, calculator card on the right (or below on mobile), then the footer. The calculator slider should move and update the principal/fees/total live.

```bash
curl -s http://localhost/greencash/ | grep -c 'class="hero" id="home"'
curl -s http://localhost/greencash/ | grep -c 'id="amt"'
```

Both `1`.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: rewrite index.php with artifact hero + calculator (apply.php-aligned amounts)"
```

### Task 3.2: Add trust strip + How it works

**Files:**
- Modify: `index.php` (insert before the footer include)

- [ ] **Step 1: Insert these two sections after the closing `</section>` of the hero and before `<?php include 'includes/footer.php'; ?>`:**

```php
<!-- ============ STRIP ============ -->
<div class="strip">
    <div class="wrap">
        <span>📍 <b>Odyssey Mall, Ballito</b></span>
        <span>🔒 POPIA-aligned data handling</span>
        <span>⚡ Decisions in minutes</span>
        <span>🤝 Employer salary-advance partner</span>
    </div>
</div>

<!-- ============ HOW IT WORKS ============ -->
<section class="block" id="how">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Simple &amp; fast</div>
            <h2>Four steps to your salary boost</h2>
            <p>From application to payout — designed to be quick, transparent and stress-free.</p>
        </div>
        <div class="steps">
            <div class="step"><div class="num">1</div><h4>Apply online</h4><p>Fill in the secure form below in just a few minutes, any time of day.</p></div>
            <div class="step"><div class="num">2</div><h4>Upload documents</h4><p>Add your ID, latest payslip and proof of bank account.</p></div>
            <div class="step"><div class="num">3</div><h4>Get a decision</h4><p>We assess affordability and confirm your offer quickly.</p></div>
            <div class="step"><div class="num">4</div><h4>Receive your cash</h4><p>Once accepted, funds are paid directly into your bank account.</p></div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Verify**

```bash
curl -s http://localhost/greencash/ | grep -c 'class="strip"'
curl -s http://localhost/greencash/ | grep -c 'id="how"'
```

Both `1`. Browser: strip shows under hero with 4 pills, then "Four steps" headline with numbered circles.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: index.php — add trust strip + how-it-works section"
```

### Task 3.3: Add Products section

**Files:**
- Modify: `index.php` (append before the footer include)

- [ ] **Step 1: Insert after the How-it-works section:**

```php
<!-- ============ PRODUCTS ============ -->
<section class="block" id="products" style="background:var(--paper-2)">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Our products</div>
            <h2>Borrowing built around your payday</h2>
            <p>Whether you need a small bridge or access to pay you've already earned, we have an option.</p>
        </div>
        <div class="grid-3">
            <div class="card">
                <div class="ico">💸</div>
                <h3>Payday Loan</h3>
                <p>A short-term loan from R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?> to R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?> to bridge you to your next salary.</p>
            </div>
            <div class="card">
                <span class="tag">Popular</span>
                <div class="ico y">📈</div>
                <h3>Earned Wage Access</h3>
                <p>Through partner employers, draw down a portion of the salary you've already worked for — before payday.</p>
            </div>
            <div class="card">
                <div class="ico">🔁</div>
                <h3>Repeat Customer</h3>
                <p>Built a good track record with us? Enjoy faster approvals and higher limits on your next application.</p>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Verify**

```bash
curl -s http://localhost/greencash/ | grep -c 'id="products"'
curl -s http://localhost/greencash/ | grep -c 'class="tag">Popular'
```

Both `1`.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: index.php — add products section (3 cards)"
```

### Task 3.4: Add Employers band (with `mailto:` CTA)

**Files:**
- Modify: `index.php`

- [ ] **Step 1: Insert after Products:**

```php
<!-- ============ EMPLOYERS ============ -->
<section class="block" id="employers">
    <div class="wrap">
        <div class="band">
            <div class="ct">
                <h2>Partner with GreenCash for your team</h2>
                <p>Give your employees responsible access to a portion of their earned salary — a powerful, no-cost financial wellness benefit that reduces stress and boosts retention.</p>
                <a href="mailto:<?= htmlspecialchars(PARTNERSHIP_EMAIL, ENT_QUOTES, 'UTF-8') ?>?subject=Partnership%20enquiry" class="btn btn-yellow">Become a partner →</a>
            </div>
            <ul>
                <li><span class="ck">✓</span><span>Zero cost to your business to set up.</span></li>
                <li><span class="ck">✓</span><span>Reduces payroll advance requests and admin.</span></li>
                <li><span class="ck">✓</span><span>Improves staff financial wellbeing &amp; morale.</span></li>
                <li><span class="ck">✓</span><span>Fully managed, compliant and secure.</span></li>
            </ul>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Verify**

```bash
curl -s http://localhost/greencash/ | grep -c 'class="band"'
curl -s http://localhost/greencash/ | grep -c 'mailto:partners@greencash.co.za'
```

Both `1`.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: index.php — add employers band with mailto partnership CTA"
```

### Task 3.5: Add Apply section (multi-step form, posts to apply.php)

**Files:**
- Modify: `index.php`

This is the biggest single addition (~150 lines). Take care with field names — they must match `apply.php`'s POST handler exactly.

- [ ] **Step 1: Insert after Employers band:**

```php
<!-- ============ APPLY ============ -->
<section class="apply" id="apply">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Start now</div>
            <h2>Apply for your loan</h2>
            <p>Complete the secure application below. It takes about 5 minutes. All fields marked with <span style="color:var(--yellow)">*</span> are required.</p>
        </div>

        <div class="form-shell">
            <div class="steps-bar" id="stepsBar">
                <div class="sb active" data-step="0"><div class="dot">1</div><span class="lbl">Personal</span></div>
                <div class="sb" data-step="1"><div class="dot">2</div><span class="lbl">Employment</span></div>
                <div class="sb" data-step="2"><div class="dot">3</div><span class="lbl">Financial</span></div>
                <div class="sb" data-step="3"><div class="dot">4</div><span class="lbl">Documents</span></div>
            </div>

            <form class="form-body" id="loanForm" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/apply.php" enctype="multipart/form-data" novalidate>
                <?= csrfField() ?>

                <!-- STEP 1: PERSONAL -->
                <div class="fstep active" data-step="0">
                    <h3>Personal details</h3>
                    <p class="desc">Tell us who you are. This must match your South African ID.</p>
                    <div class="fgrid">
                        <div class="field"><label>First name <span class="req">*</span></label><input name="first_name" required><div class="err">Please enter your first name.</div></div>
                        <div class="field"><label>Last name <span class="req">*</span></label><input name="last_name" required><div class="err">Please enter your last name.</div></div>
                        <div class="field"><label>Other names</label><input name="other_names"></div>
                        <div class="field"><label>SA ID number <span class="req">*</span></label><input name="id_number" inputmode="numeric" maxlength="13" required><div class="err">Enter a valid 13-digit ID number.</div></div>
                        <div class="field"><label>Mobile number <span class="req">*</span></label><input name="phone" inputmode="tel" placeholder="072 123 4567" required><div class="err">Please enter a valid mobile number.</div></div>
                        <div class="field"><label>Email address <span class="req">*</span></label><input type="email" name="email" placeholder="you@email.com" required><div class="err">Please enter a valid email.</div></div>
                        <div class="field full"><label>Residential address <span class="req">*</span></label><input name="address" placeholder="Street, suburb" required><div class="err">Please enter your address.</div></div>
                        <div class="field"><label>City / Town <span class="req">*</span></label><input name="city" required><div class="err">Required.</div></div>
                        <div class="field"><label>Province <span class="req">*</span></label>
                            <select name="province" required>
                                <option value="">Select…</option>
                                <option value="Gauteng">Gauteng</option>
                                <option value="Western Cape">Western Cape</option>
                                <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                <option value="Eastern Cape">Eastern Cape</option>
                                <option value="Limpopo">Limpopo</option>
                                <option value="Mpumalanga">Mpumalanga</option>
                                <option value="North West">North West</option>
                                <option value="Free State">Free State</option>
                                <option value="Northern Cape">Northern Cape</option>
                            </select>
                            <div class="err">Please select your province.</div>
                        </div>
                        <div class="field"><label>Postal code</label><input name="zip_code" inputmode="numeric" maxlength="10"></div>
                    </div>
                    <div class="form-nav">
                        <span></span>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 2: EMPLOYMENT -->
                <div class="fstep" data-step="1">
                    <h3>Employment</h3>
                    <p class="desc">We use this to assess affordability, as required by the National Credit Act.</p>
                    <div class="fgrid">
                        <div class="field"><label>Employment status <span class="req">*</span></label>
                            <select name="employment_status" required>
                                <option value="">Select…</option>
                                <option value="employed">Permanent / full-time</option>
                                <option value="contract">Contract</option>
                                <option value="self_employed">Self-employed</option>
                            </select>
                            <div class="err">Required.</div>
                        </div>
                        <div class="field"><label>Employment duration <span class="req">*</span></label>
                            <select name="employment_duration" required>
                                <option value="">Select…</option>
                                <option value="less_3m">Less than 3 months</option>
                                <option value="3_6m">3 – 6 months</option>
                                <option value="6_12m">6 – 12 months</option>
                                <option value="1_2y">1 – 2 years</option>
                                <option value="2_5y">2 – 5 years</option>
                                <option value="5y_plus">5+ years</option>
                            </select>
                            <div class="err">Required.</div>
                        </div>
                        <div class="field full"><label>Employer name <span class="req">*</span></label><input name="employer_name" required><div class="err">Required.</div></div>
                        <div class="field"><label>Employer contact <span class="req">*</span></label><input name="employer_contact" inputmode="tel" required><div class="err">Required.</div></div>
                        <div class="field"><label>Job title <span class="req">*</span></label><input name="job_title" required><div class="err">Required.</div></div>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 3: FINANCIAL -->
                <div class="fstep" data-step="2">
                    <h3>Financial</h3>
                    <p class="desc">Tell us about your income and the loan you need.</p>
                    <div class="fgrid">
                        <div class="field"><label>Net monthly salary (R) <span class="req">*</span></label><input name="salary_amount" inputmode="numeric" required><div class="err">Required.</div></div>
                        <div class="field"><label>Next pay date <span class="req">*</span></label><input type="date" name="next_payday_date" required><div class="err">Required.</div></div>
                        <div class="field full">
                            <label>Loan amount (R) <span class="req">*</span> <span class="val" id="loanAmtVal">R 5 000</span></label>
                            <input type="range" name="loan_amount" id="loanAmountSlider" min="<?= (int) MIN_LOAN_AMOUNT ?>" max="<?= (int) MAX_LOAN_AMOUNT ?>" step="500" value="5000">
                        </div>
                        <div class="field"><label>Rent (R/mo)</label><input name="rent" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Food (R/mo)</label><input name="food" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Transport (R/mo)</label><input name="transport" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Other expenses (R/mo)</label><input name="other_expenses" inputmode="numeric" placeholder="0"></div>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 4: DOCUMENTS & CONSENT -->
                <div class="fstep" data-step="3">
                    <h3>Documents &amp; consent</h3>
                    <p class="desc">Upload supporting documents and confirm your consent to proceed.</p>
                    <div class="field full">
                        <label>SA ID document <span class="req">*</span></label>
                        <label class="upload" for="id_document">
                            <div class="ui">📎 <span class="ub">Click to upload</span> ID copy (PDF/JPG/PNG)</div>
                        </label>
                        <input type="file" id="id_document" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your SA ID.</div>
                    </div>
                    <div class="field full">
                        <label>Latest payslip <span class="req">*</span></label>
                        <label class="upload" for="payslip">
                            <div class="ui">📎 <span class="ub">Click to upload</span> latest payslip (PDF/JPG/PNG)</div>
                        </label>
                        <input type="file" id="payslip" name="payslip" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your latest payslip.</div>
                    </div>
                    <div class="field full">
                        <label>3 months bank statements <span class="req">*</span></label>
                        <label class="upload" for="bank_statement">
                            <div class="ui">📎 <span class="ub">Click to upload</span> bank statements (single PDF or image)</div>
                        </label>
                        <input type="file" id="bank_statement" name="bank_statement" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your bank statements.</div>
                    </div>

                    <div style="margin-top:18px">
                        <div class="check"><input type="checkbox" required><span>I confirm the information provided is true and complete, and I consent to a credit &amp; affordability assessment.</span></div>
                        <div class="check"><input type="checkbox" required><span>I agree to the processing of my personal information in line with the <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/privacy-policy.php">Privacy Policy (POPIA)</a>.</span></div>
                        <div class="check"><input type="checkbox" required><span>I have read and accept the <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php">Terms &amp; Conditions</a> and pre-agreement disclosure.</span></div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="submit" name="submit_application" class="btn btn-primary">Submit application ✓</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
```

Then add this tiny script block at the very bottom of `index.php` **before** the footer include, to keep the Step 3 `loanAmountSlider` synced with its label:

```php
<script>
(function(){
  const s = document.getElementById('loanAmountSlider');
  const v = document.getElementById('loanAmtVal');
  if (!s || !v) return;
  const fmt = n => 'R ' + Math.round(n).toLocaleString('en-ZA');
  const sync = () => {
    v.textContent = fmt(+s.value);
    const min=+s.min, max=+s.max, p=((s.value-min)/(max-min))*100;
    s.style.setProperty('--p', p+'%');
  };
  s.addEventListener('input', sync); sync();
})();
</script>
```

- [ ] **Step 2: Verify markup**

```bash
curl -s http://localhost/greencash/ | grep -c 'name="first_name"'
curl -s http://localhost/greencash/ | grep -c 'name="submit_application"'
curl -s http://localhost/greencash/ | grep -c 'value="KwaZulu-Natal"'
curl -s http://localhost/greencash/ | grep -c 'name="csrf_token"'
curl -s http://localhost/greencash/ | grep -c 'data-step="0"'
```

Each `1`.

- [ ] **Step 3: Browser smoke test**

Open `http://localhost/greencash/#apply`. Click "Continue →" with empty fields — fields go red. Fill Step 1, click Continue — Step 2 shows. Repeat through Step 4. Don't actually submit (no MySQL yet, may hit duplicate guard).

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: index.php — add multi-step apply form posting to apply.php"
```

### Task 3.6: Add FAQ section

**Files:**
- Modify: `index.php`

- [ ] **Step 1: Insert after the Apply section:**

```php
<!-- ============ FAQ ============ -->
<section class="block" id="faq" style="background:var(--paper-2)">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Good to know</div>
            <h2>Frequently asked questions</h2>
        </div>
        <div class="faq">
            <div class="q"><button type="button">Who can apply for a GreenCash loan?<span class="pm">+</span></button><div class="ans"><p>You must be a salaried employee, 18 or older, a South African citizen or permanent resident, with a valid SA ID, an active bank account into which your salary is paid, and proof of income.</p></div></div>
            <div class="q"><button type="button">How much can I borrow?<span class="pm">+</span></button><div class="ans"><p>Loans range from R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?> to R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?> depending on your income and affordability assessment. First-time customers may start with a lower limit that grows as you build a good repayment history.</p></div></div>
            <div class="q"><button type="button">How fast will I get my money?<span class="pm">+</span></button><div class="ans"><p>Applications are processed quickly, often within minutes during business operations. Once approved and accepted, funds are paid directly to your bank account. Actual payout timing depends on your bank.</p></div></div>
            <div class="q"><button type="button">What documents do I need?<span class="pm">+</span></button><div class="ans"><p>A copy of your SA ID, your most recent payslip, three months of bank statements, and proof of residential address. You can upload these directly in the application form above.</p></div></div>
            <div class="q"><button type="button">What does earned wage access mean?<span class="pm">+</span></button><div class="ans"><p>If your employer partners with GreenCash, you can draw down a portion of the salary you've already worked for during the current pay cycle, rather than waiting until payday.</p></div></div>
            <div class="q"><button type="button">Is my information safe?<span class="pm">+</span></button><div class="ans"><p>Yes. We handle your personal information securely and in line with South Africa's Protection of Personal Information Act (POPIA). Your data is used only to assess and manage your application.</p></div></div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Verify**

```bash
curl -s http://localhost/greencash/ | grep -c 'id="faq"'
curl -s http://localhost/greencash/ | grep -c 'class="ans"'
```

First `1`, second `6`. Browser: click each `+` button — the answer expands and others collapse.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: index.php — add FAQ accordion (6 questions)"
```

### Task 3.7: Cross-check full index.php and remove the now-unused `calculator.php`

**Files:**
- Read: `index.php`
- Delete: `calculator.php` (now redundant — the calculator lives in the hero)

- [ ] **Step 1: Re-read `index.php` end-to-end**

Read the whole file to confirm: PHP top → header include → hero → strip → how → products → employers → apply → FAQ → footer include. No duplicate footer includes from intermediate tasks.

- [ ] **Step 2: Delete `calculator.php`**

```bash
git rm calculator.php
```

If any page links to `calculator.php`, update those links to `/#calc`. (Grep first.) `grep -rn calculator.php --include='*.php' C:/xampp/htdocs/greencash/` — if matches found in pages outside admin, update them in the same commit; admin matches are out of scope.

- [ ] **Step 3: Verify the home page loads and the calculator link redirect-equivalents work**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/calculator.php
```

First `200`. Second `404` (expected — file gone).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore: remove calculator.php (calculator now lives in index.php hero)"
```

---

## Chunk 4: `apply.php` (strip UI, keep POST handler, add GET state)

### Task 4.1: Strip UI, keep POST handler, add GET state branches

**Files:**
- Modify: `apply.php` (currently 571 lines — keep the top ~200 lines that run the POST handler; replace the HTML body)

- [ ] **Step 1: Identify the boundary**

Open `apply.php`. Find the line that starts the HTML output (typically right before `include 'includes/header.php';` or where `<!DOCTYPE` was inlined). Everything **above** that point is the POST handler and stays. Everything below — the multi-step form HTML — gets replaced.

- [ ] **Step 2: Replace the HTML body**

Below the POST handler block, replace everything with:

```php
<?php
$page_title = $alreadySubmitted ? 'Application received' : 'Apply';
include 'includes/header.php';
?>

<?php if ($alreadySubmitted): ?>
<section class="apply" style="min-height:calc(100vh - 200px);display:grid;place-items:center">
    <div class="wrap" style="max-width:640px">
        <div class="form-shell">
            <div class="form-body">
                <div class="success">
                    <div class="badge" style="background:linear-gradient(135deg,var(--yellow),#e0a800);color:var(--ink)">⏳</div>
                    <h3>We already received your application</h3>
                    <p>Hi <?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8') ?> — we got an application from you in the last 5 minutes. Our team is on it. You'll hear from us by SMS and email shortly.</p>
                    <p style="margin-top:18px"><a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Back to home →</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<?php
// Normal GET: redirect to the inline form on index.php
header('Location: ' . APP_URL . '/#apply', true, 302);
exit;
?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
```

The `header()` redirect must run before any output. Since the markup above only renders inside `if ($alreadySubmitted)`, the `else` branch's `header()` call fires before any byte is sent.

- [ ] **Step 3: Verify**

```bash
# GET without session flag → 302 to /#apply
curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" http://localhost/greencash/apply.php
```

Expected: `302 http://localhost/greencash/#apply`.

- [ ] **Step 4: Test the duplicate-guard state by hand**

In a logged-out browser, submit the form on index.php with valid data (test ID like `9001011234084` is fine for shape — backend regex only). Should redirect to `application-submitted.php`. Submit again with same ID within 5 minutes — should land on `apply.php` showing the "We already received" page.

If MySQL is off, skip this — note in task comments.

- [ ] **Step 5: Commit**

```bash
git add apply.php
git commit -m "refactor: apply.php — strip UI (moved to index.php), keep POST handler, GET redirects"
```

---

## Chunk 5: Auth pages

### Task 5.1: Restyle `login.php`

**Files:**
- Modify: `login.php`

- [ ] **Step 1: Replace body markup (keep PHP top/bottom)**

Between `include 'includes/header.php';` and `include 'includes/footer.php';`, replace with:

```php
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:480px">
        <div class="sec-head">
            <h2>Sign in to your account</h2>
            <p>Welcome back.</p>
        </div>
        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div class="field">
                    <label>Password <span class="req">*</span></label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login_submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:18px">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/forgot-password.php" style="color:var(--green-deep)">Forgot password?</a>
        </p>
    </div>
</section>
```

**Important:** the `name="login_submit"` attribute name must match whatever the existing `login.php` POST handler looks for. Before committing, `grep -n "isset(\$_POST\['" login.php` and update the button name to match.

- [ ] **Step 2: Verify**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/login.php
curl -s http://localhost/greencash/login.php | grep -c 'class="form-shell"'
```

`200` and `1`. Browser: form looks like the artifact's `.form-shell` pattern.

- [ ] **Step 3: Commit**

```bash
git add login.php
git commit -m "style: restyle login.php with form-shell + new design tokens"
```

### Task 5.2: Restyle `forgot-password.php`

**Files:**
- Modify: `forgot-password.php`

- [ ] **Step 1: Same pattern as login — replace body with a `.form-shell` and a single email field.**

The exact field names/POST submit-button name must match the existing handler. Grep first.

- [ ] **Step 2: Verify + commit**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/forgot-password.php
git add forgot-password.php
git commit -m "style: restyle forgot-password.php with form-shell"
```

### Task 5.3: Restyle `verify-otp.php`

**Files:**
- Modify: `verify-otp.php`

- [ ] **Step 1: Replace body. OTP page typically has a single 6-digit code input + resend link. Use `.form-shell` with one centered field; preserve the existing form action + input name + any session/token state.**

- [ ] **Step 2: Verify + commit**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/verify-otp.php
git add verify-otp.php
git commit -m "style: restyle verify-otp.php with form-shell"
```

### Task 5.4: Restyle `reset-password.php`

**Files:**
- Modify: `reset-password.php`

- [ ] **Step 1: Same pattern. Two password fields (new + confirm). Preserve existing reset-token hidden field and POST target.**

- [ ] **Step 2: Verify + commit**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/reset-password.php
git add reset-password.php
git commit -m "style: restyle reset-password.php with form-shell"
```

---

## Chunk 6: Track + Confirmation

### Task 6.1: Restyle `track-application.php`

**Files:**
- Modify: `track-application.php`

- [ ] **Step 1: Two states — lookup form and result display**

Lookup form uses `.form-shell` with one reference-number input. Result display uses the `.success` block with status-specific badges:

```php
<?php /* keep the existing PHP top: $page_title, POST handler, status fetch */ ?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:600px">
        <div class="sec-head">
            <h2>Track your application</h2>
            <p>Enter your reference number to check the latest status.</p>
        </div>

        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>Reference number <span class="req">*</span></label>
                    <input name="reference" placeholder="e.g. GC-123456" required>
                </div>
                <button type="submit" name="track_submit" class="btn btn-primary btn-block">Check status</button>
            </form>
        </div>

        <?php if (!empty($application)): ?>
        <div class="form-shell" style="margin-top:24px">
            <div class="form-body">
                <div class="success">
                    <?php
                    $status = $application['status'] ?? 'pending';
                    $badge = match ($status) {
                        'approved' => ['linear-gradient(135deg,var(--green),var(--green-deep))','#fff','✓'],
                        'rejected' => ['linear-gradient(135deg,var(--muted),#424a44)','#fff','×'],
                        default    => ['linear-gradient(135deg,var(--yellow),#e0a800)','var(--ink)','⏳'],
                    };
                    ?>
                    <div class="badge" style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>"><?= $badge[2] ?></div>
                    <h3>Status: <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($application['status_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="ref-no"><?= htmlspecialchars($application['reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
```

If the existing variable names differ from `$application`/`status`/`reference`, adjust accordingly — **grep first**.

- [ ] **Step 2: Verify**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/track-application.php
curl -s http://localhost/greencash/track-application.php | grep -c 'class="form-shell"'
```

- [ ] **Step 3: Commit**

```bash
git add track-application.php
git commit -m "style: restyle track-application.php with form-shell + success states"
```

### Task 6.2: Restyle `application-submitted.php`

**Files:**
- Modify: `application-submitted.php`

- [ ] **Step 1: Replace body with the centered `.success` block**

```php
<?php
// keep existing PHP top (read $_SESSION['app_reference'] / $_SESSION['app_name'], $page_title)
$page_title = 'Application received';
include 'includes/header.php';
?>

<section class="apply" style="min-height:calc(100vh - 200px);display:grid;place-items:center">
    <div class="wrap" style="max-width:640px">
        <div class="form-shell">
            <div class="form-body">
                <div class="success">
                    <div class="badge">✓</div>
                    <h3>Application received!</h3>
                    <p>Thank you<?= !empty($app_name) ? ', ' . htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8') : '' ?>. We've received your application and our team is reviewing it now. You'll get a decision and next steps by SMS and email shortly.</p>
                    <?php if (!empty($app_reference)): ?>
                    <div class="ref-no"><?= htmlspecialchars($app_reference, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <p style="margin-top:18px;font-size:13px">Questions? Call us on <b>+27 (0)32 000 0000</b> — open 24 hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Verify + commit**

```bash
git add application-submitted.php
git commit -m "style: restyle application-submitted.php with centered success block"
```

---

## Chunk 7: Contact + legal + 404

### Task 7.1: Restyle `contact.php` (with `#complaints` anchor)

**Files:**
- Modify: `contact.php`

- [ ] **Step 1: Replace body with two-section layout — contact details on top, contact form below, and the Complaints procedure section with `id="complaints"` anchor at the bottom**

```php
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap">
        <div class="sec-head">
            <h2>Contact us</h2>
            <p>Questions? We're open 24 hours.</p>
        </div>
        <div class="grid-3" style="grid-template-columns:1fr 2fr;align-items:start">
            <div class="card">
                <h3 style="margin-bottom:18px">Reach us</h3>
                <p><b>Address</b><br>Odyssey Mall, Ballito<br>KwaZulu-Natal</p>
                <p style="margin-top:14px"><b>Phone</b><br>+27 (0)32 000 0000</p>
                <p style="margin-top:14px"><b>Email</b><br>hello@greencash.co.za</p>
                <p style="margin-top:14px"><b>Hours</b><br>24 hours / 7 days</p>
            </div>

            <div class="form-shell">
                <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php" novalidate>
                    <?= csrfField() ?>
                    <h3 style="margin-bottom:6px">Send us a message</h3>
                    <p class="desc">We typically reply within one business day.</p>
                    <div class="fgrid">
                        <div class="field"><label>Your name <span class="req">*</span></label><input name="name" required></div>
                        <div class="field"><label>Email <span class="req">*</span></label><input type="email" name="email" required></div>
                        <div class="field full"><label>Subject <span class="req">*</span></label><input name="subject" required></div>
                        <div class="field full"><label>Message <span class="req">*</span></label><textarea name="message" rows="5" required></textarea></div>
                    </div>
                    <button type="submit" name="contact_submit" class="btn btn-primary">Send message</button>
                </form>
            </div>
        </div>

        <div style="margin-top:60px;max-width:780px">
            <h2 id="complaints">Complaints procedure</h2>
            <p style="color:var(--muted);margin-top:10px">If you have a complaint about our service or a loan decision, please email <b>complaints@greencash.co.za</b> with your reference number and a description. We acknowledge within 48 hours and aim to resolve within 14 business days. If you're not satisfied with our response, you can escalate to the National Credit Regulator (NCR) at <a href="https://www.ncr.org.za" style="color:var(--green-deep)">ncr.org.za</a>.</p>
        </div>
    </div>
</section>
```

If existing `contact.php` POST handler uses different input names, grep first and adjust.

- [ ] **Step 2: Verify the anchor works**

```bash
curl -s "http://localhost/greencash/contact.php" | grep -c 'id="complaints"'
```

`1`. Browser: open `http://localhost/greencash/contact.php#complaints` — page should scroll to the Complaints heading.

- [ ] **Step 3: Commit**

```bash
git add contact.php
git commit -m "style: restyle contact.php with cards + form + #complaints anchor"
```

### Task 7.2: Restyle `privacy-policy.php`

**Files:**
- Modify: `privacy-policy.php`

- [ ] **Step 1: Replace the body wrapper, keep policy content unchanged**

```php
<section class="block" style="background:var(--paper-2)">
    <div class="wrap" style="max-width:780px">
        <div class="sec-head" style="text-align:left;margin-bottom:30px">
            <div class="kicker">Legal</div>
            <h2>Privacy Policy (POPIA)</h2>
        </div>
        <div class="prose" style="line-height:1.8;color:var(--ink)">
            <?php /* PASTE EXISTING POLICY CONTENT HERE — unchanged */ ?>
        </div>
    </div>
</section>
```

Move the existing policy markup into the `.prose` block. If the existing markup uses Bootstrap classes (`.row`, `.col-md-X`), strip those — replace with paragraphs and headings only.

- [ ] **Step 2: Verify + commit**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/privacy-policy.php
git add privacy-policy.php
git commit -m "style: restyle privacy-policy.php with prose container"
```

### Task 7.3: Restyle `terms-and-conditions.php` (with `#pre-agreement` anchor)

**Files:**
- Modify: `terms-and-conditions.php`

- [ ] **Step 1: Same prose container pattern as privacy-policy. Add `id="pre-agreement"` to the heading of the pre-agreement disclosure section. If the existing page doesn't already have such a section, add one with placeholder text:**

```php
<h2 id="pre-agreement" style="margin-top:40px">Pre-agreement disclosure</h2>
<p style="color:var(--muted);margin-top:10px">[Placeholder — compliance to provide pre-agreement disclosure statement covering total cost of credit, interest rate, fees, and repayment schedule in line with the National Credit Act 34 of 2005.]</p>
```

- [ ] **Step 2: Verify anchor + commit**

```bash
curl -s "http://localhost/greencash/terms-and-conditions.php" | grep -c 'id="pre-agreement"'
git add terms-and-conditions.php
git commit -m "style: restyle terms.php with prose container + #pre-agreement anchor"
```

### Task 7.4: Restyle `404.php`

**Files:**
- Modify: `404.php`

- [ ] **Step 1: Replace body with centered 404 message**

```php
<section class="block" style="min-height:calc(100vh - 280px);display:grid;place-items:center;text-align:center">
    <div class="wrap" style="max-width:520px">
        <div style="font-family:'Sora',sans-serif;font-size:8rem;font-weight:800;color:var(--green-deep);line-height:1">404</div>
        <h2 style="margin-top:20px">We can't find that page</h2>
        <p style="color:var(--muted);margin:14px 0 30px">It may have moved, or the link is wrong. Let's get you somewhere useful.</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">← Back to home</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-ghost">Apply for a loan</a>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Verify + commit**

```bash
curl -s "http://localhost/greencash/this-does-not-exist" | grep -c "We can't find"
git add 404.php
git commit -m "style: restyle 404.php with centered jumbo number + CTAs"
```

---

## Chunk 8: QA & ship

### Task 8.1: Whole-site smoke test

- [ ] **Step 1: Loop every customer URL, expect 200 (or 302 for apply.php)**

```bash
for path in / index.php login.php forgot-password.php verify-otp.php reset-password.php track-application.php application-submitted.php contact.php privacy-policy.php terms-and-conditions.php apply.php; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/greencash${path}")
  echo "${code}  ${path}"
done
```

Expected: all `200` except `apply.php` which is `302` (redirect to `/#apply`). `verify-otp.php` and `reset-password.php` may be `403`/`302` if they require a session token — note that, not a fail.

- [ ] **Step 2: Confirm admin is unaffected**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/greencash/admin/
curl -s http://localhost/greencash/admin/ | grep -c 'bootstrap'
```

First `200` or `302` (depending on auth). Second should be ≥1 — admin still loads Bootstrap.

- [ ] **Step 3: Confirm no Bootstrap CDN on customer pages**

```bash
curl -s http://localhost/greencash/ | grep -c 'bootstrap'
curl -s http://localhost/greencash/login.php | grep -c 'bootstrap'
curl -s http://localhost/greencash/contact.php | grep -c 'bootstrap'
```

All `0`.

- [ ] **Step 4: Confirm artifact tokens are on every page**

```bash
for path in / login.php track-application.php contact.php; do
  echo -n "${path}: "
  curl -s "http://localhost/greencash${path}" | grep -o 'Plus+Jakarta+Sans\|Sora' | head -1
done
```

All should hit `Sora` (the Google Fonts URL).

### Task 8.2: Full-flow manual QA

- [ ] **Step 1: Apply form submission end-to-end**

Open `http://localhost/greencash/#apply`. Fill all 4 steps with a unique 13-digit ID (e.g., `9001011234084`). Submit. Confirm:
  - Redirect to `application-submitted.php` with a `GC-…` reference number visible
  - Apache error.log has no PHP errors
  - DB has a row in `salary_advance_applications` and 3 rows in `application_documents`

If MySQL is down: skip but note in commit message.

- [ ] **Step 2: Duplicate-guard test**

Within 5 minutes, submit the same ID again. Confirm:
  - Browser lands on `apply.php` showing the "We already received your application" yellow-badge `.success` page
  - Browser console clean
  - Apache error.log clean

- [ ] **Step 3: Visual side-by-side**

Open `c:\Users\user\Downloads\e3543a85-efd2-44f2-916b-02fa57c5aeeb.html` and `http://localhost/greencash/` in two browser tabs. Compare:
  - Hero gradient (dark green → yellow radial top-right)
  - Calculator card position & slider behavior
  - Sticky nav blur on scroll
  - Card hover lifts (translateY)
  - FAQ accordion animation

Spot any deviation, file a follow-up ticket (don't fix without scope).

- [ ] **Step 4: Mobile width check**

Resize browser to 380px wide. Burger menu replaces nav links, calc card stacks under hero text, form steps stack, footer collapses to single column. No horizontal scrollbar.

### Task 8.3: Final commit + branch ready for review

- [ ] **Step 1: Confirm everything is committed**

```bash
git status
git log --oneline staging --not main | head -30
```

Expected: ~20-25 commits since branching from `main`, all clean.

- [ ] **Step 2: (Optional) Open PR**

If the user wants to land this on main, follow the standard repo PR process. **Do not push or open PR without explicit user direction** — this redesign is large and the user may want to review the local branch first.

---

## Reference: Files touched

| File | Action | Where in plan |
|------|--------|---------------|
| `assets/css/style.css` | Rewrite | Tasks 1.1, 1.2 |
| `assets/js/main.js` | Rewrite | Task 1.3 |
| `includes/env.php`, `includes/env.example.php` | Add `PARTNERSHIP_EMAIL` | Task 1.4 |
| `includes/header.php` | Rewrite | Task 2.1 |
| `includes/footer.php` | Rewrite | Task 2.2 |
| `index.php` | Rewrite (multi-step) | Tasks 3.1–3.7 |
| `apply.php` | Strip UI, add GET states | Task 4.1 |
| `calculator.php` | **Delete** | Task 3.7 |
| `login.php` | Restyle body | Task 5.1 |
| `forgot-password.php` | Restyle body | Task 5.2 |
| `verify-otp.php` | Restyle body | Task 5.3 |
| `reset-password.php` | Restyle body | Task 5.4 |
| `track-application.php` | Restyle body | Task 6.1 |
| `application-submitted.php` | Restyle body | Task 6.2 |
| `contact.php` | Restyle body + `#complaints` anchor | Task 7.1 |
| `privacy-policy.php` | Restyle body | Task 7.2 |
| `terms-and-conditions.php` | Restyle body + `#pre-agreement` anchor | Task 7.3 |
| `404.php` | Restyle body | Task 7.4 |
| **Not touched** | | |
| `admin/**` | — | — |
| `includes/admin-header.php`, `includes/admin-footer.php` | — | — |
| `includes/broker-header.php`, `includes/broker-footer.php` | — | — |
| `broker-portal-login.php` | — | — |
| `serve-document.php` | — | — |
| `cron/**`, `database/**` | — | — |

---

## Skills to reference during execution

- `@superpowers:verification-before-completion` — before marking any task done, run the listed verification command and confirm output before claiming success.
- `@superpowers:executing-plans` (or `@superpowers:subagent-driven-development` if subagents available) — the actual execution harness.

---

## Closeout

When all 8 chunks are complete and Task 8.3 confirms the branch state, the redesign is done. Optional follow-ups (not in this plan):

1. Employer enquiry form — real backend (DB + email + admin view).
2. Server-side consent gating in `apply.php` POST handler.
3. Confirmation-modal between "Submit" and POST (replaces the dropped Step 5 Review).
4. Tagline-free logo variant.
5. Server-rendered PDF disclosure for `#pre-agreement` anchor.
