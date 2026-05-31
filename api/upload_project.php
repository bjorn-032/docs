<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
$user = requireAuthApi();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$zip_file = $_FILES['zip'] ?? null;
if (!$zip_file || $zip_file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>false,'error'=>'Upload error']); exit;
}

$project_title = trim($_POST['title'] ?? 'Imported Project');
if ($project_title === '') $project_title = 'Imported Project';

$zip = new ZipArchive();
if ($zip->open($zip_file['tmp_name']) !== true) {
    echo json_encode(['ok'=>false,'error'=>'Invalid zip file']); exit;
}

// Collect all file paths (skip directories)
$all_paths = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (substr($name, -1) === '/') continue;
    $all_paths[] = $name;
}

// Strip common top-level folder prefix if all files share one
$prefix = '';
if (!empty($all_paths)) {
    $first_slash = strpos($all_paths[0], '/');
    if ($first_slash !== false) {
        $candidate = substr($all_paths[0], 0, $first_slash + 1);
        $all_share = true;
        foreach ($all_paths as $p) {
            if (strpos($p, $candidate) !== 0) { $all_share = false; break; }
        }
        if ($all_share) $prefix = $candidate;
    }
}

$all_files = [];
$main_file = null;

foreach ($all_paths as $name) {
    $rel = $prefix !== '' ? substr($name, strlen($prefix)) : $name;
    if ($rel === '') continue;
    if (strpos($rel, '..') !== false) continue;

    // Skip hidden files/dirs
    $skip = false;
    foreach (explode('/', $rel) as $part) {
        if ($part !== '' && $part[0] === '.') { $skip = true; break; }
    }
    if ($skip) continue;

    $content = $zip->getFromName($name);
    if ($content === false) continue;

    $all_files[] = ['path' => $rel, 'content' => $content];
}
$zip->close();

if (empty($all_files)) {
    echo json_encode(['ok'=>false,'error'=>'No files found in archive']); exit;
}

// Determine main .typ file: prefer root main.typ, then any root .typ, then first .typ found
$typ_files = array_filter($all_files, fn($f) => strtolower(pathinfo($f['path'], PATHINFO_EXTENSION)) === 'typ');
foreach ($typ_files as $f) {
    if ($f['path'] === 'main.typ') { $main_file = $f; break; }
}
if (!$main_file) {
    foreach ($typ_files as $f) {
        if (substr_count($f['path'], '/') === 0) { $main_file = $f; break; }
    }
}
if (!$main_file && !empty($typ_files)) {
    $main_file = array_values($typ_files)[0];
}

$main_content = $main_file ? $main_file['content'] : "= New Document\n";

// Create the document record (no file content in DB)
$stmt = $db->prepare("INSERT INTO typst_documents (owner, title) VALUES (?, ?)");
$stmt->bind_param("ss", $user['sub'], $project_title);
$stmt->execute();
$doc_id = $db->insert_id;
$stmt->close();

// Save all files to data/{doc_id}/
$upload_dir = __DIR__ . "/../data/{$doc_id}";
foreach ($all_files as $f) {
    $target = $upload_dir . '/' . $f['path'];
    $dir    = dirname($target);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($target, $f['content']);
}

$db->close();
echo json_encode(['ok' => true, 'id' => $doc_id]);
