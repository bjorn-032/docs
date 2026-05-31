<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$stmt = $db->prepare("INSERT INTO typst_documents (owner, title) VALUES (?, ?)");
$title   = "Untitled Document";
$stmt->bind_param("ss", $user['sub'], $title);
$stmt->execute();
$id = $db->insert_id;
$stmt->close();
$db->close();

$upload_dir = __DIR__ . "/../data/{$id}";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
file_put_contents($upload_dir . '/main.typ', "= Hello, Typst!\n\nStart typing your document here.\n");

echo json_encode(['ok'=>true, 'id'=>$id]);
