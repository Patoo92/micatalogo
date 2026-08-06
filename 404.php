<?php
$csp_nonce = bin2hex(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$csp_nonce}' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' https: data:; connect-src 'self' https://api.iconify.design; frame-src 'none'; object-src 'none'");
header("HTTP/1.1 404 Not Found");
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada — MiCatalogo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; background: #f8fafc; }
        .error-code { font-size: 6rem; font-weight: 800; color: #2fb344; line-height: 1; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="card p-5 text-center" style="max-width:480px;width:100%;">
        <div class="error-code">404</div>
        <h2 class="fw-bold mt-2">Página no encontrada</h2>
        <p class="text-muted mb-4">La página que buscas no existe o fue eliminada.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="index.html" class="btn btn-primary">Ir al inicio</a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary" nonce="<?= $csp_nonce ?>">Volver atrás</a>
        </div>
    </div>
</body>
</html>
