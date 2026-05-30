<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
requireAuthApi();

$content = $_POST['content'] ?? '';
$id      = (int)($_POST['id'] ?? 0);
$filesJson = $_POST['files'] ?? '';
$extraFiles = $filesJson ? json_decode($filesJson, true) : [];

$uid    = uniqid('', true);
$tmpDir = "/tmp/typst_proj_{$id}_{$uid}";
$inFile  = "$tmpDir/main.typ";
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
file_put_contents($inFile, $content);

// Copy uploaded images into the temp dir so Typst can find them (preserving subfolders)
$imgDir = __DIR__ . "/../uploads/{$id}";
if (is_dir($imgDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imgDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $rel  = substr($file->getPathname(), strlen($imgDir) + 1);
        $dest = "$tmpDir/$rel";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        copy($file->getPathname(), $dest);
    }
}

if (is_array($extraFiles)) {
    foreach ($extraFiles as $f) {
        $name = trim($f['filename'] ?? '', '/');
        if ($name === '' || $name === 'main.typ') continue;
        if (strpos($name, '..') !== false) continue; // block traversal
        $dest    = "$tmpDir/$name";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        file_put_contents($dest, $f['content'] ?? '');
    }
}

$cmd    = escapeshellcmd("/bin/typst") . " compile " . escapeshellarg($inFile) . " " . escapeshellarg($outFile) . " 2>&1";
$output = [];
$exit   = 0;
exec($cmd, $output, $exit);

if ($exit !== 0 || !file_exists($outFile)) {
    echo json_encode(['ok'=>false, 'error'=>implode("\n", $output)]);
    exit;
}

$pdf = base64_encode(file_get_contents($outFile));
echo json_encode(['ok'=>true, 'pdf'=>$pdf]);
