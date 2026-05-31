<?php
require __DIR__ . '/../auth/session.php';
$user = requireAuth();

$doc_id = (int)($_GET['document_id'] ?? 0);
if (!$doc_id) { http_response_code(400); exit; }

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { http_response_code(500); exit; }

$stmt = $db->prepare("SELECT title, content FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $doc_id, $user['sub']);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) { http_response_code(403); exit; }

$stmt = $db->prepare("SELECT filename, content FROM typst_project_files WHERE document_id=?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$res = $stmt->get_result();
$extraFiles = [];
while ($row = $res->fetch_assoc()) $extraFiles[] = $row;
$stmt->close();
$db->close();

$tmpZip = tempnam(sys_get_temp_dir(), 'typst_zip_');
$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) { http_response_code(500); exit; }

$zip->addFromString('main.typ', $doc['content'] ?? '');

foreach ($extraFiles as $f) {
    $zip->addFromString($f['filename'], $f['content'] ?? '');
}

$uploadsDir = __DIR__ . "/../uploads/{$doc_id}";
if (is_dir($uploadsDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($uploadsDir) + 1));
            $zip->addFile($file->getPathname(), $rel);
        }
    }
}

$zip->close();

$title = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc['title'] ?: 'document');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $title . '.zip"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
unlink($tmpZip);
