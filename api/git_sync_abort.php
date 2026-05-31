<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$id     = (int)($_POST['id'] ?? 0);
$force  = !empty($_POST['force']); // true = discard local changes, take remote
$dir    = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }
if (!is_dir($dir . '/.git')) { echo json_encode(['ok' => false, 'error' => 'Git not initialized']); exit; }

$env = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];
$output = [];

$inRebase = is_dir($dir . '/.git/rebase-merge')
         || is_dir($dir . '/.git/rebase-apply');

if ($force) {
    // Discard local changes and align with remote HEAD
    if ($inRebase) {
        [$code,, $err] = runGit(['rebase', '--abort'], $dir, $env);
        if ($code !== 0) {
            echo json_encode(['ok' => false, 'error' => 'Could not abort rebase: ' . trim($err)]); exit;
        }
    }
    // Detect tracking branch
    [, $trackingBranch] = runGit(['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{u}'], $dir);
    $trackingBranch = trim($trackingBranch);
    if (!$trackingBranch) {
        echo json_encode(['ok' => false, 'error' => 'No remote tracking branch configured — cannot force sync.']); exit;
    }
    [$code,, $err] = runGit(['reset', '--hard', $trackingBranch], $dir);
    if ($code !== 0) {
        echo json_encode(['ok' => false, 'error' => 'Could not reset to remote: ' . trim($err)]); exit;
    }
    $output[] = 'Reset to ' . $trackingBranch;
    // Drop any leftover stash from the failed autostash
    runGit(['stash', 'drop'], $dir);

} else {
    // Abort: restore the state from before the sync started
    if ($inRebase) {
        // git rebase --abort restores both the branch and the autostash
        [$code,, $err] = runGit(['rebase', '--abort'], $dir, $env);
        if ($code !== 0) {
            echo json_encode(['ok' => false, 'error' => 'Could not abort rebase: ' . trim($err)]); exit;
        }
        $output[] = 'Rebase aborted — your changes are restored.';
    } else {
        // Stash-pop failed after a successful rebase; working tree has conflict markers
        // Discard the conflict markers and restore the stash manually
        [$code,, $err] = runGit(['checkout', '.'], $dir);
        if ($code !== 0) {
            echo json_encode(['ok' => false, 'error' => 'Could not clean working tree: ' . trim($err)]); exit;
        }
        [$code, $smOut, $smErr] = runGit(['stash', 'pop'], $dir);
        $output[] = trim($smOut . "\n" . $smErr);
    }
}

echo json_encode(['ok' => true, 'output' => implode("\n", array_filter($output))]);
