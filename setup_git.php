<?php
// Run once to create the git_user_settings table, then delete this file.
require __DIR__ . '/auth/session.php';
requireAuth();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("DB error: " . $db->connect_error);

$db->query("CREATE TABLE IF NOT EXISTS git_user_settings (
    user_sub     VARCHAR(255) NOT NULL PRIMARY KEY,
    commit_name  VARCHAR(255),
    commit_email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($db->errno) die("Error: " . $db->error);
$db->close();

echo "git_user_settings table created (or already exists). Delete this file.";
