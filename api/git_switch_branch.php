<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id     = (int)($_POST['id'] ?? 0);
$branch = preg_replace('/[^a-zA-Z0-9_.\-]/', '', trim($_POST['branch'] ?? ''));
$create = !empty($_POST['create']);

if (!$branch || strpos($branch, '..') !== false) {
    echo json_encode(['ok' => false, 'error' => 'Invalid branch name']);
    exit;
}

$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']);
    exit;
}

$sshEnv = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];

if ($create) {
    [$code,, $err] = runGit(['checkout', '-b', $branch], $dir, $sshEnv);
} else {
    // Try local first, then try tracking remote
    [$code, $out, $err] = runGit(['checkout', $branch], $dir, $sshEnv);
    if ($code !== 0) {
        [$code, $out, $err] = runGit(['checkout', '-b', $branch, 'origin/' . $branch], $dir, $sshEnv);
    }
}

if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => trim($err)]);
    exit;
}
echo json_encode(['ok' => true, 'branch' => $branch]);
