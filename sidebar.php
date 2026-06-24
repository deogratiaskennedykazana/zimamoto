 <!-- Sidebar Menu -->
<?php
/**
 * Pre-compute visibility flags for the sidebar.
 * Non-members always see everything.
 * Members only see a section if the RBAC system grants them access.
 * The old users.role string check is kept as a fallback for legacy roles.
 */
$_sRole  = strtolower($_SESSION['role'] ?? '');
$_sLevel = strtolower($_SESSION['userlevel'] ?? '');
$_sUid   = (int)($_SESSION['userid'] ?? 0);

// Roles that are treated as "restricted" like a member (original logic)
$_restrictedRoles = ['member', 'manager', 'loan comitee', 'chairman'];
$_isRestricted    = in_array($_sRole, $_restrictedRoles) && in_array($_sLevel, ['branch', 'hq']);
$_isMember        = ($_sRole === 'member');

// Helper: hide class based on flag
function _sideHide(bool $hide): string { return $hide ? ' d-none' : ''; }

// RBAC permission checks (only query DB for members — others see all)
$_canAccounting = !$_isRestricted || userHasPermission($conn, $_sUid, 'Reports', 'can_view');
$_canVouchers   = !$_isRestricted || userHasPermission($conn, $_sUid, 'Reports', 'can_view');
$_canReports    = !$_isRestricted || userHasPermission($conn, $_sUid, 'Reports', 'can_view');
$_canMembers    = !$_isRestricted || userHasPermission($conn, $_sUid, 'Members', 'can_view');
$_canAdmin      = !$_isRestricted || userHasPermission($conn, $_sUid, 'Roles',   'can_view');
$_canBudget     = !$_isMember     || userHasPermission($conn, $_sUid, 'Budget',  'can_view');
$_canMeetings   = !$_isMember     || userHasPermission($conn, $_sUid, 'Meetings','can_view');
?>
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item"><a href="./" class="nav-link"><i class="nav-icon fas fa-th"></i><p>Home</p></a></li>

        <li class="nav-header<?= _sideHide(!$_canAccounting) ?>">Accounting Menu</li>

        <li class="nav-item<?= _sideHide(!$_canAccounting) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Account Creations<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="./?page=Add_masters" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Masters list</p></a></li>
                <li class="nav-item"><a href="./?page=submaster" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Submaster list</p></a></li>
                <li class="nav-item"><a href="./?page=ledger" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Ledger List</p></a></li>
                <li class="nav-item d-none"><a href="./?page=create_minsub" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Create min subs</p></a></li>
                <li class="nav-item"><a href="./?page=coa" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Chart Of Account</p></a></li>
            </ul>
        </li>
 
        <li class="nav-item<?= _sideHide(!$_canAccounting) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Subsidiary<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="./?page=create_subsidiary" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Create Subsidiaries</p></a></li>
                <li class="nav-item"><a href="./?page=subsidiary_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Subsidiaries List</p></a></li>
            </ul>
        </li>
        
          <li class="nav-item<?= _sideHide(!$_canAccounting) ?>">
            <a href="#" class="nav-link ">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Min subsidiary <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=register_min_sub" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Register Min Sub</p></a></li>
              <li class="nav-item"><a href="./?page=min_sub_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min Subsidiary list</p></a></li>
            </ul>
          </li>
          
        <li class="nav-item">
            <a href="#" class="nav-link"><i class="nav-icon fa fa-balance-scale"></i><p>Opening Balances<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
                <li class="nav-item"><a href="./?page=add_opening_balance" class="nav-link"><i class="fa fa-plus nav-icon"></i><p>Add Opening Balance</p></a></li>
                <li class="nav-item"><a href="./?page=opening_balance_list" class="nav-link"><i class="fa fa-list nav-icon"></i><p>Opening Balance List</p></a></li>
            </ul>
        </li>

          <li class="nav-header<?= _sideHide(!$_canVouchers) ?>">Voucher Menu</li>
          <li class="nav-item<?= _sideHide(!$_canVouchers) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-clipboard"></i><p>Voucher Creation<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=receipt_voucher" class="nav-link"><i class="fas fa-angle-right nav-icon"></i><p>Receipt Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=payment_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Payment Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=journal_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Journal Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=purchase_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Purchase Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=sales_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Sales Voucher</p></a></li>
            </ul>
          </li>
          <li class="nav-item<?= _sideHide(!$_canVouchers) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-folder-open"></i><p>Min Voucher Creation<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=min_receipt_voucher" class="nav-link"><i class="fas fa-plus nav-icon"></i><p>Min Receipt Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=min_payment_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min Payment Voucher</p></a></li>
              <li class="nav-item"><a href="./?page=min_journal_voucher" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min Journal Voucher</p></a></li>
            </ul>
          </li>
          <li class="nav-item<?= _sideHide(!$_canVouchers) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-folder-open"></i><p>Min Opening Balance<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=add_min_opening_balance" class="nav-link"><i class="fas fa-plus nav-icon"></i><p>Add Min Opening Balance</p></a></li>
              <li class="nav-item"><a href="./?page=min_sub_opening_balance_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min Opening Balance List</p></a></li>
            </ul>
          </li>

          <li class="nav-header<?= _sideHide(!$_canReports) ?>">Accounting Report</li>
          <li class="nav-item<?= _sideHide(!$_canReports) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-sticky-note"></i><p>Voucher Report<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=pending_voucher_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Pending Voucher List</p></a></li>
              <li class="nav-item"><a href="./?page=transaction_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Transaction Lists</p></a></li>
            </ul>
          </li>
          <li class="nav-item<?= _sideHide(!$_canReports) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-sticky-note"></i><p>Sub Reports<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=min_sub_report_form" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min sub report</p></a></li>
              <li class="nav-item"><a href="./?page=min_sub_report_branch_form" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min sub report by branch</p></a></li>
              <li class="nav-item"><a href="./?page=min_sub_ledger_report_form" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Min Sub ledger</p></a></li>
              <li class="nav-item"><a href="./?page=sub_report_by_branch" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Subsidiary Report By Branch</p></a></li>
            </ul>
          </li>
          <li class="nav-item<?= _sideHide(!$_canReports) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-sticky-note"></i><p>Financial Report<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=pending_voucher_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Pending Voucher List</p></a></li>
              <li class="nav-item"><a href="./?page=transaction_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Transaction Lists</p></a></li>
              <li class="nav-item"><a href="./?page=ledger_report_form" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Ledger Report</p></a></li>
              <li class="nav-item"><a href="./?page=Income_statement_form" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Income Statement</p></a></li>
              <li class="nav-item <?= (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch') ? 'd-none' : '' ?>">
                <a href="./?page=balance_sheets" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Balance sheets</p></a>
              </li>
              <li class="nav-item <?= (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch') ? 'd-none' : '' ?>">
                <a href="./?page=trial_balances" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Trial balances</p></a>
              </li>
            </ul>
          </li>

          <li class="nav-header<?= _sideHide(!$_canMembers) ?>">Member Menu</li>
          <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Member Management<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=register_member" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Register Member</p></a></li>
              <li class="nav-item"><a href="./?page=upload_member" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Upload Member</p></a></li>
              <li class="nav-item"><a href="./?page=upload_contributions" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Upload Contributions</p></a></li>
              <li class="nav-item <?= (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch') ? 'd-none' : '' ?>">
                <a href="./?page=all_member_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>All Member List</p></a>
              </li>
              <li class="nav-item">
                <a href="./?page=member_list_per_branch" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Branch Member List</p></a>
              </li>
              <li class="nav-item <?= (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch') ? 'd-none' : '' ?>">
                <a href="./?page=update_member_list_per_branch" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Edit Member Details</p></a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Loan Management<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
              <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
                <a href="./?page=apply_loan" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Apply Loan</p></a>
              </li>
              <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
                <a href="./?page=upload_loan" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Upload Loan</p></a>
              </li>
              <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
                <a href="./?page=upload_loan_repayments" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Upload Repayments</p></a>
              </li>
              <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
                <a href="./?page=loan_products" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Loan Products</p></a>
              </li>
              <li class="nav-item<?= _sideHide(!$_canMembers) ?>">
                <a href="./?page=loan_applications" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Loan Applications</p></a>
              </li>
             <!-- Member self-service loan link -->
              <?php if(($_SESSION['role'] ?? '') === 'member'): ?>
              <li class="nav-item">
                <a href="./?page=apply_user_loan" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Apply for Loan</p></a>
              </li>
              <?php endif; ?>
              <li class="nav-item"><a href="./?page=my_loan" class="nav-link"><i class="far fa-circle nav-icon"></i><p>My Loan</p></a></li>
            </ul>
          </li>

           <!-- BUDGET MANAGEMENT MENU -->
           <li class="nav-header<?= _sideHide(!$_canBudget) ?>">Budget Management</li>
           <li class="nav-item<?= _sideHide(!$_canBudget) ?>">
             <a href="#" class="nav-link"><i class="nav-icon fas fa-chart-pie"></i><p>Budget<i class="right fas fa-angle-left"></i></p></a>
             <ul class="nav nav-treeview">
               <li class="nav-item"><a href="./?page=create_budget" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Create Budget</p></a></li>
               <li class="nav-item"><a href="./?page=all_budgets" class="nav-link"><i class="far fa-circle nav-icon"></i><p>All Budgets</p></a></li>
             </ul>
           </li>

           <!-- MEETING MINUTES MENU -->
           <li class="nav-header<?= _sideHide(!$_canMeetings) ?>">Meetings</li>
           <li class="nav-item<?= _sideHide(!$_canMeetings) ?>">
             <a href="#" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>Meeting Minutes<i class="right fas fa-angle-left"></i></p></a>
             <ul class="nav nav-treeview">
               <li class="nav-item"><a href="./?page=create_meeting" class="nav-link"><i class="far fa-circle nav-icon"></i><p>New Meeting Minutes</p></a></li>
               <li class="nav-item"><a href="./?page=meeting_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>All Meetings</p></a></li>
             </ul>
           </li>

           <!-- LOAN ADVISER -->
           <li class="nav-header">Loan Tools</li>
           <li class="nav-item">
             <a href="./?page=loan_adviser" class="nav-link"><i class="nav-icon fas fa-calculator"></i><p>Loan Advisor</p></a>
           </li>

           <!-- GRANTOR / NOTIFICATIONS -->
           <li class="nav-header">Notifications</li>
           <li class="nav-item">
             <a href="./?page=my_grantor_requests" class="nav-link"><i class="nav-icon fas fa-handshake"></i><p>Guarantor Requests</p></a>
           </li>
           <li class="nav-item">
             <a href="#" class="nav-link"><i class="nav-icon fas fa-bell"></i><p>Notifications<i class="right fas fa-angle-left"></i></p></a>
             <ul class="nav nav-treeview">
               <li class="nav-item"><a href="./?page=notifications" class="nav-link"><i class="far fa-circle nav-icon"></i><p>My Notifications</p></a></li>
               <li class="nav-item"><a href="./?page=notification_settings" class="nav-link"><i class="far fa-circle nav-icon"></i><p>SMS / Email Settings</p></a></li>
               <?php if (in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'superadmin', 'super admin'])): ?>
               <li class="nav-item">
                 <a href="./?page=test_notifications" class="nav-link">
                   <i class="fas fa-flask nav-icon text-warning"></i>
                   <p>Test Notifications <span class="badge badge-warning badge-sm ml-1">Admin</span></p>
                 </a>
               </li>
               <?php endif; ?>
             </ul>
           </li>


           <li class="nav-header">Account Security</li>
           <li class="nav-item">
             <a href="./?page=mfa_setup" class="nav-link"><i class="nav-icon fas fa-shield-alt"></i><p>Two-Factor Auth (2FA)</p></a>
           </li>

           <!-- ROLE & USER MANAGEMENT -->
           <li class="nav-header<?= _sideHide(!$_canAdmin) ?>">Administration</li>
           <li class="nav-item<?= _sideHide(!$_canAdmin) ?>">
             <a href="#" class="nav-link"><i class="nav-icon fas fa-users-cog"></i><p>Role Management<i class="right fas fa-angle-left"></i></p></a>
             <ul class="nav nav-treeview">
               <li class="nav-item"><a href="./?page=manage_roles" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Manage Roles</p></a></li>
               <li class="nav-item"><a href="./?page=assign_user_roles" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Assign Roles</p></a></li>
               <li class="nav-item"><a href="./?page=pending_members" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Pending Member Approvals</p></a></li>
               <li class="nav-item"><a href="./?page=audit_trail" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Audit Trail</p></a></li>
             </ul>
           </li>

           <!-- UTILITY MENU -->
           <li class="nav-header<?= _sideHide(!$_canAdmin) ?>">Utility Menu</li>
           <li class="nav-item<?= _sideHide(!$_canAdmin) ?>">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Branch Management<i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item"><a href="./?page=register_branch" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Register Branch</p></a></li>
              <li class="nav-item"><a href="./?page=branch_list" class="nav-link"><i class="far fa-circle nav-icon"></i><p>Branch list</p></a></li>
            </ul>
          </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
