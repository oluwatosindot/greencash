# Frontend Redesign — Design Specification

**Date:** 2026-06-18
**Status:** Approved by user (awaiting spec-document-reviewer pass)
**Source:** Single-file artifact template at `c:\Users\user\Downloads\e3543a85-efd2-44f2-916b-02fa57c5aeeb.html` (saved to git history as the design reference)

---

## Goal

Replace the current Bootstrap-based customer-facing UI with the artifact's design system. **All colors, gradients, shadows, radii, fonts, and component styles from the artifact carry over byte-for-byte** — no reinterpretation.

The artifact is a single-page scroller. We adopt that shape for `index.php` (hero, calculator, how-it-works, products, employers, multi-step apply form, FAQ — all on one page). Other customer pages stay as separate pages but use the same shared CSS, header, and footer so the look is uniform.

Admin and broker portals are **out of scope**. They keep Bootstrap and their own includes.

---

## Files

| File | Action |
|------|--------|
| `assets/css/style.css` | **Rewrite.** Replace 155-line Bootstrap-overlay file with the artifact's full token system + component classes. |
| `assets/js/main.js` | **Rewrite.** Replace with artifact's nav-toggle, calculator, multi-step form, FAQ accordion. Each block feature-detects its target element so it's safe on every page. |
| `includes/header.php` | **Rewrite.** Sticky dark blurred nav with logo image, link list, ghost+yellow CTAs, burger. Drop Bootstrap CDN. Keep flash-message slot, restyled. |
| `includes/footer.php` | **Rewrite.** Dark `#0c1410` 4-column grid (brand / company / legal / contact) + legal bottom row. Drop Bootstrap JS CDN. |
| `index.php` | **Rewrite.** Long single-page scroller in artifact order (see Section 3). |
| `apply.php` | **Repurpose.** Remove the multi-step UI markup. Keep the POST handler exactly as-is (validation, DB insert, file upload, email stub). On GET, redirect to `/#apply`. |
| `login.php`, `forgot-password.php`, `verify-otp.php`, `reset-password.php` | **Restyle body markup.** Same backend, same POST targets, same validation, same flash messages. New `.form-shell` + `.field` classes. |
| `track-application.php`, `application-submitted.php` | **Restyle body markup.** Use `.form-shell` for the lookup card, `.success` block for the confirmation pattern. |
| `contact.php`, `privacy-policy.php`, `terms-and-conditions.php`, `404.php` | **Restyle body markup.** Typographic prose pages using `.wrap` container and the new heading/body type. |
| `includes/admin-header.php`, `includes/admin-footer.php`, `includes/broker-header.php`, `includes/broker-footer.php`, `admin/**`, `broker-portal-login.php` | **No change.** Stay on Bootstrap. |
| `assets/img/logo01.jpeg` | **Used as-is** as the nav logo. Black background blends with the dark nav by design. |

No new files are created. No DB schema changes. No backend logic changes.

---

## Design tokens (copied from artifact, unchanged)

```css
:root{
  --green:#1aa636;
  --green-deep:#0f7a26;
  --green-dark:#0a5a1c;
  --yellow:#f4c020;
  --yellow-bright:#ffd60a;
  --ink:#0c1410;
  --ink-soft:#1a241d;
  --paper:#f6f8f4;
  --paper-2:#ffffff;
  --line:#e2e8de;
  --muted:#5e6b62;
  --shadow:0 18px 50px -18px rgba(10,40,18,.35);
  --shadow-sm:0 6px 20px -10px rgba(10,40,18,.30);
  --r:18px;
  --maxw:1200px;
}
```

**Gradients (preserved byte-for-byte):**

