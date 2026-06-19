# Step 4: Public Application Form — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the public salary advance application form, confirmation page, status tracker, and secure document proxy.

**Files:**
- Modify: `includes/config.php` (append `sendApplicationConfirmation()`)
- Create: `apply.php`
- Create: `application-submitted.php`
- Create: `track-application.php`
- Create: `serve-document.php`

---

## Chunk 1: Config stub + apply.php

---

### Task 1: Append `sendApplicationConfirmation()` to config.php

- [ ] **Step 1: Append the stub function**

Add this at the very end of `includes/config.php`:

```php

/**
 * Send application confirmation email (stub — replaced by PHPMailer in Step 7).
 */
function sendApplicationConfirmation(array $data): void {
    error_log(sprintf(
        'GREENCASH DEV CONFIRMATION: ref=%s name=%s email=%s amount=%.2f',
        $data['reference_number'],
        $data['first_name'],
        $data['email'],
        $data['loan_amount']
    ));
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l includes/config.php
```

- [ ] **Step 3: Commit**

```bash
git add includes/config.php
git commit -m "feat: add sendApplicationConfirmation stub to config"
```

---

### Task 2: Create `apply.php`

- [ ] **Step 1: Create `apply.php` with the complete implementation**

```php
<?php
require_once 'includes/config.php';

$page_title = 'Apply for a Salary Advance';
$errors     = [];

// Duplicate guard redirect: show "already submitted" notice
$alreadySubmitted = false;
if (!empty($_SESSION['form_submitted'])) {
    $alreadySubmitted  = true;
    $applicantName     = $_SESSION['applicant_name'] ?? 'there';
    unset($_SESSION['form_submitted'], $_SESSION['applicant_name']);
}

if (!$alreadySubmitted && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    verifyCsrf();

    // Duplicate submission guard (same ID within 5 minutes)
    $submittedId = trim($_POST['id_number'] ?? '');
    if (!empty($submittedId)) {
        $dup = $pdo->prepare(
            "SELECT id FROM salary_advance_applications
             WHERE id_number = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1"
        );
        $dup->execute([$submittedId]);
        if ($dup->fetch()) {
            $_SESSION['form_submitted'] = true;
            $_SESSION['applicant_name'] = sanitize(trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')));
            redirect('/apply.php');
        }
    }

    // Collect & sanitize
    $data = [
        'first_name'          => sanitize(trim($_POST['first_name']          ?? '')),
        'last_name'           => sanitize(trim($_POST['last_name']           ?? '')),
        'other_names'         => sanitize(trim($_POST['other_names']         ?? '')),
        'id_number'           => sanitize(trim($_POST['id_number']           ?? '')),
        'email'               => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'phone'               => sanitize(trim($_POST['phone']               ?? '')),
        'address'             => sanitize(trim($_POST['address']             ?? '')),
        'city'                => sanitize(trim($_POST['city']                ?? '')),
        'province'            => sanitize(trim($_POST['province']            ?? '')),
        'zip_code'            => sanitize(trim($_POST['zip_code']            ?? '')),
        'employment_status'   => sanitize(trim($_POST['employment_status']   ?? '')),
        'employer_name'       => sanitize(trim($_POST['employer_name']       ?? '')),
        'employer_contact'    => sanitize(trim($_POST['employer_contact']    ?? '')),
        'job_title'           => sanitize(trim($_POST['job_title']           ?? '')),
        'employment_duration' => sanitize(trim($_POST['employment_duration'] ?? '')),
        'salary_amount'       => (float) ($_POST['salary_amount']  ?? 0),
        'next_payday_date'    => sanitize(trim($_POST['next_payday_date']    ?? '')),
        'loan_amount'         => (float) ($_POST['loan_amount']    ?? 5000),
        'rent'                => (float) ($_POST['rent']           ?? 0),
        'food'                => (float) ($_POST['food']           ?? 0),
        'transport'           => (float) ($_POST['transport']      ?? 0),
        'other_expenses'      => (float) ($_POST['other_expenses'] ?? 0),
    ];

    // Whitelists for enum fields
    $validProvinces  = ['Gauteng','Western Cape','KwaZulu-Natal','Eastern Cape','Limpopo','Mpumalanga','North West','Free State','Northern Cape'];
    $validStatuses   = ['employed','contract','self_employed'];
    $validDurations  = ['less_3m','3_6m','6_12m','1_2y','2_5y','5y_plus'];

    // Validation
    if (empty($data['first_name']))                                       $errors[] = 'First name is required.';
    if (empty($data['last_name']))                                        $errors[] = 'Last name is required.';
    if (empty($data['id_number']) || !preg_match('/^\d{13}$/', $data['id_number'])) $errors[] = 'A valid 13-digit SA ID number is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))               $errors[] = 'A valid email address is required.';
    if (empty($data['phone']))                                            $errors[] = 'Phone number is required.';
    if (empty($data['address']))                                          $errors[] = 'Street address is required.';
    if (empty($data['city']))                                             $errors[] = 'City is required.';
    if (!in_array($data['province'], $validProvinces, true))              $errors[] = 'Please select a valid province.';
    if (!in_array($data['employment_status'], $validStatuses, true))      $errors[] = 'Please select a valid employment status.';
    if (empty($data['employer_name']))                                    $errors[] = 'Employer name is required.';
    if (empty($data['employer_contact']))                                 $errors[] = 'Employer contact number is required.';
    if (empty($data['job_title']))                                        $errors[] = 'Job title is required.';
    if (!in_array($data['employment_duration'], $validDurations, true))   $errors[] = 'Please select your employment duration.';
    if ($data['salary_amount'] <= 0)                                      $errors[] = 'Monthly salary is required.';
    if (empty($data['next_payday_date']) || strtotime($data['next_payday_date']) === false) $errors[] = 'A valid next payday date is required.';
    if ($data['loan_amount'] < MIN_LOAN_AMOUNT || $data['loan_amount'] > MAX_LOAN_AMOUNT) {
        $errors[] = 'Loan amount must be between ' . formatCurrency(MIN_LOAN_AMOUNT) . ' and ' . formatCurrency(MAX_LOAN_AMOUNT) . '.';
    }

    // File uploads
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'pdf'];
    $uploadedDocs = [];
    $requiredDocs = [
        'id_document'    => 'ID Document',
        'payslip'        => 'Payslip',
        'bank_statement' => 'Bank Statement',
    ];

    foreach ($requiredDocs as $field => $label) {
        if (empty($_FILES[$field]['name'])) {
            $errors[] = $label . ' is required.';
            continue;
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $label . ': upload error (code ' . (int) $_FILES[$field]['error'] . ').';
            continue;
        }

        $file     = $_FILES[$field];
        $origName = basename($file['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true) || !in_array($ext, $allowedExts, true)) {
            $errors[] = $label . ': only JPG, PNG, or PDF files are accepted.';
            continue;
        }

        $safeName  = $field . '_' . uniqid('', true) . '.' . $ext;
        $uploadDir = realpath(UPLOAD_DIR);
        $target    = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

        if (strpos($target, $uploadDir) !== 0) {
            $errors[] = $label . ': invalid file path.';
            continue;
        }

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $uploadedDocs[] = ['type' => $field, 'name' => $origName, 'path' => 'uploads/' . $safeName];
        } else {
            $errors[] = $label . ': could not save file. Please try again.';
        }
    }

    // Save to DB
    if (empty($errors)) {
        $ref           = generateReference();
        $repaymentDate = date('Y-m-d', strtotime($data['next_payday_date']));

        $stmt = $pdo->prepare("
            INSERT INTO salary_advance_applications
              (reference_number, source,
               first_name, last_name, other_names, id_number,
               email, phone, address, city, province, zip_code,
               employment_status, employer_name, employer_contact, job_title, employment_duration,
               salary_amount, next_payday_date, loan_amount, repayment_date,
               rent, food, transport, other_expenses)
            VALUES (?, 'public', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ref,
            $data['first_name'], $data['last_name'], $data['other_names'], $data['id_number'],
            $data['email'], $data['phone'],
            $data['address'], $data['city'], $data['province'], $data['zip_code'],
            $data['employment_status'], $data['employer_name'], $data['employer_contact'],
            $data['job_title'], $data['employment_duration'],
            $data['salary_amount'], $data['next_payday_date'],
            $data['loan_amount'], $repaymentDate,
            $data['rent'], $data['food'], $data['transport'], $data['other_expenses'],
        ]);

        $appId = (int) $pdo->lastInsertId();

        foreach ($uploadedDocs as $doc) {
            $pdo->prepare(
                "INSERT INTO application_documents (application_id, document_type, file_name, file_path)
                 VALUES (?, ?, ?, ?)"
            )->execute([$appId, $doc['type'], $doc['name'], $doc['path']]);
        }

        sendApplicationConfirmation([
            'first_name'       => $data['first_name'],
            'email'            => $data['email'],
            'reference_number' => $ref,
            'loan_amount'      => $data['loan_amount'],
        ]);

        $_SESSION['app_reference'] = $ref;
        $_SESSION['app_name']      = $data['first_name'];
        redirect('/application-submitted.php');
    }
}

include 'includes/header.php';
?>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-8">

<?php if ($alreadySubmitted): ?>
<!-- Duplicate submission notice -->
<div class="card border-0 shadow-sm text-center p-5">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
    <h3 class="mt-3">Already Submitted</h3>
    <p class="text-muted">Hi <?= sanitize($applicantName) ?>, it looks like you've already submitted an application recently. Please wait a few minutes before applying again, or <a href="track-application.php">track your existing application</a>.</p>
    <a href="index.php" class="btn btn-outline-success mt-2">Back to Home</a>
</div>

<?php else: ?>
<!-- Page header -->
<div class="text-center mb-4">
    <h2 class="fw-bold">Apply for a Salary Advance</h2>
    <p class="text-muted">R<?= number_format(MIN_LOAN_AMOUNT) ?>–R<?= number_format(MAX_LOAN_AMOUNT) ?> · 1-month term · Fast approval</p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Step indicators -->
<div class="d-flex align-items-center mb-4" id="stepIndicators">
    <?php $steps = ['Personal','Employment','Financial','Documents','Review']; ?>
    <?php foreach ($steps as $i => $label): ?>
        <?php $n = $i + 1; ?>
        <div class="text-center flex-fill step-ind" id="ind-<?= $n ?>">
            <div class="step-circle mx-auto <?= $n === 1 ? 'active' : '' ?>"><?= $n ?></div>
            <div class="step-label small d-none d-md-block"><?= $label ?></div>
        </div>
        <?php if ($n < 5): ?><div class="step-line flex-fill"></div><?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
<div class="card-body p-4">

<form method="POST" enctype="multipart/form-data" id="applyForm" novalidate>
    <?= csrfField() ?>

    <!-- ── Step 1: Personal Info ─────────────────────────────── -->
    <div class="form-step" id="step-1">
        <h5 class="mb-3 text-success">Step 1 — Personal Information</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= sanitize($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= sanitize($_POST['last_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Other Names</label>
                <input type="text" name="other_names" class="form-control" value="<?= sanitize($_POST['other_names'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">SA ID Number <span class="text-danger">*</span></label>
                <input type="text" name="id_number" class="form-control" maxlength="13" pattern="\d{13}" inputmode="numeric"
                       placeholder="13 digits" value="<?= sanitize($_POST['id_number'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control" placeholder="e.g. 0821234567" value="<?= sanitize($_POST['phone'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Street Address <span class="text-danger">*</span></label>
                <input type="text" name="address" class="form-control" value="<?= sanitize($_POST['address'] ?? '') ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                <input type="text" name="city" class="form-control" value="<?= sanitize($_POST['city'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                <select name="province" class="form-select" required>
                    <option value="">Select...</option>
                    <?php foreach (['Gauteng','Western Cape','KwaZulu-Natal','Eastern Cape','Limpopo','Mpumalanga','North West','Free State','Northern Cape'] as $prov): ?>
                        <option value="<?= $prov ?>" <?= (($_POST['province'] ?? '') === $prov) ? 'selected' : '' ?>><?= $prov ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Postal Code</label>
                <input type="text" name="zip_code" class="form-control" maxlength="10" value="<?= sanitize($_POST['zip_code'] ?? '') ?>">
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-success px-4" onclick="goStep(2)">Next <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </div>

    <!-- ── Step 2: Employment Info ────────────────────────────── -->
    <div class="form-step d-none" id="step-2">
        <h5 class="mb-3 text-success">Step 2 — Employment Information</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Employment Status <span class="text-danger">*</span></label>
                <select name="employment_status" class="form-select" required>
                    <option value="">Select...</option>
                    <option value="employed"      <?= (($_POST['employment_status'] ?? '') === 'employed')      ? 'selected' : '' ?>>Permanently Employed</option>
                    <option value="contract"      <?= (($_POST['employment_status'] ?? '') === 'contract')      ? 'selected' : '' ?>>Contract / Fixed Term</option>
                    <option value="self_employed" <?= (($_POST['employment_status'] ?? '') === 'self_employed') ? 'selected' : '' ?>>Self Employed</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Employer / Business Name <span class="text-danger">*</span></label>
                <input type="text" name="employer_name" class="form-control" value="<?= sanitize($_POST['employer_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                <input type="text" name="job_title" class="form-control" value="<?= sanitize($_POST['job_title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Employer Contact Number <span class="text-danger">*</span></label>
                <input type="tel" name="employer_contact" class="form-control" value="<?= sanitize($_POST['employer_contact'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">How long have you worked there? <span class="text-danger">*</span></label>
                <select name="employment_duration" class="form-select" required>
                    <option value="">Select...</option>
                    <option value="less_3m" <?= (($_POST['employment_duration'] ?? '') === 'less_3m') ? 'selected' : '' ?>>Less than 3 months</option>
                    <option value="3_6m"    <?= (($_POST['employment_duration'] ?? '') === '3_6m')    ? 'selected' : '' ?>>3 – 6 months</option>
                    <option value="6_12m"   <?= (($_POST['employment_duration'] ?? '') === '6_12m')   ? 'selected' : '' ?>>6 – 12 months</option>
                    <option value="1_2y"    <?= (($_POST['employment_duration'] ?? '') === '1_2y')    ? 'selected' : '' ?>>1 – 2 years</option>
                    <option value="2_5y"    <?= (($_POST['employment_duration'] ?? '') === '2_5y')    ? 'selected' : '' ?>>2 – 5 years</option>
                    <option value="5y_plus" <?= (($_POST['employment_duration'] ?? '') === '5y_plus') ? 'selected' : '' ?>>5+ years</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="goStep(1)"><i class="bi bi-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn btn-success px-4" onclick="goStep(3)">Next <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </div>

    <!-- ── Step 3: Financial Info ─────────────────────────────── -->
    <div class="form-step d-none" id="step-3">
        <h5 class="mb-3 text-success">Step 3 — Financial Information</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Monthly Gross Salary <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="salary_amount" class="form-control" min="1" step="0.01"
                           value="<?= htmlspecialchars($_POST['salary_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Next Payday Date <span class="text-danger">*</span></label>
                <input type="date" name="next_payday_date" class="form-control"
                       value="<?= sanitize($_POST['next_payday_date'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Loan Amount: <strong id="loanDisplay">R 5,000</strong></label>
                <input type="range" name="loan_amount" id="applyLoanSlider" class="form-range"
                       min="<?= MIN_LOAN_AMOUNT ?>" max="<?= MAX_LOAN_AMOUNT ?>" step="500"
                       value="<?= htmlspecialchars($_POST['loan_amount'] ?? '5000', ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex justify-content-between small text-muted">
                    <span>R <?= number_format(MIN_LOAN_AMOUNT) ?></span>
                    <span>R <?= number_format(MAX_LOAN_AMOUNT) ?></span>
                </div>
            </div>
            <div class="col-12"><p class="text-muted small mb-1 mt-2">Monthly Expenses (optional)</p></div>
            <div class="col-md-3">
                <label class="form-label">Rent / Bond</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="rent" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($_POST['rent'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Food / Groceries</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="food" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($_POST['food'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Transport</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="transport" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($_POST['transport'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Other Expenses</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="other_expenses" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($_POST['other_expenses'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="goStep(2)"><i class="bi bi-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn btn-success px-4" onclick="goStep(4)">Next <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </div>

    <!-- ── Step 4: Documents ──────────────────────────────────── -->
    <div class="form-step d-none" id="step-4">
        <h5 class="mb-3 text-success">Step 4 — Supporting Documents</h5>
        <p class="text-muted small mb-4">Accepted formats: <strong>JPG, PNG, PDF</strong>. Max file size is set by your server (default 8 MB per file).</p>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">South African ID Document <span class="text-danger">*</span></label>
                <input type="file" name="id_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                <div class="form-text">Clear copy of your green ID book, ID card, or passport.</div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Latest Payslip <span class="text-danger">*</span></label>
                <input type="file" name="payslip" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                <div class="form-text">Your most recent payslip (not older than 3 months).</div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Bank Statements (3 months) <span class="text-danger">*</span></label>
                <input type="file" name="bank_statement" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                <div class="form-text">Combine 3 months into one PDF if possible, or upload the most recent month.</div>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="goStep(3)"><i class="bi bi-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn btn-success px-4" onclick="goStep(5)">Review <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
    </div>

    <!-- ── Step 5: Review & Submit ────────────────────────────── -->
    <div class="form-step d-none" id="step-5">
        <h5 class="mb-3 text-success">Step 5 — Review & Submit</h5>
        <p class="text-muted small mb-4">Please review your details before submitting. Click Back to make changes.</p>

        <div class="row g-2 mb-4" id="reviewSummary">
            <!-- populated by JS -->
        </div>

        <div class="alert alert-info small">
            <i class="bi bi-shield-check me-1"></i>
            By submitting, you confirm that all information provided is true and accurate. Your data is processed in accordance with our <a href="privacy-policy.php">Privacy Policy</a>.
        </div>

        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="goStep(4)"><i class="bi bi-arrow-left me-1"></i> Back</button>
            <button type="submit" name="submit_application" class="btn btn-success btn-lg px-5">
                <i class="bi bi-send me-2"></i>Submit Application
            </button>
        </div>
    </div>

</form>
</div>
</div>

<?php endif; ?>

</div>
</div>
</div>
</section>

<style>
/* Step indicator */
.step-circle {
    width: 36px; height: 36px; border-radius: 50%;
    background: #dee2e6; color: #6c757d; font-weight: 700;
    display: flex; align-items: center; justify-content: center; font-size: .9rem;
    transition: background .2s, color .2s;
}
.step-circle.active { background: #2E7D32; color: #fff; }
.step-circle.done   { background: #66BB6A; color: #fff; }
.step-line { height: 3px; background: #dee2e6; margin-top: 16px; }
.step-label { color: #6c757d; font-size: .75rem; margin-top: 4px; }
</style>

<script>
(function () {
    var currentStep = 1;
    var totalSteps  = 5;

    function goStep(n) {
        if (n < 1 || n > totalSteps) return;
        if (n === 5) populateReview();

        document.querySelectorAll('.form-step').forEach(function (el) {
            el.classList.add('d-none');
        });
        document.getElementById('step-' + n).classList.remove('d-none');

        for (var i = 1; i <= totalSteps; i++) {
            var circle = document.querySelector('#ind-' + i + ' .step-circle');
            if (!circle) continue;
            circle.classList.remove('active', 'done');
            if (i < n)      circle.classList.add('done');
            else if (i === n) circle.classList.add('active');
        }

        currentStep = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.goStep = goStep;

    // Loan slider label
    var applySlider = document.getElementById('applyLoanSlider');
    var loanDisplay = document.getElementById('loanDisplay');
    if (applySlider && loanDisplay) {
        function formatSlider(v) {
            return 'R ' + parseInt(v).toLocaleString('en-ZA').replace(/\s/g, ',');
        }
        loanDisplay.textContent = formatSlider(applySlider.value);
        applySlider.addEventListener('input', function () {
            loanDisplay.textContent = formatSlider(this.value);
        });
    }

    // Populate review summary (Step 5)
    function populateReview() {
        var form    = document.getElementById('applyForm');
        var summary = document.getElementById('reviewSummary');
        if (!form || !summary) return;

        function val(name) {
            var el = form.querySelector('[name="' + name + '"]');
            return el ? (el.value || '—') : '—';
        }
        function fileVal(name) {
            var el = form.querySelector('[name="' + name + '"]');
            return (el && el.files && el.files[0]) ? el.files[0].name : '(not selected)';
        }

        var rows = [
            ['Personal', null],
            ['Full Name',            val('first_name') + ' ' + val('last_name')],
            ['Other Names',          val('other_names')],
            ['ID Number',            val('id_number')],
            ['Email',                val('email')],
            ['Phone',                val('phone')],
            ['Address',              val('address') + ', ' + val('city') + ', ' + val('province') + ' ' + val('zip_code')],
            ['Employment', null],
            ['Status',               val('employment_status').replace('_', ' ')],
            ['Employer',             val('employer_name')],
            ['Job Title',            val('job_title')],
            ['Duration',             val('employment_duration').replace(/_/g, ' ')],
            ['Financial', null],
            ['Monthly Salary',       'R ' + parseFloat(val('salary_amount') || 0).toLocaleString('en-ZA')],
            ['Loan Amount',          'R ' + parseInt(val('loan_amount') || 0).toLocaleString('en-ZA')],
            ['Next Payday',          val('next_payday_date')],
            ['Documents', null],
            ['ID Document',          fileVal('id_document')],
            ['Payslip',              fileVal('payslip')],
            ['Bank Statement',       fileVal('bank_statement')],
        ];

        var html = '';
        rows.forEach(function (row) {
            if (row[1] === null) {
                html += '<div class="col-12"><p class="fw-semibold text-success mb-1 mt-2">' + row[0] + '</p><hr class="mt-0"></div>';
            } else {
                html += '<div class="col-5 col-md-4 text-muted small">' + row[0] + '</div>'
                      + '<div class="col-7 col-md-8 small fw-medium">' + row[1] + '</div>';
            }
        });

        summary.innerHTML = html;
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l apply.php
```

