<?php
require_once __DIR__ . '/conexion.php';

$base = _getenv('SITE_URL');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$base = rtrim($base, '/');

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: no-cache, max-age=3600');

$urls = [
    ['loc' => $base . '/',                       'freq' => 'daily',   'prio' => '1.0'],
    ['loc' => $base . '/index.html',             'freq' => 'monthly', 'prio' => '0.8'],
    ['loc' => $base . '/registro.php',           'freq' => 'monthly', 'prio' => '0.5'],
    ['loc' => $base . '/login.php',              'freq' => 'monthly', 'prio' => '0.3'],
    ['loc' => $base . '/privacidad.php',         'freq' => 'yearly',  'prio' => '0.2'],
];

try {
    $stmt = $pdo->query("SELECT id, slug FROM tiendas WHERE activo = 1");
    $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tiendas = [];
}

foreach ($tiendas as $t) {
    $urls[] = ['loc' => $base . '/?tienda=' . rawurlencode($t['slug']), 'freq' => 'daily', 'prio' => '0.9'];
}

if (!empty($tiendas)) {
    $ids = array_column($tiendas, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT id, tienda_id, updated_at FROM productos WHERE tienda_id IN ($placeholders) AND stock > 0 ORDER BY id DESC LIMIT 5000");
        $stmt->execute(array_values($ids));
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $productos = [];
    }
    $slug_map = array_column($tiendas, 'slug', 'id');
    foreach ($productos as $p) {
        $slug = $slug_map[$p['tienda_id']] ?? null;
        if (!$slug) continue;
        $urls[] = [
            'loc'     => $base . '/producto.php?tienda=' . rawurlencode($slug) . '&id=' . (int)$p['id'],
            'freq'    => 'weekly',
            'prio'    => '0.7',
            'lastmod' => substr((string)$p['updated_at'], 0, 10),
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
    }
    echo '    <changefreq>' . $u['freq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $u['prio'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}
echo '</urlset>' . "\n";
