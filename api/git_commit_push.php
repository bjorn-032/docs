<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id      = (int)($_POST['id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$filesRaw = $_POST['files'] ?? ''; // JSON array of paths, or empty = stage all

$dir = getDocDir($id, $user);
if (!$dir)     { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }
if (!$message) { echo json_encode(['ok' => false, 'error' => 'Commit message required']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']);
    exit;
}

$author = getGitAuthor($user);
$sshEnv = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];
$output = [];

// Stage files
$files = [];
if ($filesRaw) {
    $decoded = json_decode($filesRaw, true);
    if (is_array($decoded)) $files = $decoded;
}

if ($files) {
    $addArgs = ['add', '--'];
    foreach ($files as $f) $addArgs[] = (string)$f;
    [$code,, $err] = runGit($addArgs, $dir);
} else {
    [$code,, $err] = runGit(['add', '-A'], $dir);
}

if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => 'git add failed: ' . trim($err)]);
    exit;
}

// Commit
[$code, $out, $err] = runGit([
    '-c', 'user.name=' . $author['name'],
    '-c', 'user.email=' . $author['email'],
    'commit', '-m', $message,
], $dir);

if ($code !== 0) {
    $combined = trim($out . "\n" . $err);
    // "nothing to commit" is exit 1 but not a real error
    if (strpos($combined, 'nothing to commit') !== false) {
        echo json_encode(['ok' => false, 'error' => 'Nothing to commit — working tree is clean.', 'output' => $combined]);
    } else {
        echo json_encode(['ok' => false, 'error' => trim($err ?: $out), 'output' => $combined]);
    }
    exit;
}
$output[] = trim($out);

// Push if remote exists
[, $remote] = runGit(['remote', 'get-url', 'origin'], $dir);
if (trim($remote)) {
    [$code, $out, $err] = runGit(['push', 'origin', 'HEAD'], $dir, $sshEnv);
    $output[] = trim($out . "\n" . $err);
    if ($code !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Commit succeeded but push failed: ' . trim($err), 'output' => implode("\n", array_filter($output))]);
        exit;
    }
}

echo json_encode(['ok' => true, 'output' => implode("\n", array_filter($output))]);
