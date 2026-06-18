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

<?php include 'includes/footer.php'; ?>
