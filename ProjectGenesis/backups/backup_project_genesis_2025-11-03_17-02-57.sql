SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for security_logs
-- ----------------------------
DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_identifier` varchar(255) NOT NULL,
  `action_type` enum('login_fail','reset_fail','password_verify_fail') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`user_identifier`,`created_at`),
  KEY `idx_ip_time` (`ip_address`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for security_logs
-- ----------------------------
INSERT INTO `security_logs` VALUES ('9', '123@gmail.com', 'login_fail', '192.168.1.158', '2025-11-03 04:07:46');
INSERT INTO `security_logs` VALUES ('10', '1', '', '192.168.8.2', '2025-11-03 04:46:01');
INSERT INTO `security_logs` VALUES ('11', '1', '', '192.168.8.2', '2025-11-03 04:46:03');
INSERT INTO `security_logs` VALUES ('12', '1', '', '192.168.8.2', '2025-11-03 04:46:06');
INSERT INTO `security_logs` VALUES ('13', '1', '', '192.168.8.2', '2025-11-03 04:46:09');
INSERT INTO `security_logs` VALUES ('14', '1', '', '192.168.8.2', '2025-11-03 04:46:11');
INSERT INTO `security_logs` VALUES ('15', '1', '', '192.168.1.157', '2025-11-03 16:11:37');
INSERT INTO `security_logs` VALUES ('16', '1', '', '192.168.1.157', '2025-11-03 16:11:41');
INSERT INTO `security_logs` VALUES ('17', '1', '', '192.168.1.157', '2025-11-03 16:11:43');
INSERT INTO `security_logs` VALUES ('18', '1', '', '192.168.1.157', '2025-11-03 16:11:45');

-- ----------------------------
-- Table structure for site_settings
-- ----------------------------
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for site_settings
-- ----------------------------
INSERT INTO `site_settings` VALUES ('1', 'maintenance_mode', '0', '2025-11-03 16:13:15');
INSERT INTO `site_settings` VALUES ('4', 'allow_new_registrations', '1', '2025-11-03 16:13:16');
INSERT INTO `site_settings` VALUES ('5', 'username_cooldown_days', '30', '2025-11-03 04:19:50');
INSERT INTO `site_settings` VALUES ('6', 'email_cooldown_days', '12', '2025-11-03 04:41:27');
INSERT INTO `site_settings` VALUES ('7', 'avatar_max_size_mb', '2', '2025-11-02 23:47:47');
INSERT INTO `site_settings` VALUES ('8', 'max_login_attempts', '8', '2025-11-03 00:23:26');
INSERT INTO `site_settings` VALUES ('9', 'lockout_time_minutes', '15', '2025-11-03 00:24:00');
INSERT INTO `site_settings` VALUES ('10', 'allowed_email_domains', 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com', '2025-11-03 04:53:39');
INSERT INTO `site_settings` VALUES ('11', 'min_password_length', '12', '2025-11-03 16:14:37');
INSERT INTO `site_settings` VALUES ('12', 'max_password_length', '72', '2025-11-03 16:01:06');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_audit_logs
-- ----------------------------
INSERT INTO `user_audit_logs` VALUES ('1', '1', 'username', 'user20251102_153602si', 'user20251102_153602siyy', '2025-11-02 23:36:53', '192.168.1.158');
INSERT INTO `user_audit_logs` VALUES ('2', '1', 'email', '123@gmail.com', '12345@gmail.com', '2025-11-02 23:37:15', '192.168.1.158');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_metadata_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_metadata
-- ----------------------------
INSERT INTO `user_metadata` VALUES ('1', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 21:36:12');
INSERT INTO `user_metadata` VALUES ('2', '2', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 21:37:00');
INSERT INTO `user_metadata` VALUES ('3', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 22:59:22');
INSERT INTO `user_metadata` VALUES ('4', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 04:07:50');
INSERT INTO `user_metadata` VALUES ('5', '1', '192.168.8.2', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 04:37:16');
INSERT INTO `user_metadata` VALUES ('6', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 15:33:33');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_preferences
-- ----------------------------
INSERT INTO `user_preferences` VALUES ('1', '1', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('2', '2', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('3', '3', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('4', '4', 'es-latam', 'system', 'personal', '1', '0');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '12345@gmail.com', 'user20251102_153602siyy', '$2y$10$4IpGeqE7vRFYeaLNTznUe.AOi0F0ZU7nNTvapcah/C6ppE3cCVnrK', '/ProjectGenesis/assets/uploads/avatars_default/user-1.png', 'founder', '0', '3ecf6f35789ab661b31681d58d621b68f13a66ac7de66de486f45418f0be0c7a', 'active', '2025-11-02 21:36:12');
INSERT INTO `users` VALUES ('2', '12@gmail.com', 'user20251102_153650l4', '$2y$10$AIsKrZkxLAuWPjTu52Zhyu1BMlTBKIYXIHdkjqC4kOMN6jaTY7it.', '/ProjectGenesis/assets/uploads/avatars_default/user-2.png', 'user', '0', '2adad061c88288daa94de84c1fda1dab1c8cd9d80d1b8125d1d5c8d81f49df1a', 'active', '2025-11-02 21:37:00');
INSERT INTO `users` VALUES ('3', '1mena@gmail.com', 'user20251102_22474914', '$2y$10$ngmHLJRG9LKSwDJmkTrhJuWVCY.zhGABNZQbkeT6dy/CQ7itO8XCu', '/ProjectGenesis/assets/uploads/avatars_default/user-3.png', 'user', '0', 'bbfb7dadf9721e452b2736ab0143778dc90d9133b840a8a2043086e9c5cdb981', 'active', '2025-11-03 04:48:52');
INSERT INTO `users` VALUES ('4', '1ena@gmail.com', 'user20251102_224901zn', '$2y$10$lauWQmhfdiYQHFqjc.ndq.Q1gxjBaS03UVAjVHRuaP3HfszpLRFmW', '/ProjectGenesis/assets/uploads/avatars_default/user-4.png', 'administrator', '0', '96a6bbf67b5f7e2e1969f0688acd5fbba83461653aaef5e7b4dc3fdb71168483', 'deleted', '2025-11-03 04:49:19');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for verification_codes
-- ----------------------------
INSERT INTO `verification_codes` VALUES ('3', '1111311@gmail.com', 'registration', 'DUG74G8U6NZA', '{\"username\":\"user20251102_155555s6\",\"password_hash\":\"$2y$10$xVzKrvIGOhBYqCjyhjU3Ce05Jv3QFAOntkWdUlokZoVLxJgTTn\\/.u\"}', '2025-11-02 21:55:56');
INSERT INTO `verification_codes` VALUES ('6', '1', 'email_change', '9EZRVZIL37KX', NULL, '2025-11-03 16:11:25');

SET FOREIGN_KEY_CHECKS=1;
