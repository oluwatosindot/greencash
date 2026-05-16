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
