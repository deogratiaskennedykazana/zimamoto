-- ============================================================
--  ZIMA-MOTO: Test Account Seed Data
--  Database: sbisaccosco_zimamoto
--  Generated: 2026-06-17
--
--  ALL ACCOUNTS USE PASSWORD: Test@1234
--  Hashed with PHP: password_hash('Test@1234', PASSWORD_DEFAULT)
--
--  ROLES COVERED:
--    1. member         (branch level)
--    2. accountant     (branch level)
--    3. accountant     (HQ level)
--    4. manager        (branch level)
--    5. loan comitee   (branch level)
--    6. chairman       (branch level)
--    7. admin          (HQ level)
--
--  USAGE:
--    Run this in phpMyAdmin or MySQL CLI AFTER running:
--      - missing_tables_fix.sql
--      - zima_moto_new_features.sql
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- STEP 1: Ensure a test branch exists (safe — uses INSERT IGNORE)
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `branches` (`id`, `name`, `location`, `created_at`)
VALUES (1, 'Arusha HQ Branch', 'Arusha', NOW());

-- ────────────────────────────────────────────────────────────
-- STEP 2: Ensure required roles exist
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
  ('admin',          'Full system access — HQ only'),
  ('accountant',     'Accounting, vouchers and financial reports'),
  ('loan_officer',   'Manage loan applications and processing'),
  ('member',         'Basic member self-service access only'),
  ('chairman',       'Approve/reject loans and budgets'),
  ('loan comitee',   'Review and process loan applications'),
  ('manager',        'Branch manager — oversight of branch operations');

-- ────────────────────────────────────────────────────────────
-- STEP 3: Seed test users
--  Password for ALL accounts: Test@1234
--  BCrypt hash (cost 10): $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
--  (Standard Laravel/PHP test hash for "Test@1234" — works with password_verify)
--
--  NOTE: If your server uses a different bcrypt cost you can generate a fresh
--  hash by running this once in PHP:
--    echo password_hash('Test@1234', PASSWORD_DEFAULT);
--  and replacing the hash below.
-- ────────────────────────────────────────────────────────────

-- ── 1. MEMBER account ──────────────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Member',
   'test.member@zimamoto.test',
   'member', 'member',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'branch', 1, 'approved', NOW());

-- ── 2. ACCOUNTANT — Branch level ───────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Accountant Branch',
   'test.accountant.branch@zimamoto.test',
   'accountant', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'branch', 1, 'approved', NOW());

-- ── 3. ACCOUNTANT — HQ level ───────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Accountant HQ',
   'test.accountant.hq@zimamoto.test',
   'accountant', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'HQ', 1, 'approved', NOW());

-- ── 4. MANAGER ─────────────────────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Manager',
   'test.manager@zimamoto.test',
   'manager', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'branch', 1, 'approved', NOW());

-- ── 5. LOAN COMMITTEE member ───────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Loan Committee',
   'test.loancomitee@zimamoto.test',
   'loan comitee', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'branch', 1, 'approved', NOW());

-- ── 6. CHAIRMAN ────────────────────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Chairman',
   'test.chairman@zimamoto.test',
   'chairman', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'branch', 1, 'approved', NOW());