Expected: `No syntax errors detected in apply.php`

- [ ] **Step 3: Load in browser and test navigation**

1. Open `http://localhost/greencash/apply.php`
2. Step indicators visible, Step 1 form visible
3. Click Next → Step 2 shows, Step 1 circle turns green (done)
4. Continue through all 5 steps
5. On Step 5 (Review): summary populated with the values you entered
6. Click Back on any step — previous step shows correctly

- [ ] **Step 4: Commit**

```bash
git add apply.php
git commit -m "feat: add multi-step salary advance application form"
```

---

## Chunk 2: Confirmation, Tracker, Document Proxy

---

### Task 3: Create `application-submitted.php`

- [ ] **Step 1: Create `application-submitted.php`**

```php
<?php
require_once 'includes/config.php';

// Guard — must arrive via successful apply.php submission
if (empty($_SESSION['app_reference'])) {
    redirect('/apply.php');
}

$ref  = $_SESSION['app_reference'];
$name = $_SESSION['app_name'] ?? 'Applicant';
unset($_SESSION['app_reference'], $_SESSION['app_name']);

$page_title = 'Application Submitted';
include 'includes/header.php';
?>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7 text-center">

    <div class="card border-0 shadow-sm p-5">
        <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
        <h2 class="mt-3 fw-bold">Application Received!</h2>
        <p class="text-muted">Thank you, <?= sanitize($name) ?>. We've received your salary advance application and will review it shortly.</p>

        <div class="bg-light rounded-3 p-4 my-4">
            <p class="text-muted small mb-1">Your Reference Number</p>
            <h3 class="fw-bold text-success mb-0" id="refNum"><?= sanitize($ref) ?></h3>
            <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyRef()">
                <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <p class="text-muted small mt-2 mb-0">Save this number — you'll need it to track your application.</p>
        </div>

        <h6 class="fw-semibold mb-3">What happens next?</h6>
        <div class="text-start">
            <div class="d-flex gap-3 mb-3">
                <div class="text-success fw-bold">1.</div>
                <div><strong>Review</strong> — Our team reviews your application and documents (within 24 hours on business days).</div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="text-success fw-bold">2.</div>
                <div><strong>Decision</strong> — You'll be notified by email once a decision has been made.</div>
            </div>
            <div class="d-flex gap-3">
                <div class="text-success fw-bold">3.</div>
                <div><strong>Disbursement</strong> — If approved, funds are transferred within 1 business day.</div>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
            <a href="track-application.php" class="btn btn-success px-4">
                <i class="bi bi-search me-1"></i>Track My Application
            </a>
            <a href="index.php" class="btn btn-outline-secondary px-4">Back to Home</a>
        </div>
    </div>

</div>
</div>
</div>
</section>

<script>
function copyRef() {
    var ref = document.getElementById('refNum').textContent.trim();
    navigator.clipboard.writeText(ref).then(function () {
        alert('Reference number copied!');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l application-submitted.php
```

