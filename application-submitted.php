<?php
require_once 'includes/config.php';

// Guard — must arrive via successful apply.php submission
if (empty($_SESSION['app_reference'])) {
    redirect('/apply.php');
}

$ref  = $_SESSION['app_reference'];
$name = $_SESSION['app_name'] ?? 'Applicant';
unset($_SESSION['app_reference'], $_SESSION['app_name']);

$page_title = 'Application Submitted';
include 'includes/header.php';
?>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7 text-center">

    <div class="card border-0 shadow-sm p-5">
        <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
        <h2 class="mt-3 fw-bold">Application Received!</h2>
        <p class="text-muted">Thank you, <?= sanitize($name) ?>. We&rsquo;ve received your salary advance application and will review it shortly.</p>

        <div class="bg-light rounded-3 p-4 my-4">
            <p class="text-muted small mb-1">Your Reference Number</p>
            <h3 class="fw-bold text-success mb-0" id="refNum"><?= sanitize($ref) ?></h3>
            <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyRef()">
                <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <p class="text-muted small mt-2 mb-0">Save this number &mdash; you&rsquo;ll need it to track your application.</p>
        </div>

        <h6 class="fw-semibold mb-3">What happens next?</h6>
        <div class="text-start">
            <div class="d-flex gap-3 mb-3">
                <div class="text-success fw-bold fs-5">1.</div>
                <div><strong>Review</strong> &mdash; Our team reviews your application and documents (within 24 hours on business days).</div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="text-success fw-bold fs-5">2.</div>
                <div><strong>Decision</strong> &mdash; You&rsquo;ll be notified by email once a decision has been made.</div>
            </div>
            <div class="d-flex gap-3">
                <div class="text-success fw-bold fs-5">3.</div>
                <div><strong>Disbursement</strong> &mdash; If approved, funds are transferred within 1 business day.</div>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
            <a href="track-application.php" class="btn btn-success px-4">
                <i class="bi bi-search me-1"></i>Track My Application
            </a>
            <a href="index.php" class="btn btn-outline-secondary px-4">Back to Home</a>
        </div>
    </div>

</div>
</div>
</div>
</section>

<script>
function copyRef() {
    var ref = document.getElementById('refNum').textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(ref).then(function () {
            alert('Reference number copied to clipboard!');
        });
    } else {
        // Fallback for older browsers
        var ta = document.createElement('textarea');
        ta.value = ref;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        alert('Reference number copied!');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
