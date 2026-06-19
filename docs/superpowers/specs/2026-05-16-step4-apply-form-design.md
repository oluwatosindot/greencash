# Step 4: Public Application Form — Design Specification

**Date:** 2026-05-16
**Status:** Approved

---

## Files

| File | Description |
|------|-------------|
| `apply.php` | Multi-step salary advance application form (public) |
| `application-submitted.php` | Confirmation page shown after successful submission |
| `track-application.php` | Applicant self-service status tracker |
| `serve-document.php` | Secure admin-only file proxy for uploaded documents |
| `includes/config.php` | Append `sendApplicationConfirmation()` stub |

---

## apply.php

### Multi-step form (all in one page, JS navigation — no page reload between steps)

**Step 1 — Personal Info**
- first_name (required), last_name (required), other_names (optional)
- id_number (13-digit SA ID, required — validated with regex `^\d{13}$`)
- email (required), phone (required)
- address (required), city (required), province (select, required), zip_code (required)

**Step 2 — Employment Info**
- employment_status (select: employed/contract/self_employed, required)
- employer_name (required), employer_contact (required), job_title (required)
- employment_duration (select: less_3m/3_6m/6_12m/1_2y/2_5y/5y_plus, required)

**Step 3 — Financial Info**
- salary_amount (number, required, min=0)
- next_payday_date (date, required)
- loan_amount (range slider R1,000–R10,000 step R500, default R5,000, required)
- Monthly expenses — rent, food, transport, other_expenses (all optional, default 0)

**Step 4 — Documents**
- id_document (file, required — ID copy)
- payslip (file, required — latest payslip)
- bank_statement (file, required — 3 months bank statements as single PDF or image)
- Accepted: JPG, PNG, PDF only (validated via finfo MIME + extension whitelist)

**Step 5 — Review & Submit**
- JS-populated read-only summary of all fields entered in steps 1–4
- Submit button (name="submit_application")
- CSRF hidden field on the form
- `enctype="multipart/form-data"` on the form

### Server-side logic

1. **Duplicate guard (5-min window):** On POST, before processing, check if same id_number was submitted in the last 5 minutes. If yes: set `$_SESSION['form_submitted'] = true`, redirect to self. On GET with that session var: show "already submitted" notice instead of form.
2. **Validation:** Required fields, email format, id_number regex, province/status/duration whitelist, loan amount range.
3. **File uploads:** For each of the 3 fields — check UPLOAD_ERR_OK, finfo MIME check, extension check, generate safe filename, realpath guard, move_uploaded_file.
4. **DB insert:** `salary_advance_applications` row, then `application_documents` rows for each uploaded file.
5. **Stub email:** `sendApplicationConfirmation()` — error_log only (PHPMailer in Step 7).
6. **Success redirect:** Set `$_SESSION['app_reference']` and `$_SESSION['app_name']`, redirect to `/application-submitted.php`.

### JS (inline in apply.php)
- `nextStep(n)` / `prevStep(n)` — hide/show `.form-step` divs, update step indicator classes
- Loan slider → update display label in real time
- Before showing Step 5 (Review): populate review-summary div with values from form fields

### Layout
- Uses `includes/header.php` + `includes/footer.php` (public nav)
- Page container: `container py-5`, max-width ~720px centered
- Step indicator bar: 5 circles with labels, active state in green

---

## application-submitted.php

- Guard: if `$_SESSION['app_reference']` not set → redirect to `apply.php`
- Show reference number prominently (copy to clipboard button)
- Next steps: "track your application" link → `track-application.php`
- Clear session vars after displaying
- Uses `includes/header.php` + `includes/footer.php`

---

## track-application.php

- GET: show form with reference_number + id_number fields
- POST (verifyCsrf): query `salary_advance_applications` WHERE reference_number = ? AND id_number = ?
- If found: show status badge + submitted date + simple status timeline
- If not found: show "No application found" error
- Status timeline shows 5 stages: Received → Under Review → Approved → Disbursed (highlight current)
- Rejected applications show a red "rejected" badge instead of timeline
- Uses `includes/header.php` + `includes/footer.php`

---

## serve-document.php

- Guard: `requireAdmin()` (broker document access added in Step 9)
- Input: `$_GET['file']` — just the filename (basename only, no path)
- Realpath validation: resolved path must start with `realpath(UPLOAD_DIR)`
- DB verification: file path must exist in `application_documents` table
- Stream with correct Content-Type (finfo), Content-Disposition: inline, X-Content-Type-Options: nosniff

---

## sendApplicationConfirmation() stub

Appended to `includes/config.php`:
```php
function sendApplicationConfirmation(array $data): void {
    // Stub: replaced by PHPMailer in Step 7
    error_log(sprintf(
        'GREENCASH DEV CONFIRMATION: ref=%s name=%s email=%s amount=%.2f',
        $data['reference_number'], $data['first_name'], $data['email'], $data['loan_amount']
    ));
}
```

---

## Security Notes

- All form output uses `sanitize()` / `htmlspecialchars()` for XSS prevention
- File uploads: MIME check via finfo + extension whitelist + realpath guard — no PHP execution possible in uploads/ (protected by .htaccess)
- CSRF: `csrfField()` + `verifyCsrf()` on every POST
- Duplicate guard: prevents rapid repeat submissions with same ID number
- serve-document.php: admin guard + realpath guard + DB lookup before streaming
- Province and enum fields: server-side whitelist validation (not just client-side required)
