// Year (footer)
const yr = document.getElementById('yr');
if (yr) yr.textContent = new Date().getFullYear();

// Flash dismisser
document.querySelectorAll('.flash .x').forEach(btn =>
  btn.addEventListener('click', () => btn.closest('.flash').remove())
);

// Mobile nav burger
const burger = document.getElementById('burger');
const navlinks = document.getElementById('navlinks');
if (burger && navlinks) {
  burger.addEventListener('click', () => navlinks.classList.toggle('open'));
  navlinks.querySelectorAll('a').forEach(a =>
    a.addEventListener('click', () => navlinks.classList.remove('open'))
  );
}

// Helper: fmt + paintSlider — used by both the hero calculator and any standalone slider
const fmt = n => 'R ' + Math.round(n).toLocaleString('en-ZA');
function paintSlider(el){
  const min = +el.min, max = +el.max;
  const p = ((el.value - min) / (max - min)) * 100;
  el.style.setProperty('--p', p + '%');
}

// Calculator (hero card on index.php)
const amt = document.getElementById('amt');
if (amt) {
  const amtVal = document.getElementById('amtVal');
  const oPrincipal = document.getElementById('oPrincipal');
  const oFees = document.getElementById('oFees');
  const oTotal = document.getElementById('oTotal');
  const FEE_RATE = 0.15; // matches includes/config.php / apply.php backend
  function calc(){
    const a = +amt.value;
    const fee = a * FEE_RATE;
    amtVal.textContent = fmt(a);
    if (oPrincipal) oPrincipal.textContent = fmt(a);
    if (oFees) oFees.textContent = fmt(fee);
    if (oTotal) oTotal.textContent = fmt(a + fee);
    paintSlider(amt);
  }
  amt.addEventListener('input', calc);
  calc();
}

// Form Step 3 loan-amount slider — mirrors value into the label & paints the fill
const loanSlider = document.getElementById('loanAmountSlider');
const loanSliderLabel = document.getElementById('loanAmtVal');
if (loanSlider && loanSliderLabel) {
  const sync = () => { loanSliderLabel.textContent = fmt(+loanSlider.value); paintSlider(loanSlider); };
  loanSlider.addEventListener('input', sync);
  sync();
}

// Multi-step apply form (index.php #apply)
const loanForm = document.getElementById('loanForm');
if (loanForm) {
  const steps = [...loanForm.querySelectorAll('.fstep')];
  const bar = [...document.querySelectorAll('#stepsBar .sb')];
  let cur = 0;

  function goto(i){
    steps.forEach(s => s.classList.remove('active'));
    steps[i].classList.add('active');
    bar.forEach((b, bi) => {
      b.classList.toggle('active', bi === i);
      b.classList.toggle('done', bi < i);
    });
    cur = i;
    document.getElementById('apply').scrollIntoView({behavior: 'smooth'});
  }

  function validateStep(i){
    let ok = true;
    steps[i].querySelectorAll('[required]').forEach(inp => {
      const field = inp.closest('.field') || inp.closest('.check');
      let bad;
      if (inp.type === 'checkbox') bad = !inp.checked;
      else if (inp.type === 'file') bad = inp.files.length === 0;
      else bad = !inp.value.trim();
      if (inp.name === 'id_number' && inp.value && !/^\d{13}$/.test(inp.value.trim())) bad = true;
      if (inp.type === 'email' && inp.value && !/^[^@]+@[^@]+\.[^@]+$/.test(inp.value)) bad = true;
      if (field) field.classList.toggle('invalid', bad);
      if (bad) ok = false;
    });
    return ok;
  }

  loanForm.querySelectorAll('[data-next]').forEach(b =>
    b.addEventListener('click', () => { if (validateStep(cur)) goto(cur + 1); })
  );
  loanForm.querySelectorAll('[data-prev]').forEach(b =>
    b.addEventListener('click', () => goto(cur - 1))
  );

  // File list display
  ['id_document', 'payslip', 'bank_statement'].forEach(name => {
    const inp = loanForm.querySelector(`input[name="${name}"]`);
    if (!inp) return;
    inp.addEventListener('change', () => {
      const field = inp.closest('.field');
      if (!field) return;
      const label = field.querySelector('.file-chosen');
      if (label) label.textContent = inp.files.length ? inp.files[0].name : '';
    });
  });

  // Sync calculator amount into Step 3's loan_amount input
  const loanAmount = loanForm.querySelector('input[name="loan_amount"]');
  document.querySelectorAll('a[href$="#apply"]').forEach(a => {
    a.addEventListener('click', () => {
      if (loanAmount && amt && !loanAmount.value) loanAmount.value = amt.value;
    });
  });
  // NOTE: No submit handler — the form posts to apply.php for real backend handling.
}

// FAQ accordion
document.querySelectorAll('.q button').forEach(btn => {
  btn.addEventListener('click', () => {
    const q = btn.parentElement;
    const ans = q.querySelector('.ans');
    const open = q.classList.contains('open');
    document.querySelectorAll('.q').forEach(x => {
      x.classList.remove('open');
      const ansEl = x.querySelector('.ans');
      if (ansEl) ansEl.style.maxHeight = null;
    });
    if (!open && ans) {
      q.classList.add('open');
      ans.style.maxHeight = ans.scrollHeight + 'px';
    }
  });
});
