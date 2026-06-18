<?php
require_once 'includes/config.php';
$page_title = 'Terms & Conditions';
include 'includes/header.php';
?>

<section class="legal-hero">
    <div class="wrap">
        <span class="kicker">● Legal · Terms</span>
        <h1>Terms &amp; Conditions</h1>
        <p>The rules that govern your salary-advance loan with GreenCash, including fees, eligibility, repayment, and your rights under the National Credit Act.</p>
        <span class="meta">📅 Last updated <?= date('F Y') ?></span>
    </div>
</section>

<section class="block" style="background:var(--paper-2);padding-top:0">
    <div class="wrap" style="max-width:780px">
        <div class="prose">
            <h2>1. Loan Product</h2>
            <p>GreenCash offers one loan product: a Salary Advance loan for permanently employed South African citizens and residents.</p>

            <h2>2. Loan Amount &amp; Term</h2>
            <ul>
                <li>Minimum loan: <strong>R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ',') ?></strong></li>
                <li>Maximum loan: <strong>R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ',') ?></strong></li>
                <li>Loan term: <strong>1 calendar month</strong></li>
                <li>Repayment date: Your next salary date as declared on the application</li>
            </ul>

            <h2>3. Fees &amp; Costs</h2>
            <ul>
                <li>Service fee: <strong>15% of the loan amount</strong> (flat, once-off)</li>
                <li>Example: A loan of R5,000 carries a service fee of R750. Total repayment: R5,750.</li>
                <li>No hidden fees. No early settlement penalties.</li>
            </ul>

            <h2>4. Eligibility</h2>
            <p>Applicants must be:</p>
            <ul>
                <li>South African citizen or permanent resident with a valid SA ID</li>
                <li>Permanently employed (not self-employed or contract workers, unless approved)</li>
                <li>18 years of age or older</li>
                <li>Able to demonstrate affordability</li>
            </ul>

            <h2>5. Repayment</h2>
            <p>The full repayment amount (loan + service fee) is due on your next salary date. Failure to repay may result in additional charges and adverse credit bureau listings.</p>

            <h2>6. NCR Registration</h2>
            <p>GreenCash is a registered credit provider in terms of the National Credit Act 34 of 2005<?php if (defined('NCR_NUMBER') && NCR_NUMBER !== ''): ?>. NCR Registration Number: <strong><?= htmlspecialchars(NCR_NUMBER, ENT_QUOTES, 'UTF-8') ?></strong><?php endif; ?>.</p>

            <h2>7. Governing Law</h2>
            <p>These terms are governed by the laws of the Republic of South Africa.</p>

            <h2 id="pre-agreement">Pre-agreement disclosure</h2>
            <p>Before you sign any loan agreement with GreenCash, you'll receive a pre-agreement statement that sets out:</p>
            <ul>
                <li>The principal debt and any deposit you must pay</li>
                <li>The total cost of credit (principal + service fee + interest)</li>
                <li>The annual interest rate where applicable, and how it's calculated</li>
                <li>All fees, charges, and the repayment schedule</li>
                <li>Your right to cool off and your right to settle the loan early without penalty</li>
            </ul>
            <p>This disclosure is provided in line with the National Credit Act 34 of 2005. You should read it carefully and only sign the agreement if you fully understand and accept its terms.</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
