-- ============================================================
--  CHATBOT ACCESS CONTROL — add allowed_roles column
--  Run in phpMyAdmin SQL tab.
-- ============================================================

-- Add role-based access control to chatbot_settings
ALTER TABLE `chatbot_settings`
    ADD COLUMN `allowed_roles` VARCHAR(500) NOT NULL DEFAULT 'admin,superadmin,super admin'
    AFTER `enabled`;

-- Patch the existing row (if any) so admins can still use it after migration
UPDATE `chatbot_settings` SET `allowed_roles` = 'admin,superadmin,super admin' WHERE id > 0;
