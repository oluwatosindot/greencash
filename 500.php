<?php
http_response_code(500);
require_once 'includes/config.php';
$page_title = 'Something went wrong';
$page_description = 'A temporary problem stopped this page from loading. Please try again in a moment.';
include 'includes/header.php';
?>

<section class="block" style="min-height:calc(100vh - 280px);display:grid;place-items:center;text-align:center">
    <div class="wrap" style="max-width:520px">
        <div style="font-family:'Sora',sans-serif;font-size:8rem;font-weight:800;color:var(--green-deep);line-height:1">500</div>
        <h1 style="margin-top:20px">Something went wrong on our side</h1>
        <p style="color:var(--muted);margin:14px 0 30px">We've logged the error and our team is looking at it. Please try again in a moment.</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">← Back to home</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php" class="btn btn-ghost">Contact us</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
