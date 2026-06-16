-- ============================================================
-- zima-moto: Missing Tables Fix (Updated 2026-06-10)
-- Run this on the live database: sbisaccosco_zimamoto
-- ============================================================

-- 1. system_notifications
CREATE TABLE IF NOT EXISTS `system_notifications` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `title`      VARCHAR(255) NOT NULL,
    `message`    TEXT NOT NULL,
    `link`       VARCHAR(500) NULL,
    `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. currencies
CREATE TABLE IF NOT EXISTS `currencies` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `code`          VARCHAR(10)  NOT NULL,
    `symbol`        VARCHAR(10)  NULL,
    `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
    `is_default`    TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `currencies` (`id`, `name`, `code`, `symbol`, `exchange_rate`, `is_default`) VALUES
(1, 'Tanzanian Shilling', 'TZS', 'TSh', 1.000000,    1),
(2, 'US Dollar',          'USD', '$',   2500.000000,  0),
(3, 'Euro',               'EUR', '€',   2700.000000,  0),
(4, 'British Pound',      'GBP', '£',   3200.000000,  0);

-- 3. audit_trail (Feature #13 — Audit Trail)
CREATE TABLE IF NOT EXISTS `audit_trail` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `action`     VARCHAR(50)  NOT NULL,
    `module`     VARCHAR(100) NOT NULL,
    `record_id`  INT UNSIGNED NULL,
    `detail`     TEXT NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(45)  NULL,
    `user_agent` VARCHAR(500) NULL,
    `session_id` VARCHAR(128) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user`    (`user_id`),
    INDEX `idx_module`  (`module`),
    INDEX `idx_action`  (`action`),
    INDEX `idx_record`  (`module`, `record_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. notification_settings (Feature #12 — SMS/Email preferences)
CREATE TABLE IF NOT EXISTS `notification_settings` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL UNIQUE,
    `notify_email`  TINYINT(1) NOT NULL DEFAULT 0,
    `notify_sms`    TINYINT(1) NOT NULL DEFAULT 0,
    `notify_in_app` TINYINT(1) NOT NULL DEFAULT 1,
    `email_address` VARCHAR(255) NULL,
    `phone_number`  VARCHAR(20)  NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. notification_queue (SMS/Email send queue)
CREATE TABLE IF NOT EXISTS `notification_queue` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NULL,
    `type`          ENUM('email','sms') NOT NULL,
    `subject`       VARCHAR(255) NULL,
    `message`       TEXT NOT NULL,
    `recipient`     VARCHAR(255) NOT NULL,
    `status`        ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `sent_at`       DATETIME NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. MFA columns on users table (Feature #14)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `totp_secret`         VARCHAR(64)  NULL AFTER `password`,
    ADD COLUMN IF NOT EXISTS `totp_enabled`        TINYINT(1)   NOT NULL DEFAULT 0 AFTER `totp_secret`,
    ADD COLUMN IF NOT EXISTS `totp_recovery_codes` LONGTEXT     NULL AFTER `totp_enabled`;

-- 7. login_logs (already may exist — safe with IF NOT EXISTS)
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT UNSIGNED NULL,
    `email`          VARCHAR(255) NULL,
    `session_id`     VARCHAR(128) NULL,
    `ip_address`     VARCHAR(45)  NULL,
    `user_agent`     VARCHAR(500) NULL,
    `status`         VARCHAR(20)  NOT NULL DEFAULT 'success',
    `failure_reason` VARCHAR(100) NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user`    (`user_id`),
    INDEX `idx_status`  (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
