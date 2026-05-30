<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id   = (int)($_POST['document_id'] ?? 0);
$filename = trim($_POST['filename'] ?? '');

if (!$filename) { echo json_encode(['ok'=>false,'error'=>'Filename required']); exit; }
$filename = trim($filename, '/');
// Allow paths like chapters/intro.typ — reject traversal or unsafe chars
if (!preg_match('/^[a-zA-Z0-9._\-]+(\/[a-zA-Z0-9._\-]+)*$/', $filename)) {
    echo json_encode(['ok'=>false,'error'=>'Invalid filename']); exit;
}
// Only add .typ when the filename has no extension at all
if (strpos(basename($filename), '.') === false) $filename .= '.typ';

// Verify ownership
$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) { $stmt->close(); $db->close(); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
$stmt->close();

$stmt = $db->prepare("INSERT INTO typst_project_files (document_id, filename, content) VALUES (?, ?, '')");
$stmt->bind_param("is", $doc_id, $filename);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();
$db->close();

echo json_encode(['ok'=>true, 'id'=>$new_id, 'filename'=>$filename]);
