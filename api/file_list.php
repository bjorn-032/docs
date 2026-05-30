<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id = (int)($_POST['document_id'] ?? 0);

$stmt = $db->prepare(
    "SELECT pf.id, pf.filename, pf.updated_at
     FROM typst_project_files pf
     JOIN typst_documents d ON d.id = pf.document_id
     WHERE pf.document_id=? AND d.owner=?
     ORDER BY pf.filename"
);
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$res = $stmt->get_result();
$files = [];
while ($row = $res->fetch_assoc()) {
    $files[] = $row;
}
$stmt->close();
$db->close();

echo json_encode(['ok'=>true, 'files'=>$files]);
