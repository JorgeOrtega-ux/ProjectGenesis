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
    banner_url VARCHAR(512) DEFAULT NULL COMMENT 'URL para el banner del perfil',
    role ENUM('user', 'moderator', 'administrator', 'founder') NOT NULL DEFAULT 'user',
    is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    auth_token VARCHAR(64) NULL DEFAULT NULL,
    account_status ENUM('active', 'suspended', 'deleted') NOT NULL DEFAULT 'active',
    
    is_online TINYINT(1) NOT NULL DEFAULT 0,
    last_seen TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
    
    , INDEX idx_is_online (is_online)
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
    is_friend_list_private TINYINT(1) NOT NULL DEFAULT 1,
    is_email_public TINYINT(1) NOT NULL DEFAULT 0,
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
('max_concurrent_users', '500'),
('max_post_length', '1000');

DROP TABLE IF EXISTS communities;
CREATE TABLE communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    privacy ENUM('public', 'private') NOT NULL DEFAULT 'public',
    access_code VARCHAR(50) NULL,
    
    `community_type` ENUM('municipio', 'universidad') NOT NULL DEFAULT 'municipio' COMMENT 'Tipo de comunidad para descripción i18n',
    icon_url VARCHAR(512) NULL DEFAULT NULL COMMENT 'URL para el icono de la comunidad',
    banner_url VARCHAR(512) NULL DEFAULT NULL COMMENT 'URL para el banner de la comunidad',
    
    max_members INT NULL DEFAULT NULL COMMENT 'Límite de miembros (null o 0 = sin límite)',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX idx_access_code (access_code),
    UNIQUE KEY uk_uuid (uuid)
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

INSERT INTO communities (uuid, name, privacy, access_code, community_type, icon_url, banner_url) VALUES
('a1b2c3d4-e5f6-7890-1234-abcdeffedcba', 'Matamoros', 'public', NULL, 'municipio', 'https://picsum.photos/seed/comm1/128/128', 'https://picsum.photos/seed/banner1/400/120'),
('b2c3d4e5-f6a7-8901-2345-bcdeffedcba1', 'Valle Hermoso', 'public', NULL, 'municipio', 'https://picsum.photos/seed/comm2/128/128', 'https://picsum.photos/seed/banner2/400/120'),
('c3d4e5f6-a7b8-9012-3456-cdeffedcba12', 'Universidad A', 'private', 'UNIA123', 'universidad', 'https://picsum.photos/seed/comm3/128/128', 'https://picsum.photos/seed/banner3/400/120'),
('d4e5f6a7-b8c9-0123-4567-deffedcba123', 'Universidad B', 'private', 'UNIB456', 'universidad', 'https://picsum.photos/seed/comm4/128/128', 'https://picsum.photos/seed/banner4/400/120');


DROP TABLE IF EXISTS `publication_attachments`;
DROP TABLE IF EXISTS `publication_files`;
DROP TABLE IF EXISTS `poll_votes`;
DROP TABLE IF EXISTS `poll_options`;
DROP TABLE IF EXISTS `publication_hashtags`; /* --- [HASTAGS] --- */
DROP TABLE IF EXISTS `hashtags`; /* --- [HASTAGS] --- */
DROP TABLE IF EXISTS `community_publications`;
DROP TABLE IF EXISTS `publication_likes`;
DROP TABLE IF EXISTS `publication_comments`;
DROP TABLE IF EXISTS `publication_bookmarks`;
DROP TABLE IF EXISTS `friendships`; 

