<?php
require_once 'includes/config.php';
$page_title = 'Terms & Conditions';
include 'includes/header.php';
?>

<section class="block" style="background:var(--paper-2)">
    <div class="wrap" style="max-width:780px">
        <div class="sec-head" style="text-align:left;margin-bottom:30px">
            <div class="kicker">Legal</div>
            <h2>Terms &amp; Conditions</h2>
        </div>
        <div class="prose" style="line-height:1.8;color:var(--ink)">
            <p style="color:var(--muted)">Last updated: <?= date('F Y') ?></p>

            <h3 style="margin-top:30px">1. Loan Product</h3>
            <p>Green Cash offers one loan product: a Salary Advance loan for permanently employed South African citizens and residents.</p>

            <h3 style="margin-top:30px">2. Loan Amount &amp; Term</h3>
            <ul>
                <li>Minimum loan: <strong>R1,000</strong></li>
                <li>Maximum loan: <strong>R10,000</strong></li>
                <li>Loan term: <strong>1 calendar month</strong></li>
                <li>Repayment date: Your next salary date as declared on the application</li>
            </ul>

            <h3 style="margin-top:30px">3. Fees &amp; Costs</h3>
            <ul>
                <li>Service fee: <strong>15% of the loan amount</strong> (flat, once-off)</li>
                <li>Example: A loan of R5,000 carries a service fee of R750. Total repayment: R5,750.</li>
                <li>No hidden fees. No early settlement penalties.</li>
            </ul>

            <h3 style="margin-top:30px">4. Eligibility</h3>
            <p>Applicants must be:</p>
            <ul>
                <li>South African citizen or permanent resident with a valid SA ID</li>
                <li>Permanently employed (not self-employed or contract workers, unless approved)</li>
                <li>18 years of age or older</li>
                <li>Able to demonstrate affordability</li>
            </ul>

            <h3 style="margin-top:30px">5. Repayment</h3>
            <p>The full repayment amount (loan + service fee) is due on your next salary date. Failure to repay may result in additional charges and adverse credit bureau listings.</p>

            <h3 style="margin-top:30px">6. NCR Registration</h3>
            <p>Green Cash is a registered credit provider in terms of the National Credit Act 34 of 2005. NCR Registration Number: [PLACEHOLDER].</p>

            <h3 style="margin-top:30px">7. Governing Law</h3>
            <p>These terms are governed by the laws of the Republic of South Africa.</p>

            <p style="margin-top:24px;padding:16px;background:var(--paper-1);border-left:3px solid var(--accent);font-size:0.95em">
                <strong>Legal notice:</strong> This is placeholder T&amp;C text. Have this document reviewed and approved by a legal professional before go-live.
            </p>

            <h2 id="pre-agreement" style="margin-top:40px">Pre-agreement disclosure</h2>
            <p style="color:var(--muted);margin-top:10px">[Placeholder &mdash; compliance to provide pre-agreement disclosure statement covering total cost of credit, interest rate, fees, and repayment schedule in line with the National Credit Act 34 of 2005.]</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
