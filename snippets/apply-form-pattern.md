# Pattern: Multi-Step Salary Advance Application Form (apply.php)

## Server-side handler (top of apply.php)

```php
<?php
require_once 'includes/config.php';

$page_title = 'Apply for a Salary Advance';
$submitted  = false;
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    verifyCsrf();

    // --- Duplicate submission guard ---
    $submittedId = trim($_POST['id_number'] ?? '');
    if (!empty($submittedId)) {
        $dup = $pdo->prepare(
            "SELECT id FROM salary_advance_applications
             WHERE id_number = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1"
        );
        $dup->execute([$submittedId]);
        if ($dup->fetch()) {
            // Already submitted recently — show confirmation instead
            $_SESSION['form_submitted'] = true;
            $_SESSION['applicant_name'] = sanitize(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
            redirect($_SERVER['PHP_SELF']);
        }
    }

    // --- Collect & sanitize ---
    $data = [
        'first_name'          => sanitize($_POST['first_name'] ?? ''),
        'last_name'           => sanitize($_POST['last_name'] ?? ''),
        'other_names'         => sanitize($_POST['other_names'] ?? ''),
        'id_number'           => sanitize($_POST['id_number'] ?? ''),
        'email'               => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'phone'               => sanitize($_POST['phone'] ?? ''),
        'address'             => sanitize($_POST['address'] ?? ''),
        'city'                => sanitize($_POST['city'] ?? ''),
        'province'            => sanitize($_POST['province'] ?? ''),
        'zip_code'            => sanitize($_POST['zip_code'] ?? ''),
        'employment_status'   => sanitize($_POST['employment_status'] ?? ''),
        'employer_name'       => sanitize($_POST['employer_name'] ?? ''),
        'employer_contact'    => sanitize($_POST['employer_contact'] ?? ''),
        'job_title'           => sanitize($_POST['job_title'] ?? ''),
        'employment_duration' => sanitize($_POST['employment_duration'] ?? ''),
        'salary_amount'       => (float)($_POST['salary_amount'] ?? 0),
        'next_payday_date'    => sanitize($_POST['next_payday_date'] ?? ''),
        'loan_amount'         => (float)($_POST['loan_amount'] ?? 0),
        'rent'                => (float)($_POST['rent'] ?? 0),
        'food'                => (float)($_POST['food'] ?? 0),
        'transport'           => (float)($_POST['transport'] ?? 0),
        'other_expenses'      => (float)($_POST['other_expenses'] ?? 0),
    ];

    // --- Validation ---
    if (empty($data['first_name']))       $errors[] = 'First name is required.';
    if (empty($data['last_name']))        $errors[] = 'Last name is required.';
    if (empty($data['id_number']))        $errors[] = 'ID number is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($data['phone']))            $errors[] = 'Phone number is required.';
    if ($data['loan_amount'] < MIN_LOAN_AMOUNT || $data['loan_amount'] > MAX_LOAN_AMOUNT) {
        $errors[] = 'Loan amount must be between ' . formatCurrency(MIN_LOAN_AMOUNT) . ' and ' . formatCurrency(MAX_LOAN_AMOUNT) . '.';
    }

    // --- File uploads ---
    $allowedMimes = ['image/jpeg','image/png','image/jpg','application/pdf'];
    $allowedExts  = ['jpg','jpeg','png','pdf'];
    $uploadedDocs = [];

    $docFields = [
        'id_document'    => 'ID Document',
        'payslip'        => 'Payslip',
        'bank_statement' => 'Bank Statement',
    ];

    foreach ($docFields as $field => $label) {
        if (!empty($_FILES[$field]['name'])) {
            $file     = $_FILES[$field];
            $origName = basename($file['name']);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes) || !in_array($ext, $allowedExts)) {
                $errors[] = $label . ': only JPG, PNG, PDF files are allowed.';
                continue;
            }

            $safeName   = uniqid($field . '_', true) . '.' . $ext;
            $targetPath = UPLOAD_DIR . $safeName;
            $realTarget = realpath(UPLOAD_DIR) . DIRECTORY_SEPARATOR . $safeName;

            // Path traversal check
            if (strpos($realTarget, realpath(UPLOAD_DIR)) !== 0) {
                $errors[] = 'Invalid file path detected.';
                continue;
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $uploadedDocs[] = [
                    'type' => $field,
                    'name' => $origName,
                    'path' => 'uploads/' . $safeName,
                ];
            }
        }
    }

    // --- Save to DB ---
    if (empty($errors)) {
        $ref = generateReference();
        $repaymentDate = date('Y-m-d', strtotime($data['next_payday_date']));

        $stmt = $pdo->prepare("
            INSERT INTO salary_advance_applications
            (reference_number, source, first_name, last_name, other_names, id_number,
             email, phone, address, city, province, zip_code,
             employment_status, employer_name, employer_contact, job_title, employment_duration,
             salary_amount, next_payday_date, loan_amount, repayment_date,
             rent, food, transport, other_expenses)
            VALUES (?, 'public', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ref,
            $data['first_name'], $data['last_name'], $data['other_names'], $data['id_number'],
            $data['email'], $data['phone'], $data['address'], $data['city'],
            $data['province'], $data['zip_code'],
            $data['employment_status'], $data['employer_name'], $data['employer_contact'],
            $data['job_title'], $data['employment_duration'],
            $data['salary_amount'], $data['next_payday_date'], $data['loan_amount'], $repaymentDate,
            $data['rent'], $data['food'], $data['transport'], $data['other_expenses'],
        ]);

        $appId = $pdo->lastInsertId();

        // Save documents
        foreach ($uploadedDocs as $doc) {
            $pdo->prepare(
                "INSERT INTO application_documents (application_id, document_type, file_name, file_path) VALUES (?,?,?,?)"
            )->execute([$appId, $doc['type'], $doc['name'], $doc['path']]);
        }

        // Send confirmation email
        sendApplicationConfirmation([
            'first_name'       => $data['first_name'],
            'email'            => $data['email'],
            'reference_number' => $ref,
            'loan_amount'      => $data['loan_amount'],
        ]);

        // Redirect to confirmation page
        $_SESSION['app_reference'] = $ref;
        $_SESSION['app_name']      = $data['first_name'];
        redirect('/application-submitted.php');
    }
}
?>
```

