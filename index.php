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

<!-- ============ EMPLOYERS ============ -->
<section class="block" id="employers">
    <div class="wrap">
        <div class="band">
            <div class="ct">
                <h2>Partner with GreenCash for your team</h2>
                <p>Give your employees responsible access to a portion of their earned salary — a powerful, no-cost financial wellness benefit that reduces stress and boosts retention.</p>
                <a href="mailto:<?= htmlspecialchars(PARTNERSHIP_EMAIL, ENT_QUOTES, 'UTF-8') ?>?subject=Partnership%20enquiry" class="btn btn-yellow">Become a partner →</a>
            </div>
            <ul>
                <li><span class="ck">✓</span><span>Zero cost to your business to set up.</span></li>
                <li><span class="ck">✓</span><span>Reduces payroll advance requests and admin.</span></li>
                <li><span class="ck">✓</span><span>Improves staff financial wellbeing &amp; morale.</span></li>
                <li><span class="ck">✓</span><span>Fully managed, compliant and secure.</span></li>
            </ul>
        </div>
    </div>
</section>

<!-- ============ APPLY ============ -->
<section class="apply" id="apply">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Start now</div>
            <h2>Apply for your loan</h2>
            <p>Complete the secure application below. It takes about 5 minutes. All fields marked with <span style="color:var(--yellow)">*</span> are required.</p>
        </div>

        <div class="form-shell">
            <div class="steps-bar" id="stepsBar">
                <div class="sb active" data-step="0"><div class="dot">1</div><span class="lbl">Personal</span></div>
                <div class="sb" data-step="1"><div class="dot">2</div><span class="lbl">Employment</span></div>
                <div class="sb" data-step="2"><div class="dot">3</div><span class="lbl">Financial</span></div>
                <div class="sb" data-step="3"><div class="dot">4</div><span class="lbl">Documents</span></div>
            </div>

            <form class="form-body" id="loanForm" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/apply.php" enctype="multipart/form-data" novalidate>
                <?= csrfField() ?>

                <!-- STEP 1: PERSONAL -->
                <div class="fstep active" data-step="0">
                    <h3>Personal details</h3>
                    <p class="desc">Tell us who you are. This must match your South African ID.</p>
                    <div class="fgrid">
                        <div class="field"><label>First name <span class="req">*</span></label><input name="first_name" required><div class="err">Please enter your first name.</div></div>
                        <div class="field"><label>Last name <span class="req">*</span></label><input name="last_name" required><div class="err">Please enter your last name.</div></div>
                        <div class="field"><label>Other names</label><input name="other_names"></div>
                        <div class="field"><label>SA ID number <span class="req">*</span></label><input name="id_number" inputmode="numeric" maxlength="13" required><div class="err">Enter a valid 13-digit ID number.</div></div>
                        <div class="field"><label>Mobile number <span class="req">*</span></label><input name="phone" inputmode="tel" placeholder="072 123 4567" required><div class="err">Please enter a valid mobile number.</div></div>
                        <div class="field"><label>Email address <span class="req">*</span></label><input type="email" name="email" placeholder="you@email.com" required><div class="err">Please enter a valid email.</div></div>
                        <div class="field full"><label>Residential address <span class="req">*</span></label><input name="address" placeholder="Street, suburb" required><div class="err">Please enter your address.</div></div>
                        <div class="field"><label>City / Town <span class="req">*</span></label><input name="city" required><div class="err">Required.</div></div>
                        <div class="field"><label>Province <span class="req">*</span></label>
                            <select name="province" required>
                                <option value="">Select…</option>
                                <option value="Gauteng">Gauteng</option>
                                <option value="Western Cape">Western Cape</option>
                                <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                <option value="Eastern Cape">Eastern Cape</option>
                                <option value="Limpopo">Limpopo</option>
                                <option value="Mpumalanga">Mpumalanga</option>
                                <option value="North West">North West</option>
                                <option value="Free State">Free State</option>
                                <option value="Northern Cape">Northern Cape</option>
                            </select>
                            <div class="err">Please select your province.</div>
                        </div>
                        <div class="field"><label>Postal code</label><input name="zip_code" inputmode="numeric" maxlength="10"></div>
                    </div>
                    <div class="form-nav">
                        <span></span>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 2: EMPLOYMENT -->
                <div class="fstep" data-step="1">
                    <h3>Employment</h3>
                    <p class="desc">We use this to assess affordability, as required by the National Credit Act.</p>
                    <div class="fgrid">
                        <div class="field"><label>Employment status <span class="req">*</span></label>
                            <select name="employment_status" required>
                                <option value="">Select…</option>
                                <option value="employed">Permanent / full-time</option>
                                <option value="contract">Contract</option>
                                <option value="self_employed">Self-employed</option>
                            </select>
                            <div class="err">Required.</div>
                        </div>
                        <div class="field"><label>Employment duration <span class="req">*</span></label>
                            <select name="employment_duration" required>
                                <option value="">Select…</option>
                                <option value="less_3m">Less than 3 months</option>
                                <option value="3_6m">3 – 6 months</option>
                                <option value="6_12m">6 – 12 months</option>
                                <option value="1_2y">1 – 2 years</option>
                                <option value="2_5y">2 – 5 years</option>
                                <option value="5y_plus">5+ years</option>
                            </select>
                            <div class="err">Required.</div>
                        </div>
                        <div class="field full"><label>Employer name <span class="req">*</span></label><input name="employer_name" required><div class="err">Required.</div></div>
                        <div class="field"><label>Employer contact <span class="req">*</span></label><input name="employer_contact" inputmode="tel" required><div class="err">Required.</div></div>
                        <div class="field"><label>Job title <span class="req">*</span></label><input name="job_title" required><div class="err">Required.</div></div>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 3: FINANCIAL -->
                <div class="fstep" data-step="2">
                    <h3>Financial</h3>
                    <p class="desc">Tell us about your income and the loan you need.</p>
                    <div class="fgrid">
                        <div class="field"><label>Net monthly salary (R) <span class="req">*</span></label><input name="salary_amount" inputmode="numeric" required><div class="err">Required.</div></div>
                        <div class="field"><label>Next pay date <span class="req">*</span></label><input type="date" name="next_payday_date" required><div class="err">Required.</div></div>
                        <div class="field full">
                            <label>Loan amount (R) <span class="req">*</span> <span class="val" id="loanAmtVal">R 5 000</span></label>
                            <input type="range" name="loan_amount" id="loanAmountSlider" min="<?= (int) MIN_LOAN_AMOUNT ?>" max="<?= (int) MAX_LOAN_AMOUNT ?>" step="500" value="5000">
                        </div>
                        <div class="field"><label>Rent (R/mo)</label><input name="rent" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Food (R/mo)</label><input name="food" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Transport (R/mo)</label><input name="transport" inputmode="numeric" placeholder="0"></div>
                        <div class="field"><label>Other expenses (R/mo)</label><input name="other_expenses" inputmode="numeric" placeholder="0"></div>
                    </div>
                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="button" class="btn btn-primary" data-next>Continue →</button>
                    </div>
                </div>

                <!-- STEP 4: DOCUMENTS & CONSENT -->
                <div class="fstep" data-step="3">
                    <h3>Documents &amp; consent</h3>
                    <p class="desc">Upload supporting documents and confirm your consent to proceed.</p>
                    <div class="field full">
                        <label>SA ID document <span class="req">*</span></label>
                        <label class="upload" for="id_document">
                            <div class="ui">📎 <span class="ub">Click to upload</span> ID copy (PDF/JPG/PNG)</div>
                        </label>
                        <input type="file" id="id_document" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your SA ID.</div>
                    </div>
                    <div class="field full">
                        <label>Latest payslip <span class="req">*</span></label>
                        <label class="upload" for="payslip">
                            <div class="ui">📎 <span class="ub">Click to upload</span> latest payslip (PDF/JPG/PNG)</div>
                        </label>
                        <input type="file" id="payslip" name="payslip" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your latest payslip.</div>
                    </div>
                    <div class="field full">
                        <label>3 months bank statements <span class="req">*</span></label>
                        <label class="upload" for="bank_statement">
                            <div class="ui">📎 <span class="ub">Click to upload</span> bank statements (single PDF or image)</div>
                        </label>
                        <input type="file" id="bank_statement" name="bank_statement" accept=".pdf,.jpg,.jpeg,.png" required style="display:none">
                        <div class="hint file-chosen"></div>
                        <div class="err">Please upload your bank statements.</div>
                    </div>

                    <div style="margin-top:18px">
                        <div class="check"><input type="checkbox" required><span>I confirm the information provided is true and complete, and I consent to a credit &amp; affordability assessment.</span></div>
                        <div class="check"><input type="checkbox" required><span>I agree to the processing of my personal information in line with the <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/privacy-policy.php">Privacy Policy (POPIA)</a>.</span></div>
                        <div class="check"><input type="checkbox" required><span>I have read and accept the <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php">Terms &amp; Conditions</a> and pre-agreement disclosure.</span></div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn btn-ghost" data-prev>← Back</button>
                        <button type="submit" name="submit_application" class="btn btn-primary">Submit application ✓</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ============ FAQ ============ -->
