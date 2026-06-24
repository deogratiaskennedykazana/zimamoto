-- ============================================================
--  ZIMA-MOTO: Loan Management Upgrade
--  Adds: loan product rules, admin review trail, indexes, seed data.
--  Safe to re-run (IF NOT EXISTS / IF EXISTS guards throughout).
--  Run this in phpMyAdmin on database: sbisaccosco_zimamoto
-- ============================================================

-- 1. Make sure loan_types exists (in case a fresh DB doesn't have it yet)
-- ============================================================
CREATE TABLE IF NOT EXISTS `loan_types` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

-- 2. Loan product rule columns — this is what lets every loan
--    product ("Emergency Loan", "Development Loan", etc.) carry
--    its own eligibility rules, instead of one rule for everything.
-- ============================================================
ALTER TABLE `loan_types`
  ADD COLUMN IF NOT EXISTS `description`             TEXT             NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `min_amount`                DECIMAL(18,2)    NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `max_amount`                DECIMAL(18,2)    NOT NULL DEFAULT 0 COMMENT '0 = no hard cap, savings multiplier still applies',
  ADD COLUMN IF NOT EXISTS `interest_rate`             DECIMAL(5,2)     NOT NULL DEFAULT 12.00 COMMENT 'annual percentage, used as default and shown to members',
  ADD COLUMN IF NOT EXISTS `min_period`                INT              NOT NULL DEFAULT 1 COMMENT 'months',
  ADD COLUMN IF NOT EXISTS `max_period`                INT              NOT NULL DEFAULT 12 COMMENT 'months',
  ADD COLUMN IF NOT EXISTS `savings_multiplier`        DECIMAL(5,2)     NOT NULL DEFAULT 3.00 COMMENT 'max eligible loan = multiplier x total savings',
  ADD COLUMN IF NOT EXISTS `required_grantors`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
  ADD COLUMN IF NOT EXISTS `processing_fee_percent`     DECIMAL(5,2)     NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `allowed_repayment_modes`    VARCHAR(100)     NOT NULL DEFAULT 'salary,standing_order',
  ADD COLUMN IF NOT EXISTS `eligibility_notes`          TEXT             NULL COMMENT 'plain-language conditions shown to the member applying',
  ADD COLUMN IF NOT EXISTS `status`                     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS `created_by`                 INT              NULL,
  ADD COLUMN IF NOT EXISTS `updated_by`                 INT              NULL,
  ADD COLUMN IF NOT EXISTS `deleted_at`                 DATETIME         NULL,
  ADD COLUMN IF NOT EXISTS `created_at`                 DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at`                 DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP;

-- 3. Loan application review trail — who reviewed, when, and why
--    a loan was rejected. Needed for the admin approval workflow.
-- ============================================================
ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `reviewed_by`         INT          NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `reviewed_at`         DATETIME     NULL AFTER `reviewed_by`,
  ADD COLUMN IF NOT EXISTS `rejection_reason`    TEXT         NULL AFTER `reviewed_at`,
  ADD COLUMN IF NOT EXISTS `eligibility_snapshot` LONGTEXT    NULL COMMENT 'JSON snapshot of the eligibility check at decision time',
  ADD COLUMN IF NOT EXISTS `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at`          DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `deleted_at`          DATETIME     NULL;

-- 4. Helpful indexes for the new filterable Loan Applications list
-- ============================================================
ALTER TABLE `loans`
  ADD INDEX IF NOT EXISTS `idx_loans_status`        (`status`),
  ADD INDEX IF NOT EXISTS `idx_loans_branch_status`  (`branch_id`, `status`),
  ADD INDEX IF NOT EXISTS `idx_loans_type`           (`loan_type`),
  ADD INDEX IF NOT EXISTS `idx_loans_user`           (`user_id`);

-- 5. Seed default loan products with real rules (only inserted if a
--    product with that name doesn't already exist — safe to re-run)
-- ============================================================
INSERT INTO `loan_types` (`name`, `description`, `min_amount`, `max_amount`, `interest_rate`, `min_period`, `max_period`, `savings_multiplier`, `required_grantors`, `processing_fee_percent`, `allowed_repayment_modes`, `eligibility_notes`, `status`)
SELECT * FROM (SELECT
  'Emergency Loan' AS name,
  'Fast, short-term loan for urgent personal needs.' AS description,
  50000.00 AS min_amount, 1000000.00 AS max_amount, 10.00 AS interest_rate,
  1 AS min_period, 6 AS max_period, 2.00 AS savings_multiplier, 1 AS required_grantors,
  1.00 AS processing_fee_percent, 'salary,standing_order' AS allowed_repayment_modes,
  'Member must have at least 3 months of active savings. One guarantor required. Maximum loan is the lower of TZS 1,000,000 or 2x total savings.' AS eligibility_notes,
  'active' AS status
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `loan_types` WHERE `name` = 'Emergency Loan');

INSERT INTO `loan_types` (`name`, `description`, `min_amount`, `max_amount`, `interest_rate`, `min_period`, `max_period`, `savings_multiplier`, `required_grantors`, `processing_fee_percent`, `allowed_repayment_modes`, `eligibility_notes`, `status`)
SELECT * FROM (SELECT
  'Development Loan' AS name,
  'General-purpose loan for development projects and investments.' AS description,
  100000.00 AS min_amount, 0.00 AS max_amount, 12.00 AS interest_rate,
  1 AS min_period, 24 AS max_period, 3.00 AS savings_multiplier, 2 AS required_grantors,
  1.00 AS processing_fee_percent, 'salary,standing_order' AS allowed_repayment_modes,
  'Member must have at least 6 months of active savings and no defaulted loans. Two guarantors required. Maximum loan is 3x total savings.' AS eligibility_notes,
  'active' AS status
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `loan_types` WHERE `name` = 'Development Loan');

INSERT INTO `loan_types` (`name`, `description`, `min_amount`, `max_amount`, `interest_rate`, `min_period`, `max_period`, `savings_multiplier`, `required_grantors`, `processing_fee_percent`, `allowed_repayment_modes`, `eligibility_notes`, `status`)
SELECT * FROM (SELECT
  'Education Loan' AS name,
  'Loan to cover school fees and education-related costs.' AS description,
  50000.00 AS min_amount, 3000000.00 AS max_amount, 8.00 AS interest_rate,
  1 AS min_period, 36 AS max_period, 3.00 AS savings_multiplier, 2 AS required_grantors,
  0.50 AS processing_fee_percent, 'salary,standing_order' AS allowed_repayment_modes,
  'Two guarantors required. Proof of admission/fee structure may be requested. Maximum loan is the lower of TZS 3,000,000 or 3x total savings.' AS eligibility_notes,
  'active' AS status
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `loan_types` WHERE `name` = 'Education Loan');

INSERT INTO `loan_types` (`name`, `description`, `min_amount`, `max_amount`, `interest_rate`, `min_period`, `max_period`, `savings_multiplier`, `required_grantors`, `processing_fee_percent`, `allowed_repayment_modes`, `eligibility_notes`, `status`)
SELECT * FROM (SELECT
  'Business Loan' AS name,
  'Working capital and expansion loan for member businesses.' AS description,
  200000.00 AS min_amount, 0.00 AS max_amount, 14.00 AS interest_rate,
  1 AS min_period, 36 AS max_period, 4.00 AS savings_multiplier, 2 AS required_grantors,
  2.00 AS processing_fee_percent, 'salary,standing_order' AS allowed_repayment_modes,
  'Member must have at least 12 months of active membership and no defaulted loans. Two guarantors required. Maximum loan is 4x total savings.' AS eligibility_notes,
  'active' AS status
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `loan_types` WHERE `name` = 'Business Loan');

-- 6. Make sure the 'Loans' RBAC module exists for restricted roles
--    (the sidebar will gate the new admin pages on this module)
-- ============================================================
-- (No INSERT needed — role_permissions rows are created on demand from
--  the Manage Roles screen; 'Loans' is now included as a valid module name
--  used by the application code.)
