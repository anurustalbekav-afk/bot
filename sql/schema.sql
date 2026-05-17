-- fear.dev — схема БД для phpMyAdmin
-- Импортируй этот файл во вкладке "Импорт" phpMyAdmin
-- или выполни:  mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS `fear_dev`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `fear_dev`;

CREATE TABLE IF NOT EXISTS `users` (
  `id`            CHAR(36)      NOT NULL,
  `email`         VARCHAR(254)  NOT NULL,
  `login`         VARCHAR(24)   NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
