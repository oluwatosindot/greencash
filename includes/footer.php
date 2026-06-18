</main>

<footer>
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-brand">
                <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/logo02.jpeg" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" style="height:60px;display:block">
                <p>GreenCash gives salaried South Africans fast, fair access to the salary they've already earned. Apply online, any time.</p>
            </div>
            <div class="foot-col">
                <h4>Company</h4>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Our loans</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            </div>
            <div class="foot-col">
                <h4>Legal</h4>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php">Terms &amp; Conditions</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/privacy-policy.php">Privacy Policy (POPIA)</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php#pre-agreement">Pre-agreement disclosure</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php#complaints">Complaints procedure</a>
            </div>
            <div class="foot-col foot-contact">
                <h4>Contact</h4>
                <b>29 Kariba Crescent, Ballito</b>
                KwaZulu-Natal, South Africa<br>
                <b style="margin-top:8px">Open 24 hours</b>
                +27 (0)78 517 7961<br>
                info@greencash.co.za
            </div>
        </div>
        <div class="foot-bottom">
            <div class="legal"><b>Responsible lending:</b> [NCR registration number — insert before launch]. Lending subject to affordability assessment. Representative cost of credit, interest and fees disclosed before acceptance, in compliance with the National Credit Act 34 of 2005. <i>Placeholder copy — confirm with compliance.</i></div>
            <div>© <span id="yr"></span> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>. All rights reserved.</div>
        </div>
    </div>
</footer>

<a class="wa-float"
   href="https://wa.me/<?= htmlspecialchars(WHATSAPP_NUMBER, ENT_QUOTES, 'UTF-8') ?>?text=<?= rawurlencode(WHATSAPP_PREFILL_MESSAGE) ?>"
   target="_blank" rel="noopener"
   aria-label="Chat with GreenCash on WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0 0 20.465 3.488"/>
    </svg>
</a>

<script src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/main.js" defer></script>
</body>
</html>