- Hero background: `radial-gradient(1100px 600px at 80% -10%, rgba(244,192,32,.18), transparent 60%), linear-gradient(160deg, #0c1410 0%, #0a3a16 55%, #0f7a26 130%)`
- Employers band: `linear-gradient(135deg, #0a5a1c, #0f7a26)`
- Apply section: `linear-gradient(180deg, #0c1410, #0a2c12)`
- Card icon (green): `linear-gradient(135deg, var(--green), var(--green-deep))`
- Card icon (yellow): `linear-gradient(135deg, var(--yellow), #e0a800)`
- Success badge: `linear-gradient(135deg, var(--green), var(--green-deep))`
- Range slider fill: `linear-gradient(90deg, var(--green) var(--p,40%), var(--line) var(--p,40%))`

**Fonts:** Sora (400–800) for display, Plus Jakarta Sans (400–700) for body. Loaded from Google Fonts CDN.

**Component classes (port verbatim from artifact):** `.wrap`, `.btn`, `.btn-primary`, `.btn-yellow`, `.btn-ghost`, `.btn-block`, `header`, `.nav`, `.brand`, `.navlinks`, `.nav-cta`, `.burger`, `.hero`, `.eyebrow`, `.trust-row`, `.calc-card`, `.slider-group`, `.calc-out`, `.calc-row`, `.strip`, `.block`, `.sec-head`, `.kicker`, `.grid-3`, `.card`, `.steps`, `.step`, `.band`, `.faq`, `.q`, `.apply`, `.form-shell`, `.steps-bar`, `.form-body`, `.fstep`, `.fgrid`, `.field`, `.check`, `.form-nav`, `.upload`, `.success`, `.ref-no`, `footer`, `.foot-grid`, `.foot-brand`, `.foot-col`, `.foot-contact`, `.foot-bottom`.

**Two new component classes** (not in artifact, needed to replace dropped Bootstrap pieces):

```css
/* Ghost button on a dark background (e.g. hero secondary CTA) */
.btn-ghost--on-dark{color:#fff;border-color:rgba(255,255,255,.35)}
.btn-ghost--on-dark:hover{color:var(--yellow);border-color:var(--yellow)}

/* Flash messages (replaces Bootstrap .alert .alert-dismissible) */
.flash{
  position:relative;
  display:flex;align-items:flex-start;gap:12px;
  background:var(--paper-2);border:1px solid var(--line);border-left:4px solid var(--green);
  border-radius:12px;padding:14px 44px 14px 18px;margin-bottom:14px;
  font-size:14.5px;color:var(--ink);
  box-shadow:var(--shadow-sm);
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

Vanilla-JS dismisser lives in `main.js`:

```js
document.querySelectorAll('.flash .x').forEach(btn =>
  btn.addEventListener('click', () => btn.closest('.flash').remove())
);
```

---

## Section 2 — Shared shells

### `includes/header.php`

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= page title logic, unchanged ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<header>
  <div class="wrap nav">
    <a href="<?= APP_URL ?>" class="brand">
      <img src="<?= APP_URL ?>/assets/img/logo01.jpeg" alt="GreenCash" style="height:42px">
    </a>
    <nav class="navlinks" id="navlinks">
      <a href="<?= APP_URL ?>/#how">How it works</a>
      <a href="<?= APP_URL ?>/#products">Loans</a>
      <a href="<?= APP_URL ?>/#employers">For employers</a>
      <a href="<?= APP_URL ?>/#faq">FAQ</a>
      <a href="<?= APP_URL ?>/track-application.php">Track</a>
    </nav>
    <div class="nav-cta">
      <a href="<?= APP_URL ?>/login.php" class="btn btn-ghost">Sign in</a>
      <a href="<?= APP_URL ?>/#apply" class="btn btn-yellow">Apply now</a>
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

**Notes:**
- All nav `href`s are absolute paths (`<?= APP_URL ?>/#how`) so links work from subpages, not just from index.
- "Track" replaces the artifact's "Apply" link in the nav, since the yellow CTA already covers Apply. Lets users find the status tracker without a footer dive.
- The `<main class="container py-4">` wrapper from the current header is replaced with an empty `<main>` so individual pages own their own layout. Pages that need a max-width container wrap their content in `.wrap`.

### `includes/footer.php`

