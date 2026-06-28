<?php
// ============================================================
//  CHATBOT TOOLS — Data Query & Write Actions
//  Included by chatbot_api.php AFTER all function files are
//  loaded and $conn / $_SESSION vars are set.
//
//  Architecture:
//  • getToolRegistry()  — defines every callable tool
//  • dispatchTool()     — validates params, checks permissions,
//                         executes, audits, returns result
//  • parseToolCall()    — detects [TOOL:...] token in AI reply
//  • executeToolFromReply() — glues AI reply → tool dispatch
// ============================================================

// ─────────────────────────────────────────────────────────────
//  PERMISSION HELPER
//  Checks $_SESSION role against a tool's allowed_roles list.
//  Also checks granular role_permissions table when available.
// ─────────────────────────────────────────────────────────────
function chatbotUserCan(mysqli $conn, int $userId, string $userRole,
                        string $module, string $action, array $toolRoles): bool
{
    // 1. Role-string gate (fast, always present)
    $adminRoles = ['admin','superadmin','super admin'];
    $isAdmin    = in_array($userRole, $adminRoles, true);
    $roleOk     = in_array($userRole, $toolRoles, true) || in_array('*', $toolRoles, true);
    if (!$roleOk) return false;

    // 2. If granular permission rows exist for this user, honour them
    //    userHasPermission() returns false both for "no rows" and "explicitly denied"
    //    — so we only consult it when we know the module has rows configured.
    if (function_exists('userHasPermission') && !$isAdmin) {
        $checkSql = "SELECT 1 FROM user_role_assignments ura
                     JOIN role_permissions rp ON ura.role_id = rp.role_id
                     WHERE ura.user_id = ? AND rp.module = ? AND ura.revoked_at IS NULL LIMIT 1";
        $chk = $conn->prepare($checkSql);
        if ($chk) {
            $chk->bind_param('is', $userId, $module);
            $chk->execute();
            $chk->store_result();
            $hasRows = $chk->num_rows > 0;
            $chk->close();
            if ($hasRows) {
                return userHasPermission($conn, $userId, $module, $action);
            }
        }
    }

    // 3. No granular config — fall back to role-string result
    return $roleOk;
}

