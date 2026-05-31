<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi(true);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id      = (int)($_POST['id'] ?? 0);
$content = $_POST['content'] ?? '';
$title   = trim($_POST['title'] ?? '');
if (!$title) $title = 'Untitled Document';

$stmt = $db->prepare("UPDATE typst_documents SET title=?, updated_at=NOW() WHERE id=? AND owner=?");
$stmt->bind_param("sis", $title, $id, $user['sub']);
$stmt->execute();
$owned = $stmt->affected_rows > 0;
$stmt->close();
$db->close();

if (!$owned) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$upload_dir = __DIR__ . "/../data/{$id}";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
file_put_contents($upload_dir . '/main.typ', $content);

echo json_encode(['ok'=>true]);