- [ ] **Step 3: Commit**

```bash
git add application-submitted.php
git commit -m "feat: add application confirmation page"
```

---

### Task 4: Create `track-application.php`

- [ ] **Step 1: Create `track-application.php`**

```php
<?php
require_once 'includes/config.php';

$page_title = 'Track Your Application';
$app        = null;
$notFound   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $ref      = sanitize(trim($_POST['reference_number'] ?? ''));
    $idNumber = sanitize(trim($_POST['id_number'] ?? ''));

    if (!empty($ref) && !empty($idNumber)) {
        $stmt = $pdo->prepare(
            "SELECT * FROM salary_advance_applications WHERE reference_number = ? AND id_number = ? LIMIT 1"
        );
        $stmt->execute([$ref, $idNumber]);
        $app = $stmt->fetch();
        if (!$app) $notFound = true;
    }
}

// Status timeline definition (ordered, for display)
$timeline = [
    'pending'      => ['label' => 'Application Received',  'icon' => 'bi-envelope-check'],
    'under_review' => ['label' => 'Under Review',          'icon' => 'bi-search'],
    'approved'     => ['label' => 'Approved',              'icon' => 'bi-hand-thumbs-up'],
    'disbursed'    => ['label' => 'Funds Disbursed',       'icon' => 'bi-bank'],
];

$statusOrder = array_keys($timeline);

include 'includes/header.php';
?>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="text-center mb-4">
        <h2 class="fw-bold">Track Your Application</h2>
        <p class="text-muted">Enter your reference number and ID number to check your application status.</p>
    </div>

    <!-- Lookup form -->
    <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="track-application.php">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. GC-20260516-A3F2K1"
                           value="<?= sanitize($_POST['reference_number'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SA ID Number</label>
                    <input type="text" name="id_number" class="form-control" placeholder="13 digits" maxlength="13"
                           value="<?= sanitize($_POST['id_number'] ?? '') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3 px-4">
                <i class="bi bi-search me-1"></i>Check Status
            </button>
        </form>
    </div>
    </div>

    <?php if ($notFound): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No application found with those details. Please check your reference number and ID number.
    </div>
    <?php endif; ?>

    <?php if ($app): ?>
    <!-- Application found -->
    <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= sanitize($app['first_name']) ?> <?= sanitize($app['last_name']) ?></h5>
                <p class="text-muted small mb-0">Ref: <strong><?= sanitize($app['reference_number']) ?></strong> &nbsp;·&nbsp; Submitted: <?= date('d M Y', strtotime($app['submitted_at'])) ?></p>
            </div>
            <?php
            $statusBadge = [
                'pending'      => 'secondary',
                'under_review' => 'warning',
                'approved'     => 'success',
                'rejected'     => 'danger',
                'disbursed'    => 'primary',
            ];
            $st    = $app['status'];
            $badge = $statusBadge[$st] ?? 'secondary';
            $label = ucwords(str_replace('_', ' ', $st));
            ?>
            <span class="badge bg-<?= $badge ?> fs-6 px-3 py-2"><?= sanitize($label) ?></span>
        </div>

        <div class="mb-3">
            <span class="text-muted small">Loan Amount:</span>
            <strong class="ms-1"><?= formatCurrency($app['loan_amount']) ?></strong>
        </div>

        <?php if ($st === 'rejected'): ?>
        <!-- Rejected — no timeline, just a note -->
        <div class="alert alert-danger">
            <i class="bi bi-x-circle me-2"></i>Unfortunately, your application was not approved at this time.
            If you have questions, please <a href="contact.php">contact us</a>.
        </div>

        <?php else: ?>
        <!-- Status timeline -->
        <div class="mt-4">
            <?php
            $currentIdx = array_search($st, $statusOrder);
            if ($currentIdx === false) $currentIdx = 0;
            foreach ($timeline as $key => $step):
                $stepIdx = array_search($key, $statusOrder);
                $isDone  = $stepIdx <= $currentIdx;
                $isNow   = $key === $st;
            ?>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="timeline-dot <?= $isDone ? 'done' : '' ?> <?= $isNow ? 'current' : '' ?>">
                    <i class="bi <?= $step['icon'] ?>"></i>
                </div>
                <div>
                    <p class="mb-0 fw-semibold <?= $isNow ? 'text-success' : ($isDone ? '' : 'text-muted') ?>">
                        <?= sanitize($step['label']) ?>
                        <?php if ($isNow): ?><span class="badge bg-success ms-2 small">Current</span><?php endif; ?>
                    </p>
                </div>
            </div>
            <?php if ($key !== 'disbursed'): ?>
            <div class="timeline-line <?= $isDone ? 'done' : '' ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    </div>
    <?php endif; ?>

</div>
</div>
</div>
</section>

<style>
.timeline-dot {
    width: 42px; height: 42px; border-radius: 50%; background: #dee2e6;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #6c757d; flex-shrink: 0;
}
.timeline-dot.done    { background: #2E7D32; color: #fff; }
.timeline-dot.current { background: #2E7D32; color: #fff; box-shadow: 0 0 0 4px #c8e6c9; }
.timeline-line { width: 2px; height: 24px; background: #dee2e6; margin-left: 20px; }
.timeline-line.done   { background: #2E7D32; }
</style>

<?php include 'includes/footer.php'; ?>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l track-application.php
```

