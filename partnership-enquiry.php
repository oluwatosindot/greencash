<?php
require_once 'includes/config.php';

// Only accept POSTs gated on the submit button
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['partner_submit'])) {
    header('Location: ' . APP_URL . '/#employers', true, 302);
    exit;
}

verifyCsrf();

$data = [
    'company'      => trim($_POST['company']      ?? ''),
    'contact_name' => trim($_POST['contact_name'] ?? ''),
    'email'        => trim($_POST['email']        ?? ''),
    'phone'        => trim($_POST['phone']        ?? ''),
    'employees'    => trim($_POST['employees']    ?? ''),
    'message'      => trim($_POST['message']      ?? ''),
];

$errors = [];
if ($data['company'] === '')                                    $errors[] = 'Company name is required.';
if ($data['contact_name'] === '')                               $errors[] = 'Your name is required.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))         $errors[] = 'A valid work email is required.';
if ($data['phone'] === '')                                      $errors[] = 'Phone number is required.';
if (mb_strlen($data['message']) > 2000)                         $errors[] = 'Message is too long (max 2000 characters).';

if (!empty($errors)) {
    setFlash('error', 'Please fix the form: ' . implode(' ', $errors));
    header('Location: ' . APP_URL . '/#employers', true, 302);
    exit;
}

$sent = sendPartnershipEnquiry($data);

if ($sent) {
    setFlash('success', 'Thanks! Your partnership enquiry was sent. Our team will be in touch within one business day.');
} else {
    // Still acknowledge the submission — don't expose internal mail failures to the user.
    setFlash('success', 'Thanks! Your enquiry was received. Our team will be in touch shortly.');
    error_log('Partnership enquiry mail failed but acknowledged to user: company=' . $data['company']);
}

header('Location: ' . APP_URL . '/#employers', true, 302);
exit;
