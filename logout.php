<?php
require_once 'init_session.php';
if (!isset($_GET['token']) || !isset($_SESSION['_logout_token']) || !hash_equals($_SESSION['_logout_token'], $_GET['token'])) {
    header("Location: login.php");
    exit;
}
unset($_SESSION['_logout_token']);
session_destroy();
header("Location: login.php");
exit;
