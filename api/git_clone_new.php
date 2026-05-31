<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi(true);

$remoteUrl = trim($_POST['remote_url'] ?? '');
$branch    = preg_replace('/[^a-zA-Z0-9_.\-]/', '', trim($_POST['branch'] ?? 'main')) ?: 'main';
$title     = trim($_POST['title'] ?? '');

if (!$remoteUrl) { echo json_encode(['ok' => false, 'error' => 'Remote URL required']); exit; }
if (!preg_match('#^(https://|ssh://|git@)#i', $remoteUrl)) {
    echo json_encode(['ok' => false, 'error' => 'Remote URL must use https, ssh, or git@ protocol']);
    exit;
}
if (strpos($branch, '..') !== false) $branch = 'main';

// Derive a title from the URL if not provided
if (!$title) {
    $title = basename(parse_url($remoteUrl, PHP_URL_PATH) ?: '');
    $title = preg_replace('/\.git$/i', '', $title) ?: 'Git Project';
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { echo json_encode(['ok' => false, 'error' => 'DB error']); exit; }

$stmt = $db->prepare("INSERT INTO typst_documents (owner, title) VALUES (?, ?)");
$stmt->bind_param("ss", $user['sub'], $title);
$stmt->execute();
$id = $db->insert_id;
$stmt->close();
$db->close();

if (!$id) { echo json_encode(['ok' => false, 'error' => 'Could not create document']); exit; }

$dir = __DIR__ . "/../data/{$id}";
if (!is_dir($dir)) mkdir($dir, 0777, true);

$sshEnv = ['GIT_SSH_COMMAND' => gitSshCommand($user['sub'])];

// Init
[$code,, $err] = runGit(['init'], $dir);
if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => 'git init failed: ' . trim($err)]);
    exit;
}

// Add remote
[$code,, $err] = runGit(['remote', 'add', 'origin', $remoteUrl], $dir, $sshEnv);
if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid remote URL: ' . trim($err)]);
    exit;
}

// Fetch
[$code, $out, $err] = runGit(['fetch', '--depth=50', 'origin'], $dir, $sshEnv);
if ($code !== 0) {
    echo json_encode(['ok' => false, 'error' => 'Could not fetch from remote: ' . trim($err ?: $out)]);
    exit;
}

// Checkout requested branch
[$code,, $err] = runGit(['checkout', '-b', $branch, 'origin/' . $branch], $dir, $sshEnv);
if ($code !== 0) {
    // Try alternate branch names
    $altBranch = $branch === 'main' ? 'master' : 'main';
    [$code2,, $err2] = runGit(['checkout', '-b', $altBranch, 'origin/' . $altBranch], $dir, $sshEnv);
    if ($code2 !== 0) {
        echo json_encode(['ok' => false, 'error' => "Branch '$branch' not found. " . trim($err)]);
        exit;
    }
}

// Init and update submodules
[, $smOut, $smErr] = runGit(['submodule', 'update', '--init', '--recursive'], $dir, $sshEnv);

// Create main.typ if repo doesn't have one
if (!file_exists($dir . '/main.typ')) {
    file_put_contents($dir . '/main.typ', '');
}

echo json_encode(['ok' => true, 'id' => $id]);