CREATE TABLE `community_publications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  
  -- --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
  `community_id` INT NULL DEFAULT NULL, 
  -- --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
  
  `user_id` INT NOT NULL,
  
  `title` VARCHAR(255) NULL DEFAULT NULL,
  
  `text_content` TEXT NULL DEFAULT NULL, 
  `post_type` ENUM('post', 'poll') NOT NULL DEFAULT 'post',
  
  `post_status` ENUM('active', 'deleted') NOT NULL DEFAULT 'active' COMMENT 'Para soft-delete',
  `privacy_level` ENUM('public', 'friends', 'private') NOT NULL DEFAULT 'public' COMMENT 'Nivel de privacidad del post',
  
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  
  -- La clave foránea se mantiene, pero solo se aplica si community_id no es NULL
  FOREIGN KEY (community_id) REFERENCES `communities`(id) ON DELETE CASCADE,
  
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  KEY `idx_community_timestamp` (`community_id`,`created_at`),
  
  INDEX `idx_post_status` (`post_status`),
  INDEX `idx_privacy_level` (`privacy_level`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `publication_files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `community_id` INT NULL DEFAULT NULL, -- <-- TAMBIÉN SE MODIFICA AQUÍ
  `file_name_system` VARCHAR(255) NOT NULL,
  `file_name_original` VARCHAR(255) NOT NULL,
  `public_url` VARCHAR(512) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `file_size` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (community_id) REFERENCES `communities`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `publication_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL,
  `file_id` INT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0, 
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  FOREIGN KEY (file_id) REFERENCES `publication_files`(id) ON DELETE CASCADE,
  UNIQUE KEY `idx_publication_file` (`publication_id`, `file_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `poll_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL, 
  `option_text` VARCHAR(255) NOT NULL,
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  INDEX `idx_publication_id` (`publication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `poll_votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL, 
  `poll_option_id` INT NOT NULL, 
  `user_id` INT NOT NULL,        
  `voted_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  FOREIGN KEY (poll_option_id) REFERENCES `poll_options`(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  UNIQUE KEY `uk_user_poll` (`user_id`, `publication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `publication_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `publication_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  UNIQUE KEY `uk_user_publication_like` (`user_id`, `publication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `publication_bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `publication_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  UNIQUE KEY `uk_user_publication_bookmark` (`user_id`, `publication_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `publication_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `publication_id` INT NOT NULL,
  `parent_comment_id` INT NULL DEFAULT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_comment_id) REFERENCES `publication_comments`(id) ON DELETE CASCADE,
  INDEX `idx_publication_id` (`publication_id`),
  INDEX `idx_parent_comment_id` (`parent_comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `friendships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id_1` INT NOT NULL,
  `user_id_2` INT NOT NULL,
  `status` ENUM('pending', 'accepted') NOT NULL DEFAULT 'pending',
  `action_user_id` INT NOT NULL, 
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  FOREIGN KEY (user_id_1) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id_2) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (action_user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  
  UNIQUE KEY `uk_friendship` (`user_id_1`, `user_id_2`),
  INDEX `idx_user_1_status` (`user_id_1`, `status`),
  INDEX `idx_user_2_status` (`user_id_2`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `actor_user_id` INT NOT NULL,
  `type` ENUM('friend_request', 'friend_accept', 'like', 'comment', 'reply') NOT NULL,
  `reference_id` INT NOT NULL COMMENT 'ID del post, comentario o usuario relevante',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  
  INDEX `idx_user_read_time` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* --- [HASTAGS] --- INICIO DE NUEVAS TABLAS --- */

DROP TABLE IF EXISTS `hashtags`;
CREATE TABLE `hashtags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tag` VARCHAR(100) NOT NULL UNIQUE COMMENT 'El texto del hashtag, sin # y en minúsculas',
  `use_count` BIGINT NOT NULL DEFAULT 1 COMMENT 'Contador de cuántas veces se ha usado',
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  INDEX `idx_tag` (`tag`),
  INDEX `idx_use_count` (`use_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `publication_hashtags`;
CREATE TABLE `publication_hashtags` (
  `publication_id` INT NOT NULL,
  `hashtag_id` INT NOT NULL,
  PRIMARY KEY (`publication_id`, `hashtag_id`),
  FOREIGN KEY (publication_id) REFERENCES `community_publications`(id) ON DELETE CASCADE,
  FOREIGN KEY (hashtag_id) REFERENCES `hashtags`(id) ON DELETE CASCADE,
  INDEX `idx_hashtag_id` (`hashtag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* --- [HASTAGS] --- FIN DE NUEVAS TABLAS --- */