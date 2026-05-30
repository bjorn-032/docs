<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id     = (int)($_POST['id'] ?? 0);
$doc_id = (int)($_POST['document_id'] ?? 0);

$stmt = $db->prepare(
    "SELECT pf.content FROM typst_project_files pf
     JOIN typst_documents d ON d.id = pf.document_id
     WHERE pf.id=? AND pf.document_id=? AND d.owner=?"
);
$stmt->bind_param("iis", $id, $doc_id, $user['sub']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
$db->close();

if (!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
echo json_encode(['ok'=>true, 'content'=>$row['content']]);
