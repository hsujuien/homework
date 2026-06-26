-- MySQL dump converted from SQLite
-- Source: C:\xampp\htdocs\smartphone-recommender\data\database.sqlite
-- Generated: 2026-06-25T10:17:57

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `recommendation_logs`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `phones`;
DROP TABLE IF EXISTS `dimension_weights`;
DROP TABLE IF EXISTS `crawler_logs`;

CREATE TABLE `crawler_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(2048) NOT NULL,
  `status` TEXT NOT NULL,
  `message` LONGTEXT NOT NULL,
  `created_at` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dimension_weights` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `dimension` VARCHAR(255) NOT NULL,
  `metric_key` VARCHAR(255) NOT NULL,
  `label` TEXT NOT NULL,
  `weight` DOUBLE NOT NULL  DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_dimension_weights_1` (`dimension`, `metric_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phones` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `brand` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255) NOT NULL,
  `price` INT  DEFAULT 0,
  `image_url` VARCHAR(2048)  DEFAULT '',
  `release_date` VARCHAR(255)  DEFAULT '',
  `panel_type` VARCHAR(255)  DEFAULT '',
  `resolution` VARCHAR(255)  DEFAULT '',
  `ppi` INT  DEFAULT 0,
  `refresh_rate` INT  DEFAULT 0,
  `touch_sampling_rate` INT  DEFAULT 0,
  `brightness` INT  DEFAULT 0,
  `cpu` VARCHAR(255)  DEFAULT '',
  `antutu_score` INT  DEFAULT 0,
  `ram_gb` INT  DEFAULT 0,
  `rom_gb` INT  DEFAULT 0,
  `battery_mah` INT  DEFAULT 0,
  `wired_charging_w` INT  DEFAULT 0,
  `wireless_charging_w` INT  DEFAULT 0,
  `main_camera_mp` DOUBLE  DEFAULT 0,
  `ultrawide_camera_mp` DOUBLE  DEFAULT 0,
  `telephoto_camera_mp` DOUBLE  DEFAULT 0,
  `macro_camera_mp` DOUBLE  DEFAULT 0,
  `front_camera_mp` DOUBLE  DEFAULT 0,
  `video_spec` VARCHAR(255)  DEFAULT '',
  `fiveg_bands` VARCHAR(255)  DEFAULT '',
  `wifi` VARCHAR(255)  DEFAULT '',
  `bluetooth` VARCHAR(255)  DEFAULT '',
  `esim` INT  DEFAULT 0,
  `fingerprint` INT  DEFAULT 0,
  `face_unlock` INT  DEFAULT 0,
  `waterproof_rating` VARCHAR(255)  DEFAULT '',
  `cooling` INT  DEFAULT 0,
  `specs_json` LONGTEXT,
  `source_url` VARCHAR(2048)  DEFAULT '',
  `created_at` TEXT NOT NULL,
  `updated_at` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_phones_1` (`brand`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` TEXT NOT NULL,
  `role` VARCHAR(255) NOT NULL  DEFAULT 'user',
  `created_at` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sqlite_autoindex_users_1` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `user_id` INT NOT NULL,
  `phone_id` INT NOT NULL,
  `created_at` TEXT NOT NULL,
  PRIMARY KEY (`user_id`, `phone_id`),
  CONSTRAINT `fk_favorites_phone_id_phones_id` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorites_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recommendation_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `needs_json` LONGTEXT NOT NULL,
  `result_json` LONGTEXT NOT NULL,
  `created_at` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_recommendation_logs_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
