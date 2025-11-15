SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for chat_files
-- ----------------------------
DROP TABLE IF EXISTS `chat_files`;
CREATE TABLE `chat_files` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uploader_id` int(11) NOT NULL,
  `file_name_system` varchar(255) NOT NULL,
  `file_name_original` varchar(255) NOT NULL,
  `public_url` varchar(512) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploader_id` (`uploader_id`),
  CONSTRAINT `chat_files_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for chat_files
-- ----------------------------
INSERT INTO `chat_files` VALUES ('1', '1', 'chat-1-1763052362-4dad5828-0.png', 'descarga.png', '/ProjectGenesis/assets/uploads/chat_attachments/chat-1-1763052362-4dad5828-0.png', 'image/png', '1675', '2025-11-13 16:46:02');
INSERT INTO `chat_files` VALUES ('2', '1', 'chat-1-1763052362-b493a756-1.jpg', 'unnamed.jpg', '/ProjectGenesis/assets/uploads/chat_attachments/chat-1-1763052362-b493a756-1.jpg', 'image/jpeg', '157573', '2025-11-13 16:46:02');

-- ----------------------------
-- Table structure for chat_message_attachments
-- ----------------------------
DROP TABLE IF EXISTS `chat_message_attachments`;
CREATE TABLE `chat_message_attachments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) NOT NULL,
  `file_id` bigint(20) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_message_file` (`message_id`,`file_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `chat_message_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_message_attachments_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `chat_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for chat_message_attachments
-- ----------------------------
INSERT INTO `chat_message_attachments` VALUES ('1', '1', '1', '0');
INSERT INTO `chat_message_attachments` VALUES ('2', '1', '2', '1');

