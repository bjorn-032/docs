<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$id  = (int)($_POST['id'] ?? 0);
$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => true, 'initialized' => false]);
    exit;
}

// Current branch
[, $branch] = runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $dir);
$branch = trim($branch) ?: 'HEAD';

// Remote URL
[, $remote] = runGit(['remote', 'get-url', 'origin'], $dir);
$remote = trim($remote) ?: null;

// Changed files (--porcelain format: XY path)
[, $statusOut] = runGit(['status', '--porcelain', '-u'], $dir);
$changes = [];
foreach (explode("\n", trim($statusOut)) as $line) {
    if (strlen($line) < 3) continue;
    $xy   = substr($line, 0, 2);
    // Path starts at position 2; ltrim removes the separator space whether git
    // outputs one space (M main.typ) or two spaces (M  main.typ / ' M main.typ').
    $path = ltrim(substr($line, 2));
    // Resolve renames (old -> new format)
    if (strpos($path, ' -> ') !== false) {
        $path = substr($path, strpos($path, ' -> ') + 4);
    }
    // Determine display status
    $index    = $xy[0];
    $worktree = $xy[1];
    if ($index === '?' && $worktree === '?') {
        $status = '?';
    } elseif ($index !== ' ' && $index !== '?') {
        $status = $index; // staged
    } else {
        $status = $worktree; // unstaged
    }
    $changes[] = ['status' => $status, 'path' => $path, 'xy' => $xy];
}

// Ahead/behind relative to tracking branch
$ahead   = null;
$behind  = null;
[$abCode, $abOut] = runGit(['rev-list', '--left-right', '--count', 'HEAD...@{u}'], $dir);
if ($abCode === 0) {
    $parts  = preg_split('/\s+/', trim($abOut));
    $ahead  = (int)($parts[0] ?? 0);
    $behind = (int)($parts[1] ?? 0);
}

echo json_encode([
    'ok'          => true,
    'initialized' => true,
    'branch'      => $branch,
    'remote'      => $remote,
    'changes'     => $changes,
    'ahead'       => $ahead,
    'behind'      => $behind,
]);
