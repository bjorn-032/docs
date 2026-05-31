<?php
header('Content-Type: application/json');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }

$token = preg_replace('/[^a-f0-9]/', '', $_POST['token'] ?? '');
if ($token === '') { echo json_encode(['ok'=>false,'error'=>'Invalid token']); exit; }

$stmt = $db->prepare("SELECT document_id FROM document_shares WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
$db->close();

if (!$row) { echo json_encode(['ok'=>false,'error'=>'Link not found']); exit; }
$id = (int)$row['document_id'];

$content   = $_POST['content'] ?? '';
$filesJson = $_POST['files'] ?? '';
$extraFiles = $filesJson ? json_decode($filesJson, true) : [];
$entry = trim($_POST['entry'] ?? 'main.typ', '/');
if ($entry === '' || strpos($entry, '..') !== false) $entry = 'main.typ';

$imgDir = __DIR__ . "/../data/{$id}";

$uid     = uniqid('', true);
$tmpDir  = "/tmp/typst_shared_{$id}_{$uid}";
$inFile  = "$tmpDir/$entry";
$outFile = "$tmpDir/output.pdf";

register_shutdown_function(function() use ($tmpDir) {
    if (is_dir($tmpDir)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($tmpDir);
    }
});

mkdir($tmpDir, 0700, true);
$inDir = dirname($inFile);
if ($inDir !== $tmpDir) mkdir($inDir, 0700, true);
file_put_contents($inFile, $content);

// Symlink uploaded assets and collect font dirs in a single pass.
$font_exts = ['ttf','otf','woff','woff2','eot'];
$font_dirs = [$tmpDir => true];
if (is_dir($imgDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imgDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $file) {
        if ($file->getFilename() === '.git') { $iter->next(); continue; }
        if (!$file->isFile()) continue;
        $rel     = substr($file->getPathname(), strlen($imgDir) + 1);
        $dest    = "$tmpDir/$rel";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        if (!file_exists($dest)) symlink($file->getPathname(), $dest);
        if (in_array(strtolower($file->getExtension()), $font_exts)) {
            $font_dirs[$destDir] = true;
        }
    }
}

if (is_array($extraFiles)) {
    foreach ($extraFiles as $f) {
        $name = trim($f['filename'] ?? '', '/');
        if ($name === '' || $name === $entry) continue;
        if (strpos($name, '..') !== false) continue;
        $dest    = "$tmpDir/$name";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        if (is_link($dest)) unlink($dest);
        file_put_contents($dest, $f['content'] ?? '');
    }
}

$font_path_args = implode('', array_map(function($d) {
    return ' --font-path ' . escapeshellarg($d);
}, array_keys($font_dirs)));

$cmd    = escapeshellcmd("/bin/typst") . " compile " . escapeshellarg($inFile) . " " . escapeshellarg($outFile) . $font_path_args . " 2>&1";
$output = [];
$exit   = 0;
exec($cmd, $output, $exit);

if ($exit !== 0 || !file_exists($outFile)) {
    echo json_encode(['ok'=>false, 'error'=>implode("\n", $output)]);
    exit;
}

echo json_encode(['ok'=>true, 'pdf'=>base64_encode(file_get_contents($outFile))]);
