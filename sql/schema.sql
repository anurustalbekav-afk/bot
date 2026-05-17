-- fear.dev — схема БД для phpMyAdmin
-- Импортируй этот файл во вкладке "Импорт" phpMyAdmin
-- или выполни:  mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS `fear_dev`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `fear_dev`;

-- Пользователи + роли
CREATE TABLE IF NOT EXISTS `users` (
  `id`            CHAR(36)      NOT NULL,
  `email`         VARCHAR(254)  NOT NULL,
  `login`         VARCHAR(24)   NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,
  `role`          VARCHAR(16)   NOT NULL DEFAULT 'user',
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_login` (`login`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Категории каталога
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(64)   NOT NULL,
  `title`      VARCHAR(120)  NOT NULL,
  `position`   INT           NOT NULL DEFAULT 0,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Стартовые категории. ON DUPLICATE — чтобы повторный импорт не падал.
INSERT INTO `categories` (`slug`, `title`, `position`) VALUES
  ('mods',    'Моды',    10),
  ('scripts', 'Скрипты', 20),
  ('maps',    'Карты',   30)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `position` = VALUES(`position`);

-- Товары
CREATE TABLE IF NOT EXISTS `products` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(120)  NOT NULL,
  `category_id`  INT UNSIGNED  NULL,
  `title`        VARCHAR(180)  NOT NULL,
  `summary`      VARCHAR(255)  NOT NULL DEFAULT '',
  `description`  TEXT          NULL,
  `price_cents`  INT UNSIGNED  NOT NULL DEFAULT 0,
  `currency`     CHAR(3)       NOT NULL DEFAULT 'USD',
  `image_url`    VARCHAR(512)  NOT NULL DEFAULT '',
  `status`       VARCHAR(16)   NOT NULL DEFAULT 'draft',
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Выдать админку конкретному пользователю (через phpMyAdmin → SQL):
-- UPDATE users SET role = 'admin' WHERE login = 'твой_логин';
