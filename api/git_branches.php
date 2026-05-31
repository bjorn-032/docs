<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$id  = (int)($_POST['id'] ?? 0);
$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => false, 'error' => 'Git not initialized']);
    exit;
}

// Current branch
[, $current] = runGit(['rev-parse', '--abbrev-ref', 'HEAD'], $dir);
$current = trim($current);

// Get remote names so we can skip bare remote entries (e.g. 'origin' from origin/HEAD symref)
[, $remotesOut] = runGit(['remote'], $dir);
$remotes = array_filter(array_map('trim', explode("\n", $remotesOut)));

// All branches (local + remote, deduplicated)
[, $branchOut] = runGit(['branch', '-a', '--format=%(refname:short)'], $dir);
$all = [];
foreach (explode("\n", trim($branchOut)) as $b) {
    $b = trim($b);
    if (!$b) continue;
    // Skip bare remote names (origin/HEAD symref appears as just 'origin')
    if (in_array($b, $remotes, true)) continue;
    // Normalise remote tracking refs: origin/main → main, skip origin/HEAD
    $stripped = null;
    foreach ($remotes as $remote) {
        if (strpos($b, $remote . '/') === 0) {
            $stripped = substr($b, strlen($remote) + 1);
            break;
        }
    }
    if ($stripped !== null) {
        if ($stripped !== 'HEAD') $all[$stripped] = true;
    } else {
        $all[$b] = true;
    }
}

echo json_encode(['ok' => true, 'current' => $current, 'branches' => array_keys($all)]);
