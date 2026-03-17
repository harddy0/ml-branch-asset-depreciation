<?php
$noLayout = true;
require_once __DIR__ . '/../src/includes/init.php';
header('Location: ' . BASE_URL . ($auth->isLoggedIn() ? '/public/dashboard/' : '/public/login/'));
exit;
