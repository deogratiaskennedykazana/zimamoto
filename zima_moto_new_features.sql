-- ============================================================
--  ZIMA-MOTO: New Features Migration
--  Run this in phpMyAdmin on database: sbisaccosco_zimamoto
-- ============================================================

-- 1. BUDGET MANAGEMENT TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `budget` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `ref_no`           VARCHAR(50) NOT NULL,
  `year`             VARCHAR(10) NOT NULL,
  `descreption`      TEXT,
  `total_amount`     DECIMAL(18,2) DEFAULT 0,
  `status`           ENUM('pending','approved','rejected') DEFAULT 'pending',
  `notes`            TEXT,
  `rejection_reason` TEXT,
  `created_by`       INT,
  `approved_by`      INT,
  `rejected_by`      INT,
  `updated_by`       INT,
  `deleted_by`       INT,
  `approved_at`      DATETIME,
  `rejected_at`      DATETIME,
  `updated_at`       DATETIME,
  `deleted_at`       DATETIME,
  `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `budget_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `budget_id`   INT NOT NULL,
  `sub_id`      INT NOT NULL,
  `description` TEXT,
  `amount`      DECIMAL(18,2) DEFAULT 0,
  `created_by`  INT,
  `updated_by`  INT,
  `deleted_by`  INT,
  `updated_at`  DATETIME,
  `deleted_at`  DATETIME,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. GRANTOR NOTIFICATIONS TABLE
-- ============================================================
ALTER TABLE `loan_grantors`
  ADD COLUMN IF NOT EXISTS `status`           ENUM('pending','accepted','rejected') DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `response_comment` TEXT,
  ADD COLUMN IF NOT EXISTS `responded_at`     DATETIME,
  ADD COLUMN IF NOT EXISTS `notified_at`      DATETIME;

CREATE TABLE IF NOT EXISTS `grantor_notifications` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id`      INT NOT NULL,
  `grantor_id`   INT NOT NULL,
  `applicant_id` INT NOT NULL,
  `token`        VARCHAR(64) NOT NULL UNIQUE,
  `status`       ENUM('pending','accepted','rejected') DEFAULT 'pending',
  `comment`      TEXT,
  `sent_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME,
  `expires_at`   DATETIME
) ENGINE=InnoDB;

-- 3. MEETING MINUTES TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `meeting_minutes` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(255) NOT NULL,
  `meeting_date`   DATE NOT NULL,
  `meeting_type`   VARCHAR(100) DEFAULT 'General',
  `venue`          VARCHAR(255),
  `chairperson`    VARCHAR(255),
  `content`        LONGTEXT,
  `status`         ENUM('draft','published') DEFAULT 'draft',
  `created_by`     INT,
  `updated_by`     INT,
  `deleted_by`     INT,
  `updated_at`     DATETIME,
  `deleted_at`     DATETIME,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `meeting_attendees` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `minutes_id`  INT NOT NULL,
  `user_id`     INT,
  `name`        VARCHAR(255),
  `role`        VARCHAR(100),
  `present`     TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. USER ROLES / PERMISSIONS TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_by`  INT,
  `deleted_at`  DATETIME,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `role_id`     INT NOT NULL,
  `module`      VARCHAR(100) NOT NULL,
  `can_view`    TINYINT(1) DEFAULT 0,
  `can_create`  TINYINT(1) DEFAULT 0,
  `can_edit`    TINYINT(1) DEFAULT 0,
  `can_delete`  TINYINT(1) DEFAULT 0,
  `can_approve` TINYINT(1) DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `user_role_assignments` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL,
  `role_id`     INT NOT NULL,
  `assigned_by` INT,
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `revoked_at`  DATETIME
) ENGINE=InnoDB;

-- 5. SYSTEM NOTIFICATIONS TABLE (for in-app alerts)
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_notifications` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `type`         VARCHAR(50) DEFAULT 'info',
  `title`        VARCHAR(255),
  `message`      TEXT,
  `link`         VARCHAR(500),
  `is_read`      TINYINT(1) DEFAULT 0,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default Roles Seed
INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
  ('admin',       'Full system access'),
  ('accountant',  'Accounting and financial reports'),
  ('loan_officer','Manage loan applications and processing'),
  ('member',      'Basic member access only'),
  ('chairman',    'Approve/reject loans and budgets'),
  ('loan_committee','Review and process loans');
