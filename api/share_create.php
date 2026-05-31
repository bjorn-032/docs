<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id = (int)($_POST['document_id'] ?? 0);
$access = ($_POST['access'] ?? 'view') === 'edit' ? 'edit' : 'view';

// Verify ownership
$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();

if (!$found) { $db->close(); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

// Get existing share or create new token
$stmt = $db->prepare("SELECT token FROM document_shares WHERE document_id=?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$res = $stmt->get_result();
$existing = $res->fetch_assoc();
$stmt->close();

if ($existing) {
    $token = $existing['token'];
    $stmt = $db->prepare("UPDATE document_shares SET access=? WHERE document_id=?");
    $stmt->bind_param("si", $access, $doc_id);
    $stmt->execute();
    $stmt->close();
} else {
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO document_shares (document_id, token, access) VALUES (?,?,?)");
    $stmt->bind_param("iss", $doc_id, $token, $access);
    $stmt->execute();
    $stmt->close();
}

$db->close();
echo json_encode(['ok'=>true, 'token'=>$token, 'access'=>$access]);
