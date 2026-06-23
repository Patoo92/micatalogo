<?php
require_once 'init_session.php';
session_destroy();
header("Location: login-admin.php");
exit;
