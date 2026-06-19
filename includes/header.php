<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
    <style>
        /* Critical preloader CSS — inlined so the loader works before style.css finishes loading */
        .preloader{position:fixed;inset:0;z-index:9999;background:#121C14;display:flex;align-items:center;justify-content:center;transition:opacity .4s ease}
        .preloader.fade-out{opacity:0;pointer-events:none}
        .preloader img{max-width:340px;width:62%;height:auto;animation:gc-preloader-pulse 1.6s ease-in-out infinite;filter:drop-shadow(0 0 32px rgba(26,166,54,.45))}
        @keyframes gc-preloader-pulse{0%,100%{transform:scale(.94);opacity:.78}50%{transform:scale(1.06);opacity:1}}
        @media (prefers-reduced-motion: reduce){.preloader img{animation:none}}
    </style>
</head>
<body>

<div class="preloader" id="gc-preloader" aria-hidden="true">
    <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/GreenCash_Logo_Dark.png" alt="">
</div>
<script>
// Hide preloader when the page is fully loaded. Minimum visible time so super-fast pages
// don't flash the loader for a single frame. Respects prefers-reduced-motion (no fade delay).
(function(){
    var p = document.getElementById('gc-preloader');
    if (!p) return;
    var t0 = Date.now();
    var minMs = 800;
    function hide(){
        var wait = Math.max(0, minMs - (Date.now() - t0));
        setTimeout(function(){
            p.classList.add('fade-out');
            setTimeout(function(){ if (p.parentNode) p.parentNode.removeChild(p); }, 500);
        }, wait);
    }
    if (document.readyState === 'complete') hide();
    else window.addEventListener('load', hide);
})();
</script>

<header>
    <div class="wrap nav">
        <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="brand">
            <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/GreenCash_Logo_Dark.png" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" style="height:70px;display:block">
        </a>
        <nav class="navlinks" id="navlinks">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Loans</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php">Track</a>
        </nav>
        <div class="nav-cta">
            <?php /* Sign in hidden until admin/customer portal is wired up */ ?>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-yellow">Apply now</a>
            <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<?php $flash = getFlash(); if ($flash):
    $type = in_array($flash['type'], ['success','error','warning','info'], true) ? $flash['type'] : 'info';
?>
<div class="wrap" style="padding-top:18px">
    <div class="flash flash--<?= $type ?>">
        <span><?= sanitize($flash['message']) ?></span>
        <button class="x" type="button" aria-label="Dismiss">×</button>
    </div>
</div>
<?php endif; ?>

<main>
