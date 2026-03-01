<?php

// auth check

require_once __DIR__ . '/../../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
$adminBase = $baseUrl . '/admin';

if (!\Core\Session::isAdmin()) {
    header('Location: ' . $adminBase . '/login.php');
    exit;
}

$conn = \Core\Database::connect();
$adminUser = \Models\Admin::findById(\Core\Session::getAdmin()['id']);

if (!$adminUser) {
    \Core\Session::destroy();
    header('Location: ' . $adminBase . '/login.php');
    exit;
}

$adminName = htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']);
