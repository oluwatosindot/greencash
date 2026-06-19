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

// GET without duplicate-guard session flag → redirect to inline form on index.php
if (!$alreadySubmitted && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ' . APP_URL . '/#apply', true, 302);
    exit;
}

if (!$alreadySubmitted && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    verifyCsrf();

    // Per-IP rate limit — duplicate-guard alone keys on id_number, so an attacker
    // could rotate IDs to fill the DB + uploads disk. 5 applications / 60 min per IP
    // is plenty for legitimate use.
    $ip = getClientIp();
    if (checkRateLimit($pdo, $ip, 'apply_ip', 5, 60)) {
        setFlash('error', 'Too many applications from your network. Please try again later.');
        redirect('/');
    }
    incrementRateLimit($pdo, $ip, 'apply_ip', 5, 60);

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
    if (empty($data['first_name']))                                                       $errors[] = 'First name is required.';
    if (empty($data['last_name']))                                                        $errors[] = 'Last name is required.';
    if (empty($data['id_number']) || !preg_match('/^\d{13}$/', $data['id_number']))      $errors[] = 'A valid 13-digit SA ID number is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))                               $errors[] = 'A valid email address is required.';
    if (empty($data['phone']))                                                            $errors[] = 'Phone number is required.';
    if (empty($data['address']))                                                          $errors[] = 'Street address is required.';
    if (empty($data['city']))                                                             $errors[] = 'City is required.';
    if (!in_array($data['province'], $validProvinces, true))                              $errors[] = 'Please select a valid province.';
    if (!in_array($data['employment_status'], $validStatuses, true))                      $errors[] = 'Please select a valid employment status.';
    if (empty($data['employer_name']))                                                    $errors[] = 'Employer name is required.';
    if (empty($data['employer_contact']))                                                 $errors[] = 'Employer contact number is required.';
    if (empty($data['job_title']))                                                        $errors[] = 'Job title is required.';
    if (!in_array($data['employment_duration'], $validDurations, true))                   $errors[] = 'Please select your employment duration.';
    if ($data['salary_amount'] <= 0)                                                      $errors[] = 'Monthly salary is required.';
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

        // Email the full application to the loans inbox (until admin portal exists)
        sendApplicationToLoans(
            array_merge($data, [
                'reference_number' => $ref,
                'application_id'   => $appId,
                'repayment_date'   => $repaymentDate,
            ]),
            $uploadedDocs
        );

        $_SESSION['app_reference'] = $ref;
        $_SESSION['app_name']      = $data['first_name'];
        redirect('/application-submitted.php');
    }
}

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
<?php elseif (!empty($errors)): ?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:640px">
        <div class="form-shell">
            <div class="form-body">
                <h3>We couldn't submit your application</h3>
                <p class="desc">Please fix the issues below and try again from the form on the home page.</p>
                <ul style="margin:18px 0;padding-left:20px;color:#d33">
                    <?php foreach ($errors as $err): ?>
                    <li style="margin-bottom:6px"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-primary">← Back to the form</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
