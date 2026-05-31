<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id = (int)($_POST['id'] ?? 0);

// Verify ownership before deleting files
$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();

if (!$found) { $db->close(); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$stmt = $db->prepare("DELETE FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $id, $user['sub']);
$stmt->execute();
$stmt->close();
$db->close();

// Remove data directory
$upload_dir = __DIR__ . "/../data/{$id}";
if (is_dir($upload_dir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($upload_dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($upload_dir);
}

echo json_encode(['ok'=>true]);
