-- ============================================================
--  Chatbot v2 — DB Migration
--  Run once in phpMyAdmin or via MySQL CLI
--  Database: sbisaccosco_zimamoto
-- ============================================================

-- Update chatbot_audit.bot_action to allow new action types
ALTER TABLE chatbot_audit 
    MODIFY COLUMN bot_action VARCHAR(50) NOT NULL DEFAULT 'answer';

-- Add columns to chatbot_audit if not already present (safe with IF NOT EXISTS workaround)
-- MySQL 5.7 doesn't support IF NOT EXISTS for columns; use this pattern:
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chatbot_audit'
    AND COLUMN_NAME = 'tool_name'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE chatbot_audit ADD COLUMN tool_name VARCHAR(80) NULL AFTER navigate_to',
    'SELECT "tool_name column already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verify chatbot_settings table has grok columns (may already exist from prior update)
-- These are safe to run again as ALTER IGNORE or via the same IF NOT EXISTS pattern:
SET @col2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='chatbot_settings' AND COLUMN_NAME='grok_api_key');
SET @sql2 = IF(@col2=0,
    'ALTER TABLE chatbot_settings ADD COLUMN grok_api_key TEXT NULL, ADD COLUMN grok_model VARCHAR(50) DEFAULT \'grok-4.3\'',
    'SELECT "grok columns already exist"');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Done. No other schema changes needed — all new features use existing tables.
-- The chatbot now queries: loans, members, branches, loan_types, min_subs,
-- and writes via: rejectLoan(), approveLoan(), updateMember(), insertLoanType(), updateLoanType()
-- All writes are logged to audit_trail (logAudit) AND chatbot_audit (chatbotLogAudit).
