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

$dir = __DIR__ . "/../data/{$doc_id}";
$images = [];
if (is_dir($dir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        if (strtolower(pathinfo($rel, PATHINFO_EXTENSION)) === 'typ') continue;
        $images[] = $rel;
    }
    sort($images);
}

echo json_encode(['ok'=>true, 'images'=>$images]);