-- ----------------------------
-- Table structure for chat_messages
-- ----------------------------
DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversation_pair` (`sender_id`,`receiver_id`,`created_at`),
  KEY `idx_receiver_unread` (`receiver_id`,`is_read`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for chat_messages
-- ----------------------------
INSERT INTO `chat_messages` VALUES ('1', '1', '2', 'CASA', '1', '2025-11-13 16:46:02');

-- ----------------------------
-- Table structure for communities
-- ----------------------------
DROP TABLE IF EXISTS `communities`;
CREATE TABLE `communities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `privacy` enum('public','private') NOT NULL DEFAULT 'public',
  `access_code` varchar(50) DEFAULT NULL,
  `community_type` enum('municipio','universidad') NOT NULL DEFAULT 'municipio' COMMENT 'Tipo de comunidad para descripción i18n',
  `icon_url` varchar(512) DEFAULT NULL COMMENT 'URL para el icono de la comunidad',
  `banner_url` varchar(512) DEFAULT NULL COMMENT 'URL para el banner de la comunidad',
  `max_members` int(11) DEFAULT NULL COMMENT 'Límite de miembros (null o 0 = sin límite)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uuid` (`uuid`),
  KEY `idx_access_code` (`access_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for communities
-- ----------------------------
INSERT INTO `communities` VALUES ('1', 'a1b2c3d4-e5f6-7890-1234-abcdeffedcba', 'Matamoros', 'public', NULL, 'municipio', 'https://picsum.photos/seed/comm1/128/128', 'https://picsum.photos/seed/banner1/400/120', NULL, '2025-11-13 04:19:04');
INSERT INTO `communities` VALUES ('2', 'b2c3d4e5-f6a7-8901-2345-bcdeffedcba1', 'Valle Hermoso', 'public', NULL, 'municipio', 'https://picsum.photos/seed/comm2/128/128', 'https://picsum.photos/seed/banner2/400/120', NULL, '2025-11-13 04:19:04');
INSERT INTO `communities` VALUES ('3', 'c3d4e5f6-a7b8-9012-3456-cdeffedcba12', 'Universidad A', 'private', 'UNIA123', 'universidad', 'https://picsum.photos/seed/comm3/128/128', 'https://picsum.photos/seed/banner3/400/120', NULL, '2025-11-13 04:19:04');
INSERT INTO `communities` VALUES ('4', 'd4e5f6a7-b8c9-0123-4567-deffedcba123', 'Universidad B', 'private', 'UNIB456', 'universidad', 'https://picsum.photos/seed/comm4/128/128', 'https://picsum.photos/seed/banner4/400/120', NULL, '2025-11-13 04:19:04');

-- ----------------------------
-- Table structure for community_publications
-- ----------------------------
DROP TABLE IF EXISTS `community_publications`;
CREATE TABLE `community_publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text_content` text DEFAULT NULL,
  `post_type` enum('post','poll') NOT NULL DEFAULT 'post',
  `post_status` enum('active','deleted') NOT NULL DEFAULT 'active' COMMENT 'Para soft-delete',
  `privacy_level` enum('public','friends','private') NOT NULL DEFAULT 'public' COMMENT 'Nivel de privacidad del post',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_community_timestamp` (`community_id`,`created_at`),
  KEY `idx_post_status` (`post_status`),
  KEY `idx_privacy_level` (`privacy_level`),
  CONSTRAINT `community_publications_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_publications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for community_publications
-- ----------------------------

-- ----------------------------
-- Table structure for friendships
-- ----------------------------
DROP TABLE IF EXISTS `friendships`;
CREATE TABLE `friendships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_1` int(11) NOT NULL,
  `user_id_2` int(11) NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `action_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_friendship` (`user_id_1`,`user_id_2`),
  KEY `action_user_id` (`action_user_id`),
  KEY `idx_user_1_status` (`user_id_1`,`status`),
  KEY `idx_user_2_status` (`user_id_2`,`status`),
  CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`user_id_1`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`user_id_2`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_ibfk_3` FOREIGN KEY (`action_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for friendships
-- ----------------------------
INSERT INTO `friendships` VALUES ('1', '1', '2', 'accepted', '1', '2025-11-13 05:27:46', '2025-11-13 05:27:51');

-- ----------------------------
-- Table structure for hashtags
-- ----------------------------
DROP TABLE IF EXISTS `hashtags`;
CREATE TABLE `hashtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(100) NOT NULL COMMENT 'El texto del hashtag, sin # y en minúsculas',
  `use_count` bigint(20) NOT NULL DEFAULT 1 COMMENT 'Contador de cuántas veces se ha usado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`),
  KEY `idx_tag` (`tag`),
  KEY `idx_use_count` (`use_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for hashtags
-- ----------------------------

-- ----------------------------
-- Table structure for poll_options
-- ----------------------------
DROP TABLE IF EXISTS `poll_options`;
CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_publication_id` (`publication_id`),
  CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for poll_options
-- ----------------------------

-- ----------------------------
-- Table structure for poll_votes
-- ----------------------------
DROP TABLE IF EXISTS `poll_votes`;
CREATE TABLE `poll_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `poll_option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_poll` (`user_id`,`publication_id`),
  KEY `publication_id` (`publication_id`),
  KEY `poll_option_id` (`poll_option_id`),
  CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`poll_option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for poll_votes
-- ----------------------------

-- ----------------------------
-- Table structure for publication_attachments
-- ----------------------------
DROP TABLE IF EXISTS `publication_attachments`;
CREATE TABLE `publication_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_publication_file` (`publication_id`,`file_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `publication_attachments_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_attachments_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `publication_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_attachments
-- ----------------------------

-- ----------------------------
-- Table structure for publication_bookmarks
-- ----------------------------
DROP TABLE IF EXISTS `publication_bookmarks`;
CREATE TABLE `publication_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_publication_bookmark` (`user_id`,`publication_id`),
  KEY `publication_id` (`publication_id`),
  CONSTRAINT `publication_bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_bookmarks_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_bookmarks
-- ----------------------------

-- ----------------------------
-- Table structure for publication_comments
-- ----------------------------
DROP TABLE IF EXISTS `publication_comments`;
CREATE TABLE `publication_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_publication_id` (`publication_id`),
  KEY `idx_parent_comment_id` (`parent_comment_id`),
  CONSTRAINT `publication_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_comments_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `publication_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_comments
-- ----------------------------

-- ----------------------------
-- Table structure for publication_files
-- ----------------------------
DROP TABLE IF EXISTS `publication_files`;
CREATE TABLE `publication_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `community_id` int(11) DEFAULT NULL,
  `file_name_system` varchar(255) NOT NULL,
  `file_name_original` varchar(255) NOT NULL,
  `public_url` varchar(512) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `community_id` (`community_id`),
  CONSTRAINT `publication_files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_files_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_files
-- ----------------------------

-- ----------------------------
-- Table structure for publication_hashtags
-- ----------------------------
DROP TABLE IF EXISTS `publication_hashtags`;
CREATE TABLE `publication_hashtags` (
  `publication_id` int(11) NOT NULL,
  `hashtag_id` int(11) NOT NULL,
  PRIMARY KEY (`publication_id`,`hashtag_id`),
  KEY `idx_hashtag_id` (`hashtag_id`),
  CONSTRAINT `publication_hashtags_ibfk_1` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_hashtags_ibfk_2` FOREIGN KEY (`hashtag_id`) REFERENCES `hashtags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_hashtags
-- ----------------------------

-- ----------------------------
-- Table structure for publication_likes
-- ----------------------------
DROP TABLE IF EXISTS `publication_likes`;
CREATE TABLE `publication_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_publication_like` (`user_id`,`publication_id`),
  KEY `publication_id` (`publication_id`),
  CONSTRAINT `publication_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `publication_likes_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `community_publications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for publication_likes
-- ----------------------------

-- ----------------------------
-- Table structure for security_logs
-- ----------------------------
DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_identifier` varchar(255) NOT NULL,
  `action_type` enum('login_fail','reset_fail','password_verify_fail','preference_spam') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`user_identifier`,`created_at`),
  KEY `idx_ip_time` (`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for security_logs
-- ----------------------------

-- ----------------------------
-- Table structure for site_settings
-- ----------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for site_settings
-- ----------------------------
INSERT INTO `site_settings` VALUES ('1', 'maintenance_mode', '0');
INSERT INTO `site_settings` VALUES ('2', 'allow_new_registrations', '1');
INSERT INTO `site_settings` VALUES ('3', 'username_cooldown_days', '30');
INSERT INTO `site_settings` VALUES ('4', 'email_cooldown_days', '12');
INSERT INTO `site_settings` VALUES ('5', 'avatar_max_size_mb', '2');
INSERT INTO `site_settings` VALUES ('6', 'max_login_attempts', '5');
INSERT INTO `site_settings` VALUES ('7', 'lockout_time_minutes', '5');
INSERT INTO `site_settings` VALUES ('8', 'allowed_email_domains', 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com');
INSERT INTO `site_settings` VALUES ('9', 'min_password_length', '8');
INSERT INTO `site_settings` VALUES ('10', 'max_password_length', '72');
INSERT INTO `site_settings` VALUES ('11', 'min_username_length', '6');
INSERT INTO `site_settings` VALUES ('12', 'max_username_length', '32');
INSERT INTO `site_settings` VALUES ('13', 'max_email_length', '255');
INSERT INTO `site_settings` VALUES ('14', 'code_resend_cooldown_seconds', '60');
INSERT INTO `site_settings` VALUES ('15', 'max_concurrent_users', '500');
INSERT INTO `site_settings` VALUES ('16', 'max_post_length', '1000');

-- ----------------------------
-- Table structure for user_audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `user_audit_logs`;
CREATE TABLE `user_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `change_type` enum('username','email','password') NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `changed_by_ip` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_type_time` (`user_id`,`change_type`,`changed_at`),
  CONSTRAINT `user_audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_audit_logs
-- ----------------------------

-- ----------------------------
-- Table structure for user_communities
-- ----------------------------
DROP TABLE IF EXISTS `user_communities`;
CREATE TABLE `user_communities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_community` (`user_id`,`community_id`),
  KEY `community_id` (`community_id`),
  CONSTRAINT `user_communities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_communities_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_communities
-- ----------------------------

-- ----------------------------
-- Table structure for user_metadata
-- ----------------------------
DROP TABLE IF EXISTS `user_metadata`;
CREATE TABLE `user_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_type` varchar(50) DEFAULT 'Unknown',
  `browser_info` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  CONSTRAINT `user_metadata_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_metadata
-- ----------------------------
INSERT INTO `user_metadata` VALUES ('1', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '1', '2025-11-13 04:19:40');
INSERT INTO `user_metadata` VALUES ('2', '2', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '1', '2025-11-13 05:21:19');
INSERT INTO `user_metadata` VALUES ('3', '1', '192.168.1.155', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '1', '2025-11-13 16:09:30');
INSERT INTO `user_metadata` VALUES ('4', '2', '192.168.1.155', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '1', '2025-11-13 16:09:53');

-- ----------------------------
-- Table structure for user_notifications
-- ----------------------------
DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `actor_user_id` int(11) NOT NULL,
  `type` enum('friend_request','friend_accept','like','comment','reply') NOT NULL,
  `reference_id` int(11) NOT NULL COMMENT 'ID del post, comentario o usuario relevante',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `actor_user_id` (`actor_user_id`),
  KEY `idx_user_read_time` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_notifications
-- ----------------------------
INSERT INTO `user_notifications` VALUES ('2', '2', '1', 'friend_accept', '1', '0', '2025-11-13 05:27:52');

-- ----------------------------
-- Table structure for user_preferences
-- ----------------------------
DROP TABLE IF EXISTS `user_preferences`;
CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en-us',
  `theme` enum('system','light','dark') NOT NULL DEFAULT 'system',
  `usage_type` varchar(50) NOT NULL DEFAULT 'personal',
  `open_links_in_new_tab` tinyint(1) NOT NULL DEFAULT 1,
  `increase_message_duration` tinyint(1) NOT NULL DEFAULT 0,
  `is_friend_list_private` tinyint(1) NOT NULL DEFAULT 1,
  `is_email_public` tinyint(1) NOT NULL DEFAULT 0,
  `employment` varchar(100) DEFAULT 'none',
  `education` varchar(100) DEFAULT 'none',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_preferences
-- ----------------------------
INSERT INTO `user_preferences` VALUES ('1', '1', 'es-mx', 'system', 'personal', '1', '0', '1', '0', 'none', 'none');
INSERT INTO `user_preferences` VALUES ('2', '2', 'es-latam', 'system', 'personal', '1', '0', '1', '0', 'none', 'none');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(512) DEFAULT NULL COMMENT 'URL para el banner del perfil',
  `role` enum('user','moderator','administrator','founder') NOT NULL DEFAULT 'user',
  `is_2fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auth_token` varchar(64) DEFAULT NULL,
  `account_status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `bio` text DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_is_online` (`is_online`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '12@gmail.com', 'user20251112_221928tg', '$2y$10$0FYHOoZ/hokjRxL5h/fw5Ot7akQKIQrVsbldT5E6QtO44RSdv8OP2', '/ProjectGenesis/assets/uploads/avatars_default/user-1.png', NULL, 'founder', '0', '5195e594e44b146334e9163e1675635544083a54c4585beb766d8023e55ed5fe', 'active', NULL, '0', '2025-11-13 16:48:31', '2025-11-13 04:19:39');
INSERT INTO `users` VALUES ('2', '12345@gmail.com', 'user20251112_2321044j', '$2y$10$dlY8olsWMv0GrnV1VYWsuupfPjlaKFAmm6pKDQyQDUe2Ba2N1VtQq', '/ProjectGenesis/assets/uploads/avatars_default/user-2.png', NULL, 'user', '0', '16e5d323a92c8caa68f558fa335802e8d88a0f1e9e094369feecc93f8a4d28ff', 'active', NULL, '0', '2025-11-13 16:47:25', '2025-11-13 05:21:18');

-- ----------------------------
-- Table structure for verification_codes
-- ----------------------------
DROP TABLE IF EXISTS `verification_codes`;
CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `code_type` enum('registration','password_reset','2fa','email_change') NOT NULL,
  `code` varchar(255) NOT NULL,
  `payload` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_identifier_type` (`identifier`,`code_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for verification_codes
-- ----------------------------

SET FOREIGN_KEY_CHECKS=1;
