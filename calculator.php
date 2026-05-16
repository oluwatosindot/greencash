<?php
require_once 'includes/config.php';
$page_title = 'Loan Calculator';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="text-center mb-4">
            <h1 class="section-title">Loan Calculator</h1>
            <p class="text-muted mt-2">See exactly what you'll repay before you apply. No surprises.</p>
        </div>

        <div class="calculator-widget">
            <label class="fw-semibold mb-1 d-flex justify-content-between">
                <span>Loan Amount</span>
                <span id="sliderValue" class="text-success fw-bold">R 5,000.00</span>
            </label>
            <input type="range" class="form-range mb-4"
                   id="loanSlider" min="1000" max="10000" step="500" value="5000">

            <div class="calc-result-row">
                <span class="label">Loan Amount</span>
                <span class="value" id="loanAmount">R 5,000.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Service Fee (15%)</span>
                <span class="value" id="serviceFee">R 750.00</span>
            </div>
            <div class="calc-result-row">
                <span class="label">Loan Term</span>
                <span class="value">1 Month</span>
            </div>
            <div class="calc-result-row total mt-2">
                <span class="label fw-semibold">Total Repayment</span>
                <span class="value" id="totalRepayment">R 5,750.00</span>
            </div>

            <a href="<?= APP_URL ?>/apply.php" class="btn btn-gc w-100 mt-4 py-2">
                <i class="bi bi-pencil-square me-2"></i>Apply Now
            </a>
            <p class="text-center text-muted small mt-2 mb-0">
                Borrow R1,000–R10,000 · 1 month term · 15% flat fee
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
