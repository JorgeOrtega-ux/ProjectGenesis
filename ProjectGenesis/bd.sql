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
    account_status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
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

DROP TABLE IF EXISTS site_settings;
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

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
('min_username_length', '6'),
('max_username_length', '32'),
('max_email_length', '255'),
('code_resend_cooldown_seconds', '60'),
('max_concurrent_users', '500');

-- --- ▼▼▼ INICIO DE TABLAS MODIFICADAS ▼▼▼ ---

DROP TABLE IF EXISTS communities;
CREATE TABLE communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    uuid CHAR(36) NOT NULL,
    -- --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
    name VARCHAR(100) NOT NULL,
    privacy ENUM('public', 'private') NOT NULL DEFAULT 'public',
    access_code VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX idx_access_code (access_code),
    -- --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    UNIQUE KEY uk_uuid (uuid)
    -- --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
);

DROP TABLE IF EXISTS user_communities;
CREATE TABLE user_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    community_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_community (user_id, community_id)
);

-- Insertar datos de ejemplo (con UUIDs)
INSERT INTO communities (uuid, name, privacy, access_code) VALUES
('a1b2c3d4-e5f6-7890-1234-abcdeffedcba', 'Matamoros', 'public', NULL),
('b2c3d4e5-f6a7-8901-2345-bcdeffedcba1', 'Valle Hermoso', 'public', NULL),
('c3d4e5f6-a7b8-9012-3456-cdeffedcba12', 'Universidad A', 'private', 'UNIA123'),
('d4e5f6a7-b8c9-0123-4567-deffedcba123', 'Universidad B', 'private', 'UNIB456');

-- --- ▲▲▲ FIN DE TABLAS MODIFICADAS ▲▲▲ ---


-- ---
-- Tablas para el sistema de Publicaciones
-- ---

DROP TABLE IF EXISTS `publication_attachments`;
DROP TABLE IF EXISTS `publication_files`;
DROP TABLE IF EXISTS `community_publications`;

--
-- Estructura para la tabla `community_publications`
-- (Adaptada de `group_messages`)
--
CREATE TABLE `community_publications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
 `community_id` INT NULL DEFAULT NULL,
  `user_id` INT NOT NULL,
  
  -- El texto de la publicación.
  `text_content` TEXT NULL DEFAULT NULL, 
  
  -- Tipo de publicación (post, poll)
  `post_type` ENUM('post', 'poll') NOT NULL DEFAULT 'post',

  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),

  -- Llaves foráneas
  FOREIGN KEY (community_id) REFERENCES `communities`(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  
  -- Índices
  KEY `idx_community_timestamp` (`community_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura para la tabla `publication_files`
-- (Adaptada de `chat_files`)
--
CREATE TABLE `publication_files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
 `user_id` INT NOT NULL,      -- Quién subió el archivo
  `community_id` INT NULL DEFAULT NULL, -- A qué comunidad pertenece (para limpieza/organización)
  
  `file_name_system` VARCHAR(255) NOT NULL,
  `file_name_original` VARCHAR(255) NOT NULL,
  `public_url` VARCHAR(512) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL, -- ej: "image/png", "video/mp4"
  `file_size` INT NOT NULL,
  
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),

  -- Llaves foráneas
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (community_id) REFERENCES `communities`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura para la tabla `publication_attachments`
-- (Adaptada de `message_attachments`)
--
CREATE TABLE `publication_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL, -- FK a community_publications.id
  `file_id` INT NOT NULL,        -- FK a publication_files.id
  
  -- Para mostrar las fotos en el orden que el usuario las subió
  `sort_order` INT NOT NULL DEFAULT 0, 

  -- Llaves foráneas
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES `publication_files`(id) ON DELETE CASCADE,
  
  -- Asegura que no se pueda adjuntar el mismo archivo dos veces a la misma publicación
  UNIQUE KEY `idx_publication_file` (`publication_id`, `file_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


