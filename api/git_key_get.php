<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$pub = ensureSshKey($user['sub']);
if (!$pub) {
    echo json_encode(['ok' => false, 'error' => 'Failed to generate SSH key']);
    exit;
}
echo json_encode(['ok' => true, 'public_key' => $pub]);
