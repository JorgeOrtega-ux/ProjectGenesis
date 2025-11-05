SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for security_logs
-- ----------------------------
INSERT INTO `security_logs` VALUES ('1', '12', 'login_fail', '192.168.1.156', '2025-11-04 04:23:08');
INSERT INTO `security_logs` VALUES ('2', '12', 'login_fail', '192.168.1.156', '2025-11-04 04:23:12');
INSERT INTO `security_logs` VALUES ('3', '12', 'login_fail', '192.168.1.156', '2025-11-04 04:23:16');
INSERT INTO `security_logs` VALUES ('7', '12345@gmail.com', 'login_fail', '192.168.1.157', '2025-11-04 18:23:56');
INSERT INTO `security_logs` VALUES ('8', '3', 'preference_spam', '192.168.1.157', '2025-11-04 19:31:19');

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for site_settings
-- ----------------------------
INSERT INTO `site_settings` VALUES ('1', 'maintenance_mode', '0');
INSERT INTO `site_settings` VALUES ('2', 'allow_new_registrations', '0');
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
INSERT INTO `site_settings` VALUES ('15', 'max_concurrent_users', '10');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_audit_logs
-- ----------------------------
INSERT INTO `user_audit_logs` VALUES ('1', '1', 'password', '$2y$10$1f2R4HMZWRA48pySHk3nHe23/B.J4dXycftCcHvxIHrhqgz6jRo2q', '$2y$10$brKy1E2kPvR5YRw/BN8jpeljGnly2UE7QWCgxMswztrEJPTcf42W2', '2025-11-02 03:22:55', '192.168.1.157');
INSERT INTO `user_audit_logs` VALUES ('2', '1', 'password', '$2y$10$brKy1E2kPvR5YRw/BN8jpeljGnly2UE7QWCgxMswztrEJPTcf42W2', '$2y$10$RerI4iQ6xZf/0hEv/F2jEer/1qlv4Kx1xAufSK6PjjeEzMoYyymLy', '2025-11-04 04:24:18', '192.168.1.157');
INSERT INTO `user_audit_logs` VALUES ('3', '2', 'password', '$2y$10$MI5HTvixOaGb/uOxZPTh8.xIrrysZ8wsDJAEhUAmkcnYw51Qus3Su', '$2y$10$LzNw0yE9lMa7LJVmdrzjFe4moivI3JkXxEV4/iecwIOoLOxpq8Q.K', '2025-11-04 18:30:15', '192.168.1.157');
INSERT INTO `user_audit_logs` VALUES ('4', '2', 'username', 'user20251104_112720u7', 'user20251104_112720u7d', '2025-11-04 18:35:48', '192.168.1.155');

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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_metadata
-- ----------------------------
INSERT INTO `user_metadata` VALUES ('1', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '0', '2025-11-04 04:04:30');
INSERT INTO `user_metadata` VALUES ('2', '1', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '0', '2025-11-04 04:04:55');
INSERT INTO `user_metadata` VALUES ('3', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '0', '2025-11-04 04:05:47');
INSERT INTO `user_metadata` VALUES ('4', '1', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '0', '2025-11-04 04:10:15');
INSERT INTO `user_metadata` VALUES ('5', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '0', '2025-11-04 04:10:29');
INSERT INTO `user_metadata` VALUES ('6', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '0', '2025-11-04 04:11:03');
INSERT INTO `user_metadata` VALUES ('7', '1', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '0', '2025-11-04 04:11:11');
INSERT INTO `user_metadata` VALUES ('8', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 04:11:39');
INSERT INTO `user_metadata` VALUES ('9', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '0', '2025-11-04 04:22:42');
INSERT INTO `user_metadata` VALUES ('10', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '0', '2025-11-04 04:23:20');
INSERT INTO `user_metadata` VALUES ('11', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 04:35:47');
INSERT INTO `user_metadata` VALUES ('12', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 04:41:35');
INSERT INTO `user_metadata` VALUES ('13', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 16:50:28');
INSERT INTO `user_metadata` VALUES ('14', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 16:52:00');
INSERT INTO `user_metadata` VALUES ('15', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 16:52:42');
INSERT INTO `user_metadata` VALUES ('16', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_1_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '1', '2025-11-04 18:31:57');
INSERT INTO `user_metadata` VALUES ('17', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 18:32:34');
INSERT INTO `user_metadata` VALUES ('18', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_1_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '1', '2025-11-04 18:34:18');
INSERT INTO `user_metadata` VALUES ('19', '3', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 18:38:55');
INSERT INTO `user_metadata` VALUES ('20', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:05:36');
INSERT INTO `user_metadata` VALUES ('21', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_1_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '1', '2025-11-04 19:07:01');
INSERT INTO `user_metadata` VALUES ('22', '1', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 19:13:29');
INSERT INTO `user_metadata` VALUES ('23', '3', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 19:17:09');
INSERT INTO `user_metadata` VALUES ('24', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:27:35');
INSERT INTO `user_metadata` VALUES ('25', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:28:56');
INSERT INTO `user_metadata` VALUES ('26', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:31:12');
INSERT INTO `user_metadata` VALUES ('27', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:33:21');
INSERT INTO `user_metadata` VALUES ('28', '3', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '1', '2025-11-04 19:34:08');
INSERT INTO `user_metadata` VALUES ('29', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:34:21');
INSERT INTO `user_metadata` VALUES ('30', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:44:08');
INSERT INTO `user_metadata` VALUES ('31', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:47:52');
INSERT INTO `user_metadata` VALUES ('32', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:48:24');
INSERT INTO `user_metadata` VALUES ('33', '3', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '1', '2025-11-04 19:49:08');

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_preferences
-- ----------------------------
INSERT INTO `user_preferences` VALUES ('1', '1', 'es-mx', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('2', '2', 'es-mx', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('3', '3', 'es-latam', 'system', 'personal', '1', '0');

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
  `role` enum('user','moderator','administrator','founder') NOT NULL DEFAULT 'user',
  `is_2fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auth_token` varchar(64) DEFAULT NULL,
  `account_status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '12@gmail.com', 'user20251103_2204215x', '$2y$10$RerI4iQ6xZf/0hEv/F2jEer/1qlv4Kx1xAufSK6PjjeEzMoYyymLy', '/ProjectGenesis/assets/uploads/avatars_default/user-1.png', 'founder', '0', '0b18a5ce89a162cffd05e289251c31480edf0d3a397bcaf0f68f911d2e71a0e8', 'active', '2025-11-04 04:04:29');
INSERT INTO `users` VALUES ('2', '122@gmail.com', 'user20251104_112720u7d', '$2y$10$LzNw0yE9lMa7LJVmdrzjFe4moivI3JkXxEV4/iecwIOoLOxpq8Q.K', '/ProjectGenesis/assets/uploads/avatars_default/user-2.png', 'user', '0', '3baffa390f21697918cfc0d47fa4cb645c0af07869d6df84715ea08c442bdb54', 'active', '2025-11-04 17:27:45');
INSERT INTO `users` VALUES ('3', 'jorge1ortega.484@gmail.com', 'user20251104_123829el', '$2y$10$9tGf2iE5pLWTABLWBhvwRer6dFkEA2OP35FKEEhY0wybKMkNJ8t5m', '/ProjectGenesis/assets/uploads/avatars_default/user-3.png', 'user', '0', '91b7e89bccdf9d3aefa029c929ce4f48fa7a378fba7ed7b45e1455578aa2aa6b', 'active', '2025-11-04 18:38:54');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for verification_codes
-- ----------------------------
INSERT INTO `verification_codes` VALUES ('3', '122@gmail.com', '2fa', 'GG9GUZDRGJM0', NULL, '2025-11-04 18:30:33');

SET FOREIGN_KEY_CHECKS=1;
