</main>

<footer class="mt-5 py-4" style="background:#1B5E20; color:rgba(255,255,255,0.85);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <span class="fw-bold" style="color:#fff; font-size:1.1rem;">
                    <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <p class="small mt-1 mb-0">Get Your Salary In Advance — Today</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0 text-md-center">
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/privacy-policy.php" class="text-white-50 me-3 small text-decoration-none">Privacy Policy</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/terms-and-conditions.php" class="text-white-50 me-3 small text-decoration-none">Terms &amp; Conditions</a>
                <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php" class="text-white-50 small text-decoration-none">Contact</a>
            </div>
            <div class="col-md-4 text-md-end small">
                &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>. All rights reserved.
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/main.js" defer></script>
</body>
</html>