## Multi-step form HTML (Bootstrap 5)

```html
<!-- Step indicators -->
<div class="d-flex justify-content-center mb-4">
    <div class="step-indicator active" data-step="1">1. Personal</div>
    <div class="step-indicator" data-step="2">2. Employment</div>
    <div class="step-indicator" data-step="3">3. Financial</div>
    <div class="step-indicator" data-step="4">4. Documents</div>
    <div class="step-indicator" data-step="5">5. Review</div>
</div>

<form method="POST" enctype="multipart/form-data" id="applyForm">
    <?= csrfField() ?>

    <!-- Step 1: Personal Info -->
    <div class="form-step" id="step-1">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name *</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name *</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">SA ID Number *</label>
                <input type="text" name="id_number" class="form-control" maxlength="13" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Province *</label>
                <select name="province" class="form-select" required>
                    <option value="">Select province...</option>
                    <option>Gauteng</option>
                    <option>Western Cape</option>
                    <option>KwaZulu-Natal</option>
                    <option>Eastern Cape</option>
                    <option>Limpopo</option>
                    <option>Mpumalanga</option>
                    <option>North West</option>
                    <option>Free State</option>
                    <option>Northern Cape</option>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-primary mt-3" onclick="nextStep(2)">Next →</button>
    </div>

    <!-- Step 2: Employment Info -->
    <div class="form-step d-none" id="step-2">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Employment Status *</label>
                <select name="employment_status" class="form-select" required>
                    <option value="">Select...</option>
                    <option value="employed">Permanently Employed</option>
                    <option value="contract">Contract / Fixed Term</option>
                    <option value="self_employed">Self Employed</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer Name *</label>
                <input type="text" name="employer_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Job Title *</label>
                <input type="text" name="job_title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer Contact Number *</label>
                <input type="tel" name="employer_contact" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">How long have you worked there? *</label>
                <select name="employment_duration" class="form-select" required>
                    <option value="">Select...</option>
                    <option value="less_3m">Less than 3 months</option>
                    <option value="3_6m">3 – 6 months</option>
                    <option value="6_12m">6 – 12 months</option>
                    <option value="1_2y">1 – 2 years</option>
                    <option value="2_5y">2 – 5 years</option>
                    <option value="5y_plus">5+ years</option>
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" onclick="nextStep(1)">← Back</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next →</button>
        </div>
    </div>

    <!-- Step 3: Financial Info (key fields) -->
    <div class="form-step d-none" id="step-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Monthly Salary *</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="salary_amount" class="form-control" min="0" step="0.01" required>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Next Payday Date *</label>
                <input type="date" name="next_payday_date" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Loan Amount: <strong id="loanDisplay">R 5,000</strong></label>
                <input type="range" name="loan_amount" id="loanSlider"
                       class="form-range" min="1000" max="10000" step="500" value="5000">
                <div class="d-flex justify-content-between small text-muted">
                    <span>R 1,000</span><span>R 10,000</span>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" name="submit_application" class="btn btn-success btn-lg">
        Submit Application
    </button>
</form>
```

## JavaScript for multi-step + slider (assets/js/main.js)

```javascript
// Multi-step navigation
function nextStep(step) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.add('d-none'));
    document.getElementById('step-' + step).classList.remove('d-none');
    document.querySelectorAll('.step-indicator').forEach(s => s.classList.remove('active'));
    document.querySelector('[data-step="' + step + '"]').classList.add('active');
}

// Loan amount slider
const slider = document.getElementById('loanSlider');
const display = document.getElementById('loanDisplay');
if (slider) {
    slider.addEventListener('input', function () {
        display.textContent = 'R ' + parseInt(this.value).toLocaleString();
    });
}
```
