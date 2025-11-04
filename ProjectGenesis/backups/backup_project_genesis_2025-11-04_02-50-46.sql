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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for security_logs
-- ----------------------------
INSERT INTO `security_logs` VALUES ('9', '123@gmail.com', 'login_fail', '192.168.1.158', '2025-11-03 04:07:46');
INSERT INTO `security_logs` VALUES ('21', 'h', 'login_fail', '192.168.1.157', '2025-11-03 19:53:19');
INSERT INTO `security_logs` VALUES ('22', '1', '', '192.168.1.157', '2025-11-03 20:09:36');
INSERT INTO `security_logs` VALUES ('23', '1', '', '192.168.1.157', '2025-11-03 20:09:38');
INSERT INTO `security_logs` VALUES ('24', '1', '', '192.168.1.157', '2025-11-03 20:09:40');
INSERT INTO `security_logs` VALUES ('25', '1', '', '192.168.1.157', '2025-11-03 20:09:43');
INSERT INTO `security_logs` VALUES ('26', '1', '', '192.168.1.157', '2025-11-03 20:09:45');
INSERT INTO `security_logs` VALUES ('27', '1', '', '192.168.1.157', '2025-11-03 20:09:47');

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for site_settings
-- ----------------------------
INSERT INTO `site_settings` VALUES ('1', 'maintenance_mode', '0', '2025-11-03 20:24:17');
INSERT INTO `site_settings` VALUES ('4', 'allow_new_registrations', '1', '2025-11-03 20:24:18');
INSERT INTO `site_settings` VALUES ('5', 'username_cooldown_days', '30', '2025-11-03 04:19:50');
INSERT INTO `site_settings` VALUES ('6', 'email_cooldown_days', '12', '2025-11-03 04:41:27');
INSERT INTO `site_settings` VALUES ('7', 'avatar_max_size_mb', '2', '2025-11-02 23:47:47');
INSERT INTO `site_settings` VALUES ('8', 'max_login_attempts', '8', '2025-11-03 00:23:26');
INSERT INTO `site_settings` VALUES ('9', 'lockout_time_minutes', '15', '2025-11-03 00:24:00');
INSERT INTO `site_settings` VALUES ('10', 'allowed_email_domains', 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com', '2025-11-03 04:53:39');
INSERT INTO `site_settings` VALUES ('11', 'min_password_length', '12', '2025-11-03 16:14:37');
INSERT INTO `site_settings` VALUES ('12', 'max_password_length', '72', '2025-11-04 02:50:09');
INSERT INTO `site_settings` VALUES ('13', 'min_username_length', '6', '2025-11-03 21:44:09');
INSERT INTO `site_settings` VALUES ('14', 'max_username_length', '32', '2025-11-03 21:44:09');
INSERT INTO `site_settings` VALUES ('15', 'max_email_length', '255', '2025-11-03 21:48:55');
INSERT INTO `site_settings` VALUES ('16', 'code_resend_cooldown_seconds', '60', '2025-11-03 21:44:09');
INSERT INTO `site_settings` VALUES ('17', 'max_concurrent_users', '40', '2025-11-03 23:52:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_audit_logs
-- ----------------------------
INSERT INTO `user_audit_logs` VALUES ('1', '1', 'username', 'user20251102_153602si', 'user20251102_153602siyy', '2025-11-02 23:36:53', '192.168.1.158');
INSERT INTO `user_audit_logs` VALUES ('2', '1', 'email', '123@gmail.com', '12345@gmail.com', '2025-11-02 23:37:15', '192.168.1.158');
INSERT INTO `user_audit_logs` VALUES ('3', '4', 'username', 'user20251102_224901zn', 'user20251102_224901zndd', '2025-11-03 19:12:32', '192.168.1.157');
INSERT INTO `user_audit_logs` VALUES ('4', '5', 'username', 'user20251103_140241ii', 'user20251103_140241ii6', '2025-11-03 20:03:27', '192.168.1.157');
INSERT INTO `user_audit_logs` VALUES ('5', '13', 'username', 'user202511aaaaa03_143457cb', 'user202511aaaaa03_143457cbc', '2025-11-03 20:40:05', '192.168.1.157');

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_metadata
-- ----------------------------
INSERT INTO `user_metadata` VALUES ('1', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 21:36:12');
INSERT INTO `user_metadata` VALUES ('2', '2', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 21:37:00');
INSERT INTO `user_metadata` VALUES ('3', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 22:59:22');
INSERT INTO `user_metadata` VALUES ('4', '1', '192.168.1.158', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 04:07:50');
INSERT INTO `user_metadata` VALUES ('5', '1', '192.168.8.2', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 04:37:16');
INSERT INTO `user_metadata` VALUES ('6', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 15:33:33');
INSERT INTO `user_metadata` VALUES ('7', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 19:51:46');
INSERT INTO `user_metadata` VALUES ('8', '5', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:03:00');
INSERT INTO `user_metadata` VALUES ('9', '6', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:04:30');
INSERT INTO `user_metadata` VALUES ('10', '7', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:17:34');
INSERT INTO `user_metadata` VALUES ('11', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:19:59');
INSERT INTO `user_metadata` VALUES ('12', '9', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:31:25');
INSERT INTO `user_metadata` VALUES ('13', '10', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:35:26');
INSERT INTO `user_metadata` VALUES ('14', '13', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:39:57');
INSERT INTO `user_metadata` VALUES ('15', '14', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:44:33');
INSERT INTO `user_metadata` VALUES ('16', '53', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 20:48:59');
INSERT INTO `user_metadata` VALUES ('17', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:18:15');
INSERT INTO `user_metadata` VALUES ('18', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:23:10');
INSERT INTO `user_metadata` VALUES ('19', '56', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:54:24');
INSERT INTO `user_metadata` VALUES ('20', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:58:17');
INSERT INTO `user_metadata` VALUES ('21', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '2025-11-03 23:00:35');
INSERT INTO `user_metadata` VALUES ('22', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '2025-11-03 23:05:27');
INSERT INTO `user_metadata` VALUES ('23', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '2025-11-03 23:06:54');
INSERT INTO `user_metadata` VALUES ('24', '2', '192.168.1.156', 'Mobile', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-03 23:09:01');
INSERT INTO `user_metadata` VALUES ('25', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 23:13:25');
INSERT INTO `user_metadata` VALUES ('26', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 23:14:24');
INSERT INTO `user_metadata` VALUES ('27', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 23:20:11');
INSERT INTO `user_metadata` VALUES ('28', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 23:31:42');
INSERT INTO `user_metadata` VALUES ('29', '2', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 23:32:56');
INSERT INTO `user_metadata` VALUES ('30', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '2025-11-03 23:53:11');
INSERT INTO `user_metadata` VALUES ('31', '2', '192.168.1.155', 'Mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.7390.96 Mobile/15E148 Safari/604.1', '2025-11-03 23:58:13');
INSERT INTO `user_metadata` VALUES ('32', '1', '192.168.1.157', 'Desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 02:49:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for user_preferences
-- ----------------------------
INSERT INTO `user_preferences` VALUES ('1', '1', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('2', '2', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('3', '3', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('4', '4', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('5', '5', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('6', '6', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('7', '7', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('8', '9', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('9', '10', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('10', '13', 'es-mx', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('11', '14', 'es-latam', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('12', '53', 'es-mx', 'system', 'personal', '1', '0');
INSERT INTO `user_preferences` VALUES ('13', '56', 'es-mx', 'system', 'personal', '1', '0');

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
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '12345@gmail.com', 'user20251102_153602siyy', '$2y$10$4IpGeqE7vRFYeaLNTznUe.AOi0F0ZU7nNTvapcah/C6ppE3cCVnrK', '/ProjectGenesis/assets/uploads/avatars_default/user-1.png', 'founder', '0', '3ecf6f35789ab661b31681d58d621b68f13a66ac7de66de486f45418f0be0c7a', 'active', '2025-11-02 21:36:12');
INSERT INTO `users` VALUES ('2', '12@gmail.com', 'user20251102_153650l4', '$2y$10$AIsKrZkxLAuWPjTu52Zhyu1BMlTBKIYXIHdkjqC4kOMN6jaTY7it.', '/ProjectGenesis/assets/uploads/avatars_default/user-2.png', 'user', '0', '2adad061c88288daa94de84c1fda1dab1c8cd9d80d1b8125d1d5c8d81f49df1a', 'active', '2025-11-02 21:37:00');
INSERT INTO `users` VALUES ('3', '1mena@gmail.com', 'user20251102_22474914', '$2y$10$ngmHLJRG9LKSwDJmkTrhJuWVCY.zhGABNZQbkeT6dy/CQ7itO8XCu', '/ProjectGenesis/assets/uploads/avatars_default/user-3.png', 'user', '0', 'bbfb7dadf9721e452b2736ab0143778dc90d9133b840a8a2043086e9c5cdb981', 'active', '2025-11-03 04:48:52');
INSERT INTO `users` VALUES ('4', '1ena@gmail.com', 'user20251102_224901zndd', '$2y$10$lauWQmhfdiYQHFqjc.ndq.Q1gxjBaS03UVAjVHRuaP3HfszpLRFmW', '/ProjectGenesis/assets/uploads/avatars_default/user-4.png', 'administrator', '0', '96a6bbf67b5f7e2e1969f0688acd5fbba83461653aaef5e7b4dc3fdb71168483', 'suspended', '2025-11-03 04:49:19');
INSERT INTO `users` VALUES ('5', '1e1a@gmail.com', 'user20251103_140241ii6', '$2y$10$0ZFVYUWcqvuR5JIW/CfSuuS5FdLZreZO79aH1ZlFd/r80q2G5dzxa', '/ProjectGenesis/assets/uploads/avatars_default/user-5.png', 'user', '0', '7b2496e9eab32c34fed832804af736d1097da4fad0053df663a0c3514ad3ccde', 'active', '2025-11-03 20:02:59');
INSERT INTO `users` VALUES ('6', '111111111na@gmail.com', 'user20251103_1404200f', '$2y$10$ffDRgWyPFMiZOIpdGWTgfeJk5pvtzlRtEbRcWwj9Gv7.9tKXxpWW2', '/ProjectGenesis/assets/uploads/avatars_default/user-6.png', 'user', '0', '31f739579cdd1b13b4ff692c52b1d32f0cc68f2562bea0f9d4889ee27d0d1a5d', 'active', '2025-11-03 20:04:30');
INSERT INTO `users` VALUES ('7', 'WFWFWa@gmail.com', 'user20251103_141708yq', '$2y$10$EGGG8asF2D99G4U9COCDueT0C845/x5w/4Akj8Xv/5qQ7cOYaTgmO', '/ProjectGenesis/assets/uploads/avatars_default/user-7.png', 'user', '0', '286b58445a68c2522970f71fe7136a26fa15c06f417ba29d8590c15d1f602aea', 'active', '2025-11-03 20:17:33');
INSERT INTO `users` VALUES ('9', 'hhhga1311@gmail.com', 'user20251103_143107f6', '$2y$10$m.agvLdGmrtmd6Dv.IIWQugUaKaEnXTOPkvWArHnVaRy.QSK3AFkm', '/ProjectGenesis/assets/uploads/avatars_default/user-9.png', 'user', '0', '0e075f11edef8c56a51bef77777538c2b6fbf5676cafa062a6e46311e7804fa1', 'active', '2025-11-03 20:31:24');
INSERT INTO `users` VALUES ('10', 'ssssssena@gmail.com', 'user20251103_143457cb', '$2y$10$9E8DZQvTt0oyPkU6m8ybKOHa3SGU1QXMHaAAv9ajCTuofeMRye9qa', '/ProjectGenesis/assets/uploads/avatars_default/user-10.png', 'user', '0', 'fe9496e470f9554cec2c5d5747e27883363b052d9b0832211b8afd163eb6a6e6', 'active', '2025-11-03 20:35:26');
INSERT INTO `users` VALUES ('13', 'ddd1@gmail.com', 'user202511aaaaa03_143457cbc', '$2y$10$mDN9xhsb4LELtUNOBRkohO7AE3kaAaeYaVFEFh875YLFXpMYKuRsC', '/ProjectGenesis/assets/uploads/avatars_default/user-13.png', 'user', '0', '65655059feeaafa16fae0ec40f7902d505c9b88e575001893940480349eddb8d', 'active', '2025-11-03 20:39:57');
INSERT INTO `users` VALUES ('14', 'ddddddmena@gmail.com', 'user20251103_144407ly', '$2y$10$1vsWFXrC2U6ldPKmOKimuOxJNXZPCQIPbiCjEEYjbO6mFSMtgN9BC', '/ProjectGenesis/assets/uploads/avatars_default/user-14.png', 'user', '0', '57407d6fade2542b9fb0db4bfd89a86630368259433ba39c8f207254e1fc22e9', 'active', '2025-11-03 20:44:32');
INSERT INTO `users` VALUES ('53', 'd@gmail.com', 'user20251103_144844zu', '$2y$10$CEBIZDbhzrwMTV1GKrNyFu1suSAwqTkQ16pzqPdCBZW5T2vGHYfUW', '/ProjectGenesis/assets/uploads/avatars_default/user-53.png', 'user', '0', 'f914dd2acc847a88e0f095158c76d9ed5d2832879fc915e2a6dd87526cb44b99', 'active', '2025-11-03 20:48:58');
INSERT INTO `users` VALUES ('56', '1ega1311@gmail.com', 'user20251103_165404g8', '$2y$10$scl4LvlFyuVvcauVhqu6fuqC3QH4gUM.nKQ4Ph1PYUIzP4lTCSOhy', '/ProjectGenesis/assets/uploads/avatars_default/user-56.png', 'user', '0', 'ea9f7088765619b1494a8c5ea491ff6f6a59c392c9ed88fffcc07ba5379d7ee3', 'active', '2025-11-03 22:54:23');

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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records for verification_codes
-- ----------------------------
INSERT INTO `verification_codes` VALUES ('3', '1111311@gmail.com', 'registration', 'DUG74G8U6NZA', '{\"username\":\"user20251102_155555s6\",\"password_hash\":\"$2y$10$xVzKrvIGOhBYqCjyhjU3Ce05Jv3QFAOntkWdUlokZoVLxJgTTn\\/.u\"}', '2025-11-02 21:55:56');
INSERT INTO `verification_codes` VALUES ('7', '1', 'email_change', '93PKS7C9KRFT', NULL, '2025-11-03 19:09:53');
INSERT INTO `verification_codes` VALUES ('8', '1111na@gmail.com', 'registration', 'PMWCCAYZKMGC', '{\"username\":\"user20251103_135237sp\",\"password_hash\":\"$2y$10$s5KGjNkySQHbVD7m3.1AXuDfn2NDp80q06O.wXMGGDoYKr.qJHOQO\"}', '2025-11-03 19:52:37');
INSERT INTO `verification_codes` VALUES ('13', '7', 'email_change', '0869MNHYDNMW', NULL, '2025-11-03 20:20:36');
INSERT INTO `verification_codes` VALUES ('15', 'sssssssa@gmail.com', 'registration', '88OTEZ2RRVA7', '{\"username\":\"user20251103_143107f6\",\"password_hash\":\"$2y$10$NU7EsbVCRFRuIjXd\\/Dl30eZzjA1R3RP.vfgc6N1J.tXFVpR96JFKq\"}', '2025-11-03 20:31:16');
INSERT INTO `verification_codes` VALUES ('18', 'dna@gmail.com', 'registration', 'SRYKPWTDFIR9', '{\"username\":\"user20251103_144407ly\",\"password_hash\":\"$2y$10$0thT6p117mhPV3VN47lnYefo177e6\\/ScZCd76ZDptRJeeClSyj0fC\"}', '2025-11-03 20:44:09');
INSERT INTO `verification_codes` VALUES ('20', '11111111aguilar1mena@gmail.com', 'registration', 'VHEBRIJROF2T', '{\"username\":\"user20251103_144844zu\",\"password_hash\":\"$2y$10$8C2qGBnnMV.9d\\/TsuVWSfe6RRSByMS5XwzpZzEw5KIb6j01wSlqJK\"}', '2025-11-03 20:48:46');
INSERT INTO `verification_codes` VALUES ('22', '12345@gmail.com', 'password_reset', 'HKUSO5Y7NRYZ', NULL, '2025-11-03 22:46:31');

SET FOREIGN_KEY_CHECKS=1;
