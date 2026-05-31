<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id   = (int)($_POST['id'] ?? 0);
$path = $_POST['path'] ?? '';

if ($path === '' || strpos($path, '..') !== false || $path[0] === '/') {
    echo json_encode(['ok' => false, 'error' => 'Invalid path']); exit;
}

$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']); exit;
}

// For untracked files (??) just delete; for tracked changes restore from index/HEAD
[$code, $out, $err] = runGit(['status', '--porcelain', '--', $path], $dir);
$status = trim($out);
$xy = $status ? substr($status, 0, 2) : '  ';

if ($xy === '??') {
    // Untracked — delete the file
    $target = $dir . '/' . $path;
    if (file_exists($target) && strpos(realpath($target), realpath($dir)) === 0) {
        unlink($target);
    }
    echo json_encode(['ok' => true]);
} else {
    // Tracked — restore working tree to HEAD
    [$code,, $err] = runGit(['restore', '--', $path], $dir);
    if ($code !== 0) {
        // Fallback for older git
        [$code,, $err] = runGit(['checkout', '--', $path], $dir);
    }
    if ($code !== 0) {
        echo json_encode(['ok' => false, 'error' => trim($err)]); exit;
    }
    echo json_encode(['ok' => true]);
}