- [ ] **Step 3: Commit**

```bash
git add track-application.php
git commit -m "feat: add application status tracker"
```

---

### Task 5: Create `serve-document.php`

- [ ] **Step 1: Create `serve-document.php`**

```php
<?php
require_once 'includes/config.php';
requireAdmin(); // Admin-only — broker access added in Step 9

$fileParam = trim($_GET['file'] ?? '');

if (empty($fileParam)) {
    http_response_code(404);
    exit('File not found.');
}

// Only accept a bare filename — no directory components
$filename  = basename($fileParam);
$uploadDir = realpath(UPLOAD_DIR);

if ($uploadDir === false) {
    http_response_code(500);
    exit('Upload directory unavailable.');
}

$filePath = realpath($uploadDir . DIRECTORY_SEPARATOR . $filename);

// Path traversal guard
if ($filePath === false || strpos($filePath, $uploadDir . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found.');
}

// Verify file is registered in application_documents (prevents serving arbitrary files)
$stmt = $pdo->prepare(
    "SELECT id FROM application_documents WHERE file_path = ? LIMIT 1"
);
$stmt->execute(['uploads/' . $filename]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Access denied.');
}

// Determine MIME and stream
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
if (!in_array($mimeType, $allowed, true)) {
    http_response_code(403);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');
readfile($filePath);
exit();
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l serve-document.php
```

