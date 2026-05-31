<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$name  = trim($_POST['commit_name']  ?? '');
$email = trim($_POST['commit_email'] ?? '');
if (!$name || !$email) {
    echo json_encode(['ok' => false, 'error' => 'Name and email are required']);
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok' => false, 'error' => 'DB error']); exit; }

$stmt = $db->prepare(
    "INSERT INTO git_user_settings (user_sub, commit_name, commit_email)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE commit_name=VALUES(commit_name), commit_email=VALUES(commit_email)"
);
$stmt->bind_param("sss", $user['sub'], $name, $email);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['ok' => true]);
