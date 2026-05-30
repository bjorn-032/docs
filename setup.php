<?php
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$sql = "CREATE TABLE IF NOT EXISTS typst_documents (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  owner       VARCHAR(255) NOT NULL DEFAULT '',
  title       VARCHAR(500) NOT NULL DEFAULT 'Untitled Document',
  content     LONGTEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($db->query($sql)) {
    echo "Table created (or already exists). You can delete this file now.";
} else {
    echo "Error: " . $db->error;
}
$db->close();
