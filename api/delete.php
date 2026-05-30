<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id = (int)($_POST['id'] ?? 0);
$stmt = $db->prepare("DELETE FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $id, $user['sub']);
$stmt->execute();
$stmt->close();
$db->close();

echo json_encode(['ok'=>true]);