<section class="block" id="faq" style="background:var(--paper-2)">
    <div class="wrap">
        <div class="sec-head">
            <div class="kicker">Good to know</div>
            <h2>Frequently asked questions</h2>
        </div>
        <div class="faq">
            <div class="q"><button type="button">Who can apply for a GreenCash loan?<span class="pm">+</span></button><div class="ans"><p>You must be a salaried employee, 18 or older, a South African citizen or permanent resident, with a valid SA ID, an active bank account into which your salary is paid, and proof of income.</p></div></div>
            <div class="q"><button type="button">How much can I borrow?<span class="pm">+</span></button><div class="ans"><p>Loans range from R<?= number_format(MIN_LOAN_AMOUNT, 0, '.', ' ') ?> to R<?= number_format(MAX_LOAN_AMOUNT, 0, '.', ' ') ?> depending on your income and affordability assessment. First-time customers may start with a lower limit that grows as you build a good repayment history.</p></div></div>
            <div class="q"><button type="button">How fast will I get my money?<span class="pm">+</span></button><div class="ans"><p>Applications are processed quickly, often within minutes during business operations. Once approved and accepted, funds are paid directly to your bank account. Actual payout timing depends on your bank.</p></div></div>
            <div class="q"><button type="button">What documents do I need?<span class="pm">+</span></button><div class="ans"><p>A copy of your SA ID, your most recent payslip, three months of bank statements, and proof of residential address. You can upload these directly in the application form above.</p></div></div>
            <div class="q"><button type="button">What does earned wage access mean?<span class="pm">+</span></button><div class="ans"><p>If your employer partners with GreenCash, you can draw down a portion of the salary you've already worked for during the current pay cycle, rather than waiting until payday.</p></div></div>
            <div class="q"><button type="button">Is my information safe?<span class="pm">+</span></button><div class="ans"><p>Yes. We handle your personal information securely and in line with South Africa's Protection of Personal Information Act (POPIA). Your data is used only to assess and manage your application.</p></div></div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
