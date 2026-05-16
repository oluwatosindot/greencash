<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — Green Cash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gc-primary: #2E7D32; }
        body { font-family: 'Inter', sans-serif; }
        .page-404 {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex-direction: column;
            padding: 2rem;
        }
        .error-code {
            font-size: clamp(5rem, 15vw, 10rem);
            font-weight: 700;
            color: var(--gc-primary);
            line-height: 1;
        }
    </style>
</head>
<body>
<div class="page-404">
    <div class="error-code">404</div>
    <h1 class="h3 fw-semibold mt-3 mb-2">Page Not Found</h1>
    <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
    <div class="d-flex gap-3 flex-wrap justify-content-center">
        <a href="/" class="btn btn-success px-4">
            <i class="bi bi-house me-2"></i>Go Home
        </a>
        <a href="/apply.php" class="btn btn-outline-success px-4">
            <i class="bi bi-pencil-square me-2"></i>Apply Now
        </a>
    </div>
</div>
</body>
</html>
