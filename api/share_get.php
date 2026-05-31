<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id = (int)($_POST['document_id'] ?? 0);

// Verify ownership
$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();

if (!$found) { $db->close(); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$stmt = $db->prepare("SELECT token, access FROM document_shares WHERE document_id=?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$res = $stmt->get_result();
$share = $res->fetch_assoc();
$stmt->close();
$db->close();

echo json_encode(['ok'=>true, 'share'=>$share ?: null]);
