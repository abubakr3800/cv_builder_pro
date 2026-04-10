-- ============================================================
--  CV Builder Pro — Database Schema
--  Author  : Ahmed Mohamed Abubakr
--  Site    : https://abubakr.rf.gd/
--  Phone   : 01113284597
--  Version : 1.0.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `cv_builder_pro`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `cv_builder_pro`;

-- ------------------------------------------------------------
--  USERS
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150)    NOT NULL,
  `email`         VARCHAR(255)    NOT NULL UNIQUE,
  `password`      VARCHAR(255)    NOT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `avatar`        VARCHAR(255)    DEFAULT NULL,
  `remember_token` VARCHAR(100)   DEFAULT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (password: Admin@1234 — CHANGE IMMEDIATELY)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Ahmed Mohamed Abubakr', 'admin@abubakr.rf.gd',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ------------------------------------------------------------
--  CVs  (one user → many CVs)
-- ------------------------------------------------------------
CREATE TABLE `cvs` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED    NOT NULL,
  `title`         VARCHAR(150)    NOT NULL DEFAULT 'My CV',
  `lang`          ENUM('en','ar','both') NOT NULL DEFAULT 'en',
  `template`      ENUM('classic','modern','minimal') NOT NULL DEFAULT 'modern',
  `completeness`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  PERSONAL INFO  (one CV → one record)
-- ------------------------------------------------------------
CREATE TABLE `personal_info` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cv_id`           INT UNSIGNED  NOT NULL UNIQUE,
  `full_name`       VARCHAR(150)  DEFAULT NULL,
  `full_name_ar`    VARCHAR(150)  DEFAULT NULL,
  `job_title`       VARCHAR(150)  DEFAULT NULL,
  `job_title_ar`    VARCHAR(150)  DEFAULT NULL,
  `email`           VARCHAR(255)  DEFAULT NULL,
  `phone`           VARCHAR(50)   DEFAULT NULL,
  `address`         VARCHAR(255)  DEFAULT NULL,
  `address_ar`      VARCHAR(255)  DEFAULT NULL,
  `website`         VARCHAR(255)  DEFAULT NULL,
  `linkedin`        VARCHAR(255)  DEFAULT NULL,
  `github`          VARCHAR(255)  DEFAULT NULL,
  `summary`         TEXT          DEFAULT NULL,
  `summary_ar`      TEXT          DEFAULT NULL,
  `photo`           VARCHAR(255)  DEFAULT NULL,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  EXPERIENCE
-- ------------------------------------------------------------
CREATE TABLE `experience` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `company`       VARCHAR(150)    NOT NULL,
  `company_ar`    VARCHAR(150)    DEFAULT NULL,
  `job_title`     VARCHAR(150)    NOT NULL,
  `job_title_ar`  VARCHAR(150)    DEFAULT NULL,
  `start_date`    DATE            NOT NULL,
  `end_date`      DATE            DEFAULT NULL,
  `is_current`    TINYINT(1)      NOT NULL DEFAULT 0,
  `description`   TEXT            DEFAULT NULL,
  `description_ar` TEXT           DEFAULT NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
  INDEX `idx_cv_exp` (`cv_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  EDUCATION
-- ------------------------------------------------------------
CREATE TABLE `education` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `degree`        VARCHAR(150)    NOT NULL,
  `degree_ar`     VARCHAR(150)    DEFAULT NULL,
  `institution`   VARCHAR(150)    NOT NULL,
  `institution_ar` VARCHAR(150)   DEFAULT NULL,
  `start_date`    DATE            NOT NULL,
  `end_date`      DATE            DEFAULT NULL,
  `is_current`    TINYINT(1)      NOT NULL DEFAULT 0,
  `grade`         VARCHAR(50)     DEFAULT NULL,
  `description`   TEXT            DEFAULT NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
  INDEX `idx_cv_edu` (`cv_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  SKILLS
-- ------------------------------------------------------------
CREATE TABLE `skills` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `name`          VARCHAR(100)    NOT NULL,
  `name_ar`       VARCHAR(100)    DEFAULT NULL,
  `level`         TINYINT UNSIGNED NOT NULL DEFAULT 3
                  COMMENT '1=Beginner 2=Elementary 3=Intermediate 4=Advanced 5=Expert',
  `category`      VARCHAR(80)     DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
  INDEX `idx_cv_skill` (`cv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  LANGUAGES
-- ------------------------------------------------------------
CREATE TABLE `languages` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `name`          VARCHAR(80)     NOT NULL,
  `name_ar`       VARCHAR(80)     DEFAULT NULL,
  `proficiency`   ENUM('native','fluent','advanced','intermediate','basic')
                  NOT NULL DEFAULT 'intermediate',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
  INDEX `idx_cv_lang` (`cv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  CERTIFICATES
-- ------------------------------------------------------------
CREATE TABLE `certificates` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `name`          VARCHAR(200)    NOT NULL,
  `name_ar`       VARCHAR(200)    DEFAULT NULL,
  `issuer`        VARCHAR(150)    DEFAULT NULL,
  `issue_date`    DATE            DEFAULT NULL,
  `expiry_date`   DATE            DEFAULT NULL,
  `credential_url` VARCHAR(255)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`) REFERENCES `cvs`(`id`) ON DELETE CASCADE,
  INDEX `idx_cv_cert` (`cv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  EXPORT LOGS
-- ------------------------------------------------------------
CREATE TABLE `export_logs` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cv_id`         INT UNSIGNED    NOT NULL,
  `user_id`       INT UNSIGNED    NOT NULL,
  `format`        ENUM('pdf','docx') NOT NULL,
  `template`      VARCHAR(50)     NOT NULL,
  `file_path`     VARCHAR(255)    DEFAULT NULL,
  `exported_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cv_id`)   REFERENCES `cvs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_export_user` (`user_id`),
  INDEX `idx_export_date` (`exported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  PASSWORD RESET TOKENS
-- ------------------------------------------------------------
CREATE TABLE `password_resets` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`         VARCHAR(255)    NOT NULL,
  `token`         VARCHAR(100)    NOT NULL,
  `expires_at`    TIMESTAMP       NOT NULL,
  `used`          TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_pr_email` (`email`),
  INDEX `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
