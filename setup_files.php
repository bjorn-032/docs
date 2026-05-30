<?php
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("DB error: " . $db->connect_error);

$sql = "CREATE TABLE IF NOT EXISTS typst_project_files (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    filename    VARCHAR(255) NOT NULL DEFAULT 'untitled.typ',
    content     LONGTEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_doc (document_id)
)";

if ($db->query($sql)) {
    echo "Table typst_project_files created (or already exists). Delete this file now.";
} else {
    echo "Error: " . $db->error;
}
$db->close();
