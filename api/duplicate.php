<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$id = (int)($_POST['id'] ?? 0);

$stmt = $db->prepare("SELECT title FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $id, $user['sub']);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) { $db->close(); echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

$new_title = $doc['title'] . ' (copy)';
$stmt = $db->prepare("INSERT INTO typst_documents (owner, title) VALUES (?, ?)");
$stmt->bind_param("ss", $user['sub'], $new_title);
$stmt->execute();
$new_id = $db->insert_id;
$stmt->close();
$db->close();

// Copy all project files
$src = __DIR__ . "/../data/{$id}";
$dst = __DIR__ . "/../data/{$new_id}";

if (is_dir($src)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    mkdir($dst, 0777, true);
    foreach ($iter as $item) {
        $target = $dst . '/' . $iter->getSubPathname();
        $item->isDir() ? mkdir($target, 0777, true) : copy($item->getPathname(), $target);
    }
}

echo json_encode(['ok' => true, 'id' => $new_id]);