// ─────────────────────────────────────────────────────────────
//  TOOL REGISTRY
//  Each tool entry:
//    description  — shown to the AI in the system prompt
//    params       — expected parameter names (from AI JSON)
//    allowed_roles— roles that may call this tool
//    module       — maps to role_permissions.module
//    permission   — 'can_view' | 'can_edit' | 'can_approve'
//    is_write     — true → requires_confirmation flow
//    handler      — callable(mysqli, array $params, int $userId,
//                            string $role, int $branchId): array
//                   returns ['ok'=>bool,'message'=>string,'data'=>mixed]
// ─────────────────────────────────────────────────────────────
function getToolRegistry(mysqli $conn, int $userId, string $userRole, int $branchId): array
{
    $adminRoles = ['admin','superadmin','super admin'];
    $staffRoles = array_merge($adminRoles, ['accountant','manager','loan comitee','chairman']);

    return [

        // ══════════════════════════════════════════════════════
        //  READ TOOLS
        // ══════════════════════════════════════════════════════

        'list_loans' => [
            'description'  => 'List/filter/sort loans. Params: status(pending|approved|rejected|all), search(member name), branch_id, date_from(Y-m-d), date_to(Y-m-d), sort_by(id|principle|created_at), sort_dir(asc|desc), limit(1-100).',
            'params'       => ['status','search','branch_id','date_from','date_to','sort_by','sort_dir','limit'],
            'allowed_roles'=> $staffRoles,
            'module'       => 'loans',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $filters = [];
                if (!empty($p['status'])    && $p['status'] !== 'all') $filters['status']    = $p['status'];
                if (!empty($p['search']))    $filters['search']    = $p['search'];
                if (!empty($p['date_from'])) $filters['date1']     = $p['date_from'];
                if (!empty($p['date_to']))   $filters['date2']     = $p['date_to'];
                if (!empty($p['branch_id'])) $filters['branch_id'] = (int)$p['branch_id'];
                // Scope non-admin staff to their branch unless they specified one
                $adminRoles = ['admin','superadmin','super admin'];
                if (!in_array($role, $adminRoles, true) && $branchId > 0 && empty($p['branch_id'])) {
                    $filters['branch_id'] = $branchId;
                }
                $limit = min((int)($p['limit'] ?? 20), 100);
                $filters['limit'] = $limit;

                // sort support (selectLoansFiltered doesn't support sort natively — we add ORDER via filter)
                $sortBy  = in_array($p['sort_by']  ?? '', ['id','principle','created_at'], true)
                           ? $p['sort_by'] : 'id';
                $sortDir = strtolower($p['sort_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

                // We call selectLoansFiltered then sort in PHP (avoids modifying shared function)
                $rows = selectLoansFiltered($conn, $filters);
                if (!is_array($rows)) return ['ok'=>false,'message'=>'Database query failed.','data'=>null];

                // Sort
                usort($rows, function($a, $b) use ($sortBy, $sortDir) {
                    $va = $a[$sortBy] ?? 0;
                    $vb = $b[$sortBy] ?? 0;
                    $cmp = is_numeric($va) ? ($va <=> $vb) : strcmp((string)$va,(string)$vb);
                    return $sortDir === 'ASC' ? $cmp : -$cmp;
                });

                if (empty($rows)) return ['ok'=>true,'message'=>'No loans found matching your criteria.','data'=>[]];

                $lines = [];
                foreach ($rows as $r) {
                    $lines[] = "ID:{$r['id']} | {$r['member_name']} | {$r['product_name']} | "
                             . "TZS ".number_format((float)$r['principle'])
                             . " | {$r['status']} | Branch:{$r['branch_name']}"
                             . " | Applied:".substr($r['created_at'],0,10);
                }
                return ['ok'=>true,
                        'message'=>count($lines)." loan(s) found:\n".implode("\n",$lines),
                        'data'=>$rows];
            },
        ],

        'get_loan_details' => [
            'description'  => 'Get full details of a specific loan including eligibility check and guarantors. Params: loan_id(required).',
            'params'       => ['loan_id'],
            'allowed_roles'=> $staffRoles,
            'module'       => 'loans',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId = (int)($p['loan_id'] ?? 0);
                if ($loanId <= 0) return ['ok'=>false,'message'=>'Please provide a valid loan_id.','data'=>null];

                $loan = selectLoanById($conn, $loanId);
                if (!$loan || !is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];

                // Branch scope for non-admin
                $adminRoles = ['admin','superadmin','super admin'];
                if (!in_array($role, $adminRoles, true) && $branchId > 0 && (int)$loan['branch_id'] !== $branchId) {
                    return ['ok'=>false,'message'=>'You can only view loans in your branch.','data'=>null];
                }

                $eligibility = null;
                try { $eligibility = evaluateLoanEligibility($conn, $loanId); } catch(Throwable $e) {}

                $grantors = selectLoanGrantorByLoanId($conn, $loanId);
                $schedule = selectLoanScheduleByLoanId($conn, $loanId);

                $msg  = "Loan #{$loanId} — {$loan['member_name']} | {$loan['loan_type_name']}\n";
                $msg .= "Amount: TZS ".number_format((float)$loan['principle'])." | Period:{$loan['period']}m | Status:{$loan['status']}\n";
                $msg .= "Applied: ".substr($loan['created_at'],0,10);
                if ($loan['approve_date']) $msg .= " | Approved:".substr($loan['approve_date'],0,10);
                if ($loan['rejection_reason']) $msg .= "\nRejection reason: {$loan['rejection_reason']}";

                if ($eligibility && empty($eligibility['error'])) {
                    $msg .= "\nEligibility: ".strtoupper($eligibility['recommendation']);
                    foreach ($eligibility['checks'] as $c) {
                        $icon = $c['status']==='pass'?'✓':($c['status']==='warning'?'⚠':'✗');
                        $msg .= "\n  {$icon} {$c['label']}: {$c['detail']}";
                    }
                }

                if (is_array($grantors) && count($grantors)) {
                    $msg .= "\nGuarantors: ".implode(', ', array_column($grantors,'name'));
                }

                $paidInstallments = 0; $totalInstallments = 0;
                if (is_array($schedule)) {
                    $totalInstallments = count($schedule);
                    foreach ($schedule as $s) {
                        if (($s['status']??'') === 'paid') $paidInstallments++;
                    }
                    $msg .= "\nSchedule: {$paidInstallments}/{$totalInstallments} installments paid";
                }

                return ['ok'=>true,'message'=>$msg,'data'=>compact('loan','eligibility','grantors','schedule')];
            },
        ],

        'list_members' => [
            'description'  => 'List/filter/sort members. Params: search(name), branch_id, status(approved|pending|rejected), sort_by(name|created_at), sort_dir(asc|desc), limit(1-100).',
            'params'       => ['search','branch_id','status','sort_by','sort_dir','limit'],
            'allowed_roles'=> $staffRoles,
            'module'       => 'members',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $adminRoles = ['admin','superadmin','super admin'];
                $scopeBranch = (!in_array($role,$adminRoles,true) && $branchId > 0 && empty($p['branch_id']))
                               ? $branchId : (int)($p['branch_id'] ?? 0);

                $where = ['m.deleted_at IS NULL','u.deleted_at IS NULL'];
                $params = []; $types = '';
                if ($scopeBranch > 0) { $where[] = 'm.branch_id=?'; $types.='i'; $params[]=$scopeBranch; }
                if (!empty($p['search']))  { $where[]='u.name LIKE ?'; $types.='s'; $params[]='%'.trim($p['search']).'%'; }
                if (!empty($p['status']))  { $where[]='m.status=?';    $types.='s'; $params[]=trim($p['status']); }

                $sortBy  = in_array($p['sort_by']??'',['name','created_at'],true) ? ($p['sort_by']==='name'?'u.name':'m.created_at') : 'u.name';
                $sortDir = strtolower($p['sort_dir']??'asc')==='desc'?'DESC':'ASC';
                $limit   = min((int)($p['limit']??30),100);

                $sql = "SELECT m.id,m.reg_no,m.phone,m.status,m.created_at,u.name,u.email,b.name AS branch_name
                        FROM members m
                        JOIN users u ON u.id=m.user_id
                        LEFT JOIN branches b ON b.id=m.branch_id
                        WHERE ".implode(' AND ',$where)."
                        ORDER BY {$sortBy} {$sortDir} LIMIT {$limit}";
                $stmt = $conn->prepare($sql);
                if (!$stmt) return ['ok'=>false,'message'=>'Query failed: '.$conn->error,'data'=>null];
                if ($params) $stmt->bind_param($types,...$params);
                $stmt->execute();
                $rows = stmt_fetch_all($stmt); $stmt->close();

                if (empty($rows)) return ['ok'=>true,'message'=>'No members found.','data'=>[]];
                $lines = [];
                foreach ($rows as $r) {
                    $lines[] = "#{$r['id']} {$r['name']} | Reg:{$r['reg_no']} | Branch:{$r['branch_name']} | Phone:{$r['phone']} | Status:{$r['status']}";
                }
                return ['ok'=>true,'message'=>count($lines)." member(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'get_member_details' => [
            'description'  => 'Get full details of a member including savings and loan summary. Params: member_id OR reg_no OR name_search(one of these required).',
            'params'       => ['member_id','reg_no','name_search'],
            'allowed_roles'=> $staffRoles,
            'module'       => 'members',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $member = null;
                if (!empty($p['member_id'])) {
                    $member = selectMemberById($conn, (int)$p['member_id']);
                } elseif (!empty($p['reg_no'])) {
                    $stmt = $conn->prepare("SELECT m.*,u.name,u.email,u.status AS user_status,b.name AS branch_name
                        FROM members m JOIN users u ON u.id=m.user_id
                        LEFT JOIN branches b ON b.id=m.branch_id
                        WHERE m.reg_no=? AND m.deleted_at IS NULL LIMIT 1");
                    if ($stmt) { $stmt->bind_param('s',$p['reg_no']); $stmt->execute(); $member=stmt_fetch_assoc($stmt); $stmt->close(); }
                } elseif (!empty($p['name_search'])) {
                    $like = '%'.trim($p['name_search']).'%';
                    $stmt = $conn->prepare("SELECT m.*,u.name,u.email,u.status AS user_status,b.name AS branch_name
                        FROM members m JOIN users u ON u.id=m.user_id
                        LEFT JOIN branches b ON b.id=m.branch_id
                        WHERE u.name LIKE ? AND m.deleted_at IS NULL LIMIT 1");
                    if ($stmt) { $stmt->bind_param('s',$like); $stmt->execute(); $member=stmt_fetch_assoc($stmt); $stmt->close(); }
                } else {
                    return ['ok'=>false,'message'=>'Provide member_id, reg_no, or name_search.','data'=>null];
                }

                if (!$member || !is_array($member)) return ['ok'=>false,'message'=>'Member not found.','data'=>null];

                // Branch scope
                $adminRoles = ['admin','superadmin','super admin'];
                if (!in_array($role,$adminRoles,true) && $branchId>0 && (int)($member['branch_id']??0)!==$branchId) {
                    return ['ok'=>false,'message'=>'Member not in your branch.','data'=>null];
                }

                $membUserId = (int)($member['user_id']??0);
                $savings = ['saving'=>0,'amana'=>0,'share'=>0,'total'=>0];
                if ($membUserId && function_exists('getMemberTotalSavings')) {
                    try { $savings = getMemberTotalSavings($conn,$membUserId); } catch(Throwable $e){}
                }
                $outstanding = ['outstanding_balance'=>0,'active_loan_count'=>0];
                if ($membUserId && function_exists('getMemberOutstandingLoanBalance')) {
                    try { $outstanding = getMemberOutstandingLoanBalance($conn,$membUserId); } catch(Throwable $e){}
                }

                $msg  = "Member: {$member['name']} | Reg:{$member['reg_no']} | ID:{$member['id']}\n";
                $msg .= "Branch:{$member['branch_name']} | Phone:{$member['phone']} | Gender:{$member['gender']}\n";
                $msg .= "Status:{$member['status']} | NIDA:{$member['nida']}\n";
                $msg .= "Savings — Saving:TZS ".number_format($savings['saving'])
                      ." | Amana:TZS ".number_format($savings['amana'])
                      ." | Share:TZS ".number_format($savings['share'])
                      ." | Total:TZS ".number_format($savings['total'])."\n";
                $msg .= "Active loans:{$outstanding['active_loan_count']} | Outstanding:TZS ".number_format($outstanding['outstanding_balance']);

                return ['ok'=>true,'message'=>$msg,'data'=>compact('member','savings','outstanding')];
            },
        ],

        'list_loan_products' => [
            'description'  => 'List loan products/types. Params: status(active|inactive|all), limit.',
            'params'       => ['status','limit'],
            'allowed_roles'=> array_merge($staffRoles, ['member']),
            'module'       => 'loans',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $statusFilter = $p['status'] ?? 'active';
                $limit = min((int)($p['limit']??20),50);
                $where = ['lt.deleted_at IS NULL'];
                $types = ''; $params = [];
                if ($statusFilter !== 'all') { $where[]='lt.status=?'; $types.='s'; $params[]=$statusFilter; }
                $sql = "SELECT lt.* FROM loan_types lt WHERE ".implode(' AND ',$where)." ORDER BY lt.name LIMIT {$limit}";
                $stmt = $conn->prepare($sql);
                if (!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if ($params) $stmt->bind_param($types,...$params);
                $stmt->execute(); $rows=stmt_fetch_all($stmt); $stmt->close();
                if (empty($rows)) return ['ok'=>true,'message'=>'No loan products found.','data'=>[]];
                $lines=[];
                foreach($rows as $r){
                    $lines[]="#{$r['id']} {$r['name']} | Rate:{$r['interest_rate']}% | "
                            ."Min:TZS ".number_format((float)$r['min_amount'])." Max:TZS ".number_format((float)$r['max_amount'])
                            ." | Period:{$r['min_period']}-{$r['max_period']}m | Grantors:{$r['required_grantors']} | Status:{$r['status']}";
                }
                return ['ok'=>true,'message'=>count($lines)." product(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'list_branches' => [
            'description'  => 'List all branches. Params: limit.',
            'params'       => ['limit'],
            'allowed_roles'=> $staffRoles,
            'module'       => 'branches',
            'permission'   => 'can_view',
            'is_write'     => false,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $limit = min((int)($p['limit']??30),100);
                $rows = selectAllBranches($conn);
                if (!is_array($rows)) return ['ok'=>false,'message'=>'Could not load branches.','data'=>null];
                $rows = array_slice($rows,0,$limit);
                if(empty($rows)) return ['ok'=>true,'message'=>'No branches found.','data'=>[]];
                $lines=[];
                foreach($rows as $r){
                    $lines[]="#{$r['id']} {$r['name']} | Phone:{$r['phone']} | {$r['mkoa']}";
                }
                return ['ok'=>true,'message'=>count($lines)." branch(es):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  WRITE TOOLS  (all require confirmation)
        // ══════════════════════════════════════════════════════

        'reject_loan' => [
            'description'  => 'Reject a pending loan application. Params: loan_id(required), reason(required - must be a clear reason).',
            'params'       => ['loan_id','reason'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'loans',
            'permission'   => 'can_approve',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId = (int)($p['loan_id']??0);
                $reason = trim($p['reason']??'');
                if ($loanId<=0) return ['ok'=>false,'message'=>'Please provide a valid loan_id.','data'=>null];
                if ($reason==='') return ['ok'=>false,'message'=>'Please provide a rejection reason.','data'=>null];

                $loan = selectLoanById($conn,$loanId);
                if (!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if ($loan['status']==='rejected') return ['ok'=>false,'message'=>"Loan #{$loanId} is already rejected.",'data'=>null];
                if ($loan['status']==='approved') return ['ok'=>false,'message'=>"Loan #{$loanId} is already approved and cannot be rejected.",'data'=>null];

                $membUserId = (int)($loan['user_id']??0);
                $old = ['status'=>$loan['status']];
                $result = rejectLoan($conn,$loanId,$reason,$userId);
                if ($result !== true) return ['ok'=>false,'message'=>"Failed to reject loan: {$result}",'data'=>null];

                // Notify member
                if ($membUserId && function_exists('createSystemNotification')) {
                    try {
                        createSystemNotification($conn,$membUserId,'Loan Application Rejected',
                            "Your loan application (TZS ".number_format((float)$loan['principle']).") was not approved. Reason: {$reason}",
                            'danger','./?page=my_loan');
                    } catch(Throwable $e) {}
                }

                // Audit
                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'reject','loans',$loanId,
                        "[Chatbot] Rejected loan #{$loanId}. Reason: {$reason}",
                        $old,['status'=>'rejected','rejection_reason'=>$reason]);
                }

                return ['ok'=>true,'message'=>"Loan #{$loanId} (TZS ".number_format((float)$loan['principle']).") has been rejected. Member has been notified.",'data'=>null];
            },
        ],

        'approve_loan' => [
            'description'  => 'Approve a pending loan. Params: loan_id(required), interest_rate(%, required), approve_date(Y-m-d, required). The interest_amount is auto-calculated.',
            'params'       => ['loan_id','interest_rate','approve_date'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'loans',
            'permission'   => 'can_approve',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId       = (int)($p['loan_id']??0);
                $interestRate = (float)($p['interest_rate']??0);
                $approveDate  = trim($p['approve_date']??date('Y-m-d'));
                if ($loanId<=0) return ['ok'=>false,'message'=>'Please provide a valid loan_id.','data'=>null];
                if ($interestRate<=0) return ['ok'=>false,'message'=>'Please provide a valid interest_rate (%).','data'=>null];
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$approveDate)) return ['ok'=>false,'message'=>'approve_date must be YYYY-MM-DD.','data'=>null];

                $loan = selectLoanById($conn,$loanId);
                if (!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if ($loan['status']==='approved') return ['ok'=>false,'message'=>"Loan #{$loanId} is already approved.",'data'=>null];
                if ($loan['status']==='rejected') return ['ok'=>false,'message'=>"Loan #{$loanId} has been rejected and cannot be approved.",'data'=>null];

                $principle   = (float)$loan['principle'];
                $loanTerm    = (int)$loan['period'];
                $membUserId  = (int)$loan['user_id'];
                $loanBranchId= (int)$loan['branch_id'];
                // Interest: simple (principle * rate/100 total), matching generateSchedule logic
                $interestAmount = $principle * ($interestRate / 100);

                // Account IDs match loan_controller.php hardcoded values
                $loanSuspenceAccount         = 3028;
                $accruedInterestIncomeOnLoan = 3027;

                $LoanSub = selectMinSubByUserIDAndCategory($conn,$membUserId,'loan');
                if (!$LoanSub||!is_array($LoanSub)) {
                    return ['ok'=>false,'message'=>'Loan sub-account not found for this member.','data'=>null];
                }

                $ref = "LAJV/{$loanId}";
                $principleEntry = createMinTransaction($conn,$ref,(int)$LoanSub['id'],
                    'approved loan:principle',$principle,$loanSuspenceAccount,
                    $approveDate,$userId,$loanBranchId,'active');
                if (!$principleEntry) return ['ok'=>false,'message'=>'Failed to create principal ledger entry.','data'=>null];

                $interestEntry = createMinTransaction($conn,$ref,(int)$LoanSub['id'],
                    'approved loan:interest',$interestAmount,$accruedInterestIncomeOnLoan,
                    $approveDate,$userId,$loanBranchId,'active');
                if (!$interestEntry) return ['ok'=>false,'message'=>'Failed to create interest ledger entry.','data'=>null];

                $approved = approveLoan($conn,$loanId,$interestAmount,$interestRate,'approved',$approveDate,$userId);
                if ($approved !== true) return ['ok'=>false,'message'=>"approveLoan failed: {$approved}",'data'=>null];

                // Eligibility snapshot
                try {
                    $snap = evaluateLoanEligibility($conn,$loanId);
                    $stmtSnap = $conn->prepare('UPDATE loans SET eligibility_snapshot=? WHERE id=?');
                    if ($stmtSnap) { $snapJson=json_encode($snap); $stmtSnap->bind_param('si',$snapJson,$loanId); $stmtSnap->execute(); }
                } catch(Throwable $e){}

                // Generate repayment schedule
                $schedule = generateSchedule($principle,$interestRate,$loanTerm,'month',$approveDate);
                if ($schedule&&is_array($schedule)) {
                    foreach($schedule as $repayment){
                        insertSchedule($conn,$membUserId,$loanBranchId,$loanId,
                            $repayment['principle'],$repayment['interest'],
                            $repayment['repayment_date'],0.0,'pending');
                    }
                }

                // Notify member
                if (function_exists('createSystemNotification')) {
                    try {
                        createSystemNotification($conn,$membUserId,'Loan Approved',
                            "Congratulations! Your loan of TZS ".number_format($principle)." has been approved. "
                            ."Repayment begins ".date('d/m/Y',strtotime($approveDate.' +1 month')).".",
                            'success','./?page=my_loan');
                    } catch(Throwable $e){}
                }

                // Audit
                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'approve','loans',$loanId,
                        "[Chatbot] Approved loan #{$loanId}. Rate:{$interestRate}% Date:{$approveDate}",
                        ['status'=>$loan['status']],
                        ['status'=>'approved','interest_rate'=>$interestRate,'approve_date'=>$approveDate]);
                }

                return ['ok'=>true,
                        'message'=>"Loan #{$loanId} (TZS ".number_format($principle).") approved at {$interestRate}% interest. "
                                  ."Repayment schedule of {$loanTerm} installments created. Member notified.",
                        'data'=>null];
            },
        ],

        'edit_member' => [
            'description'  => 'Edit a member\'s basic info. Params: member_id OR reg_no (one required), then any of: phone, gender(male|female), status(approved|pending|rejected), nida, birthdate(Y-m-d).',
            'params'       => ['member_id','reg_no','phone','gender','status','nida','birthdate'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'members',
            'permission'   => 'can_edit',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                // Resolve member
                $member = null;
                if (!empty($p['member_id'])) {
                    $member = selectMemberById($conn,(int)$p['member_id']);
                } elseif (!empty($p['reg_no'])) {
                    $stmt=$conn->prepare("SELECT m.*,u.name,u.email,u.status AS user_status,b.name AS branch_name
                        FROM members m JOIN users u ON u.id=m.user_id
                        LEFT JOIN branches b ON b.id=m.branch_id
                        WHERE m.reg_no=? AND m.deleted_at IS NULL LIMIT 1");
                    if($stmt){$stmt->bind_param('s',$p['reg_no']);$stmt->execute();$member=stmt_fetch_assoc($stmt);$stmt->close();}
                } else {
                    return ['ok'=>false,'message'=>'Provide member_id or reg_no to identify the member.','data'=>null];
                }
                if (!$member||!is_array($member)) return ['ok'=>false,'message'=>'Member not found.','data'=>null];

                $membUserId = (int)$member['user_id'];
                // Collect what to update — keep existing values for fields not provided
                $newPhone     = !empty($p['phone'])     ? trim($p['phone'])     : $member['phone'];
                $newGender    = !empty($p['gender'])    ? trim($p['gender'])    : $member['gender'];
                $newStatus    = !empty($p['status'])    ? trim($p['status'])    : $member['status'];
                $newNida      = !empty($p['nida'])      ? trim($p['nida'])      : $member['nida'];
                $newBirthdate = !empty($p['birthdate']) ? trim($p['birthdate']) : $member['birthdate'];

                // Validate
                if (!in_array($newGender,['male','female'],true)) {
                    return ['ok'=>false,'message'=>'gender must be "male" or "female".','data'=>null];
                }
                if (!in_array($newStatus,['approved','pending','rejected'],true)) {
                    return ['ok'=>false,'message'=>'status must be approved, pending, or rejected.','data'=>null];
                }

                $old = ['phone'=>$member['phone'],'gender'=>$member['gender'],
                        'status'=>$member['status'],'nida'=>$member['nida'],'birthdate'=>$member['birthdate']];

                $result = updateMember($conn,$membUserId,$newPhone,$newBirthdate,$newNida,$newGender,$newStatus);
                if ($result !== true) return ['ok'=>false,'message'=>"Update failed: {$result}",'data'=>null];

                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'update','members',(int)$member['id'],
                        "[Chatbot] Edited member #{$member['id']} ({$member['name']})",$old,
                        ['phone'=>$newPhone,'gender'=>$newGender,'status'=>$newStatus,'nida'=>$newNida,'birthdate'=>$newBirthdate]);
                }

                return ['ok'=>true,'message'=>"Member {$member['name']} (#{$member['id']}) updated successfully.",'data'=>null];
            },
        ],

        'create_loan_product' => [
            'description'  => 'Create a new loan product. Params: name(required), interest_rate(%, required), min_amount, max_amount, min_period(months), max_period(months), required_grantors, savings_multiplier, description, status(active|inactive).',
            'params'       => ['name','interest_rate','min_amount','max_amount','min_period','max_period','required_grantors','savings_multiplier','description','status'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'loans',
            'permission'   => 'can_create',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $name = trim($p['name']??'');
                if ($name==='') return ['ok'=>false,'message'=>'Product name is required.','data'=>null];
                $interestRate = (float)($p['interest_rate']??0);
                if ($interestRate<=0) return ['ok'=>false,'message'=>'interest_rate must be > 0.','data'=>null];

                $d = [
                    'name'                    => $name,
                    'description'             => trim($p['description']??''),
                    'min_amount'              => (float)($p['min_amount']??0),
                    'max_amount'              => (float)($p['max_amount']??0),
                    'interest_rate'           => $interestRate,
                    'min_period'              => (int)($p['min_period']??1),
                    'max_period'              => (int)($p['max_period']??12),
                    'savings_multiplier'      => (float)($p['savings_multiplier']??3),
                    'required_grantors'       => (int)($p['required_grantors']??0),
                    'processing_fee_percent'  => 0.0,
                    'allowed_repayment_modes' => 'salary,standing_order',
                    'eligibility_notes'       => '',
                    'status'                  => (($p['status']??'active')==='inactive')?'inactive':'active',
                ];

                $result = insertLoanType($conn,$d,$userId);
                if (!is_numeric($result)||$result<=0) return ['ok'=>false,'message'=>"Failed to create product: {$result}",'data'=>null];

                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'create','loans',(int)$result,"[Chatbot] Created loan product: {$name}",[],['id'=>$result,'name'=>$name]);
                }
                return ['ok'=>true,'message'=>"Loan product '{$name}' created (ID:{$result}) with {$interestRate}% rate.",'data'=>['id'=>$result]];
            },
        ],

        'update_loan_product' => [
            'description'  => 'Update an existing loan product. Params: product_id(required), then any of: name, interest_rate, min_amount, max_amount, min_period, max_period, required_grantors, savings_multiplier, description, status(active|inactive).',
            'params'       => ['product_id','name','interest_rate','min_amount','max_amount','min_period','max_period','required_grantors','savings_multiplier','description','status'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'loans',
            'permission'   => 'can_edit',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $productId = (int)($p['product_id']??0);
                if ($productId<=0) return ['ok'=>false,'message'=>'Provide product_id.','data'=>null];
                $existing = selectLoanTypeById($conn,$productId);
                if (!$existing||!is_array($existing)) return ['ok'=>false,'message'=>"Loan product #{$productId} not found.",'data'=>null];

                $d = [
                    'name'                    => !empty($p['name'])              ? trim($p['name'])             : $existing['name'],
                    'description'             => isset($p['description'])        ? trim($p['description'])      : $existing['description'],
                    'min_amount'              => isset($p['min_amount'])          ? (float)$p['min_amount']      : (float)$existing['min_amount'],
                    'max_amount'              => isset($p['max_amount'])          ? (float)$p['max_amount']      : (float)$existing['max_amount'],
                    'interest_rate'           => isset($p['interest_rate'])       ? (float)$p['interest_rate']   : (float)$existing['interest_rate'],
                    'min_period'              => isset($p['min_period'])          ? (int)$p['min_period']        : (int)$existing['min_period'],
                    'max_period'              => isset($p['max_period'])          ? (int)$p['max_period']        : (int)$existing['max_period'],
                    'savings_multiplier'      => isset($p['savings_multiplier'])  ? (float)$p['savings_multiplier'] : (float)$existing['savings_multiplier'],
                    'required_grantors'       => isset($p['required_grantors'])   ? (int)$p['required_grantors'] : (int)$existing['required_grantors'],
                    'processing_fee_percent'  => (float)($existing['processing_fee_percent']??0),
                    'allowed_repayment_modes' => $existing['allowed_repayment_modes']??'salary,standing_order',
                    'eligibility_notes'       => $existing['eligibility_notes']??'',
                    'status'                  => isset($p['status']) ? ((trim($p['status'])==='inactive')?'inactive':'active') : $existing['status'],
                ];

                $result = updateLoanType($conn,$productId,$d,$userId);
                if ($result !== true) return ['ok'=>false,'message'=>"Update failed: {$result}",'data'=>null];

                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'update','loans',$productId,
                        "[Chatbot] Updated loan product #{$productId} ({$d['name']})",$existing,$d);
                }
                return ['ok'=>true,'message'=>"Loan product #{$productId} '{$d['name']}' updated successfully.",'data'=>null];
            },
        ],

        'toggle_loan_product_status' => [
            'description'  => 'Activate or deactivate a loan product. Params: product_id(required), status(active|inactive, required).',
            'params'       => ['product_id','status'],
            'allowed_roles'=> $adminRoles,
            'module'       => 'loans',
            'permission'   => 'can_edit',
            'is_write'     => true,
            'handler'      => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $productId = (int)($p['product_id']??0);
                $newStatus = trim($p['status']??'');
                if ($productId<=0) return ['ok'=>false,'message'=>'Provide product_id.','data'=>null];
                if (!in_array($newStatus,['active','inactive'],true)) return ['ok'=>false,'message'=>'status must be active or inactive.','data'=>null];
                $existing = selectLoanTypeById($conn,$productId);
                if (!$existing||!is_array($existing)) return ['ok'=>false,'message'=>"Product #{$productId} not found.",'data'=>null];
                $result = toggleLoanTypeStatus($conn,$productId,$newStatus);
                if ($result!==true) return ['ok'=>false,'message'=>"Toggle failed: {$result}",'data'=>null];
                if (function_exists('logAudit')) {
                    logAudit($conn,$userId,'update','loans',$productId,
                        "[Chatbot] Toggled loan product #{$productId} to {$newStatus}",
                        ['status'=>$existing['status']],['status'=>$newStatus]);
                }
                return ['ok'=>true,'message'=>"Loan product '{$existing['name']}' is now {$newStatus}.",'data'=>null];
            },
        ],

    ]; // end registry
}


