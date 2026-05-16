<?php
require_once 'includes/config.php';
$page_title = 'Get Your Salary In Advance — Today';
include 'includes/header.php';
?>

<!-- ===================== HERO ===================== -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-3">Get Your Salary In Advance — Today</h1>
                <p class="mb-4">Fast, secure salary advance loans from R1,000 to R10,000. Repaid in 1 month. No hidden fees.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/apply.php" class="btn btn-light btn-lg fw-semibold px-4">
                        <i class="bi bi-pencil-square me-2"></i>Apply Now
                    </a>
                    <a href="#calculator" id="checkRate" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-calculator me-2"></i>Check My Rate
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center pt-4 pt-lg-0">
                <i class="bi bi-cash-stack" style="font-size:8rem; opacity:0.3;"></i>
            </div>
        </div>
    </div>
</section>

<!-- ===================== HOW IT WORKS ===================== -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle mt-2">Get your salary advance in three simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <h5 class="fw-semibold">Apply Online</h5>
                    <p class="text-muted small">Fill in our simple application form in under 5 minutes from your phone or computer.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="bi bi-patch-check"></i></div>
                    <h5 class="fw-semibold">Get Approved</h5>
                    <p class="text-muted small">Our team reviews your application quickly. Most decisions are made within 24 hours.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="bi bi-bank"></i></div>
                    <h5 class="fw-semibold">Receive Funds</h5>
                    <p class="text-muted small">Once approved, funds are transferred directly to your bank account — same day.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CALCULATOR ===================== -->
<section class="py-5 bg-light" id="calculator">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How Much Do You Need?</h2>
            <p class="section-subtitle mt-2">Use our calculator to see your exact repayment amount before you apply.</p>
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

            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/apply.php" class="btn btn-gc w-100 mt-4 py-2">
                <i class="bi bi-pencil-square me-2"></i>Apply Now
            </a>
            <p class="text-center text-muted small mt-2 mb-0">No obligation. Takes less than 5 minutes.</p>
        </div>
    </div>
</section>

<!-- ===================== TRUST SIGNALS ===================== -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-clock-fill"></i></div>
                    <h6 class="fw-semibold mb-1">Fast Approval</h6>
                    <p>Decision within 24 hours of submission</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-shield-check"></i></div>
                    <h6 class="fw-semibold mb-1">Secure &amp; Safe</h6>
                    <p>Your data is encrypted and never shared</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trust-badge">
                    <div class="trust-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <h6 class="fw-semibold mb-1">NCR Registered</h6>
                    <p>Fully compliant with South African regulations</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
