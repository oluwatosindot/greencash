/* ============================================================
   Green Cash — main.js
   Calculator widget logic. Loaded by footer.php on all public pages.
   IMPORTANT: Guard all getElementById calls — this script runs on
   every public page, most of which do not have a calculator widget.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Loan Calculator ----
    const FEE_RATE = 0.15; // 15% flat service fee

    // Null guard: main.js loads on every public page via footer.php.
    // Pages without the calculator widget return null — bail out early.
    // All element lookups must come AFTER this guard.
    const slider = document.getElementById('loanSlider');
    if (!slider) return;

    const elAmount    = document.getElementById('loanAmount');
    const elFee       = document.getElementById('serviceFee');
    const elRepayment = document.getElementById('totalRepayment');
    const elSliderVal = document.getElementById('sliderValue');

    // Format as "R 1,000.00" — regex-based for consistent comma separators.
    // Do NOT use toLocaleString('en-ZA') — SA locale uses spaces, not commas.
    function formatR(n) {
        return 'R ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateCalc() {
        var loan  = parseFloat(slider.value);
        var fee   = loan * FEE_RATE;
        var total = loan + fee;

        if (elSliderVal)  elSliderVal.textContent  = formatR(loan);
        if (elAmount)     elAmount.textContent      = formatR(loan);
        if (elFee)        elFee.textContent         = formatR(fee);
        if (elRepayment)  elRepayment.textContent   = formatR(total);
    }

    slider.addEventListener('input', updateCalc);
    updateCalc(); // populate on page load

    // ---- Smooth scroll for "Check My Rate" CTA on homepage ----
    var checkRateBtn = document.getElementById('checkRate');
    if (checkRateBtn) {
        checkRateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById('calculator');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

});