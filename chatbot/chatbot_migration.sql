-- ============================================================
--  CHATBOT SUBSYSTEM MIGRATION
--  Run once in phpMyAdmin on sbisaccosco_zimamoto
--  Safe to re-run (IF NOT EXISTS guards used).
-- ============================================================

-- 1. System-wide chatbot settings (one row, admin-managed)
CREATE TABLE IF NOT EXISTS `chatbot_settings` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `enabled`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=disabled, 1=enabled',
  `api_key`    VARCHAR(255) NULL             COMMENT 'Google Gemini API key',
  `model`      VARCHAR(100) NOT NULL DEFAULT 'gemini-1.5-flash',
  `updated_by` INT NULL,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed single settings row
INSERT INTO `chatbot_settings` (`enabled`, `model`)
SELECT 0, 'gemini-1.5-flash'
WHERE NOT EXISTS (SELECT 1 FROM `chatbot_settings`);

-- 2. Per-user conversation history (per browser session)
CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL,
  `session_key` VARCHAR(64) NOT NULL          COMMENT 'Random token per login session',
  `messages`    LONGTEXT NOT NULL DEFAULT '[]' COMMENT 'JSON [{role,content}]',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_chat_user`    (`user_id`),
  INDEX `idx_chat_session` (`session_key`)
) ENGINE=InnoDB;

-- 3. Chatbot audit log for security review
CREATE TABLE IF NOT EXISTS `chatbot_audit` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `role_at_time` VARCHAR(100) NULL,
  `user_message` TEXT NOT NULL,
  `bot_action`   VARCHAR(50) NULL  COMMENT 'answer|navigate|error',
  `navigate_to`  VARCHAR(200) NULL COMMENT 'page navigated to if applicable',
  `ip_address`   VARCHAR(45) NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_chatbot_audit_user` (`user_id`)
) ENGINE=InnoDB;