```html
</main>
<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <img src="<?= APP_URL ?>/assets/img/logo01.jpeg" alt="GreenCash" style="height:48px">
        <p>GreenCash gives salaried South Africans fast, fair access to the salary they've already earned. Apply online, any time.</p>
      </div>
      <div class="foot-col">
        <h4>Company</h4>
        <a href="<?= APP_URL ?>/#how">How it works</a>
        <a href="<?= APP_URL ?>/#products">Our loans</a>
        <a href="<?= APP_URL ?>/#employers">For employers</a>
        <a href="<?= APP_URL ?>/#faq">FAQ</a>
      </div>
      <div class="foot-col">
        <h4>Legal</h4>
        <a href="<?= APP_URL ?>/terms-and-conditions.php">Terms &amp; Conditions</a>
        <a href="<?= APP_URL ?>/privacy-policy.php">Privacy Policy (POPIA)</a>
        <a href="<?= APP_URL ?>/terms-and-conditions.php#pre-agreement">Pre-agreement disclosure</a>
        <a href="<?= APP_URL ?>/contact.php#complaints">Complaints procedure</a>
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
      <div>© <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</div>
    </div>
  </div>
</footer>
<script src="<?= APP_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
```

**Compliance placeholder is preserved as-is.** Spec does not invent NCR registration or rate text — those are for the compliance team to fill in.

---

## Section 3 — `index.php` (artifact-shape single page)

Single PHP file. Sections in this order, copied verbatim from artifact:

1. **Hero** (`<section class="hero" id="home">`) — eyebrow pill, headline with `.hl` yellow span, lead paragraph, two CTAs (`.btn-primary` "Apply for cash" → `#apply`, `.btn-ghost btn-ghost--on-dark` "See how it works" → `#how`), trust-row with three `<b>` stats. **Calculator card** on the right (`.calc-card`). The `--on-dark` modifier replaces the artifact's inline `style="color:#fff;border-color:rgba(255,255,255,.35)"` so the pattern is reusable on any other dark section.
2. **Strip** (`<div class="strip">`) — four pills (location, POPIA, decisions, employer-partner).
3. **How it works** (`<section class="block" id="how">`) — sec-head + 4 `.step` cards with numbered circles.
4. **Products** (`<section class="block" id="products">`) — 3 cards (Payday Loan, Earned Wage Access [with `.tag` "Popular"], Repeat Customer).
5. **Employers band** (`<section class="block" id="employers">`) — green gradient `.band` with copy + checklist.
6. **Apply** (`<section class="apply" id="apply">`) — toggle tabs + two `.form-shell`s (applicant 4-step + employer enquiry).
7. **FAQ** (`<section class="block" id="faq">`) — 6 accordion items.

Footer comes from `includes/footer.php`.

### Calculator wiring

The artifact's calculator uses an illustrative `0.05 monthly + initFee` formula. **Replace with the existing site's flat 15% / 1-month model** so the on-page estimate matches the backend (`assets/js/main.js` already has `FEE_RATE = 0.15`, `includes/config.php` has `LOAN_TERM_MONTHS = 1`).

