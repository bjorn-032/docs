<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id  = (int)($_POST['id'] ?? 0);
$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']); exit;
}

$env = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];

// --autostash stashes any working-tree changes before the rebase and re-applies
// them afterward, so uncommitted edits are preserved across the sync.
[$code, $out, $err] = runGit(['pull', '--rebase', '--autostash'], $dir, $env);

$combined = trim($out . "\n" . $err);

if ($code !== 0) {
    // Check whether a stash conflict left the repo in a bad state
    $conflicts = strpos($combined, 'CONFLICT') !== false
              || strpos($combined, 'conflict') !== false;
    $errMsg   = trim($err ?: $out);
    $firstLine = trim(explode("\n", $errMsg)[0] ?? $errMsg);
    echo json_encode([
        'ok'        => false,
        'conflicts' => $conflicts,
        'error'     => $conflicts ? $errMsg : $firstLine,
        'output'    => $combined,
    ]);
    exit;
}

// Update submodules to match the new HEAD
[, $smOut, $smErr] = runGit(['submodule', 'update', '--init', '--recursive'], $dir, $env);
$smMsg = trim($smOut . "\n" . $smErr);
if ($smMsg) $combined = trim($combined . "\n" . $smMsg);

echo json_encode(['ok' => true, 'output' => $combined]);