// ─────────────────────────────────────────────────────────────
//  DISPATCH  — validate, permission-check, execute a tool
// ─────────────────────────────────────────────────────────────
function dispatchTool(string $toolName, array $params,
                      mysqli $conn, int $userId, string $userRole, int $branchId): array
{
    $registry = getToolRegistry($conn, $userId, $userRole, $branchId);

    if (!isset($registry[$toolName])) {
        return ['ok'=>false,'message'=>"Unknown tool: {$toolName}",'is_write'=>false];
    }

    $tool = $registry[$toolName];

    // Permission check
    if (!chatbotUserCan($conn, $userId, $userRole, $tool['module'], $tool['permission'], $tool['allowed_roles'])) {
        return ['ok'=>false,'message'=>"You don't have permission to use the '{$toolName}' tool.",'is_write'=>false];
    }

    try {
        $result           = ($tool['handler'])($conn, $params, $userId, $userRole, $branchId);
        $result['is_write'] = $tool['is_write'];
        return $result;
    } catch (Throwable $e) {
        error_log("chatbot_tools dispatchTool({$toolName}): " . $e->getMessage());
        return ['ok'=>false,'message'=>'An internal error occurred while running the tool.','is_write'=>$tool['is_write']];
    }
}


// ─────────────────────────────────────────────────────────────
//  BUILD TOOL DESCRIPTIONS for the system prompt
// ─────────────────────────────────────────────────────────────
function buildToolDescriptions(string $userRole, mysqli $conn, int $userId, int $branchId): string
{
    $registry = getToolRegistry($conn, $userId, $userRole, $branchId);
    $adminRoles = ['admin','superadmin','super admin'];

    $lines = [];
    foreach ($registry as $name => $tool) {
        if (!in_array($userRole, $tool['allowed_roles'], true) && !in_array('*', $tool['allowed_roles'], true)) continue;
        $tag = $tool['is_write'] ? '[WRITE]' : '[READ]';
        $lines[] = "  • {$name} {$tag}: {$tool['description']}";
    }

    if (empty($lines)) return '';
    return implode("\n", $lines);
}


