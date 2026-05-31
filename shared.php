<?php
require_once __DIR__ . '/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { http_response_code(500); die("DB error"); }

$token = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
if ($token === '') { http_response_code(404); die("Link not found"); }

$stmt = $db->prepare("SELECT document_id FROM document_shares WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

if (!$row) { http_response_code(404); die("Link not found"); }

header("Location: /editor/" . (int)$row['document_id'] . "?token=" . urlencode($token));
exit;
