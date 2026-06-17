
-- ============================================================
-- 6. AUDIT TRAIL TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_trail` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT,
  `action`      VARCHAR(50) NOT NULL,
  `module`      VARCHAR(100) NOT NULL,
  `record_id`   INT DEFAULT NULL,
  `detail`      TEXT,
  `old_values`  LONGTEXT,
  `new_values`  LONGTEXT,
  `ip_address`  VARCHAR(45),
  `user_agent`  TEXT,
  `session_id`  VARCHAR(128),
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id`  (`user_id`),
  INDEX `idx_module`   (`module`),
  INDEX `idx_action`   (`action`),
  INDEX `idx_created`  (`created_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 7. NOTIFICATION SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT NOT NULL UNIQUE,
  `notify_email`  TINYINT(1) DEFAULT 0,
  `notify_sms`    TINYINT(1) DEFAULT 0,
  `notify_in_app` TINYINT(1) DEFAULT 1,
  `email_address` VARCHAR(255),
  `phone_number`  VARCHAR(30),
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 8. NOTIFICATION QUEUE TABLE (for async email/SMS processing)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notification_queue` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT,
  `type`          ENUM('email','sms') NOT NULL,
  `subject`       VARCHAR(255),
  `message`       TEXT,
  `recipient`     VARCHAR(255) NOT NULL,
  `status`        ENUM('pending','sent','failed') DEFAULT 'pending',
  `error_message` TEXT,
  `sent_at`       DATETIME,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- ============================================================
-- 9. TOTP / MFA COLUMNS ON users TABLE
--    (Run only if not already added)
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `totp_secret`         VARCHAR(64)   DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `totp_enabled`        TINYINT(1)    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `totp_recovery_codes` LONGTEXT      DEFAULT NULL;
