<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi(true);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id   = (int)($_POST['document_id'] ?? 0);
$filename = trim($_POST['filename'] ?? '', '/');
$newname  = trim($_POST['new_filename'] ?? '', '/');

if (!$filename || !$newname) { echo json_encode(['ok'=>false,'error'=>'Filename required']); exit; }
if (strpos($filename, '..') !== false || strpos($newname, '..') !== false) {
    echo json_encode(['ok'=>false,'error'=>'Invalid filename']); exit;
}
if (!preg_match('/^[a-zA-Z0-9._\-]+(\/[a-zA-Z0-9._\-]+)*$/', $newname)) {
    echo json_encode(['ok'=>false,'error'=>'Invalid filename']); exit;
}

$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$found) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$base    = __DIR__ . "/../data/{$doc_id}";
$oldPath = "{$base}/{$filename}";
$newPath = "{$base}/{$newname}";

if (!is_file($oldPath)) { echo json_encode(['ok'=>false,'error'=>'File not found']); exit; }

$newDir = dirname($newPath);
if (!is_dir($newDir)) mkdir($newDir, 0777, true);
rename($oldPath, $newPath);

echo json_encode(['ok' => true, 'filename' => $newname]);
