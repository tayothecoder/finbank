<?php

// secure file server - validates ownership and streams files

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

if (!\Core\Session::isLoggedIn() || !\Core\Session::get('pin_verified')) {
    http_response_code(403);
    exit('Access denied');
}

$sessionUser = \Core\Session::getUser();
$user = \Models\Account::findByInternetId($sessionUser['internet_id']);
if (!$user) {
    http_response_code(403);
    exit('Access denied');
}

$filename = $_GET['file'] ?? '';
$type = $_GET['type'] ?? 'kyc';

// prevent directory traversal
if (empty($filename) || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
    http_response_code(400);
    exit('Invalid request');
}

// determine file path and validate ownership
if ($type === 'kyc') {
    // check if the file belongs to this user
    $owned = ($user['id_front'] === $filename || $user['id_back'] === $filename || $user['proof_of_address'] === $filename);
    if (!$owned) {
        http_response_code(403);
        exit('Access denied');
    }
    $filePath = __DIR__ . '/../uploads/kyc/' . $filename;
} elseif ($type === 'ticket') {
    // verify ticket attachment belongs to user
    $db = \Core\Database::connect();
    $stmt = $db->prepare('SELECT id FROM tickets WHERE internet_id = :iid AND attachment = :att LIMIT 1');
    $stmt->execute(['iid' => $user['internet_id'], 'att' => $filename]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit('Access denied');
    }
    $filePath = __DIR__ . '/../uploads/tickets/' . $filename;
} else {
    http_response_code(400);
    exit('Invalid type');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// detect content type and stream
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=3600');
readfile($filePath);
exit;
