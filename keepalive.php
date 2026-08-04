<?php
require_once 'init_session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

$activa = isset($_SESSION['tienda_id']) || isset($_SESSION['admin_id']);
echo json_encode(['ok' => $activa, 'time' => time(), 'expira' => time() + 1800]);
