<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id   = (int)($_POST['document_id'] ?? 0);
$old_name = ltrim($_POST['old_filename'] ?? '', '/');
$new_name = ltrim($_POST['new_filename'] ?? '', '/');

$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$found) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

function valid_img_path($name) {
    if (empty($name)) return false;
    if (strpos($name, '..') !== false) return false;
    return preg_match('/^[a-zA-Z0-9._\-]+(\/[a-zA-Z0-9._\-]+)*$/', $name) ? $name : false;
}

$old_name = valid_img_path($old_name);
$new_name = valid_img_path($new_name);
if (!$old_name || !$new_name || $old_name === $new_name) {
    echo json_encode(['ok'=>false,'error'=>'Invalid filename']); exit;
}

$base     = __DIR__ . "/../data/{$doc_id}";
$old_path = "$base/$old_name";
$new_path = "$base/$new_name";

if (!file_exists($old_path)) { echo json_encode(['ok'=>false,'error'=>'File not found']); exit; }

$new_dir = dirname($new_path);
if (!is_dir($new_dir)) mkdir($new_dir, 0777, true);

if (!rename($old_path, $new_path)) { echo json_encode(['ok'=>false,'error'=>'Move failed']); exit; }

echo json_encode(['ok'=>true, 'filename'=>$new_name]);
