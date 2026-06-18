<?php
require_once 'includes/config.php';
$page_title = 'Your Salary Boost';
include 'includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero" id="home">
    <div class="wrap">
        <div>
            <span class="eyebrow">● Available 24 hours · 100% online</span>
            <h1>Get the salary you've <span class="hl">already earned</span> — today.</h1>
            <p class="lead">GreenCash gives salaried South Africans fast, fair access to a portion of their earned pay. No queues, no paperwork mountains. Apply in minutes and get a decision quickly.</p>
            <div class="hero-actions">
                <a href="#apply" class="btn btn-primary">Apply for cash →</a>
                <a href="#how" class="btn btn-ghost btn-ghost--on-dark">See how it works</a>
            </div>
            <div class="trust-row">
                <div class="ti"><b>R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?>–R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?></b>Loan range</div>
                <div class="ti"><b>24 hrs</b>Always open</div>
                <div class="ti"><b>Minutes</b>To apply</div>
            </div>
        </div>

        <!-- Calculator -->
        <div class="calc-card" id="calc">
            <h3>How much do you need?</h3>
            <p class="sub">Move the slider for an instant estimate.</p>
            <div class="slider-group">
                <label>Loan amount <span class="val" id="amtVal">R <?= number_format(5000, 0, '.', ' ') ?></span></label>
                <input type="range" id="amt" min="<?= (int) MIN_LOAN_AMOUNT ?>" max="<?= (int) MAX_LOAN_AMOUNT ?>" step="500" value="5000">
            </div>
            <div class="calc-out">
                <div class="calc-row"><span>Principal</span><span id="oPrincipal">R 5 000</span></div>
                <div class="calc-row"><span>Service fee (15%)</span><span id="oFees">R 750</span></div>
                <div class="calc-row total"><span>Total repayable</span><span id="oTotal">R 5 750</span></div>
            </div>
            <a href="#apply" class="btn btn-primary btn-block">Apply for this amount</a>
            <p class="calc-note">Estimate only. Final cost shown before you accept, in line with the National Credit Act.</p>
        </div>
    </div>
</section>

<!-- ============ STRIP ============ -->
<div class="strip">
    <div class="wrap">
        <span>📍 <b>Odyssey Mall, Ballito</b></span>
        <span>🔒 POPIA-aligned data handling</span>
        <span>⚡ Decisions in minutes</span>
        <span>🤝 Employer salary-advance partner</span>
    </div>
</div>

<!-- ============ HOW IT WORKS ============ -->
<section class="block" id="how">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Simple &amp; fast</div>
            <h2>Four steps to your salary boost</h2>
            <p>From application to payout — designed to be quick, transparent and stress-free.</p>
        </div>
        <div class="steps">
            <div class="step"><div class="num">1</div><h4>Apply online</h4><p>Fill in the secure form below in just a few minutes, any time of day.</p></div>
            <div class="step"><div class="num">2</div><h4>Upload documents</h4><p>Add your ID, latest payslip and proof of bank account.</p></div>
            <div class="step"><div class="num">3</div><h4>Get a decision</h4><p>We assess affordability and confirm your offer quickly.</p></div>
            <div class="step"><div class="num">4</div><h4>Receive your cash</h4><p>Once accepted, funds are paid directly into your bank account.</p></div>
        </div>
    </div>
</section>

<!-- ============ PRODUCTS ============ -->
<section class="block" id="products" style="background:var(--paper-2)">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Our products</div>
            <h2>Borrowing built around your payday</h2>
            <p>Whether you need a small bridge or access to pay you've already earned, we have an option.</p>
        </div>
        <div class="grid-3">
            <div class="card">
                <div class="ico">💸</div>
                <h3>Payday Loan</h3>
                <p>A short-term loan from R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?> to R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?> to bridge you to your next salary.</p>
            </div>
            <div class="card">
                <span class="tag">Popular</span>
                <div class="ico y">📈</div>
                <h3>Earned Wage Access</h3>
                <p>Through partner employers, draw down a portion of the salary you've already worked for — before payday.</p>
            </div>
            <div class="card">
                <div class="ico">🔁</div>
                <h3>Repeat Customer</h3>
                <p>Built a good track record with us? Enjoy faster approvals and higher limits on your next application.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
