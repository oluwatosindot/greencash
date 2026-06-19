<?php
require_once 'includes/config.php';

// Only accept POSTs gated on the submit button
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['partner_submit'])) {
    header('Location: ' . APP_URL . '/#employers', true, 302);
    exit;
}

verifyCsrf();

// Per-IP rate limit — anti-spam.
$ip = getClientIp();
if (checkRateLimit($pdo, $ip, 'partner_ip', 5, 60)) {
    setFlash('error', 'Too many enquiries from your network. Please try again in an hour.');
    header('Location: ' . APP_URL . '/#employers', true, 302);
    exit;
}
incrementRateLimit($pdo, $ip, 'partner_ip', 5, 60);

$data = [
    'company'      => trim($_POST['company']      ?? ''),
    'contact_name' => trim($_POST['contact_name'] ?? ''),
    'email'        => trim($_POST['email']        ?? ''),
    'phone'        => trim($_POST['phone']        ?? ''),
    'employees'    => trim($_POST['employees']    ?? ''),
    'message'      => trim($_POST['message']      ?? ''),
];

$errors = [];
if ($data['contact_name'] === '')                               $errors[] = 'Your name is required.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))         $errors[] = 'A valid email is required.';
if ($data['phone'] === '')                                      $errors[] = 'Phone number is required.';
if (mb_strlen($data['message']) > 2000)                         $errors[] = 'Message is too long (max 2000 characters).';

if (!empty($errors)) {
    setFlash('error', 'Please fix the form: ' . implode(' ', $errors));
    header('Location: ' . APP_URL . '/#employers', true, 302);
    exit;
}

// PERSIST FIRST — write the enquiry to the DB before attempting to email so a
// SMTP failure can't silently lose a business lead. email_sent_at stays NULL
// until mail() returns true, so failed sends are recoverable from the admin
// portal (and a cron could retry them).
$ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
try {
    $stmt = $pdo->prepare(
        "INSERT INTO partnership_enquiries
           (company, contact_name, email, phone, employees, message, ip_address, user_agent, email_send_attempts)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)"
    );
    $stmt->execute([
        $data['company'] !== '' ? $data['company'] : null,
        $data['contact_name'],
        $data['email'],
        $data['phone'],
        $data['employees'] !== '' ? $data['employees'] : null,
        $data['message'] !== '' ? $data['message'] : null,
        $ip,
        $ua,
    ]);
    $enquiryId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    // Don't leak DB errors to the user. Log and acknowledge — the email attempt
    // below is still a chance to capture the lead even if persistence broke.
    error_log('partnership_enquiries insert failed: ' . $e->getMessage());
    $enquiryId = 0;
}

// Try to email. Mark the row as sent on success so the team knows what's
// already been routed to the info@ inbox.
$sent = sendPartnershipEnquiry($data);

if ($enquiryId > 0) {
    try {
        $pdo->prepare(
            "UPDATE partnership_enquiries
                SET email_send_attempts = email_send_attempts + 1,
                    email_sent_at       = " . ($sent ? "NOW()" : "email_sent_at") . "
              WHERE id = ?"
        )->execute([$enquiryId]);
    } catch (PDOException $e) {
        error_log('partnership_enquiries update failed: ' . $e->getMessage());
    }
}

if ($sent) {
    setFlash('success', 'Thanks! Your partnership enquiry was sent. Our team will be in touch within one business day.');
} else {
    // We have the row in the DB — acknowledge without exposing the SMTP failure.
    setFlash('success', 'Thanks! Your enquiry was received. Our team will be in touch shortly.');
    if ($enquiryId === 0) {
        error_log('Partnership enquiry: mail failed AND DB insert failed. Lead lost. Email=' . $data['email']);
    } else {
        error_log("Partnership enquiry id={$enquiryId}: stored to DB, mail failed. Retry from admin.");
    }
}

header('Location: ' . APP_URL . '/#employers', true, 302);
exit;
