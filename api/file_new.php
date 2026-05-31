<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id   = (int)($_POST['document_id'] ?? 0);
$filename = trim($_POST['filename'] ?? '', '/');

if (!$filename) { echo json_encode(['ok'=>false,'error'=>'Filename required']); exit; }
if (!preg_match('/^[a-zA-Z0-9._\-]+(\/[a-zA-Z0-9._\-]+)*$/', $filename)) {
    echo json_encode(['ok'=>false,'error'=>'Invalid filename']); exit;
}
if (strpos(basename($filename), '.') === false) $filename .= '.typ';

$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$found) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$target = __DIR__ . "/../data/{$doc_id}/{$filename}";
$dir    = dirname($target);
if (!is_dir($dir)) mkdir($dir, 0777, true);
if (!file_exists($target)) file_put_contents($target, '');

echo json_encode(['ok' => true, 'id' => $filename, 'filename' => $filename]);
