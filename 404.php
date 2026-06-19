<?php
http_response_code(404);
require_once 'includes/config.php';
$page_title = 'Page Not Found';
include 'includes/header.php';
?>

<section class="block" style="min-height:calc(100vh - 280px);display:grid;place-items:center;text-align:center">
    <div class="wrap" style="max-width:520px">
        <div style="font-family:'Sora',sans-serif;font-size:8rem;font-weight:800;color:var(--green-deep);line-height:1">404</div>
        <h1 style="margin-top:20px">We can't find that page</h1>
        <p style="color:var(--muted);margin:14px 0 30px">It may have moved, or the link is wrong. Let's get you somewhere useful.</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">← Back to home</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-ghost">Apply for a loan</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
