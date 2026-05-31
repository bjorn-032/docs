CREATE DATABASE IF NOT EXISTS `documents` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'docs'@'localhost' IDENTIFIED BY 'NSjY9bHbtkxsjyWuepo8uPud';
GRANT ALL PRIVILEGES ON `documents`.* TO 'docs'@'localhost';
FLUSH PRIVILEGES;

USE `documents`;

CREATE TABLE IF NOT EXISTS typst_documents (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL DEFAULT '',
    title      VARCHAR(500) NOT NULL DEFAULT 'Untitled Document',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS document_shares (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    token       VARCHAR(64) NOT NULL,
    access      ENUM('view','edit') NOT NULL DEFAULT 'view',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY  uniq_token (token),
    FOREIGN KEY (document_id) REFERENCES typst_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS git_user_settings (
    user_sub     VARCHAR(255) NOT NULL PRIMARY KEY,
    commit_name  VARCHAR(255),
    commit_email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
