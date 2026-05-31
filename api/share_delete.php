<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id = (int)($_POST['document_id'] ?? 0);

// Verify ownership then delete
$stmt = $db->prepare(
    "DELETE ds FROM document_shares ds
     JOIN typst_documents td ON td.id = ds.document_id
     WHERE ds.document_id=? AND td.owner=?"
);
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['ok'=>true]);
