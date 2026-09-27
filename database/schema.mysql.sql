-- Schema reference only. The web installer creates these tables automatically.
-- Contains no account data. Do not import before running the installer.
SET NAMES utf8mb4;

CREATE TABLE `d2_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(16) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `auth_version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `creator_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  CONSTRAINT `1` FOREIGN KEY (`creator_id`) REFERENCES `d2_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_memberships` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`group_id`) REFERENCES `d2_groups` (`id`),
  CONSTRAINT `2` FOREIGN KEY (`user_id`) REFERENCES `d2_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `title` varchar(140) NOT NULL,
  `description` text NOT NULL,
  `points` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `archived` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `d2_challenges_group` (`group_id`),
  CONSTRAINT `1` FOREIGN KEY (`group_id`) REFERENCES `d2_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `proof` varchar(80) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `feedback` text NOT NULL,
  `awarded_points` int(11) NOT NULL DEFAULT 0,
  `reviewed_by` int(11) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenge_id` (`challenge_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `d2_submissions_status` (`status`),
  CONSTRAINT `1` FOREIGN KEY (`challenge_id`) REFERENCES `d2_challenges` (`id`),
  CONSTRAINT `2` FOREIGN KEY (`user_id`) REFERENCES `d2_users` (`id`),
  CONSTRAINT `3` FOREIGN KEY (`reviewed_by`) REFERENCES `d2_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) DEFAULT NULL,
  `event` varchar(80) NOT NULL,
  `entity` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `actor_id` (`actor_id`),
  CONSTRAINT `1` FOREIGN KEY (`actor_id`) REFERENCES `d2_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_attempts` (
  `bucket` varchar(64) NOT NULL,
  `attempts` int(11) NOT NULL,
  `expires_at` int(11) NOT NULL,
  PRIMARY KEY (`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `d2_settings` (
  `name` varchar(80) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO d2_settings (name,value) VALUES ('schema_version','1');
