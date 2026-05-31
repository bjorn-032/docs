<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id         = (int)($_POST['id'] ?? 0);
$remoteUrl  = trim($_POST['remote_url'] ?? '');
$branch     = preg_replace('/[^a-zA-Z0-9_.\-]/', '', trim($_POST['branch'] ?? 'main')) ?: 'main';
if (strpos($branch, '..') !== false) $branch = 'main';

$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git already initialized for this project']);
    exit;
}

$sshEnv  = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];
$output  = [];

// Init
[$code, $out, $err] = runGit(['init'], $dir);
if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => 'git init failed: ' . trim($err)]);
    exit;
}
$output[] = trim($out ?: $err);

if ($remoteUrl) {
    // Add remote
    [$code,, $err] = runGit(['remote', 'add', 'origin', $remoteUrl], $dir, $sshEnv);
    if ($code !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Could not add remote: ' . trim($err)]);
        exit;
    }

    // Fetch
    [$code, $out, $err] = runGit(['fetch', 'origin'], $dir, $sshEnv);
    if ($code !== 0) {
        // Remote might be empty — not fatal
        $output[] = 'Note: fetch failed (remote may be empty): ' . trim($err);
    } else {
        $output[] = trim($out ?: $err);

        // Try to checkout the requested branch
        [$code, $out, $err] = runGit(['checkout', '-b', $branch, 'origin/' . $branch], $dir, $sshEnv);
        if ($code !== 0) {
            // Try simple checkout in case local branch exists
            [$code, $out, $err] = runGit(['checkout', $branch], $dir);
        }
        if ($code !== 0) {
            $output[] = 'Note: could not checkout branch, staying on default: ' . trim($err);
        } else {
            $output[] = trim($out ?: $err);
            // Init and update submodules
            [, $smOut, $smErr] = runGit(['submodule', 'update', '--init', '--recursive'], $dir, $sshEnv);
            $smMsg = trim($smOut . "\n" . $smErr);
            if ($smMsg) $output[] = $smMsg;
        }
    }
}

echo json_encode(['ok' => true, 'output' => implode("\n", array_filter($output))]);
