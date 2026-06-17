-- ============================================================
--  SMS MFA Migration
--  Run once in phpMyAdmin on: sbisaccosco_zimamoto
-- ============================================================

-- 1. Add SMS MFA flag to users table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `sms_mfa_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `totp_recovery_codes`;

-- 2. OTP storage table (short-lived codes sent by SMS)
CREATE TABLE IF NOT EXISTS `sms_otp` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `otp_hash`   VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sms_otp_user` (`user_id`),
  INDEX `idx_sms_otp_expires` (`expires_at`)
) ENGINE=InnoDB;
