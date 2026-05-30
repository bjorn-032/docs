<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$stmt = $db->prepare("INSERT INTO typst_documents (owner, title, content) VALUES (?, ?, ?)");
$title   = "Untitled Document";
$content = "= Hello, Typst!\n\nStart typing your document here.\n";
$stmt->bind_param("sss", $user['sub'], $title, $content);
$stmt->execute();
$id = $db->insert_id;
$stmt->close();
$db->close();

echo json_encode(['ok'=>true, 'id'=>$id]);
