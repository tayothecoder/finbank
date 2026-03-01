<?php

// admin logout

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';

// clear admin session only
unset($_SESSION['_admin']);

header('Location: ' . $baseUrl . '/admin/login.php');
exit;
