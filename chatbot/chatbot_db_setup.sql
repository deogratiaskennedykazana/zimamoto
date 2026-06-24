-- ============================================================
--  CHATBOT SUBSYSTEM — Database Setup
--  Run this once in phpMyAdmin or MySQL CLI.
--  Database: sbisaccosco_zimamoto  (local) / tellicerpsysco_sbisaccosco_zimamoto (live)
-- ============================================================

-- ── 1. Chatbot settings (one row) ────────────────────────────
CREATE TABLE IF NOT EXISTS `chatbot_settings` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `enabled`    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=on, 0=off',
    `api_key`    VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Google Gemini API key (server-side only)',
    `model`      VARCHAR(60)  NOT NULL DEFAULT 'gemini-1.5-flash',
    `updated_by` INT(11)      DEFAULT NULL COMMENT 'users.id of last admin who saved',
    `updated_at` DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Chatbot audit log ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `chatbot_audit` (
    `id`            BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `user_id`       INT(11)      NOT NULL,
    `role_at_time`  VARCHAR(60)  NOT NULL DEFAULT '',
    `user_message`  TEXT         NOT NULL,
    `bot_action`    VARCHAR(50)  NOT NULL DEFAULT 'answer' COMMENT 'answer|navigate|error',
    `navigate_to`   VARCHAR(100) DEFAULT NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`   (`user_id`),
    KEY `idx_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
