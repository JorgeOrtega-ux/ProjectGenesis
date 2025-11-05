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
    action_type ENUM('login_fail', 'reset_fail', 'password_verify_fail', 'preference_spam') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_time (user_identifier, created_at),
    INDEX idx_ip_time (ip_address, created_at)
);

-- --- ▼▼▼ TABLA MODIFICADA ▼▼▼ ---
DROP TABLE IF EXISTS user_metadata;
CREATE TABLE user_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    device_type VARCHAR(50) DEFAULT 'Unknown',
    browser_info TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    , INDEX idx_user_active (user_id, is_active)
);
-- --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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

-- --- ▼▼▼ INICIO DE NUEVA TABLA Y DATOS ▼▼▼ ---
DROP TABLE IF EXISTS site_settings;
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

-- Insertar las configuraciones por defecto
INSERT INTO site_settings (setting_key, setting_value) VALUES
('maintenance_mode', '0'),
('allow_new_registrations', '1'),
('username_cooldown_days', '30'),
('email_cooldown_days', '12'),
('avatar_max_size_mb', '2'),
('max_login_attempts', '5'),
('lockout_time_minutes', '5'),
('allowed_email_domains', 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com'),
('min_password_length', '8'),
('max_password_length', '72'),
-- --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
('min_username_length', '6'),
('max_username_length', '32'),
('max_email_length', '255'),
('code_resend_cooldown_seconds', '60'),
-- --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
('max_concurrent_users', '500');

-- --- ▼▼▼ INICIO DE TABLAS DE GRUPOS ▼▼▼ ---

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL DEFAULT (uuid()),
  `name` varchar(255) NOT NULL,
  `group_type` enum('municipio','universidad','preparatoria','general') NOT NULL DEFAULT 'general',
  `access_key` varchar(20) NOT NULL,
  `privacy` enum('publico','privado') NOT NULL DEFAULT 'privado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `access_key` (`access_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `user_groups`;
CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `role` enum('member','admin','owner') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_in_group` (`user_id`,`group_id`),
  KEY `fk_user_groups_user` (`user_id`),
  KEY `fk_user_groups_group` (`group_id`),
  CONSTRAINT `fk_user_groups_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_groups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- ▼▼▼ INSERCIÓN DE GRUPOS DE EJEMPLO ▼▼▼ ---
INSERT INTO `groups` (`name`, `group_type`, `access_key`, `privacy`) VALUES
('Universidad de Tamaulipas', 'universidad', 'UT2025', 'privado'),
('Municipio de Matamoros', 'municipio', 'MTMS25', 'privado'),
('Prepa Cbtis 135', 'preparatoria', 'CBTIS135', 'privado'),
('Grupo de Pruebas', 'general', 'TEST123', 'privado'),
('Comunidad Pública', 'general', 'PUBLIC01', 'publico');

-- --- ▲▲▲ FIN DE TABLAS DE GRUPOS ▲▲▲ ---

SET FOREIGN_KEY_CHECKS=1;