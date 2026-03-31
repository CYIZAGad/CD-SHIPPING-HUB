<?php
require_once 'config/database.php';
$_SESSION = [];
session_destroy();
header("Location: " . SITE_URL);
exit;
