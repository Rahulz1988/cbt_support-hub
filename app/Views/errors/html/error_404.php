<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 — CBT Support Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    </style>
</head>
<body>
    <div class="text-center">
        <div style="font-size:5rem;font-weight:700;color:#e2e8f0;">404</div>
        <h4 class="fw-bold text-dark">Page Not Found</h4>
        <p class="text-muted">The page you are looking for doesn't exist.</p>
        <a href="<?= site_url('login') ?>" class="btn btn-primary btn-sm mt-2">Go to Login</a>
    </div>
</body>
</html>