- [ ] **Step 3: Commit**

```bash
git add serve-document.php
git commit -m "feat: add secure admin document proxy"
```

---

### Task 6: Full syntax check + end-to-end test

- [ ] **Step 1: Syntax check all Step 4 files**

```bash
php -l includes/config.php
php -l apply.php
php -l application-submitted.php
php -l track-application.php
php -l serve-document.php
```

Expected: `No syntax errors detected` on every file.

- [ ] **Step 2: Full form submission test**

1. Open `http://localhost/greencash/apply.php`
2. Complete all 5 steps with valid data
3. On Step 5 (Review) — verify summary shows your entered values
4. Submit — expected: redirect to `application-submitted.php` with reference number
5. Check XAMPP error log for: `GREENCASH DEV CONFIRMATION: ref=GC-...`
6. Click "Track My Application" → use reference + ID number → application found, status = "pending"
7. Open phpMyAdmin → verify row in `salary_advance_applications` and rows in `application_documents`

- [ ] **Step 3: Duplicate submission test**

1. Go to `apply.php` again, enter the **same ID number** used in the previous test
2. Submit within 5 minutes
3. Expected: redirect back to `apply.php` showing "Already Submitted" notice

- [ ] **Step 4: Validation test**

1. Submit the form with empty required fields (click Next through all steps and submit)
2. Expected: error list at the top of the page listing missing fields

- [ ] **Step 5: Track — not found test**

1. Open `track-application.php`
2. Submit a fake reference number
3. Expected: yellow "No application found" alert

- [ ] **Step 6: Serve document test**

1. Log in as admin (`admin@greencash.co.za`)
2. Find an uploaded filename from `application_documents` in phpMyAdmin (e.g. `id_document_6845...jpg`)
3. Open `http://localhost/greencash/serve-document.php?file=id_document_6845...jpg`
4. Expected: file renders inline (image or PDF) in the browser
5. Open the URL in a new private/incognito tab (not logged in) → expected: redirect to `login.php`

---

## Done Condition

1. `apply.php` — multi-step form navigates correctly; JS review summary populated
2. Submission saves to `salary_advance_applications` + `application_documents` tables
3. Duplicate ID within 5 min → "Already Submitted" notice
4. Confirmation email stub logged to XAMPP error log
5. `application-submitted.php` shows reference number with copy button; guard redirects if accessed directly
6. `track-application.php` — found → status timeline; not found → warning alert; rejected → red banner
7. `serve-document.php` — streams files for admin; 403 for unauthenticated users; path traversal blocked
8. All 5 files pass `php -l` with no syntax errors