**Slider range:** R1,000–R10,000 in R500 steps (matches existing `apply.php` validation, not the artifact's R500–R8,000).

**Term:** Fixed at 1 month. The artifact's term slider is **removed** — the backend doesn't support multi-month repayment. Re-introduce when/if the backend grows that capability.

**Calculator JS:**

```js
const FEE_RATE = 0.15;
function calc() {
  const a = +amt.value;
  const fee = a * FEE_RATE;
  amtVal.textContent = fmt(a);
  oPrincipal.textContent = fmt(a);
  oFees.textContent = fmt(fee);
  oTotal.textContent = fmt(a + fee);
  paintSlider(amt);
}
```

### Apply form wiring

**Direction chosen:** **Shrink the artifact form to match the existing backend.** The backend (apply.php) is the source of truth; bank-detail collection is intentionally post-approval, not at application time. The artifact's banking/dob/pay-frequency/gross-net/purpose fields are removed. Nothing in apply.php's POST handler changes.

The form's 4 steps map **exactly** to apply.php's field names, types, and whitelists (verified by reading apply.php lines 33–82):

**Step 1 — Personal**

| Field | Input | Required | Notes |
|-------|-------|----------|-------|
| `first_name` | text | ✓ | |
| `last_name` | text | ✓ | (was `surname` in artifact) |
| `other_names` | text | — | (new field, not in artifact) |
| `id_number` | text, `inputmode="numeric"`, `maxlength="13"` | ✓ | regex `^\d{13}$` |
| `email` | email | ✓ | |
| `phone` | text, `inputmode="tel"` | ✓ | (was `mobile` in artifact) |
| `address` | text | ✓ | |
| `city` | text | ✓ | |
| `province` | **select** | ✓ | Options below |
| `zip_code` | text, `inputmode="numeric"` | — | Optional — apply.php has no server-side requirement; matches current behavior. (was `postal` in artifact) |

**Drop from artifact:** `dob` (backend derives from `id_number`).

**Province options (value = label, exact strings from `$validProvinces`):**

```html
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
```

**Step 2 — Employment**

| Field | Input | Required | Options / notes |
|-------|-------|----------|-----------------|
| `employment_status` | select | ✓ | `employed` → "Permanent / full-time", `contract` → "Contract", `self_employed` → "Self-employed" |
| `employer_name` | text | ✓ | |
| `employer_contact` | text, `inputmode="tel"` | ✓ | (new vs. artifact) |
| `job_title` | text | ✓ | |
| `employment_duration` | select | ✓ | See options below |

**`employment_duration` options (value → display label):**

```html
<option value="">Select…</option>
<option value="less_3m">Less than 3 months</option>
<option value="3_6m">3 – 6 months</option>
<option value="6_12m">6 – 12 months</option>
<option value="1_2y">1 – 2 years</option>
<option value="2_5y">2 – 5 years</option>
<option value="5y_plus">5+ years</option>
```

**Drop from artifact:** `empType` (collapsed into `employment_status`), `payFreq`, `nextPay` moves to Step 3 as `next_payday_date`, `grossPay`, `netPay`.

**Step 3 — Financial**

| Field | Input | Required | Notes |
|-------|-------|----------|-------|
| `salary_amount` | text, `inputmode="numeric"` | ✓ | Net monthly take-home (single field, backend asks for `salary_amount > 0`) |
| `next_payday_date` | date | ✓ | |
| `loan_amount` | **range slider** | ✓ | `MIN_LOAN_AMOUNT`–`MAX_LOAN_AMOUNT` (R1,000–R10,000), step R500 |
| `rent` | text, `inputmode="numeric"` | — | Default 0 |
| `food` | text, `inputmode="numeric"` | — | Default 0 |
| `transport` | text, `inputmode="numeric"` | — | Default 0 |
| `other_expenses` | text, `inputmode="numeric"` | — | Default 0 |

**Critical:** The four expense fields **must be split** as shown — apply.php reads each individually with `(float)$_POST['rent']` etc. A single `expenses` field would write 0 to all four columns.

**Drop from artifact:** `purpose`, `bank`, `accType`, `accNumber`, `accHolder` (banking captured post-approval).

**Step 4 — Documents & consent**

| Field | Input | Required | Notes |
|-------|-------|----------|-------|
| `id_document` | file | ✓ | jpg/jpeg/png/pdf |
| `payslip` | file | ✓ | jpg/jpeg/png/pdf |
| `bank_statement` | file | ✓ | jpg/jpeg/png/pdf |
| Consent checkboxes | checkbox × 4 | ✓ (client-side only) | NCA, POPIA, Terms, Marketing (optional). **Presentational only** — apply.php's POST handler does not validate consent server-side. Required attribute is enforced by JS step-validation, not the backend. If product/legal needs server-side consent gating, that's a separate ticket. |

**Submit button:**

```html
<button type="submit" name="submit_application" class="btn btn-primary">
  Submit application ✓
</button>
```

The `name="submit_application"` is **load-bearing** — apply.php gates POST handling on `isset($_POST['submit_application'])` (line 15). Missing it = silent no-op.

**Form element:**

```html
<form action="<?= APP_URL ?>/apply.php" method="post" enctype="multipart/form-data" id="loanForm" novalidate>
  <?= csrfField() ?>
  <!-- 4 .fstep divs -->
</form>
```

`csrfField()` is confirmed to exist (`includes/config.php:92`); pairs with `verifyCsrf()` (line 103).

**Client-side behavior:**
- Multi-step navigation, per-step validation, file-list display — identical to artifact JS.
- The artifact's `submitApplication()` stub is **deleted**. The form posts to apply.php for real handling.
- On apply.php success, server-side redirects to `/application-submitted.php` with `$_SESSION['app_reference']` and `$_SESSION['app_name']` (existing behavior, unchanged).
- On apply.php validation errors, server redirects back to `/#apply` with errors flashed (existing flash mechanism — see flash component spec below).

### Employer enquiry form — DEFERRED

**Removed from this PR.** Building a real partnership-enquiry backend (DB table, email pipeline, admin view) is its own scope. For this redesign:

- The **Employers band** stays (artifact section 5) — green gradient, checklist, "Become a partner →" CTA.
- The CTA links to `mailto:partners@greencash.co.za?subject=Partnership enquiry` for now (configurable via `PARTNERSHIP_EMAIL` constant in `includes/config.php` — added in this PR as a one-line addition; safe to change later without a re-deploy of the spec).
- The artifact's `#tabEmployer` toggle, `#employerShell` form, and `partnerForm` JS handler are **omitted** from index.php.
- The "I'm an employee / I'm an employer" tab UI above the form is **removed** — the apply section just shows the applicant form directly.

**Follow-up ticket:** real partner-enquiry endpoint + admin view. Not in this PR.

### apply.php — GET behavior

The existing `apply.php` GET handler serves the "already submitted" notice when `$_SESSION['form_submitted']` is set (lines 8–13). The spec must preserve this UX state.

**apply.php after redesign:**

- **POST:** unchanged — full handler runs (duplicate guard, validation, file upload, DB insert, email stub, redirect to `/application-submitted.php`).
- **GET with `$_SESSION['form_submitted']` set:** render a minimal "already submitted" page using the new `.success` component (yellow badge — pending state — not green; "We already received an application from you in the last 5 minutes" copy; reference number; CTA back to `/`). Page uses `includes/header.php` + `includes/footer.php` for nav/footer consistency.
- **GET without session flag:** 302 redirect to `/#apply` (where the inline form lives).

### Calculator copy alignment

- The artifact's `R500–R8 000` copy in hero trust-row, calculator caption, and FAQ ("How much can I borrow?") is **rewritten to `R1,000–R10,000`** to match `MIN_LOAN_AMOUNT`/`MAX_LOAN_AMOUNT`.

---

## Section 4 — Other customer pages

Each page keeps its existing PHP top (`require_once 'includes/config.php'`, page-level POST handling, $page_title, `include 'includes/header.php'`) and its existing PHP bottom (`include 'includes/footer.php'`). Only the HTML body markup between them is rewritten.

### Auth pages (`login.php`, `forgot-password.php`, `verify-otp.php`, `reset-password.php`)

Pattern: centered `.form-shell` (max-width 480px) on a paper background, headline + sublead, fields using `.field` class, primary CTA. Same form `action`, same input names, same flash messages.

```html
<section class="block" style="background:var(--paper); min-height:calc(100vh - 200px)">
  <div class="wrap" style="max-width:480px">
    <div class="sec-head">
      <h2>Sign in to your account</h2>
      <p>Welcome back.</p>
    </div>
    <div class="form-shell">
      <form class="form-body" method="post" action="<?= APP_URL ?>/login.php">
        <?= csrfField() ?>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
      </form>
    </div>
    <p style="text-align:center;margin-top:18px"><a href="<?= APP_URL ?>/forgot-password.php" style="color:var(--green-deep)">Forgot password?</a></p>
  </div>
</section>
```

### `track-application.php`

`.form-shell` lookup card → status display. Status states use the artifact's `.success` badge pattern with color variants:
- **Approved:** green badge `linear-gradient(135deg, var(--green), var(--green-deep))`, checkmark icon
- **Pending:** yellow badge using `var(--yellow)`, clock icon
- **Rejected:** muted gray badge `linear-gradient(135deg, var(--muted), #424a44)`, x icon

### `application-submitted.php`

Single centered `.success` block:

```html
<section class="apply" style="min-height:calc(100vh - 120px);display:grid;place-items:center">
  <div class="wrap" style="max-width:640px">
    <div class="form-shell">
      <div class="form-body">
        <div class="success">
          <div class="badge">✓</div>
          <h3>Application received!</h3>
          <p>Thank you, <?= $app_name ?>. Our team is reviewing your application now.</p>
          <div class="ref-no"><?= $app_reference ?></div>
          <p style="margin-top:18px;font-size:13px">Questions? Call us on <b>+27 (0)32 000 0000</b> — open 24 hours.</p>
        </div>
      </div>
    </div>
  </div>
</section>
```

### `contact.php`

Two-column `.grid-2` (defined as the new utility): contact details left, form right. Contact details mirror the footer's address/hours/phone/email.

### `privacy-policy.php`, `terms-and-conditions.php`

Single-column typographic prose. `.wrap` container, `max-width: 760px`, headings in Sora, body in Plus Jakarta Sans. Content (the actual policy text) is **unchanged** — only the surrounding markup and typography change.

### `404.php`

Centered: big "404" in Sora 8rem, message, two pill CTAs (`.btn-primary` → Home, `.btn-ghost` → Apply).

---

## Section 5 — JavaScript (`assets/js/main.js`)

Rewritten as one file, feature-detected so it's safe to load on every page:

```js
// Year
const yr = document.getElementById('yr'); if (yr) yr.textContent = new Date().getFullYear();

// Mobile nav
const burger = document.getElementById('burger');
const navlinks = document.getElementById('navlinks');
if (burger && navlinks) {
  burger.addEventListener('click', () => navlinks.classList.toggle('open'));
  navlinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => navlinks.classList.remove('open')));
}

// Calculator
if (document.getElementById('amt')) { /* … artifact calc, but FEE_RATE=0.15 fixed-1-month … */ }

// Apply form tabs
if (document.getElementById('tabApplicant')) { /* … artifact tab toggle … */ }

// Multi-step form
if (document.getElementById('loanForm')) {
  // Steps, validation, file-list display
  // NOTE: submit is NOT preventDefault'd — form posts to apply.php for real backend handling
  // The artifact's submitApplication() stub is removed
}

// FAQ accordion
document.querySelectorAll('.q button').forEach(btn => { /* … artifact accordion … */ });
```

Existing `assets/js/main.js` (55 lines, just the calculator) is fully superseded.

---

## What is NOT changing

- Database schema, models, all PHP backend logic (validation, file uploads, sessions, CSRF, email stubs, cron jobs, document proxy).
- Admin panel (`admin/**`), `includes/admin-header.php`, `includes/admin-footer.php` — still Bootstrap 5.
- Broker portal (`includes/broker-header.php`, `includes/broker-footer.php`, `broker-portal-login.php`) — still Bootstrap 5.
- `serve-document.php`, `cron/`, `database/` — backend only.
- `includes/config.php` — no token/rate/constant changes. `getServiceFeeRate()` / `FEE_RATE` / `LOAN_TERM_MONTHS` unchanged.

---

## Risks & open items

1. **CSS class collision during rollout.** Old `style.css` defines `.hero`, `.step-card`, `.btn-gc`, `.calculator-widget`, `.trust-badge`, `.section-title`, `.section-subtitle`. These are replaced with the new class names in the same change. Any page not restyled in this PR will render unstyled — mitigated by restyling **all** customer pages in one PR (listed in Files table).
2. **Bootstrap removed from customer pages but still loaded on admin.** Acceptable — admin uses its own `admin-header.php` which still loads Bootstrap. The Bootstrap CDN link is removed from `includes/header.php` only.
3. **Employer partner form is DEFERRED.** Removed from this PR entirely. Employers band CTA becomes a `mailto:` link (via new `PARTNERSHIP_EMAIL` constant in `includes/config.php`). Real form + admin view is a follow-up ticket.
4. **Logo readability.** `logo01.jpeg` contains a "YOUR SALARY BOOST" tagline that will be illegible at the artifact's 42px nav height. Acceptable as a decorative wordmark; a tagline-free variant can replace it later without code changes.
5. **Compliance placeholders preserved.** NCR registration number and responsible-lending copy are left as bracketed placeholders in `includes/footer.php`. Compliance team to fill in before production launch.
6. **Term slider dropped.** Artifact has a 1–6 month term slider; backend supports `LOAN_TERM_MONTHS = 1` only. Reintroduce when backend grows multi-term capability.
7. **`dob` field dropped.** Artifact has a date-of-birth input; existing site derives DOB from SA ID. Dropped to avoid schema divergence.
8. **Banking-detail fields dropped from form.** Artifact's bank-name/account-type/account-number/account-holder are removed — banking is captured post-approval. If product requires up-front collection, that's a separate spec.
9. **`other_names` field added vs. artifact.** Backend accepts it; surfacing it in the personal step matches backend completeness.
10. **Employment status enum collapse.** Artifact's "Part-time" option has no backend value and is dropped. Backend supports `employed/contract/self_employed` only.
11. **Pre-agreement / Complaints links use `#`-anchors.** Footer links to `terms-and-conditions.php#pre-agreement` and `contact.php#complaints`. Those anchor IDs must exist on those pages — add `<h2 id="pre-agreement">` and `<h2 id="complaints">` sections when restyling those pages.
12. **"Review" step intentionally dropped.** Current `apply.php` UI has 5 steps (Personal/Employment/Financial/Documents/**Review**). The artifact has 4 — Documents step doubles as the final consent + submit step. Users lose the "review-before-submit" affordance. Acceptable UX trade-off (the artifact's pattern is industry-standard for high-conversion loan forms); flagged here for visibility. If needed, a confirmation modal between "Submit" and the POST is a cheap follow-up.
13. **Consent checkboxes are client-side only.** apply.php does not validate consent flags on POST. New form's `required` attribute on consent boxes is JS-enforced, not server-enforced. If legal/compliance demands a server gate, that's a follow-up (add `consent_nca`, `consent_popia`, `consent_terms` validation to apply.php's handler).
14. **No JS framework.** Plain vanilla DOM JS. If complexity grows, a small framework (Alpine, htmx) may help — out of scope for this redesign.

---

## Success criteria

- Visiting `/` shows the artifact's exact look-and-feel: dark sticky nav with logo, hero with calculator, the 7 sections in order, dark footer.
- All token values (colors, gradients, shadows, radii) match the artifact's CSS variables exactly.
- Sora and Plus Jakarta Sans render correctly across browsers.
- Multi-step form on index posts to `apply.php` and the existing backend processes it without changes; success redirects to `application-submitted.php` showing the new `.success` styling.
- `/login.php`, `/track-application.php`, `/contact.php`, `/privacy-policy.php`, `/terms-and-conditions.php`, `/404.php` all render with the new shared header/footer and restyled bodies.
- `/admin/` still loads with Bootstrap, unaffected.
- Mobile nav burger works; calculator slider works; FAQ accordion works; multi-step form steps + validation + file-list work.
- Footer's "Pre-agreement disclosure" link scrolls to `<h2 id="pre-agreement">` on terms-and-conditions.php. Footer's "Complaints procedure" link scrolls to `<h2 id="complaints">` on contact.php. Both anchors must be added when those pages are restyled.
