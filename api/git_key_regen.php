<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$dir = _gitKeyDir($user['sub']);
$key = _gitKeyPath($user['sub']);

foreach ([$key, $key . '.pub'] as $f) {
    if (file_exists($f)) unlink($f);
}

$pub = ensureSshKey($user['sub']);
if (!$pub) {
    echo json_encode(['ok' => false, 'error' => 'Key generation failed']);
    exit;
}
echo json_encode(['ok' => true, 'public_key' => $pub]);