// ─────────────────────────────────────────────────────────────
//  PARSE TOOL CALL from AI reply text
//  AI embeds: [TOOL:tool_name|param1=val1|param2=val2]
// ─────────────────────────────────────────────────────────────
function parseToolCall(string $text): ?array
{
    // Match [TOOL:name] or [TOOL:name|key=val|key2=val2...]
    if (!preg_match('/\[TOOL:([a-zA-Z_]{1,60})((?:\|[^|\]]+)*)\]/', $text, $m)) {
        return null;
    }
    $toolName = $m[1];
    $params   = [];
    if (!empty($m[2])) {
        $pairs = explode('|', ltrim($m[2], '|'));
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '') continue;
            $eqPos = strpos($pair, '=');
            if ($eqPos === false) continue;
            $key   = trim(substr($pair, 0, $eqPos));
            $value = trim(substr($pair, $eqPos + 1));
            if ($key !== '') $params[$key] = $value;
        }
    }
    return ['tool' => $toolName, 'params' => $params, 'raw' => $m[0]];
}


// ─────────────────────────────────────────────────────────────
//  PENDING CONFIRMATION STORE  (session-based)
//  When a write tool needs confirmation, we store it in session
//  and return a confirmation prompt to the user. On next
//  message "yes/confirm" we execute; "no/cancel" we discard.
// ─────────────────────────────────────────────────────────────
function storePendingToolCall(string $toolName, array $params, string $summary): void
{
    $_SESSION['chatbot_pending_tool'] = [
        'tool'    => $toolName,
        'params'  => $params,
        'summary' => $summary,
        'expires' => time() + 120, // 2 minutes to confirm
    ];
}

function getPendingToolCall(): ?array
{
    $p = $_SESSION['chatbot_pending_tool'] ?? null;
    if (!$p || time() > ($p['expires'] ?? 0)) {
        unset($_SESSION['chatbot_pending_tool']);
        return null;
    }
    return $p;
}

function clearPendingToolCall(): void
{
    unset($_SESSION['chatbot_pending_tool']);
}

function isConfirmation(string $msg): bool
{
    $lower = strtolower(trim($msg));
    return in_array($lower, ['yes','y','confirm','ndio','ok','sawa','proceed','execute','do it'], true)
        || preg_match('/^(yes|confirm|ndio|sawa|proceed)/i', $lower);
}

function isCancellation(string $msg): bool
{
    $lower = strtolower(trim($msg));
    return in_array($lower, ['no','n','cancel','hapana','stop','abort','sitaki'], true)
        || preg_match('/^(no\b|cancel|hapana|stop|abort)/i', $lower);
}
