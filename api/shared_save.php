<?php
header('Content-Type: application/json');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$token = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? '');
if ($token === '') { echo json_encode(['ok'=>false,'error'=>'Invalid token']); exit; }

$stmt = $db->prepare("SELECT document_id, access FROM document_shares WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row || $row['access'] !== 'edit') {
    $db->close();
    echo json_encode(['ok'=>false,'error'=>'Not allowed']);
    exit;
}

$id      = (int)$row['document_id'];
$content = $_POST['content'] ?? '';
$title   = trim($_POST['title'] ?? '');
if (!$title) $title = 'Untitled Document';

$stmt = $db->prepare("UPDATE typst_documents SET title=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param("si", $title, $id);
$stmt->execute();
$stmt->close();
$db->close();

$dir = __DIR__ . "/../data/{$id}";
if (!is_dir($dir)) mkdir($dir, 0777, true);
file_put_contents($dir . '/main.typ', $content);

echo json_encode(['ok'=>true]);
