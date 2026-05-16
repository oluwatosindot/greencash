<?php
require_once 'includes/config.php';
$page_title = 'Terms & Conditions';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="section-title mb-1">Terms &amp; Conditions</h1>
        <p class="text-muted mb-4">Last updated: <?= date('F Y') ?></p>

        <div class="card border-0 shadow-sm p-4">
            <h5>1. Loan Product</h5>
            <p>Green Cash offers one loan product: a Salary Advance loan for permanently employed South African citizens and residents.</p>

            <h5 class="mt-4">2. Loan Amount &amp; Term</h5>
            <ul>
                <li>Minimum loan: <strong>R1,000</strong></li>
                <li>Maximum loan: <strong>R10,000</strong></li>
                <li>Loan term: <strong>1 calendar month</strong></li>
                <li>Repayment date: Your next salary date as declared on the application</li>
            </ul>

            <h5 class="mt-4">3. Fees &amp; Costs</h5>
            <ul>
                <li>Service fee: <strong>15% of the loan amount</strong> (flat, once-off)</li>
                <li>Example: A loan of R5,000 carries a service fee of R750. Total repayment: R5,750.</li>
                <li>No hidden fees. No early settlement penalties.</li>
            </ul>

            <h5 class="mt-4">4. Eligibility</h5>
            <p>Applicants must be:</p>
            <ul>
                <li>South African citizen or permanent resident with a valid SA ID</li>
                <li>Permanently employed (not self-employed or contract workers, unless approved)</li>
                <li>18 years of age or older</li>
                <li>Able to demonstrate affordability</li>
            </ul>

            <h5 class="mt-4">5. Repayment</h5>
            <p>The full repayment amount (loan + service fee) is due on your next salary date. Failure to repay may result in additional charges and adverse credit bureau listings.</p>

            <h5 class="mt-4">6. NCR Registration</h5>
            <p>Green Cash is a registered credit provider in terms of the National Credit Act 34 of 2005. NCR Registration Number: [PLACEHOLDER].</p>

            <h5 class="mt-4">7. Governing Law</h5>
            <p>These terms are governed by the laws of the Republic of South Africa.</p>

            <div class="alert alert-warning mt-4 small">
                <strong>Legal notice:</strong> This is placeholder T&amp;C text. Have this document reviewed and approved by a legal professional before go-live.
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
