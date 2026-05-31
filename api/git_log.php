<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$id  = (int)($_POST['id'] ?? 0);
$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => true, 'commits' => []]);
    exit;
}

[, $out] = runGit(['log', '--pretty=format:%h%x09%s', '-15'], $dir);
$commits = [];
foreach (explode("\n", trim($out)) as $line) {
    if (!$line) continue;
    $tab = strpos($line, "\t");
    if ($tab === false) continue;
    $commits[] = ['hash' => substr($line, 0, $tab), 'subject' => substr($line, $tab + 1)];
}

echo json_encode(['ok' => true, 'commits' => $commits]);
