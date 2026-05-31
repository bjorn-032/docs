<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id  = (int)($_POST['id'] ?? 0);
$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']);
    exit;
}

$env = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];
[$code, $out, $err] = runGit(['pull', '--ff-only'], $dir, $env);

$combined = trim(($out ?: '') . ($err ? "\n" . $err : ''));
if ($code !== 0) {
    $errMsg = trim($err ?: $out);
    // Trim verbose git help text — keep only the first non-empty line
    $firstLine = trim(explode("\n", $errMsg)[0] ?? $errMsg);
    echo json_encode(['ok' => false, 'error' => $firstLine, 'output' => $combined]);
    exit;
}

// Sync submodules after pull
[, $smOut, $smErr] = runGit(['submodule', 'update', '--init', '--recursive'], $dir, $env);
$smMsg = trim($smOut . "\n" . $smErr);
if ($smMsg) $combined = trim($combined . "\n" . $smMsg);

echo json_encode(['ok' => true, 'output' => $combined]);
