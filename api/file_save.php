<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id      = (int)($_POST['id'] ?? 0);
$doc_id  = (int)($_POST['document_id'] ?? 0);
$content = $_POST['content'] ?? '';

$stmt = $db->prepare(
    "UPDATE typst_project_files pf
     JOIN typst_documents d ON d.id = pf.document_id
     SET pf.content=?, pf.updated_at=NOW()
     WHERE pf.id=? AND pf.document_id=? AND d.owner=?"
);
$stmt->bind_param("siis", $content, $id, $doc_id, $user['sub']);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['ok'=>true]);
