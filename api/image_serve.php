<?php
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$doc_id   = (int)($_GET['document_id'] ?? 0);
$filename = ltrim($_GET['filename'] ?? '', '/');
if (!$doc_id || !$filename) { http_response_code(400); exit; }
if (strpos($filename, '..') !== false || strpos($filename, "\0") !== false ||
    $filename === '' || $filename[0] === '/') {
    http_response_code(400); exit;
}

// Verify ownership
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$found) { http_response_code(403); exit; }

$path = __DIR__ . "/../data/{$doc_id}/" . $filename;
if (!file_exists($path) || !is_file($path)) { http_response_code(404); exit; }

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
