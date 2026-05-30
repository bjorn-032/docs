<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$doc_id = (int)($_POST['document_id'] ?? 0);

$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$found) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>false,'error'=>'Upload error']); exit;
}

$allowed_types = ['image/png','image/jpeg','image/gif','image/webp','image/svg+xml','application/pdf'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['ok'=>false,'error'=>'File type not allowed']); exit;
}

// If a validated relative path is provided (batch/folder uploads), use it;
// otherwise fall back to sanitising the file's own name.
$rel = ltrim($_POST['path'] ?? '', '/');
if ($rel && strpos($rel, '..') === false && preg_match('/^[a-zA-Z0-9._\-]+(\/[a-zA-Z0-9._\-]+)*$/', $rel)) {
    $filename = $rel;
} else {
    $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($file['name']));
}

$dir = __DIR__ . "/../uploads/{$doc_id}";
$target_dir = dirname("$dir/$filename");
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

if (!move_uploaded_file($file['tmp_name'], "$dir/$filename")) {
    echo json_encode(['ok'=>false,'error'=>'Failed to save file']); exit;
}

echo json_encode(['ok'=>true, 'filename'=>$filename]);