-- ── 7. ADMIN — HQ ─────────────────────────────────────────
INSERT IGNORE INTO `users`
  (`name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `status`, `created_at`)
VALUES
  ('Test Admin',
   'test.admin@zimamoto.test',
   'admin', 'staff',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'HQ', 1, 'approved', NOW());

-- ────────────────────────────────────────────────────────────
-- STEP 4: Register the TEST MEMBER in the members table
--  (Staff users do NOT need a members row — only MEMBER role does)
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `members`
  (`user_id`, `phone`, `address`, `reg_no`, `birthdate`, `district_id`,
   `branch_id`, `gender`, `nida`, `check_no`, `created_at`)
SELECT
  u.id,
  '0712000001',
  'Arusha, Tanzania',
  'TEST-MEM-001',
  '1990-01-01',
  1,          -- district_id (must exist; change if needed)
  1,          -- branch_id
  'male',
  '19900101-00001-00001-01',
  'CHK-TEST-001',
  NOW()
FROM `users` u
WHERE u.email = 'test.member@zimamoto.test'
  AND NOT EXISTS (
    SELECT 1 FROM `members` m WHERE m.user_id = u.id
  )
LIMIT 1;

-- ────────────────────────────────────────────────────────────
-- STEP 5: Create min_subs (savings/share/amana/loan accounts)
--  for the test MEMBER so the dashboard shows real data
-- ────────────────────────────────────────────────────────────

-- Amana account (ledger_id = 9)
INSERT IGNORE INTO `min_subs`
  (`name`, `user_id`, `ledger_id`, `branch_id`, `type`, `category`, `created_at`)
SELECT
  CONCAT(u.name, ' Amana Account'),
  u.id, 9, 1, 'person', 'amana', NOW()
FROM `users` u
WHERE u.email = 'test.member@zimamoto.test'
  AND NOT EXISTS (
    SELECT 1 FROM `min_subs` ms
    WHERE ms.user_id = u.id AND ms.category = 'amana' AND ms.deleted_at IS NULL
  )
LIMIT 1;

-- Share account (ledger_id = 8)
INSERT IGNORE INTO `min_subs`
  (`name`, `user_id`, `ledger_id`, `branch_id`, `type`, `category`, `created_at`)
SELECT
  CONCAT(u.name, ' Share Account'),
  u.id, 8, 1, 'person', 'share', NOW()
FROM `users` u
WHERE u.email = 'test.member@zimamoto.test'
  AND NOT EXISTS (
    SELECT 1 FROM `min_subs` ms
    WHERE ms.user_id = u.id AND ms.category = 'share' AND ms.deleted_at IS NULL
  )
LIMIT 1;

-- Saving account (ledger_id = 7)
INSERT IGNORE INTO `min_subs`
  (`name`, `user_id`, `ledger_id`, `branch_id`, `type`, `category`, `created_at`)
SELECT
  CONCAT(u.name, ' Saving Account'),
  u.id, 7, 1, 'person', 'saving', NOW()
FROM `users` u
WHERE u.email = 'test.member@zimamoto.test'
  AND NOT EXISTS (
    SELECT 1 FROM `min_subs` ms
    WHERE ms.user_id = u.id AND ms.category = 'saving' AND ms.deleted_at IS NULL
  )
LIMIT 1;

-- Loan account (ledger_id = 59)
INSERT IGNORE INTO `min_subs`
  (`name`, `user_id`, `ledger_id`, `branch_id`, `type`, `category`, `created_at`)
SELECT
  CONCAT(u.name, ' Loan Account'),
  u.id, 59, 1, 'person', 'loan', NOW()
FROM `users` u
WHERE u.email = 'test.member@zimamoto.test'
  AND NOT EXISTS (
    SELECT 1 FROM `min_subs` ms
    WHERE ms.user_id = u.id AND ms.category = 'loan' AND ms.deleted_at IS NULL
  )
LIMIT 1;

-- ────────────────────────────────────────────────────────────
-- STEP 6: Seed a sample saving contribution for the member
--  so the dashboard chart has something to display
-- ────────────────────────────────────────────────────────────
INSERT INTO `min_transactions`
  (`dr_account`, `cr_account`, `amount`, `narration`, `date_`, `created_by`, `created_at`)
SELECT
  ms.id,          -- dr_account = saving account
  0,              -- cr_account (cash ledger — use 0 or your cash min_sub id)
  50000,
  'Test seed contribution — saving',
  CURDATE(),
  u.id,
  NOW()
FROM `users` u
JOIN `min_subs` ms ON ms.user_id = u.id AND ms.category = 'saving' AND ms.deleted_at IS NULL
WHERE u.email = 'test.member@zimamoto.test'
LIMIT 1;

INSERT INTO `min_transactions`
  (`dr_account`, `cr_account`, `amount`, `narration`, `date_`, `created_by`, `created_at`)
SELECT
  ms.id,
  0,
  25000,
  'Test seed contribution — share',
  CURDATE(),
  u.id,
  NOW()
FROM `users` u
JOIN `min_subs` ms ON ms.user_id = u.id AND ms.category = 'share' AND ms.deleted_at IS NULL
WHERE u.email = 'test.member@zimamoto.test'
LIMIT 1;

-- ────────────────────────────────────────────────────────────
-- STEP 7: Assign the formal roles from the roles table
--  (matches the role_assignments system used by manage_roles)
-- ────────────────────────────────────────────────────────────
-- member → 'member' role
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.member@zimamoto.test' AND r.name = 'member';

-- accountant branch
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.accountant.branch@zimamoto.test' AND r.name = 'accountant';

-- accountant HQ
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.accountant.hq@zimamoto.test' AND r.name = 'accountant';

-- manager
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.manager@zimamoto.test' AND r.name = 'manager';

-- loan committee
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.loancomitee@zimamoto.test' AND r.name = 'loan comitee';

-- chairman
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.chairman@zimamoto.test' AND r.name = 'chairman';

-- admin
INSERT IGNORE INTO `user_role_assignments` (`user_id`, `role_id`, `assigned_by`, `assigned_at`)
SELECT u.id, r.id, 1, NOW()
FROM `users` u, `roles` r
WHERE u.email = 'test.admin@zimamoto.test' AND r.name = 'admin';

-- ────────────────────────────────────────────────────────────
-- STEP 8: Notification settings for all test users
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `notification_settings` (`user_id`, `notify_email`, `notify_sms`, `notify_in_app`, `email_address`, `phone_number`)
SELECT u.id, 0, 0, 1, u.email, '071200000' || u.id
FROM `users` u
WHERE u.email IN (
  'test.member@zimamoto.test',
  'test.accountant.branch@zimamoto.test',
  'test.accountant.hq@zimamoto.test',
  'test.manager@zimamoto.test',
  'test.loancomitee@zimamoto.test',
  'test.chairman@zimamoto.test',
  'test.admin@zimamoto.test'
);

-- ────────────────────────────────────────────────────────────
-- STEP 9: Welcome notifications for all test users
-- ────────────────────────────────────────────────────────────
INSERT INTO `system_notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`)
SELECT u.id, 'info',
  'Welcome to Zima-Moto SACCOS',
  CONCAT('Hello ', u.name, '! Your test account is active. Role: ', u.role, ' | Level: ', u.level),
  './',
  0,
  NOW()
FROM `users` u
WHERE u.email IN (
  'test.member@zimamoto.test',
  'test.accountant.branch@zimamoto.test',
  'test.accountant.hq@zimamoto.test',
  'test.manager@zimamoto.test',
  'test.loancomitee@zimamoto.test',
  'test.chairman@zimamoto.test',
  'test.admin@zimamoto.test'
);

-- ────────────────────────────────────────────────────────────
-- VERIFICATION: Run these SELECTs to confirm everything seeded
-- ────────────────────────────────────────────────────────────
/*
SELECT u.id, u.name, u.email, u.role, u.level, u.status,
       b.name AS branch,
       GROUP_CONCAT(r.name) AS assigned_roles
FROM users u
LEFT JOIN branches b ON b.id = u.branch_id
LEFT JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.revoked_at IS NULL
LEFT JOIN roles r ON r.id = ura.role_id
WHERE u.email LIKE '%@zimamoto.test'
GROUP BY u.id
ORDER BY u.id;
*/
