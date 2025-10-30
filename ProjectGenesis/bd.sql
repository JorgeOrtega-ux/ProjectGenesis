DROP DATABASE IF EXISTS project_genesis;
CREATE DATABASE project_genesis;
USE project_genesis;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image_url VARCHAR(255) NULL,
    role ENUM('user', 'moderator', 'administrator', 'founder') NOT NULL DEFAULT 'user',
    is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    auth_token VARCHAR(64) NULL DEFAULT NULL,
    -- ▼▼▼ Columna añadida ▼▼▼
    account_status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
    -- ▲▲▲ Columna añadida ▲▲▲
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

DROP TABLE IF EXISTS verification_codes;
CREATE TABLE verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    code_type ENUM('registration', 'password_reset', '2fa', 'email_change') NOT NULL,
    code VARCHAR(255) NOT NULL,
    payload TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX idx_identifier_type (identifier, code_type)
);

DROP TABLE IF EXISTS security_logs;
CREATE TABLE security_logs (
    id INT NOT NULL AUTO_INCREMENT,
    user_identifier VARCHAR(255) NOT NULL,
    action_type ENUM('login_fail', 'reset_fail', 'password_verify_fail') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_time (user_identifier, created_at),
    INDEX idx_ip_time (ip_address, created_at)
);

DROP TABLE IF EXISTS user_metadata;
CREATE TABLE user_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    device_type VARCHAR(50) DEFAULT 'Unknown',
    browser_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS user_audit_logs;
CREATE TABLE user_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    change_type ENUM('username', 'email', 'password') NOT NULL,
    old_value VARCHAR(255) NOT NULL,
    new_value VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    changed_by_ip VARCHAR(45) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type_time (user_id, change_type, changed_at)
);

DROP TABLE IF EXISTS user_preferences;
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    language VARCHAR(10) NOT NULL DEFAULT 'en-us',
    theme ENUM('system', 'light', 'dark') NOT NULL DEFAULT 'system',
    usage_type VARCHAR(50) NOT NULL DEFAULT 'personal',
    open_links_in_new_tab TINYINT(1) NOT NULL DEFAULT 1,
    increase_message_duration TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);