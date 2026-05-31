<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$author = getGitAuthor($user);
echo json_encode(['ok' => true, 'commit_name' => $author['name'], 'commit_email' => $author['email']]);
