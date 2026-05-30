<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id      = (int)($_POST['id'] ?? 0);
$content = $_POST['content'] ?? '';
$title   = trim($_POST['title'] ?? '');
if (!$title) $title = 'Untitled Document';

// owner check is part of the WHERE so a wrong user silently saves nothing
$stmt = $db->prepare("UPDATE typst_documents SET content=?, title=?, updated_at=NOW() WHERE id=? AND owner=?");
$stmt->bind_param("ssis", $content, $title, $id, $user['sub']);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['ok'=>true]);
