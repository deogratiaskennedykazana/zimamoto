<?php
// ============================================================
//  CHATBOT TOOLS v5 — Unlimited CRUD + Full Authorization
//  All roles can interact with everything they're permitted to.
//  New in v5:
//  • Fixed broken string literal in get_member_statement
//  • Added: create_branch, create_meeting, create_budget,
//    create_voucher, bulk_approve_members, bulk_reject_loans,
//    get_loan_schedule, savings_adjustment, list_vouchers,
//    list_audit_trail, list_notifications, mark_notification_read,
//    get_subsidiary_balance, reverse_transaction,
//    list_repayment_schedule, send_member_notification,
//    create_user (with auto sub-accounts), update_user_role,
//    get_branch_details, list_roles, create_loan_for_member
//  • Smart intent: name-only searches auto-resolve to member
//  • All writes: confirm → execute → notify → audit
// ============================================================

// ── Input normalisation helpers ───────────────────────────────

function chatbotParseAmount(string $raw): float
{
    $raw = strtolower(trim(str_replace([',',' '], '', $raw)));
    if (preg_match('/^([\d.]+)m$/', $raw, $m)) return (float)$m[1] * 1_000_000;
    if (preg_match('/^([\d.]+)k$/', $raw, $m)) return (float)$m[1] * 1_000;
    return (float)preg_replace('/[^\d.]/', '', $raw);
}

function chatbotParseDate(string $raw): ?string
{
    $raw = trim($raw);
    $lc = strtolower($raw);
    if ($lc === 'today')     return date('Y-m-d');
    if ($lc === 'yesterday') return date('Y-m-d', strtotime('-1 day'));
    if ($lc === 'tomorrow')  return date('Y-m-d', strtotime('+1 day'));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m))
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    $ts = strtotime($raw);
    if ($ts && $ts > 0) return date('Y-m-d', $ts);
    return null;
}

function chatbotNormalisePhone(string $raw): string
{
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) === 10 && $digits[0] === '0') return '255'.substr($digits,1);
    if (strlen($digits) === 9)                         return '255'.$digits;
    if (strlen($digits) === 12 && substr($digits,0,3)==='255') return $digits;
    return $digits;
}

function chatbotFuzzyMatch(string $input, array $candidates): ?string
{
    $input = strtolower(trim($input));
    foreach ($candidates as $c) { if (strtolower($c) === $input) return $c; }
    $best = null; $bestScore = 0;
    foreach ($candidates as $c) {
        similar_text($input, strtolower($c), $pct);
        if ($pct > $bestScore && $pct > 60) { $bestScore = $pct; $best = $c; }
    }
    return $best;
}

function chatbotValidateNida(string $nida): bool
{
    return (bool)preg_match('/^\d{20}$/', preg_replace('/\D/','',$nida));
}

// ── Shared resolve helpers ────────────────────────────────────

function chatbotResolveMember(mysqli $conn, array $p, int $callerUserId, string $role, int $branchId): array
{
    $adminRoles = ['admin','superadmin','super admin'];
    $member = null;
    $base = "SELECT m.*,u.name,u.email,u.status AS user_status,b.name AS branch_name
              FROM members m JOIN users u ON u.id=m.user_id
              LEFT JOIN branches b ON b.id=m.branch_id WHERE m.deleted_at IS NULL";

    if (!empty($p['member_id'])) {
        $member = selectMemberById($conn,(int)$p['member_id']);
    } elseif (!empty($p['reg_no'])) {
        $stmt = $conn->prepare($base." AND m.reg_no=? LIMIT 1");
        if ($stmt){$stmt->bind_param('s',$p['reg_no']);$stmt->execute();$member=stmt_fetch_assoc($stmt);$stmt->close();}
    } elseif (!empty($p['name_search'])) {
        $like='%'.trim($p['name_search']).'%';
        $stmt=$conn->prepare($base." AND u.name LIKE ? LIMIT 1");
        if($stmt){$stmt->bind_param('s',$like);$stmt->execute();$member=stmt_fetch_assoc($stmt);$stmt->close();}
    } elseif (!in_array($role,$adminRoles,true)) {
        $stmt=$conn->prepare($base." AND m.user_id=? LIMIT 1");
        if($stmt){$stmt->bind_param('i',$callerUserId);$stmt->execute();$member=stmt_fetch_assoc($stmt);$stmt->close();}
    }

    if (!$member || !is_array($member)) return ['error'=>'Member not found. Provide member_id, reg_no, or name_search.'];
    if (!in_array($role,$adminRoles,true) && $branchId>0 && (int)($member['branch_id']??0)!==$branchId)
        return ['error'=>'Member is not in your branch.'];
    return ['member'=>$member];
}

function chatbotResolveUser(mysqli $conn, array $p): array
{
    $user = null;
    if (!empty($p['user_id'])) {
        $stmt=$conn->prepare("SELECT * FROM users WHERE id=? AND deleted_at IS NULL LIMIT 1");
        if($stmt){$stmt->bind_param('i',(int)$p['user_id']);$stmt->execute();$user=stmt_fetch_assoc($stmt);$stmt->close();}
    } elseif (!empty($p['email'])) {
        $stmt=$conn->prepare("SELECT * FROM users WHERE email=? AND deleted_at IS NULL LIMIT 1");
        if($stmt){$stmt->bind_param('s',$p['email']);$stmt->execute();$user=stmt_fetch_assoc($stmt);$stmt->close();}
    } elseif (!empty($p['name_search'])) {
        $like='%'.trim($p['name_search']).'%';
        $stmt=$conn->prepare("SELECT * FROM users WHERE name LIKE ? AND deleted_at IS NULL LIMIT 1");
        if($stmt){$stmt->bind_param('s',$like);$stmt->execute();$user=stmt_fetch_assoc($stmt);$stmt->close();}
    }
    if (!$user||!is_array($user)) return ['error'=>'User not found. Provide user_id, email, or name_search.'];
    return ['user'=>$user];
}

function chatbotGetSavingGLCrAccount(mysqli $conn, string $category): int
{
    $glMap = ['saving'=>3001,'amana'=>3002,'share'=>3003];
    return $glMap[$category] ?? 3001;
}

function chatbotEnsureMemberSubAccounts(mysqli $conn, int $userId, int $branchId, string $name): void
{
    // Use prepared statements to avoid any SQL injection risk on userId
    foreach(['saving'=>'Savings Account','amana'=>'Amana Account','share'=>'Share Account','loan'=>'Loan Account'] as $cat=>$label){
        $chk=$conn->prepare("SELECT id FROM min_subs WHERE user_id=? AND category=? LIMIT 1");
        if(!$chk) continue;
        $chk->bind_param('is',$userId,$cat);$chk->execute();$chk->store_result();
        $exists=$chk->num_rows>0;$chk->close();
        if(!$exists){
            createMinsub($conn,"{$name} {$label}",$userId,1,$branchId,'debit',$cat);
        }
    }
}

// ── Wizard session helpers ─────────────────────────────────────

function chatbotGetWizard(): ?array
{
    $w = $_SESSION['chatbot_wizard'] ?? null;
    if (!$w) return null;
    if (time() > ($w['expires']??0)) { unset($_SESSION['chatbot_wizard']); return null; }
    return $w;
}
function chatbotSetWizard(array $w): void { $w['expires']=time()+600; $_SESSION['chatbot_wizard']=$w; }
function chatbotClearWizard(): void { unset($_SESSION['chatbot_wizard']); }

function chatbotWizardStep(array $wizard, string $fieldValue): array
{
    $wizard['data'][$wizard['fields'][$wizard['step']]['key']] = $fieldValue;
    $wizard['step']++;
    return $wizard;
}

// ── Permission helper ──────────────────────────────────────────
function chatbotUserCan(mysqli $conn, int $userId, string $userRole,
                        string $module, string $action, array $toolRoles): bool
{
    $adminRoles = ['admin','superadmin','super admin'];
    $isAdmin    = in_array($userRole,$adminRoles,true);
    $roleOk     = in_array($userRole,$toolRoles,true) || in_array('*',$toolRoles,true);
    if (!$roleOk) return false;
    if (function_exists('userHasPermission') && !$isAdmin) {
        $chk=$conn->prepare("SELECT 1 FROM user_role_assignments ura
             JOIN role_permissions rp ON ura.role_id=rp.role_id
             WHERE ura.user_id=? AND rp.module=? AND ura.revoked_at IS NULL LIMIT 1");
        if ($chk) {
            $chk->bind_param('is',$userId,$module);$chk->execute();$chk->store_result();
            $has=$chk->num_rows>0;$chk->close();
            if ($has) return userHasPermission($conn,$userId,$module,$action);
        }
    }
    return $roleOk;
}

// ═════════════════════════════════════════════════════════════
//  MAIN TOOL REGISTRY
// ═════════════════════════════════════════════════════════════
function getToolRegistry(mysqli $conn, int $userId, string $userRole, int $branchId): array
{
    $adminRoles   = ['admin','superadmin','super admin'];
    $staffRoles   = array_merge($adminRoles,['accountant','manager','loan comitee','chairman']);
    $financeRoles = array_merge($adminRoles,['accountant']);
    $allRoles     = array_merge($staffRoles,['member']);

    return [

        // ══════════════════════════════════════════════════════
        //  DASHBOARD
        // ══════════════════════════════════════════════════════
        'dashboard_stats' => [
            'description'   => 'Real-time dashboard: members, loans, savings totals, pending items. No params.',
            'params'        => [],
            'allowed_roles' => $staffRoles,
            'module'=>'dashboard','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $isAdmin=in_array($role,$adminRoles,true);
                $bW=(!$isAdmin&&$branchId>0)?"AND branch_id={$branchId}":'';
                $bTW=(!$isAdmin&&$branchId>0)?"AND mt.branch_id={$branchId}":'';
                $r=$conn->query("SELECT COUNT(*) AS c FROM members WHERE deleted_at IS NULL {$bW}");
                $members=(int)($r?$r->fetch_assoc()['c']:0);
                $r=$conn->query("SELECT COUNT(*) AS c FROM members WHERE status='pending' AND deleted_at IS NULL {$bW}");
                $pendingMem=(int)($r?$r->fetch_assoc()['c']:0);
                $r=$conn->query("SELECT status,COUNT(*) AS c,COALESCE(SUM(principle),0) AS t FROM loans WHERE deleted_at IS NULL {$bW} GROUP BY status");
                $loans=[];if($r) while($row=$r->fetch_assoc()) $loans[$row['status']]=['count'=>(int)$row['c'],'total'=>(float)$row['t']];
                $r=$conn->query("SELECT ms.category,COALESCE(SUM(mt.amount),0) AS total FROM min_transactions mt JOIN min_subs ms ON ms.id=mt.dr_account WHERE ms.category IN ('saving','amana','share') AND mt.deleted_at IS NULL {$bTW} GROUP BY ms.category");
                $savings=[];if($r) while($row=$r->fetch_assoc()) $savings[$row['category']]=(float)$row['total'];
                $overdueQ="SELECT COUNT(*) AS c FROM loan_schedules ls JOIN loans l ON l.id=ls.loan_id WHERE ls.status!='paid' AND ls.payment_date<CURDATE() AND l.deleted_at IS NULL".((!$isAdmin&&$branchId>0)?" AND l.branch_id={$branchId}":'');
                $r=$conn->query($overdueQ);$overdue=(int)($r?$r->fetch_assoc()['c']:0);
                $msg="📊 **Live Dashboard**\n";
                $msg.="👥 Members: {$members}".($pendingMem>0?" | ⏳{$pendingMem} pending":''). "\n";
                $msg.="⚠️ Overdue installments: {$overdue}\n";
                $msg.="🏦 Loans:\n";
                foreach($loans as $st=>$d){$icon=['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$st]??'•';$msg.="  {$icon} ".ucfirst($st).": {$d['count']} | TZS ".number_format($d['total'])."\n";}
                $msg.="💰 Savings:\n";
                $msg.="  Saving : TZS ".number_format($savings['saving']??0)."\n";
                $msg.="  Amana  : TZS ".number_format($savings['amana']??0)."\n";
                $msg.="  Shares : TZS ".number_format($savings['share']??0)."\n";
                $msg.="  TOTAL  : TZS ".number_format(($savings['saving']??0)+($savings['amana']??0)+($savings['share']??0));
                return ['ok'=>true,'message'=>$msg,'data'=>compact('members','loans','savings','pendingMem','overdue')];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  LOAN READ TOOLS
        // ══════════════════════════════════════════════════════
        'list_loans' => [
            'description'   => 'List/filter loans. Params: status(pending|approved|rejected|all), search(member name), branch_id, date_from, date_to, sort_by(id|principle|created_at), sort_dir(asc|desc), limit(max 100).',
            'params'        => ['status','search','branch_id','date_from','date_to','sort_by','sort_dir','limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $filters=[];
                if (!empty($p['status'])&&$p['status']!=='all') $filters['status']=$p['status'];
                if (!empty($p['search'])) $filters['search']=$p['search'];
                if (!empty($p['date_from'])) $filters['date1']=chatbotParseDate($p['date_from'])??$p['date_from'];
                if (!empty($p['date_to']))   $filters['date2']=chatbotParseDate($p['date_to'])??$p['date_to'];
                if (!empty($p['branch_id'])) $filters['branch_id']=(int)$p['branch_id'];
                if (!in_array($role,$adminRoles,true)&&$branchId>0&&empty($p['branch_id'])) $filters['branch_id']=$branchId;
                $filters['limit']=min((int)($p['limit']??25),100);
                $sortBy=in_array($p['sort_by']??'',['id','principle','created_at'],true)?$p['sort_by']:'id';
                $sortDir=strtolower($p['sort_dir']??'desc')==='asc'?'ASC':'DESC';
                $rows=selectLoansFiltered($conn,$filters);
                if(!is_array($rows)) return ['ok'=>false,'message'=>'Database query failed.','data'=>null];
                usort($rows,fn($a,$b)=>$sortDir==='ASC'?($a[$sortBy]??0)<=>($b[$sortBy]??0):($b[$sortBy]??0)<=>($a[$sortBy]??0));
                if(empty($rows)) return ['ok'=>true,'message'=>'No loans found.','data'=>[]];
                $lines=[];
                foreach($rows as $r){
                    $si=['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$r['status']]??'•';
                    $lines[]="{$si} ID:{$r['id']} | {$r['member_name']} | {$r['product_name']} | TZS ".number_format((float)$r['principle'])." | Branch:{$r['branch_name']} | ".substr($r['created_at'],0,10);
                }
                return ['ok'=>true,'message'=>count($lines)." loan(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'get_loan_details' => [
            'description'   => 'Full loan details + eligibility + guarantors + schedule. Params: loan_id.',
            'params'        => ['loan_id'],
            'allowed_roles' => $staffRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $loanId=(int)($p['loan_id']??0);
                if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                $loan=selectLoanById($conn,$loanId);
                if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if(!in_array($role,$adminRoles,true)&&$branchId>0&&(int)$loan['branch_id']!==$branchId)
                    return ['ok'=>false,'message'=>'Loan not in your branch.','data'=>null];
                $eligibility=null;
                try{$eligibility=evaluateLoanEligibility($conn,$loanId);}catch(Throwable $e){}
                $grantors=selectLoanGrantorByLoanId($conn,$loanId);
                $schedule=selectLoanScheduleByLoanId($conn,$loanId);
                $si=['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$loan['status']]??'•';
                $msg="{$si} Loan #{$loanId} — {$loan['member_name']}\n";
                $msg.="Product:{$loan['loan_type_name']} | Amount:TZS ".number_format((float)$loan['principle'])." | Period:{$loan['period']}m\n";
                $msg.="Status:{$loan['status']} | Applied:".substr($loan['created_at'],0,10);
                if($loan['approve_date']) $msg.=" | Approved:".substr($loan['approve_date'],0,10);
                if($loan['rejection_reason']) $msg.="\n❌ Reason:{$loan['rejection_reason']}";
                if($eligibility&&empty($eligibility['error'])){
                    $icons=['pass'=>'✅','warning'=>'⚠️','fail'=>'❌'];
                    $rec=['recommended'=>'✅ RECOMMENDED','review_carefully'=>'⚠️ REVIEW','not_recommended'=>'❌ NOT RECOMMENDED'];
                    $msg.="\n\nEligibility: ".($rec[$eligibility['recommendation']]??'UNKNOWN');
                    foreach($eligibility['checks'] as $c)
                        $msg.="\n  ".($icons[$c['status']]??'•')." {$c['label']}: {$c['detail']}";
                    $msg.="\n  Max by savings: TZS ".number_format($eligibility['max_by_savings']);
                }
                if(is_array($grantors)&&count($grantors)){
                    $gl=array_map(fn($g)=>$g['name'].' ('.($g['status']??'pending').')',$grantors);
                    $msg.="\nGuarantors: ".implode(', ',$gl);
                }
                if(is_array($schedule)&&count($schedule)){
                    $paid=count(array_filter($schedule,fn($s)=>($s['status']??'')==='paid'));
                    $total=count($schedule);
                    $overdue=count(array_filter($schedule,fn($s)=>($s['status']??'')==='pending'&&$s['payment_date']<date('Y-m-d')));
                    $msg.="\nSchedule:{$paid}/{$total} paid";
                    if($overdue>0) $msg.=" | ⚠️{$overdue} overdue";
                }
                return ['ok'=>true,'message'=>$msg,'data'=>compact('loan','eligibility','grantors','schedule')];
            },
        ],

        'get_loan_schedule' => [
            'description'   => 'Show full repayment schedule for a loan. Params: loan_id.',
            'params'        => ['loan_id'],
            'allowed_roles' => $staffRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $loanId=(int)($p['loan_id']??0);
                if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                $loan=selectLoanById($conn,$loanId);
                if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if(!in_array($role,$adminRoles,true)&&$branchId>0&&(int)$loan['branch_id']!==$branchId)
                    return ['ok'=>false,'message'=>'Loan not in your branch.','data'=>null];
                $schedule=selectLoanScheduleByLoanId($conn,$loanId);
                if(!is_array($schedule)||empty($schedule)) return ['ok'=>true,'message'=>"No schedule for loan #{$loanId}.",'data'=>[]];
                $totalPaid=0;$totalOwed=0;
                $lines=["📅 Schedule — Loan #{$loanId} ({$loan['member_name']}):\n"];
                foreach($schedule as $i=>$s){
                    $statusIcon=['paid'=>'✅','pending'=>'⏳','partial'=>'🔸'][$s['status']]??'•';
                    $inst=(float)$s['principle']+(float)$s['interest_amount'];
                    $paid=(float)($s['paid_amount']??0);
                    $owed=$inst-$paid;
                    $overdue=($s['status']==='pending'&&$s['payment_date']<date('Y-m-d'))?'⚠️ OVERDUE':'';
                    $lines[]="{$statusIcon} #".($i+1)." | Due:{$s['payment_date']} | Inst:TZS ".number_format($inst)." | Paid:TZS ".number_format($paid)." | Owed:TZS ".number_format($owed)." {$overdue}";
                    if($s['status']==='paid') $totalPaid+=$inst; else $totalOwed+=$owed;
                }
                $lines[]="\nTotal Paid: TZS ".number_format($totalPaid)." | Outstanding: TZS ".number_format($totalOwed);
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$schedule];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  MEMBER READ TOOLS
        // ══════════════════════════════════════════════════════
        'list_members' => [
            'description'   => 'List/filter members. Params: search(name/reg_no/phone), branch_id, status(approved|pending|rejected), sort_by(name|created_at), sort_dir, limit.',
            'params'        => ['search','branch_id','status','sort_by','sort_dir','limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'members','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $scopeBranch=(!in_array($role,$adminRoles,true)&&$branchId>0&&empty($p['branch_id']))?$branchId:(int)($p['branch_id']??0);
                $where=['m.deleted_at IS NULL','u.deleted_at IS NULL'];$params=[];$types='';
                if($scopeBranch>0){$where[]='m.branch_id=?';$types.='i';$params[]=$scopeBranch;}
                if(!empty($p['search'])){
                    $where[]='(u.name LIKE ? OR m.reg_no LIKE ? OR m.phone LIKE ?)';
                    $types.='sss';$like='%'.trim($p['search']).'%';$params[]=$like;$params[]=$like;$params[]=$like;
                }
                if(!empty($p['status'])){
                    $status=chatbotFuzzyMatch($p['status'],['approved','pending','rejected'])??$p['status'];
                    $where[]='m.status=?';$types.='s';$params[]=$status;
                }
                $sortBy=in_array($p['sort_by']??'',['name','created_at'],true)?($p['sort_by']==='name'?'u.name':'m.created_at'):'u.name';
                $sortDir=strtolower($p['sort_dir']??'asc')==='desc'?'DESC':'ASC';
                $limit=min((int)($p['limit']??30),100);
                $sql="SELECT m.id,m.reg_no,m.phone,m.status,m.created_at,u.name,u.email,b.name AS branch_name
                      FROM members m JOIN users u ON u.id=m.user_id LEFT JOIN branches b ON b.id=m.branch_id
                      WHERE ".implode(' AND ',$where)." ORDER BY {$sortBy} {$sortDir} LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed: '.$conn->error,'data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No members found.','data'=>[]];
                $lines=[];
                foreach($rows as $r){
                    $icon=['approved'=>'✅','pending'=>'⏳','rejected'=>'❌'][$r['status']]??'•';
                    $lines[]="{$icon} #{$r['id']} {$r['name']} | Reg:{$r['reg_no']} | Branch:{$r['branch_name']} | Phone:{$r['phone']}";
                }
                return ['ok'=>true,'message'=>count($lines)." member(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'get_member_details' => [
            'description'   => 'Full member profile + savings balances + loan summary. Params: member_id OR reg_no OR name_search.',
            'params'        => ['member_id','reg_no','name_search'],
            'allowed_roles' => $staffRoles,
            'module'=>'members','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];
                $savings=['saving'=>0,'amana'=>0,'share'=>0,'total'=>0];
                if($muid&&function_exists('getMemberTotalSavings')) try{$savings=getMemberTotalSavings($conn,$muid);}catch(Throwable $e){}
                $outstanding=['outstanding_balance'=>0,'active_loan_count'=>0];
                if($muid&&function_exists('getMemberOutstandingLoanBalance')) try{$outstanding=getMemberOutstandingLoanBalance($conn,$muid);}catch(Throwable $e){}
                $age='';
                if(!empty($member['birthdate'])) $age=' | Age:'.floor((time()-strtotime($member['birthdate']))/31536000).'y';
                $msg="👤 {$member['name']} | Reg:{$member['reg_no']} | ID:{$member['id']}\n";
                $msg.="Branch:{$member['branch_name']} | Phone:{$member['phone']} | Gender:{$member['gender']}{$age}\n";
                $msg.="Status:".(['approved'=>'✅ Approved','pending'=>'⏳ Pending','rejected'=>'❌ Rejected'][$member['status']]??$member['status'])." | NIDA:{$member['nida']}\n";
                $msg.="💰 Saving:TZS ".number_format($savings['saving'])." | Amana:TZS ".number_format($savings['amana'])." | Share:TZS ".number_format($savings['share'])." | Total:TZS ".number_format($savings['total'])."\n";
                $msg.="🏦 Active loans:{$outstanding['active_loan_count']} | Outstanding:TZS ".number_format($outstanding['outstanding_balance']);
                return ['ok'=>true,'message'=>$msg,'data'=>compact('member','savings','outstanding')];
            },
        ],

        'get_member_statement' => [
            'description'   => 'View transaction history for a member. Params: member_id OR reg_no OR name_search, category(saving|amana|share|loan|all), date_from, date_to, limit.',
            'params'        => ['member_id','reg_no','name_search','category','date_from','date_to','limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'members','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];
                $category=chatbotFuzzyMatch($p['category']??'all',['saving','amana','share','loan','all'])??'all';
                $limit=min((int)($p['limit']??30),100);
                // Use parameterised category binding to avoid interpolation
                $where=['ms.user_id=?','mt.deleted_at IS NULL'];$types='i';$params=[$muid];
                if($category!=='all'){$where[]='ms.category=?';$types.='s';$params[]=$category;}
                if(!empty($p['date_from'])){$d=chatbotParseDate($p['date_from']);if($d){$where[]='mt.date_>=?';$types.='s';$params[]=$d;}}
                if(!empty($p['date_to'])){$d=chatbotParseDate($p['date_to']);if($d){$where[]='mt.date_<=?';$types.='s';$params[]=$d;}}
                $sql="SELECT mt.date_,mt.description,mt.amount,ms.category,ms.name AS account_name,mt.ref_no
                      FROM min_transactions mt JOIN min_subs ms ON ms.id=mt.dr_account
                      WHERE ".implode(' AND ',$where)."
                      ORDER BY mt.date_ DESC,mt.id DESC LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                $stmt->bind_param($types,...$params);$stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>"No transactions found for {$member['name']}.",'data'=>[]];
                $catIcon=['saving'=>'💵','amana'=>'🏦','share'=>'📈','loan'=>'🏧'];
                $lines=["📄 Statement: {$member['name']} | ".strtoupper($category)."\n"];
                $total=0;
                foreach($rows as $r){
                    $icon=$catIcon[$r['category']]??'•';
                    $lines[]="{$icon} {$r['date_']} | {$r['account_name']} | TZS ".number_format((float)$r['amount'])." | {$r['description']} | Ref:{$r['ref_no']}";
                    $total+=(float)$r['amount'];
                }
                $lines[]="\nTotal shown: TZS ".number_format($total);
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        'my_account' => [
            'description'   => 'Show logged-in user their own savings, loans, and profile. No params needed.',
            'params'        => [],
            'allowed_roles' => $allRoles,
            'module'=>'members','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $stmt=$conn->prepare("SELECT m.*,u.name,u.email,b.name AS branch_name FROM members m JOIN users u ON u.id=m.user_id LEFT JOIN branches b ON b.id=m.branch_id WHERE m.user_id=? AND m.deleted_at IS NULL LIMIT 1");
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                $stmt->bind_param('i',$userId);$stmt->execute();$member=stmt_fetch_assoc($stmt);$stmt->close();
                if(!$member||!is_array($member)) return ['ok'=>false,'message'=>'Your member profile was not found.','data'=>null];
                $savings=['saving'=>0,'amana'=>0,'share'=>0,'total'=>0];
                try{if(function_exists('getMemberTotalSavings'))$savings=getMemberTotalSavings($conn,$userId);}catch(Throwable $e){}
                $outstanding=['outstanding_balance'=>0,'active_loan_count'=>0];
                try{if(function_exists('getMemberOutstandingLoanBalance'))$outstanding=getMemberOutstandingLoanBalance($conn,$userId);}catch(Throwable $e){}
                $loans=[];
                try{$loans=selectLoanByUserId($conn,$userId);}catch(Throwable $e){}
                $msg="👤 Your Account: {$member['name']} | Reg:{$member['reg_no']}\n";
                $msg.="Branch:{$member['branch_name']} | Phone:{$member['phone']}\n";
                $msg.="💰 Saving:TZS ".number_format($savings['saving'])." | Amana:TZS ".number_format($savings['amana'])." | Share:TZS ".number_format($savings['share'])." | Total:TZS ".number_format($savings['total'])."\n";
                if($outstanding['active_loan_count']>0)
                    $msg.="🏦 Active loans:{$outstanding['active_loan_count']} | Outstanding:TZS ".number_format($outstanding['outstanding_balance'])."\n";
                if(is_array($loans)&&count($loans)){
                    $msg.="Loan history:\n";
                    foreach(array_slice($loans,0,5) as $l){
                        $icon=['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$l['status']]??'•';
                        $msg.="  {$icon} TZS ".number_format((float)$l['principle'])." — {$l['status']} (".substr($l['created_at'],0,10).")\n";
                    }
                }
                return ['ok'=>true,'message'=>rtrim($msg),'data'=>compact('member','savings','outstanding')];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  ANALYTICS
        // ══════════════════════════════════════════════════════
        'loan_calculator' => [
            'description'   => 'Calculate repayments. Params: amount, rate(%), period(months), rate_type(monthly|annual).',
            'params'        => ['amount','rate','period','rate_type'],
            'allowed_roles' => $allRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $amount=chatbotParseAmount($p['amount']??'0');$rate=(float)($p['rate']??0);$period=(int)($p['period']??0);
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount.','data'=>null];
                if($rate<=0)   return ['ok'=>false,'message'=>'Provide a valid interest rate.','data'=>null];
                if($period<=0) return ['ok'=>false,'message'=>'Provide a valid period in months.','data'=>null];
                $rateType=strtolower($p['rate_type']??'annual');
                $mr=$rateType==='monthly'?$rate/100:$rate/12/100;
                $mp=$mr>0?$amount*$mr*pow(1+$mr,$period)/(pow(1+$mr,$period)-1):$amount/$period;
                $tr=$mp*$period;$ti=$tr-$amount;
                $fi=$amount*($rate/100);$fm=($amount+$fi)/$period;
                $msg="💰 Loan Calculator\nAmount:TZS ".number_format($amount)." | Rate:{$rate}% ".($rateType==='monthly'?'pm':'pa')." | Period:{$period}m\n\n";
                $msg.="📊 Reducing Balance:\n  Monthly:TZS ".number_format($mp,2)." | Interest:TZS ".number_format($ti,2)." | Total:TZS ".number_format($tr,2)."\n\n";
                $msg.="📊 Flat Rate (system):\n  Monthly:TZS ".number_format($fm,2)." | Interest:TZS ".number_format($fi,2)." | Total:TZS ".number_format($amount+$fi,2);
                return ['ok'=>true,'message'=>$msg,'data'=>compact('amount','rate','period','mp','ti','tr')];
            },
        ],

        'loan_analytics' => [
            'description'   => 'Loan stats grouped by status|branch|product|month. Params: group_by, date_from, date_to, branch_id.',
            'params'        => ['group_by','date_from','date_to','branch_id'],
            'allowed_roles' => $staffRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $groupBy=chatbotFuzzyMatch($p['group_by']??'status',['status','branch','product','month'])??'status';
                $bFilter=(!in_array($role,$adminRoles,true)&&$branchId>0)?$branchId:(int)($p['branch_id']??0);
                $where=['l.deleted_at IS NULL'];$types='';$params=[];
                if($bFilter>0){$where[]='l.branch_id=?';$types.='i';$params[]=$bFilter;}
                if(!empty($p['date_from'])){$d=chatbotParseDate($p['date_from']);if($d){$where[]='l.created_at>=?';$types.='s';$params[]=$d.' 00:00:00';}}
                if(!empty($p['date_to'])){$d=chatbotParseDate($p['date_to']);if($d){$where[]='l.created_at<=?';$types.='s';$params[]=$d.' 23:59:59';}}
                $wStr=implode(' AND ',$where);
                $selMap=['status'=>"l.status AS grp,COUNT(*) AS cnt,COALESCE(SUM(l.principle),0) AS total",'branch'=>"COALESCE(b.name,'Unknown') AS grp,COUNT(*) AS cnt,COALESCE(SUM(l.principle),0) AS total",'product'=>"COALESCE(lt.name,'Unknown') AS grp,COUNT(*) AS cnt,COALESCE(SUM(l.principle),0) AS total",'month'=>"DATE_FORMAT(l.created_at,'%Y-%m') AS grp,COUNT(*) AS cnt,COALESCE(SUM(l.principle),0) AS total"];
                $joinMap=['branch'=>"LEFT JOIN branches b ON b.id=l.branch_id",'product'=>"LEFT JOIN loan_types lt ON lt.id=l.loan_type"];
                $grpMap=['status'=>'l.status','branch'=>'b.name','product'=>'lt.name','month'=>"DATE_FORMAT(l.created_at,'%Y-%m')"];
                $sql="SELECT {$selMap[$groupBy]} FROM loans l ".($joinMap[$groupBy]??'')." WHERE {$wStr} GROUP BY {$grpMap[$groupBy]} ORDER BY total DESC";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Analytics failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No data found.','data'=>[]];
                $tot=array_sum(array_column($rows,'cnt'));$grand=array_sum(array_column($rows,'total'));
                $lines=["📊 Loans by ".strtoupper($groupBy).":\n"];
                foreach($rows as $r){$pct=$tot>0?round($r['cnt']/$tot*100):'—';$lines[]="  ".($r['grp']??'Unknown').": {$r['cnt']} ({$pct}%) | TZS ".number_format((float)$r['total']);}
                $lines[]="\nTotal: {$tot} | TZS ".number_format($grand);
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        'overdue_report' => [
            'description'   => 'Show overdue loan installments. Params: branch_id, limit.',
            'params'        => ['branch_id','limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $bFilter=(!in_array($role,$adminRoles,true)&&$branchId>0)?$branchId:(int)($p['branch_id']??0);
                $limit=min((int)($p['limit']??20),100);
                $where=["ls.status!='paid'","ls.payment_date<CURDATE()","l.deleted_at IS NULL","ls.deleted_at IS NULL"];
                $types='';$params=[];
                if($bFilter>0){$where[]='l.branch_id=?';$types.='i';$params[]=$bFilter;}
                $sql="SELECT u.name AS member_name,l.id AS loan_id,l.principle,ls.id AS schedule_id,
                             ls.payment_date,ls.principle AS inst_principal,ls.interest_amount,ls.paid_amount,
                             DATEDIFF(CURDATE(),ls.payment_date) AS days_overdue
                      FROM loan_schedules ls JOIN loans l ON ls.loan_id=l.id JOIN users u ON u.id=l.user_id
                      WHERE ".implode(' AND ',$where)." ORDER BY days_overdue DESC LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'✅ No overdue installments.','data'=>[]];
                $totalOwed=0;
                $lines=["⚠️ ".count($rows)." overdue installment(s):\n"];
                foreach($rows as $r){
                    $owed=((float)$r['inst_principal']+(float)$r['interest_amount'])-(float)$r['paid_amount'];
                    $totalOwed+=$owed;
                    $lines[]="  Loan#{$r['loan_id']} {$r['member_name']} | Due:{$r['payment_date']} | {$r['days_overdue']}d | Owed:TZS ".number_format($owed);
                }
                $lines[]="\nTotal owed: TZS ".number_format($totalOwed);
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        'search_all' => [
            'description'   => 'Search across members, loans, branches at once. Params: q(query string).',
            'params'        => ['q'],
            'allowed_roles' => $staffRoles,
            'module'=>'members','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $q=trim($p['q']??'');
                if(strlen($q)<2) return ['ok'=>false,'message'=>'Provide at least 2 characters.','data'=>null];
                $like="%{$q}%";$results=[];
                $stmt=$conn->prepare("SELECT 'member' AS type,u.name AS label,m.reg_no AS detail,m.id AS record_id FROM members m JOIN users u ON u.id=m.user_id WHERE (u.name LIKE ? OR m.reg_no LIKE ? OR m.phone LIKE ?) AND m.deleted_at IS NULL LIMIT 5");
                if($stmt){$stmt->bind_param('sss',$like,$like,$like);$stmt->execute();$r=stmt_fetch_all($stmt);$stmt->close();$results=array_merge($results,$r);}
                $stmt=$conn->prepare("SELECT 'loan' AS type,CONCAT(u.name,' — TZS ',FORMAT(l.principle,0)) AS label,l.status AS detail,l.id AS record_id FROM loans l JOIN users u ON u.id=l.user_id WHERE u.name LIKE ? AND l.deleted_at IS NULL LIMIT 5");
                if($stmt){$stmt->bind_param('s',$like);$stmt->execute();$r=stmt_fetch_all($stmt);$stmt->close();$results=array_merge($results,$r);}
                $stmt=$conn->prepare("SELECT 'branch' AS type,name AS label,phone AS detail,id AS record_id FROM branches WHERE name LIKE ? AND deleted_at IS NULL LIMIT 3");
                if($stmt){$stmt->bind_param('s',$like);$stmt->execute();$r=stmt_fetch_all($stmt);$stmt->close();$results=array_merge($results,$r);}
                if(empty($results)) return ['ok'=>true,'message'=>"No results for \"{$q}\".",'data'=>[]];
                $icons=['member'=>'👤','loan'=>'🏦','branch'=>'🏢'];
                $lines=["🔍 Results for \"{$q}\":"];
                foreach($results as $r) $lines[]=($icons[$r['type']]??'•')." ".strtoupper($r['type'])."#{$r['record_id']}: {$r['label']} | {$r['detail']}";
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$results];
            },
        ],

        'check_loan_eligibility' => [
            'description'   => 'Check loan eligibility. Params: member_id OR name_search, amount, period(months), product_id.',
            'params'        => ['member_id','name_search','amount','period','product_id'],
            'allowed_roles' => $allRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $targetUserId=$userId;
                if(!empty($p['member_id'])&&in_array($role,$adminRoles,true)){$m=selectMemberById($conn,(int)$p['member_id']);if($m) $targetUserId=(int)$m['user_id'];}
                elseif(!empty($p['name_search'])&&in_array($role,$adminRoles,true)){$like='%'.trim($p['name_search']).'%';$stmt=$conn->prepare("SELECT m.user_id FROM members m JOIN users u ON u.id=m.user_id WHERE u.name LIKE ? AND m.deleted_at IS NULL LIMIT 1");if($stmt){$stmt->bind_param('s',$like);$stmt->execute();$row=stmt_fetch_assoc($stmt);$stmt->close();if($row)$targetUserId=(int)$row['user_id'];}}
                $amount=chatbotParseAmount($p['amount']??'0');$period=(int)($p['period']??0);$productId=(int)($p['product_id']??0);
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount.','data'=>null];
                if($period<=0) return ['ok'=>false,'message'=>'Provide a valid period in months.','data'=>null];
                if(!function_exists('getLoanAdvisorSuggestion')) return ['ok'=>false,'message'=>'Eligibility function not available.','data'=>null];
                $result=getLoanAdvisorSuggestion($conn,$targetUserId,$amount,$productId,$period);
                $icon=$result['is_affordable']?'✅':'❌';
                $msg="{$icon} Eligibility\nAmount:TZS ".number_format($amount)." | Period:{$period}m\nProduct:{$result['loan_type_name']}\nSavings:TZS ".number_format($result['total_savings'])." | Max:TZS ".number_format($result['max_loan_based_on_savings'])."\nMonthly:TZS ".number_format($result['monthly_payment'],2)." | Interest:TZS ".number_format($result['total_interest'],2)."\nVerdict:{$result['message']}";
                return ['ok'=>true,'message'=>$msg,'data'=>$result];
            },
        ],

        'list_loan_products' => [
            'description'   => 'List loan products. Params: status(active|inactive|all), limit.',
            'params'        => ['status','limit'],
            'allowed_roles' => $allRoles,
            'module'=>'loans','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $sf=$p['status']??'active';$limit=min((int)($p['limit']??20),50);
                $where=['lt.deleted_at IS NULL'];$types='';$params=[];
                if($sf!=='all'){$where[]='lt.status=?';$types.='s';$params[]=$sf;}
                $stmt=$conn->prepare("SELECT lt.* FROM loan_types lt WHERE ".implode(' AND ',$where)." ORDER BY lt.name LIMIT {$limit}");
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No loan products.','data'=>[]];
                $lines=[];
                foreach($rows as $r) $lines[]=($r['status']==='active'?'✅':'⏸')." #{$r['id']} {$r['name']} | Rate:{$r['interest_rate']}% | TZS ".number_format((float)$r['min_amount'])."-".number_format((float)$r['max_amount'])." | {$r['min_period']}-{$r['max_period']}m | Grantors:{$r['required_grantors']}";
                return ['ok'=>true,'message'=>count($lines)." product(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'list_branches' => [
            'description'   => 'List all branches. Params: limit.',
            'params'        => ['limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'branches','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $rows=$conn->query("SELECT b.*,mk.name AS mkoa,(SELECT COUNT(*) FROM members m WHERE m.branch_id=b.id AND m.deleted_at IS NULL) AS member_count,(SELECT COUNT(*) FROM loans l WHERE l.branch_id=b.id AND l.status='pending' AND l.deleted_at IS NULL) AS pending_loans FROM branches b JOIN mikoa mk ON mk.id=b.region WHERE b.deleted_at IS NULL ORDER BY b.name LIMIT ".min((int)($p['limit']??30),100));
                if(!$rows) return ['ok'=>false,'message'=>'Could not load branches.','data'=>null];
                $data=$rows->fetch_all(MYSQLI_ASSOC);
                if(empty($data)) return ['ok'=>true,'message'=>'No branches.','data'=>[]];
                $lines=[];
                foreach($data as $r) $lines[]="🏢 #{$r['id']} {$r['name']} | {$r['mkoa']} | 👥{$r['member_count']} | ⏳{$r['pending_loans']} pending | ☎{$r['phone']}";
                return ['ok'=>true,'message'=>count($lines)." branch(es):\n".implode("\n",$lines),'data'=>$data];
            },
        ],

        'get_branch_details' => [
            'description'   => 'Full details for a specific branch. Params: branch_id.',
            'params'        => ['branch_id'],
            'allowed_roles' => $staffRoles,
            'module'=>'branches','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $bid=(int)($p['branch_id']??$branchId);
                if($bid<=0) return ['ok'=>false,'message'=>'Provide branch_id.','data'=>null];
                $rows=$conn->query("SELECT b.*,mk.name AS mkoa,wi.name AS wilaya FROM branches b JOIN mikoa mk ON mk.id=b.region LEFT JOIN wilaya wi ON wi.id=b.district WHERE b.id={$bid} AND b.deleted_at IS NULL LIMIT 1");
                if(!$rows) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                $branch=$rows->fetch_assoc();
                if(!$branch) return ['ok'=>false,'message'=>"Branch #{$bid} not found.",'data'=>null];
                $r=$conn->query("SELECT COUNT(*) AS c FROM members WHERE branch_id={$bid} AND deleted_at IS NULL");$mCount=(int)($r?$r->fetch_assoc()['c']:0);
                $r=$conn->query("SELECT status,COUNT(*) AS c,COALESCE(SUM(principle),0) AS t FROM loans WHERE branch_id={$bid} AND deleted_at IS NULL GROUP BY status");
                $loans=[];if($r) while($row=$r->fetch_assoc()) $loans[$row['status']]=['count'=>(int)$row['c'],'total'=>(float)$row['t']];
                $msg="🏢 Branch #{$bid}: {$branch['name']}\n";
                $msg.="Region:{$branch['mkoa']} | District:{$branch['wilaya']}\n";
                $msg.="Phone:{$branch['phone']} | Members:{$mCount}\n";
                foreach($loans as $st=>$d){$icon=['pending'=>'⏳','approved'=>'✅','rejected'=>'❌'][$st]??'•';$msg.="{$icon} Loans[{$st}]:{$d['count']} | TZS ".number_format($d['total'])."\n";}
                return ['ok'=>true,'message'=>rtrim($msg),'data'=>compact('branch','mCount','loans')];
            },
        ],

        'list_meetings' => [
            'description'   => 'List meetings. Params: limit, upcoming_only(true|false).',
            'params'        => ['limit','upcoming_only'],
            'allowed_roles' => $staffRoles,
            'module'=>'meetings','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $upOnly=strtolower($p['upcoming_only']??'false')!=='false';
                $limit=min((int)($p['limit']??10),30);
                $where=$upOnly?"WHERE date_>=CURDATE() AND deleted_at IS NULL":"WHERE deleted_at IS NULL";
                $rows=$conn->query("SELECT * FROM meetings {$where} ORDER BY date_ ASC LIMIT {$limit}");
                if(!$rows) return ['ok'=>false,'message'=>'Could not load meetings.','data'=>null];
                $data=$rows->fetch_all(MYSQLI_ASSOC);
                if(empty($data)) return ['ok'=>true,'message'=>'No meetings found.','data'=>[]];
                $lines=[];
                foreach($data as $r) $lines[]="📅 {$r['date_']} — ".($r['title']??$r['agenda']??'Meeting')." | Venue:".($r['venue']??'—')." | Type:".($r['type']??'—');
                return ['ok'=>true,'message'=>count($lines)." meeting(s):\n".implode("\n",$lines),'data'=>$data];
            },
        ],

        'list_transactions' => [
            'description'   => 'List financial transactions. Params: branch_id, member_id OR reg_no OR name_search, date_from, date_to, category(saving|amana|share|loan|all), limit.',
            'params'        => ['branch_id','member_id','reg_no','name_search','date_from','date_to','category','limit'],
            'allowed_roles' => $financeRoles,
            'module'=>'transactions','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $limit=min((int)($p['limit']??30),100);
                $bFilter=(!in_array($role,$adminRoles,true)&&$branchId>0&&empty($p['branch_id']))?$branchId:(int)($p['branch_id']??0);
                $category=chatbotFuzzyMatch($p['category']??'all',['saving','amana','share','loan','all'])??'all';
                $where=['mt.deleted_at IS NULL'];$types='';$params=[];
                if($bFilter>0){$where[]='mt.branch_id=?';$types.='i';$params[]=$bFilter;}
                if($category!=='all'){$where[]='ms.category=?';$types.='s';$params[]=$category;}
                if(!empty($p['date_from'])){$d=chatbotParseDate($p['date_from']);if($d){$where[]='mt.date_>=?';$types.='s';$params[]=$d;}}
                if(!empty($p['date_to'])){$d=chatbotParseDate($p['date_to']);if($d){$where[]='mt.date_<=?';$types.='s';$params[]=$d;}}
                if(!empty($p['member_id'])||!empty($p['reg_no'])||!empty($p['name_search'])){
                    $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                    if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                    $muid=(int)$res['member']['user_id'];
                    $where[]='ms.user_id=?';$types.='i';$params[]=$muid;
                }
                $sql="SELECT mt.id,mt.date_,mt.ref_no,mt.description,mt.amount,mt.status,ms.name AS account_name,ms.category,u.name AS member_name FROM min_transactions mt JOIN min_subs ms ON ms.id=mt.dr_account LEFT JOIN users u ON u.id=ms.user_id WHERE ".implode(' AND ',$where)." ORDER BY mt.date_ DESC,mt.id DESC LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed: '.$conn->error,'data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No transactions found.','data'=>[]];
                $total=array_sum(array_column($rows,'amount'));
                $catIcon=['saving'=>'💵','amana'=>'🏦','share'=>'📈','loan'=>'🏧'];
                $lines=["📋 Transactions (".count($rows)."):\n"];
                foreach($rows as $r) $lines[]=($catIcon[$r['category']]??'•')." {$r['date_']} | ".($r['member_name']??'—')." | {$r['account_name']} | TZS ".number_format((float)$r['amount'])." | {$r['description']}";
                $lines[]="\nTotal: TZS ".number_format($total);
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        'list_users' => [
            'description'   => 'List system users. Params: search(name/email), role, status(active|inactive), limit.',
            'params'        => ['search','role','status','limit'],
            'allowed_roles' => $adminRoles,
            'module'=>'users','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $limit=min((int)($p['limit']??30),100);
                $where=['u.deleted_at IS NULL'];$types='';$params=[];
                if(!empty($p['search'])){$like='%'.trim($p['search']).'%';$where[]='(u.name LIKE ? OR u.email LIKE ?)';$types.='ss';$params[]=$like;$params[]=$like;}
                if(!empty($p['role'])){$where[]='u.role=?';$types.='s';$params[]=$p['role'];}
                if(!empty($p['status'])){$ns=strtolower($p['status'])==='inactive'?'inactive':'active';$where[]='u.status=?';$types.='s';$params[]=$ns;}
                $stmt=$conn->prepare("SELECT u.id,u.name,u.email,u.role,u.status,u.created_at FROM users u WHERE ".implode(' AND ',$where)." ORDER BY u.name LIMIT {$limit}");
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No users found.','data'=>[]];
                $lines=[];
                foreach($rows as $r) $lines[]=($r['status']==='active'?'✅':'🚫')." #{$r['id']} {$r['name']} | {$r['email']} | Role:{$r['role']}";
                return ['ok'=>true,'message'=>count($lines)." user(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'list_budgets' => [
            'description'   => 'List budgets. Params: branch_id, date_from, date_to, limit.',
            'params'        => ['branch_id','date_from','date_to','limit'],
            'allowed_roles' => $staffRoles,
            'module'=>'budgets','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $limit=min((int)($p['limit']??20),50);
                $bFilter=(!in_array($role,$adminRoles,true)&&$branchId>0&&empty($p['branch_id']))?$branchId:(int)($p['branch_id']??0);
                $where=['deleted_at IS NULL'];$types='';$params=[];
                if($bFilter>0){$where[]='branch_id=?';$types.='i';$params[]=$bFilter;}
                $stmt=$conn->prepare("SELECT * FROM budgets WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC LIMIT {$limit}");
                if(!$stmt){$rows=$conn->query("SELECT * FROM budgets WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT {$limit}");$data=$rows?$rows->fetch_all(MYSQLI_ASSOC):[];}
                else{if($params)$stmt->bind_param($types,...$params);$stmt->execute();$data=stmt_fetch_all($stmt);$stmt->close();}
                if(empty($data)) return ['ok'=>true,'message'=>'No budgets found.','data'=>[]];
                $lines=[];
                foreach($data as $r) $lines[]="📋 #{$r['id']} ".($r['title']??$r['name']??'Budget')." | TZS ".number_format((float)($r['amount']??$r['total']??0))." | ".substr($r['created_at']??'',0,10);
                return ['ok'=>true,'message'=>count($lines)." budget(s):\n".implode("\n",$lines),'data'=>$data];
            },
        ],

        'list_subsidiaries' => [
            'description'   => 'List ledger sub-accounts. Params: category(saving|amana|share|loan), member_id OR name_search, limit.',
            'params'        => ['category','member_id','name_search','limit'],
            'allowed_roles' => $financeRoles,
            'module'=>'subsidiaries','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $limit=min((int)($p['limit']??30),100);
                $where=['ms.deleted_at IS NULL'];$types='';$params=[];
                if(!empty($p['category'])){$where[]='ms.category=?';$types.='s';$params[]=$p['category'];}
                if(!empty($p['member_id'])||!empty($p['name_search'])){
                    $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                    if(!isset($res['error'])){$muid=(int)$res['member']['user_id'];$where[]='ms.user_id=?';$types.='i';$params[]=$muid;}
                }
                $sql="SELECT ms.*,u.name AS member_name FROM min_subs ms LEFT JOIN users u ON u.id=ms.user_id WHERE ".implode(' AND ',$where)." ORDER BY ms.name LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No accounts found.','data'=>[]];
                $catIcon=['saving'=>'💵','amana'=>'🏦','share'=>'📈','loan'=>'🏧'];
                $lines=[];
                foreach($rows as $r) $lines[]=($catIcon[$r['category']]??'📁')." #{$r['id']} {$r['name']} | Cat:{$r['category']}".($r['member_name']?" | {$r['member_name']}":'');
                return ['ok'=>true,'message'=>count($lines)." account(s):\n".implode("\n",$lines),'data'=>$rows];
            },
        ],

        'get_subsidiary_balance' => [
            'description'   => 'Get current balance of a sub-account. Params: sub_account_id.',
            'params'        => ['sub_account_id'],
            'allowed_roles' => $financeRoles,
            'module'=>'transactions','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $subId=(int)($p['sub_account_id']??0);
                if($subId<=0) return ['ok'=>false,'message'=>'Provide sub_account_id.','data'=>null];
                $sub=getMinSubById($conn,$subId);
                if(!$sub||!is_array($sub)) return ['ok'=>false,'message'=>"Account #{$subId} not found.",'data'=>null];
                $r=$conn->query("SELECT COALESCE(SUM(amount),0) AS balance FROM min_transactions WHERE dr_account={$subId} AND deleted_at IS NULL");
                $dr=(float)($r?$r->fetch_assoc()['balance']:0);
                $r=$conn->query("SELECT COALESCE(SUM(amount),0) AS balance FROM min_transactions WHERE cr_account={$subId} AND deleted_at IS NULL");
                $cr=(float)($r?$r->fetch_assoc()['balance']:0);
                $balance=$sub['type']==='debit'?$dr-$cr:$cr-$dr;
                $msg="💰 Account: {$sub['name']}\nID:{$subId} | Category:{$sub['category']}\nDebits: TZS ".number_format($dr)." | Credits: TZS ".number_format($cr)."\nNet Balance: TZS ".number_format($balance);
                return ['ok'=>true,'message'=>$msg,'data'=>compact('sub','dr','cr','balance')];
            },
        ],

        'list_notifications' => [
            'description'   => 'List notifications for the current user or a specific user (admins). Params: user_id(admin only), unread_only(true|false), limit.',
            'params'        => ['user_id','unread_only','limit'],
            'allowed_roles' => $allRoles,
            'module'=>'notifications','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $limit=min((int)($p['limit']??15),50);
                $targetUser=(!empty($p['user_id'])&&in_array($role,$adminRoles,true))?(int)$p['user_id']:$userId;
                $unreadOnly=strtolower($p['unread_only']??'false')!=='false';
                $where=['user_id=?','deleted_at IS NULL'];$types='i';$params=[$targetUser];
                if($unreadOnly){$where[]="read_at IS NULL";}
                $stmt=$conn->prepare("SELECT * FROM system_notifications WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC LIMIT {$limit}");
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                $stmt->bind_param($types,...$params);$stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No notifications found.','data'=>[]];
                $typeIcon=['success'=>'✅','danger'=>'❌','warning'=>'⚠️','info'=>'ℹ️'];
                $lines=["🔔 Notifications (".count($rows)."):\n"];
                foreach($rows as $r){
                    $icon=$typeIcon[$r['type']]??'•';$read=$r['read_at']?'(read)':'⚡ NEW';
                    $lines[]="{$icon} {$r['title']} | {$read} | ".substr($r['created_at'],0,16);
                }
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        'list_audit_trail' => [
            'description'   => 'View audit trail (admin only). Params: user_id, module, action, date_from, date_to, limit.',
            'params'        => ['user_id','module','action','date_from','date_to','limit'],
            'allowed_roles' => $adminRoles,
            'module'=>'audit','permission'=>'can_view','is_write'=>false,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $limit=min((int)($p['limit']??25),100);
                $where=['at.deleted_at IS NULL'];$types='';$params=[];
                if(!empty($p['user_id'])){$where[]='at.user_id=?';$types.='i';$params[]=(int)$p['user_id'];}
                if(!empty($p['module'])){$where[]='at.module=?';$types.='s';$params[]=$p['module'];}
                if(!empty($p['action'])){$where[]='at.action=?';$types.='s';$params[]=$p['action'];}
                if(!empty($p['date_from'])){$d=chatbotParseDate($p['date_from']);if($d){$where[]='at.created_at>=?';$types.='s';$params[]=$d.' 00:00:00';}}
                if(!empty($p['date_to'])){$d=chatbotParseDate($p['date_to']);if($d){$where[]='at.created_at<=?';$types.='s';$params[]=$d.' 23:59:59';}}
                $sql="SELECT at.*,u.name AS actor FROM audit_trail at LEFT JOIN users u ON u.id=at.user_id WHERE ".implode(' AND ',$where)." ORDER BY at.created_at DESC LIMIT {$limit}";
                $stmt=$conn->prepare($sql);
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                if($params) $stmt->bind_param($types,...$params);
                $stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(empty($rows)) return ['ok'=>true,'message'=>'No audit records found.','data'=>[]];
                $lines=["📋 Audit Trail (".count($rows)."):\n"];
                foreach($rows as $r) $lines[]="  ".substr($r['created_at'],0,16)." | {$r['actor']} | {$r['module']}.{$r['action']} | ".($r['detail']??$r['notes']??'');
                return ['ok'=>true,'message'=>implode("\n",$lines),'data'=>$rows];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  FINANCIAL WRITE TOOLS
        // ══════════════════════════════════════════════════════
        'deposit_savings' => [
            'description'   => 'Deposit into member savings/amana/share account. Params: member_id OR reg_no OR name_search, category(saving|amana|share), amount, date(default today), description.',
            'params'        => ['member_id','reg_no','name_search','category','amount','date','description'],
            'allowed_roles' => $financeRoles,
            'module'=>'transactions','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];$mbranchId=(int)($member['branch_id']??$branchId);
                $category=chatbotFuzzyMatch(strtolower($p['category']??'saving'),['saving','amana','share']);
                if(!$category) return ['ok'=>false,'message'=>'category must be saving, amana, or share.','data'=>null];
                $amount=chatbotParseAmount($p['amount']??'0');
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount > 0.','data'=>null];
                $date=chatbotParseDate($p['date']??'today')??date('Y-m-d');
                $desc=trim($p['description']??ucfirst($category).' deposit via chatbot');
                $sub=selectMinSubByUserIDAndCategory($conn,$muid,$category);
                if(!$sub||!is_array($sub)) return ['ok'=>false,'message'=>"Member has no {$category} account.",'data'=>null];
                $subId=(int)$sub['id'];
                $crAccount=chatbotGetSavingGLCrAccount($conn,$category);
                $cashStmt=$conn->prepare("SELECT id FROM min_subs WHERE user_id IS NULL AND branch_id=? AND category=? AND deleted_at IS NULL LIMIT 1");
                if($cashStmt){$cashStmt->bind_param('is',$mbranchId,$category);$cashStmt->execute();$cr=stmt_fetch_assoc($cashStmt);$cashStmt->close();if($cr&&!empty($cr['id']))$crAccount=(int)$cr['id'];}
                $ref='DEP/'.strtoupper(substr($category,0,3)).'/'.date('Ymd').'/'.$muid;
                $txId=createMinTransaction($conn,$ref,$subId,$desc,$amount,$crAccount,$date,$userId,$mbranchId,'active');
                if(!is_numeric($txId)||$txId<=0) return ['ok'=>false,'message'=>"Transaction failed. Error:{$txId}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','transactions',(int)$txId,"[Chatbot] Deposited TZS ".number_format($amount)." into {$member['name']}'s {$category}",[],['amount'=>$amount,'category'=>$category,'member_id'=>$member['id']]);
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$muid,ucfirst($category).' Deposit','TZS '.number_format($amount).' deposited into your '.ucfirst($category).' account on '.$date.'.','success','./?page=my_loan');}catch(Throwable $e){}
                return ['ok'=>true,'message'=>"✅ TZS ".number_format($amount)." deposited into {$member['name']}'s ".ucfirst($category)." account.\nRef:{$ref} | Date:{$date}",'data'=>['tx_id'=>$txId,'ref'=>$ref]];
            },
        ],

        'withdraw_savings' => [
            'description'   => 'Withdraw from member savings/amana/share account. Params: member_id OR reg_no OR name_search, category(saving|amana|share), amount, date(default today), description.',
            'params'        => ['member_id','reg_no','name_search','category','amount','date','description'],
            'allowed_roles' => $financeRoles,
            'module'=>'transactions','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];$mbranchId=(int)($member['branch_id']??$branchId);
                $category=chatbotFuzzyMatch(strtolower($p['category']??'saving'),['saving','amana','share']);
                if(!$category) return ['ok'=>false,'message'=>'category must be saving, amana, or share.','data'=>null];
                $amount=chatbotParseAmount($p['amount']??'0');
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount > 0.','data'=>null];
                $currentBalance=0;
                if(function_exists('getMemberTotalSavings')) try{$sav=getMemberTotalSavings($conn,$muid);$currentBalance=(float)($sav[$category]??0);}catch(Throwable $e){}
                if($currentBalance<$amount) return ['ok'=>false,'message'=>"Insufficient balance. Current {$category}: TZS ".number_format($currentBalance)." | Requested: TZS ".number_format($amount),'data'=>null];
                $date=chatbotParseDate($p['date']??'today')??date('Y-m-d');
                $desc=trim($p['description']??ucfirst($category).' withdrawal via chatbot');
                $sub=selectMinSubByUserIDAndCategory($conn,$muid,$category);
                if(!$sub||!is_array($sub)) return ['ok'=>false,'message'=>"No {$category} account for this member.",'data'=>null];
                $subId=(int)$sub['id'];
                $crAccount=chatbotGetSavingGLCrAccount($conn,$category);
                $cashStmt=$conn->prepare("SELECT id FROM min_subs WHERE user_id IS NULL AND branch_id=? AND category=? AND deleted_at IS NULL LIMIT 1");
                if($cashStmt){$cashStmt->bind_param('is',$mbranchId,$category);$cashStmt->execute();$cr=stmt_fetch_assoc($cashStmt);$cashStmt->close();if($cr&&!empty($cr['id']))$crAccount=(int)$cr['id'];}
                $ref='WTH/'.strtoupper(substr($category,0,3)).'/'.date('Ymd').'/'.$muid;
                $txId=createMinTransaction($conn,$ref,$crAccount,$desc,$amount,$subId,$date,$userId,$mbranchId,'active');
                if(!is_numeric($txId)||$txId<=0) return ['ok'=>false,'message'=>"Transaction failed. Error:{$txId}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','transactions',(int)$txId,"[Chatbot] Withdrew TZS ".number_format($amount)." from {$member['name']}'s {$category}",[],['amount'=>$amount,'category'=>$category,'member_id'=>$member['id']]);
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$muid,ucfirst($category).' Withdrawal','TZS '.number_format($amount).' withdrawn from your '.ucfirst($category).' account on '.$date.'.','warning','./?page=my_loan');}catch(Throwable $e){}
                return ['ok'=>true,'message'=>"✅ TZS ".number_format($amount)." withdrawn from {$member['name']}'s ".ucfirst($category).".\nRef:{$ref} | Date:{$date}\nNew balance: TZS ".number_format($currentBalance-$amount),'data'=>['tx_id'=>$txId,'ref'=>$ref]];
            },
        ],

        'savings_adjustment' => [
            'description'   => 'Manually adjust (correct) a savings balance with a reason. Params: member_id OR name_search, category(saving|amana|share), adjustment_amount(positive=increase, negative=decrease), reason.',
            'params'        => ['member_id','reg_no','name_search','category','adjustment_amount','reason'],
            'allowed_roles' => $adminRoles,
            'module'=>'transactions','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];$mbranchId=(int)($member['branch_id']??$branchId);
                $category=chatbotFuzzyMatch(strtolower($p['category']??'saving'),['saving','amana','share']);
                if(!$category) return ['ok'=>false,'message'=>'category must be saving, amana, or share.','data'=>null];
                $rawAdj=trim($p['adjustment_amount']??'0');
                $negative=($rawAdj[0]??'')==='-';
                $adjAmount=chatbotParseAmount(ltrim($rawAdj,'-'));
                if($adjAmount<=0) return ['ok'=>false,'message'=>'Provide a valid adjustment_amount.','data'=>null];
                $reason=trim($p['reason']??'Manual adjustment via chatbot');
                $sub=selectMinSubByUserIDAndCategory($conn,$muid,$category);
                if(!$sub||!is_array($sub)) return ['ok'=>false,'message'=>"No {$category} account for this member.",'data'=>null];
                $subId=(int)$sub['id'];$crAccount=chatbotGetSavingGLCrAccount($conn,$category);
                $ref='ADJ/'.strtoupper(substr($category,0,3)).'/'.date('Ymd').'/'.$muid;
                $date=date('Y-m-d');
                $desc="Adjustment: {$reason}";
                if(!$negative){$txId=createMinTransaction($conn,$ref,$subId,$desc,$adjAmount,$crAccount,$date,$userId,$mbranchId,'active');}
                else{$txId=createMinTransaction($conn,$ref,$crAccount,$desc,$adjAmount,$subId,$date,$userId,$mbranchId,'active');}
                if(!is_numeric($txId)||$txId<=0) return ['ok'=>false,'message'=>"Adjustment failed.",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','transactions',(int)$txId,"[Chatbot] Adjustment ".($negative?'-':'+')."TZS ".number_format($adjAmount)." for {$member['name']}'s {$category}: {$reason}",[],[]);
                return ['ok'=>true,'message'=>"✅ Adjustment applied to {$member['name']}'s ".ucfirst($category).".\nChange: ".($negative?'-':'+')."TZS ".number_format($adjAmount)." | Ref:{$ref}",'data'=>['tx_id'=>$txId]];
            },
        ],

        'record_loan_repayment' => [
            'description'   => 'Record a loan repayment. Params: loan_id, amount, date(default today), payment_method(cash|mobile|bank), notes.',
            'params'        => ['loan_id','amount','date','payment_method','notes'],
            'allowed_roles' => $financeRoles,
            'module'=>'loans','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $loanId=(int)($p['loan_id']??0);
                if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                $amount=chatbotParseAmount($p['amount']??'0');
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount.','data'=>null];
                $loan=selectLoanById($conn,$loanId);
                if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if($loan['status']!=='approved') return ['ok'=>false,'message'=>"Loan #{$loanId} is not active (status:{$loan['status']}).",'data'=>null];
                if(!in_array($role,$adminRoles,true)&&$branchId>0&&(int)$loan['branch_id']!==$branchId) return ['ok'=>false,'message'=>'Loan not in your branch.','data'=>null];
                $date=chatbotParseDate($p['date']??'today')??date('Y-m-d');
                $method=chatbotFuzzyMatch(strtolower($p['payment_method']??'cash'),['cash','mobile','bank'])??'cash';
                $notes=trim($p['notes']??'Repayment via chatbot');
                $schedule=selectLoanScheduleByLoanId($conn,$loanId);
                if(!is_array($schedule)||empty($schedule)) return ['ok'=>false,'message'=>"No schedule for loan #{$loanId}.",'data'=>null];
                $unpaid=array_filter($schedule,fn($s)=>($s['status']??'')==='pending'||($s['status']??'')==='partial');
                if(empty($unpaid)) return ['ok'=>false,'message'=>"All installments for loan #{$loanId} already paid. 🎉",'data'=>null];
                usort($unpaid,fn($a,$b)=>strcmp($a['payment_date'],$b['payment_date']));
                $nextInst=reset($unpaid);$scheduleId=(int)$nextInst['id'];
                $instTotal=(float)$nextInst['principle']+(float)$nextInst['interest_amount'];
                $alreadyPaid=(float)($nextInst['paid_amount']??0);$remaining=$instTotal-$alreadyPaid;
                $membUserId=(int)$loan['user_id'];$loanBranchId=(int)$loan['branch_id'];
                $loanSub=selectMinSubByUserIDAndCategory($conn,$membUserId,'loan');
                if(!$loanSub||!is_array($loanSub)) return ['ok'=>false,'message'=>"Member loan sub-account not found.",'data'=>null];
                $ref='RPY/'.date('Ymd').'/'.$loanId;
                $txId=createMinTransaction($conn,$ref,(int)$loanSub['id'],"Repayment: {$notes} [{$method}]",$amount,3027,$date,$userId,$loanBranchId,'active');
                if(!is_numeric($txId)||$txId<=0) return ['ok'=>false,'message'=>"Transaction failed.",'data'=>null];
                $newPaid=$alreadyPaid+$amount;
                $newStatus=$newPaid>=$instTotal?'paid':'partial';
                $upStmt=$conn->prepare("UPDATE loan_schedules SET paid_amount=?,status=?,updated_at=NOW() WHERE id=?");
                if($upStmt){$upStmt->bind_param('dsi',$newPaid,$newStatus,$scheduleId);$upStmt->execute();$upStmt->close();}
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','loans',$loanId,"[Chatbot] Repayment TZS ".number_format($amount)." for loan #{$loanId}",[],['amount'=>$amount,'schedule_id'=>$scheduleId,'status'=>$newStatus]);
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$membUserId,'Loan Repayment Received','TZS '.number_format($amount).' received for Loan #'.$loanId.'.','success','./?page=my_loan');}catch(Throwable $e){}
                $msg="✅ Repayment for Loan #{$loanId} ({$loan['member_name']})\nAmount:TZS ".number_format($amount)." | Method:{$method} | Date:{$date}\nInstallment:".($newStatus==='paid'?'FULLY PAID ✅':'partial, still owed TZS '.number_format($remaining-$amount))."\nRef:{$ref}";
                return ['ok'=>true,'message'=>$msg,'data'=>['tx_id'=>$txId,'schedule_id'=>$scheduleId,'status'=>$newStatus]];
            },
        ],

        'mark_installment_paid' => [
            'description'   => 'Mark a specific schedule installment as paid. Params: schedule_id, amount(optional), date(default today).',
            'params'        => ['schedule_id','amount','date'],
            'allowed_roles' => $financeRoles,
            'module'=>'loans','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $schedId=(int)($p['schedule_id']??0);
                if($schedId<=0) return ['ok'=>false,'message'=>'Provide schedule_id.','data'=>null];
                $stmt=$conn->prepare("SELECT ls.*,l.user_id,l.branch_id,l.status AS loan_status,u.name AS member_name FROM loan_schedules ls JOIN loans l ON l.id=ls.loan_id JOIN users u ON u.id=l.user_id WHERE ls.id=? LIMIT 1");
                if(!$stmt) return ['ok'=>false,'message'=>'Query failed.','data'=>null];
                $stmt->bind_param('i',$schedId);$stmt->execute();$row=stmt_fetch_assoc($stmt);$stmt->close();
                if(!$row||!is_array($row)) return ['ok'=>false,'message'=>"Installment #{$schedId} not found.",'data'=>null];
                if($row['status']==='paid') return ['ok'=>false,'message'=>"Already paid.",'data'=>null];
                if($row['loan_status']!=='approved') return ['ok'=>false,'message'=>"Loan is not active.",'data'=>null];
                if(!in_array($role,$adminRoles,true)&&$branchId>0&&(int)$row['branch_id']!==$branchId) return ['ok'=>false,'message'=>'Not in your branch.','data'=>null];
                $fullAmount=(float)$row['principle']+(float)$row['interest_amount'];
                $amount=isset($p['amount'])&&$p['amount']!==''?chatbotParseAmount($p['amount']):$fullAmount;
                $date=chatbotParseDate($p['date']??'today')??date('Y-m-d');
                $upStmt=$conn->prepare("UPDATE loan_schedules SET paid_amount=?,status='paid',updated_at=NOW() WHERE id=?");
                if(!$upStmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                $upStmt->bind_param('di',$amount,$schedId);$upStmt->execute();$upStmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','loans',(int)$row['loan_id'],"[Chatbot] Marked installment #{$schedId} paid for {$row['member_name']}",[],['schedule_id'=>$schedId,'amount'=>$amount]);
                return ['ok'=>true,'message'=>"✅ Installment #{$schedId} marked paid for {$row['member_name']}.\nAmount:TZS ".number_format($amount)." | Date:{$date}",'data'=>null];
            },
        ],

        'create_financial_transaction' => [
            'description'   => 'Advanced: raw transaction between any two sub-accounts. Params: dr_account_id, cr_account_id, amount, description, date, ref_no.',
            'params'        => ['dr_account_id','cr_account_id','amount','description','date','ref_no'],
            'allowed_roles' => $financeRoles,
            'module'=>'transactions','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $drId=(int)($p['dr_account_id']??0);$crId=(int)($p['cr_account_id']??0);
                if($drId<=0||$crId<=0) return ['ok'=>false,'message'=>'Provide dr_account_id and cr_account_id.','data'=>null];
                if($drId===$crId) return ['ok'=>false,'message'=>'DR and CR accounts must differ.','data'=>null];
                $amount=chatbotParseAmount($p['amount']??'0');
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount > 0.','data'=>null];
                $drSub=getMinSubById($conn,$drId);$crSub=getMinSubById($conn,$crId);
                if(!$drSub||!is_array($drSub)) return ['ok'=>false,'message'=>"DR account #{$drId} not found.",'data'=>null];
                if(!$crSub||!is_array($crSub)) return ['ok'=>false,'message'=>"CR account #{$crId} not found.",'data'=>null];
                $date=chatbotParseDate($p['date']??'today')??date('Y-m-d');
                $desc=trim($p['description']??'Manual transaction via chatbot');
                $ref=trim($p['ref_no']??'CHAT/'.date('YmdHis'));
                $txId=createMinTransaction($conn,$ref,$drId,$desc,$amount,$crId,$date,$userId,$branchId,'active');
                if(!is_numeric($txId)||$txId<=0) return ['ok'=>false,'message'=>"Transaction failed.",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','transactions',(int)$txId,"[Chatbot] Manual tx DR#{$drId} CR#{$crId} TZS ".number_format($amount),[],['dr'=>$drId,'cr'=>$crId,'amount'=>$amount,'ref'=>$ref]);
                return ['ok'=>true,'message'=>"✅ Transaction created.\nDR:{$drSub['name']} → CR:{$crSub['name']}\nAmount:TZS ".number_format($amount)." | Ref:{$ref} | Date:{$date}",'data'=>['tx_id'=>$txId,'ref'=>$ref]];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  LOAN WRITE TOOLS
        // ══════════════════════════════════════════════════════
        'approve_loan' => [
            'description'   => 'Approve a pending loan. Params: loan_id, interest_rate(%), approve_date(default today).',
            'params'        => ['loan_id','interest_rate','approve_date'],
            'allowed_roles' => $adminRoles,
            'module'=>'loans','permission'=>'can_approve','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId=(int)($p['loan_id']??0);$ir=(float)($p['interest_rate']??0);
                $approveDate=chatbotParseDate($p['approve_date']??'today')??date('Y-m-d');
                if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                if($ir<=0) return ['ok'=>false,'message'=>'Provide interest_rate (%).','data'=>null];
                $loan=selectLoanById($conn,$loanId);
                if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if($loan['status']==='approved') return ['ok'=>false,'message'=>"Already approved.",'data'=>null];
                if($loan['status']==='rejected') return ['ok'=>false,'message'=>"Already rejected.",'data'=>null];
                $principle=(float)$loan['principle'];$loanTerm=(int)$loan['period'];
                $membUserId=(int)$loan['user_id'];$loanBranchId=(int)$loan['branch_id'];
                $interestAmount=$principle*($ir/100);
                $LoanSub=selectMinSubByUserIDAndCategory($conn,$membUserId,'loan');
                if(!$LoanSub||!is_array($LoanSub)) return ['ok'=>false,'message'=>'Loan sub-account not found.','data'=>null];
                $ref="LAJV/{$loanId}";
                if(!createMinTransaction($conn,$ref,(int)$LoanSub['id'],'approved loan:principle',$principle,3028,$approveDate,$userId,$loanBranchId,'active')) return ['ok'=>false,'message'=>'Failed to create principal entry.','data'=>null];
                if(!createMinTransaction($conn,$ref,(int)$LoanSub['id'],'approved loan:interest',$interestAmount,3027,$approveDate,$userId,$loanBranchId,'active')) return ['ok'=>false,'message'=>'Failed to create interest entry.','data'=>null];
                $approved=approveLoan($conn,$loanId,$interestAmount,$ir,'approved',$approveDate,$userId);
                if($approved!==true) return ['ok'=>false,'message'=>"approveLoan failed: {$approved}",'data'=>null];
                try{$snap=evaluateLoanEligibility($conn,$loanId);$st=$conn->prepare('UPDATE loans SET eligibility_snapshot=? WHERE id=?');if($st){$sj=json_encode($snap);$st->bind_param('si',$sj,$loanId);$st->execute();}}catch(Throwable $e){}
                $schedule=generateSchedule($principle,$ir,$loanTerm,'month',$approveDate);
                if($schedule&&is_array($schedule)) foreach($schedule as $rep) insertSchedule($conn,$membUserId,$loanBranchId,$loanId,$rep['principle'],$rep['interest'],$rep['repayment_date'],0.0,'pending');
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$membUserId,'Loan Approved',"Your loan of TZS ".number_format($principle)." has been approved.",'success','./?page=my_loan');}catch(Throwable $e){}
                if(function_exists('logAudit')) logAudit($conn,$userId,'approve','loans',$loanId,"[Chatbot] Approved loan #{$loanId}",['status'=>$loan['status']],['status'=>'approved','interest_rate'=>$ir,'approve_date'=>$approveDate]);
                return ['ok'=>true,'message'=>"✅ Loan #{$loanId} (TZS ".number_format($principle).") approved at {$ir}%. {$loanTerm} installments created. Member notified.",'data'=>null];
            },
        ],

        'reject_loan' => [
            'description'   => 'Reject a pending loan. Params: loan_id, reason.',
            'params'        => ['loan_id','reason'],
            'allowed_roles' => $adminRoles,
            'module'=>'loans','permission'=>'can_approve','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId=(int)($p['loan_id']??0);$reason=trim($p['reason']??'');
                if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                if($reason==='') return ['ok'=>false,'message'=>'Provide a rejection reason.','data'=>null];
                $loan=selectLoanById($conn,$loanId);
                if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                if($loan['status']==='rejected') return ['ok'=>false,'message'=>"Already rejected.",'data'=>null];
                if($loan['status']==='approved') return ['ok'=>false,'message'=>"Cannot reject an approved loan.",'data'=>null];
                $result=rejectLoan($conn,$loanId,$reason,$userId);
                if($result!==true) return ['ok'=>false,'message'=>"Failed: {$result}",'data'=>null];
                $membUserId=(int)($loan['user_id']??0);
                if($membUserId&&function_exists('createSystemNotification')) try{createSystemNotification($conn,$membUserId,'Loan Rejected',"Your loan (TZS ".number_format((float)$loan['principle']).") was rejected. Reason:{$reason}",'danger','./?page=my_loan');}catch(Throwable $e){}
                if(function_exists('logAudit')) logAudit($conn,$userId,'reject','loans',$loanId,"[Chatbot] Rejected #{$loanId}",['status'=>$loan['status']],['status'=>'rejected','reason'=>$reason]);
                return ['ok'=>true,'message'=>"✅ Loan #{$loanId} rejected. Member notified.",'data'=>null];
            },
        ],

        'create_loan_for_member' => [
            'description'   => 'Submit a loan application on behalf of a member (staff). Params: member_id OR reg_no OR name_search, amount, period(months), loan_type_id, repayment_mode(salary|standing_order).',
            'params'        => ['member_id','reg_no','name_search','amount','period','loan_type_id','repayment_mode'],
            'allowed_roles' => $financeRoles,
            'module'=>'loans','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];$mbranchId=(int)($member['branch_id']??$branchId);
                if(($member['status']??'')!=='approved') return ['ok'=>false,'message'=>"Member is not approved (status:{$member['status']}).",'data'=>null];
                $amount=chatbotParseAmount($p['amount']??'0');$period=(int)($p['period']??0);
                $loanTypeId=(int)($p['loan_type_id']??0);$repMode=chatbotFuzzyMatch(strtolower($p['repayment_mode']??'salary'),['salary','standing_order'])??'salary';
                if($amount<=0) return ['ok'=>false,'message'=>'Provide a valid amount.','data'=>null];
                if($period<=0) return ['ok'=>false,'message'=>'Provide a valid period in months.','data'=>null];
                if(!function_exists('insertLoan')) return ['ok'=>false,'message'=>'insertLoan() function not available.','data'=>null];
                $newLoanId=insertLoan($conn,$mbranchId,$muid,$amount,0.0,0.0,$period,'pending',$repMode,null,$loanTypeId>0?$loanTypeId:null);
                if(!is_numeric($newLoanId)||$newLoanId<=0) return ['ok'=>false,'message'=>"Failed to submit loan.",'data'=>null];
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$muid,'Loan Application Submitted','Your application of TZS '.number_format($amount).' is under review.','info','./?page=my_loan');}catch(Throwable $e){}
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','loans',$newLoanId,"[Chatbot] Submitted loan for {$member['name']}",[],['amount'=>$amount,'period'=>$period,'member_id'=>$member['id']]);
                return ['ok'=>true,'message'=>"✅ Loan #{$newLoanId} submitted for {$member['name']}.\nAmount:TZS ".number_format($amount)." | Period:{$period}m | Mode:{$repMode}",'data'=>['loan_id'=>$newLoanId]];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  MEMBER WRITE TOOLS
        // ══════════════════════════════════════════════════════
        'approve_member' => [
            'description'   => 'Approve a pending member. Params: member_id OR reg_no OR name_search.',
            'params'        => ['member_id','reg_no','name_search'],
            'allowed_roles' => $adminRoles,
            'module'=>'members','permission'=>'can_approve','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];
                if(($member['status']??'')!=='pending') return ['ok'=>false,'message'=>"Status is '{$member['status']}', not pending.",'data'=>null];
                $membUserId=(int)$member['user_id'];
                $result=updateMember($conn,$membUserId,$member['phone'],$member['birthdate'],$member['nida'],$member['gender'],'approved');
                if($result!==true) return ['ok'=>false,'message'=>"Failed: {$result}",'data'=>null];
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$membUserId,'Membership Approved',"Welcome! Your SACCOS membership is approved.",'success','./?page=my_loan');}catch(Throwable $e){}
                if(function_exists('logAudit')) logAudit($conn,$userId,'approve','members',(int)$member['id'],"[Chatbot] Approved {$member['name']}",['status'=>'pending'],['status'=>'approved']);
                return ['ok'=>true,'message'=>"✅ {$member['name']} approved as a member. They have been notified.",'data'=>null];
            },
        ],

        'bulk_approve_members' => [
            'description'   => 'Approve all pending members in a branch. Params: branch_id(required for non-admins), confirm_count(optional safety check).',
            'params'        => ['branch_id','confirm_count'],
            'allowed_roles' => $adminRoles,
            'module'=>'members','permission'=>'can_approve','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) use ($adminRoles) {
                $bFilter=(!in_array($role,$adminRoles,true)&&$branchId>0)?$branchId:(int)($p['branch_id']??0);
                $bWhere=$bFilter>0?"AND branch_id={$bFilter}":'';
                $r=$conn->query("SELECT COUNT(*) AS c FROM members WHERE status='pending' AND deleted_at IS NULL {$bWhere}");
                $count=(int)($r?$r->fetch_assoc()['c']:0);
                if($count===0) return ['ok'=>true,'message'=>'No pending members found.','data'=>['approved'=>0]];
                if(!empty($p['confirm_count'])&&(int)$p['confirm_count']!==$count)
                    return ['ok'=>false,'message'=>"Count mismatch. Expected {$p['confirm_count']}, found {$count}.",'data'=>null];
                $rows=$conn->query("SELECT m.*,u.name FROM members m JOIN users u ON u.id=m.user_id WHERE m.status='pending' AND m.deleted_at IS NULL {$bWhere}");
                $approved=0;$failed=0;
                if($rows) while($member=$rows->fetch_assoc()){
                    $muid=(int)$member['user_id'];
                    $result=updateMember($conn,$muid,$member['phone'],$member['birthdate'],$member['nida'],$member['gender'],'approved');
                    if($result===true){$approved++;if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$muid,'Membership Approved',"Your SACCOS membership is approved.",'success','./?page=my_loan');}catch(Throwable $e){}}
                    else $failed++;
                }
                if(function_exists('logAudit')) logAudit($conn,$userId,'bulk_approve','members',0,"[Chatbot] Bulk approved {$approved} members",[],['approved'=>$approved,'failed'=>$failed]);
                return ['ok'=>true,'message'=>"✅ Bulk approval done.\nApproved:{$approved} | Failed:{$failed}",'data'=>['approved'=>$approved,'failed'=>$failed]];
            },
        ],

        'edit_member' => [
            'description'   => 'Edit member info. Params: member_id OR reg_no OR name_search, then any of: phone, gender(male|female), status(approved|pending|rejected), nida, birthdate.',
            'params'        => ['member_id','reg_no','name_search','phone','gender','status','nida','birthdate'],
            'allowed_roles' => $adminRoles,
            'module'=>'members','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$membUserId=(int)$member['user_id'];
                $newPhone=!empty($p['phone'])?chatbotNormalisePhone($p['phone']):$member['phone'];
                $newGender=!empty($p['gender'])?chatbotFuzzyMatch(strtolower($p['gender']),['male','female'])??$p['gender']:$member['gender'];
                $newStatus=!empty($p['status'])?chatbotFuzzyMatch(strtolower($p['status']),['approved','pending','rejected'])??$p['status']:$member['status'];
                $newNida=!empty($p['nida'])?preg_replace('/\D/','',$p['nida']):$member['nida'];
                $newBirthdate=!empty($p['birthdate'])?(chatbotParseDate($p['birthdate'])??$member['birthdate']):$member['birthdate'];
                if(!in_array($newGender,['male','female'],true)) return ['ok'=>false,'message'=>'gender must be male or female.','data'=>null];
                if(!in_array($newStatus,['approved','pending','rejected'],true)) return ['ok'=>false,'message'=>'status must be approved, pending, or rejected.','data'=>null];
                if(!empty($p['nida'])&&!chatbotValidateNida($newNida)) return ['ok'=>false,'message'=>'NIDA must be 20 digits.','data'=>null];
                $old=['phone'=>$member['phone'],'gender'=>$member['gender'],'status'=>$member['status']];
                $result=updateMember($conn,$membUserId,$newPhone,$newBirthdate,$newNida,$newGender,$newStatus);
                if($result!==true) return ['ok'=>false,'message'=>"Update failed: {$result}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','members',(int)$member['id'],"[Chatbot] Edited {$member['name']}",$old,['phone'=>$newPhone,'gender'=>$newGender,'status'=>$newStatus]);
                return ['ok'=>true,'message'=>"✅ {$member['name']} updated successfully.",'data'=>null];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  USER MANAGEMENT
        // ══════════════════════════════════════════════════════
        'create_user' => [
            'description'   => 'Create a new system user (with member profile + sub-accounts). Params: name, email, password(min 8), role(member|accountant|manager|admin|...), branch_id, phone, gender(male|female), birthdate, nida.',
            'params'        => ['name','email','password','role','branch_id','phone','gender','birthdate','nida'],
            'allowed_roles' => $adminRoles,
            'module'=>'users','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $name=trim($p['name']??'');$email=trim($p['email']??'');$pass=trim($p['password']??'');
                $newRole=strtolower(trim($p['role']??'member'));$phone=chatbotNormalisePhone(trim($p['phone']??''));
                $gender=chatbotFuzzyMatch(strtolower($p['gender']??'male'),['male','female'])??'male';
                $bTarget=(int)($p['branch_id']??$branchId);$birthdate=chatbotParseDate($p['birthdate']??'')??'';
                $nida=preg_replace('/\D/','',$p['nida']??'');
                if(!$name) return ['ok'=>false,'message'=>'name is required.','data'=>null];
                if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'message'=>'Valid email is required.','data'=>null];
                if(strlen($pass)<8) return ['ok'=>false,'message'=>'password must be at least 8 characters.','data'=>null];
                $chk=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
                if($chk){$chk->bind_param('s',$email);$chk->execute();$ex=stmt_fetch_assoc($chk);$chk->close();}
                if(!empty($ex)) return ['ok'=>false,'message'=>"Email {$email} is already in use.",'data'=>null];
                $hashed=password_hash($pass,PASSWORD_DEFAULT);
                $regNo='';
                $conn->begin_transaction();
                try{
                    $uStmt=$conn->prepare("INSERT INTO users (name,email,password,role,status,created_at) VALUES (?,?,?,?,'active',NOW())");
                    if(!$uStmt) throw new Exception("User prepare failed.");
                    $uStmt->bind_param('ssss',$name,$email,$hashed,$newRole);
                    if(!$uStmt->execute()) throw new Exception("User insert: ".$uStmt->error);
                    $newUserId=$uStmt->insert_id;$uStmt->close();
                    $newMembId=null;
                    if($newRole==='member'){
                        $regNo='MEM/'.date('Y').'/'.str_pad($newUserId,5,'0',STR_PAD_LEFT);
                        $checkNo='CHK/'.date('Y').'/'.$newUserId;
                        $newMembId=registerMember($conn,$newUserId,$phone,'',$regNo,$birthdate,1,$bTarget,$gender,$nida,$checkNo);
                        if(!is_numeric($newMembId)||$newMembId<=0) throw new Exception("Member insert failed.");
                    }
                    chatbotEnsureMemberSubAccounts($conn,$newUserId,$bTarget,$name);
                    $conn->commit();
                    if(function_exists('logAudit')) logAudit($conn,$userId,'create','users',$newUserId,"[Chatbot] Created user {$name} ({$newRole})",[],['email'=>$email,'role'=>$newRole]);
                    $msg="✅ User created!\nName:{$name} | Role:{$newRole}\nEmail:{$email}";
                    if($newMembId) $msg.="\nMember ID:{$newMembId} | Reg:{$regNo}";
                    return ['ok'=>true,'message'=>$msg,'data'=>['user_id'=>$newUserId,'member_id'=>$newMembId]];
                } catch(Exception $e){$conn->rollback();return ['ok'=>false,'message'=>"Failed: ".$e->getMessage(),'data'=>null];}
            },
        ],

        'toggle_user_status' => [
            'description'   => 'Activate or deactivate a user account. Params: user_id OR email OR name_search, status(active|inactive).',
            'params'        => ['user_id','email','name_search','status'],
            'allowed_roles' => $adminRoles,
            'module'=>'users','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveUser($conn,$p);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $user=$res['user'];
                if((int)$user['id']===$userId) return ['ok'=>false,'message'=>'Cannot change your own status.','data'=>null];
                $ns=chatbotFuzzyMatch(strtolower($p['status']??'active'),['active','inactive'])??'active';
                $stmt=$conn->prepare("UPDATE users SET status=?,updated_at=NOW() WHERE id=?");
                if(!$stmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                $stmt->bind_param('si',$ns,(int)$user['id']);$stmt->execute();$stmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','users',(int)$user['id'],"[Chatbot] Set {$user['name']} to {$ns}",['status'=>$user['status']],['status'=>$ns]);
                return ['ok'=>true,'message'=>($ns==='active'?'✅':'🚫')." {$user['name']} is now {$ns}.",'data'=>null];
            },
        ],

        'update_user_role' => [
            'description'   => "Change a user's role. Params: user_id OR email OR name_search, new_role.",
            'params'        => ['user_id','email','name_search','new_role'],
            'allowed_roles' => $adminRoles,
            'module'=>'users','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveUser($conn,$p);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $user=$res['user'];
                if((int)$user['id']===$userId) return ['ok'=>false,'message'=>'Cannot change your own role.','data'=>null];
                $newRole=strtolower(trim($p['new_role']??''));
                $validRoles=['member','accountant','manager','chairman','loan comitee','admin','superadmin'];
                $matched=chatbotFuzzyMatch($newRole,$validRoles);
                if(!$matched) return ['ok'=>false,'message'=>"Invalid role '{$newRole}'. Valid: ".implode(', ',$validRoles),'data'=>null];
                $stmt=$conn->prepare("UPDATE users SET role=?,updated_at=NOW() WHERE id=?");
                if(!$stmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                $stmt->bind_param('si',$matched,(int)$user['id']);$stmt->execute();$stmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','users',(int)$user['id'],"[Chatbot] Changed {$user['name']} role to {$matched}",['role'=>$user['role']],['role'=>$matched]);
                return ['ok'=>true,'message'=>"✅ {$user['name']}'s role changed from {$user['role']} to {$matched}.",'data'=>null];
            },
        ],

        'reset_user_password' => [
            'description'   => "Reset a user's password. Params: user_id OR email OR name_search, new_password(min 8 chars).",
            'params'        => ['user_id','email','name_search','new_password'],
            'allowed_roles' => $adminRoles,
            'module'=>'users','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $newPass=trim($p['new_password']??'');
                if(strlen($newPass)<8) return ['ok'=>false,'message'=>'Password must be at least 8 characters.','data'=>null];
                $res=chatbotResolveUser($conn,$p);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $user=$res['user'];
                $hashed=password_hash($newPass,PASSWORD_DEFAULT);
                $stmt=$conn->prepare("UPDATE users SET password=?,updated_at=NOW() WHERE id=?");
                if(!$stmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                $stmt->bind_param('si',$hashed,(int)$user['id']);$stmt->execute();$stmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','users',(int)$user['id'],"[Chatbot] Password reset for {$user['name']}",[],[]);
                return ['ok'=>true,'message'=>"✅ Password reset for {$user['name']} ({$user['email']}).",'data'=>null];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  LOAN PRODUCTS
        // ══════════════════════════════════════════════════════
        'create_loan_product' => [
            'description'   => 'Create a loan product. Params: name, interest_rate(%), min_amount, max_amount, min_period, max_period, required_grantors, savings_multiplier, description, status.',
            'params'        => ['name','interest_rate','min_amount','max_amount','min_period','max_period','required_grantors','savings_multiplier','description','status'],
            'allowed_roles' => $adminRoles,
            'module'=>'loans','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $name=trim($p['name']??'');if(!$name) return ['ok'=>false,'message'=>'name required.','data'=>null];
                $ir=(float)($p['interest_rate']??0);if($ir<=0) return ['ok'=>false,'message'=>'interest_rate must be > 0.','data'=>null];
                $d=['name'=>$name,'description'=>trim($p['description']??''),'min_amount'=>chatbotParseAmount($p['min_amount']??'0'),'max_amount'=>chatbotParseAmount($p['max_amount']??'0'),'interest_rate'=>$ir,'min_period'=>(int)($p['min_period']??1),'max_period'=>(int)($p['max_period']??12),'savings_multiplier'=>(float)($p['savings_multiplier']??3),'required_grantors'=>(int)($p['required_grantors']??0),'processing_fee_percent'=>0.0,'allowed_repayment_modes'=>'salary,standing_order','eligibility_notes'=>'','status'=>(($p['status']??'active')==='inactive')?'inactive':'active'];
                $result=insertLoanType($conn,$d,$userId);
                if(!is_numeric($result)||$result<=0) return ['ok'=>false,'message'=>"Failed: {$result}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','loans',(int)$result,"[Chatbot] Created product {$name}",[],['id'=>$result]);
                return ['ok'=>true,'message'=>"✅ Product '{$name}' created (ID:{$result}) at {$ir}%.",'data'=>['id'=>$result]];
            },
        ],

        'update_loan_product' => [
            'description'   => 'Update a loan product. Params: product_id, then any of: name, interest_rate, min_amount, max_amount, min_period, max_period, required_grantors, savings_multiplier, description, status.',
            'params'        => ['product_id','name','interest_rate','min_amount','max_amount','min_period','max_period','required_grantors','savings_multiplier','description','status'],
            'allowed_roles' => $adminRoles,
            'module'=>'loans','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $pid=(int)($p['product_id']??0);if($pid<=0) return ['ok'=>false,'message'=>'Provide product_id.','data'=>null];
                $ex=selectLoanTypeById($conn,$pid);if(!$ex||!is_array($ex)) return ['ok'=>false,'message'=>"Product #{$pid} not found.",'data'=>null];
                $d=['name'=>!empty($p['name'])?trim($p['name']):$ex['name'],'description'=>isset($p['description'])?trim($p['description']):$ex['description'],'min_amount'=>isset($p['min_amount'])?chatbotParseAmount($p['min_amount']):(float)$ex['min_amount'],'max_amount'=>isset($p['max_amount'])?chatbotParseAmount($p['max_amount']):(float)$ex['max_amount'],'interest_rate'=>isset($p['interest_rate'])?(float)$p['interest_rate']:(float)$ex['interest_rate'],'min_period'=>isset($p['min_period'])?(int)$p['min_period']:(int)$ex['min_period'],'max_period'=>isset($p['max_period'])?(int)$p['max_period']:(int)$ex['max_period'],'savings_multiplier'=>isset($p['savings_multiplier'])?(float)$p['savings_multiplier']:(float)$ex['savings_multiplier'],'required_grantors'=>isset($p['required_grantors'])?(int)$p['required_grantors']:(int)$ex['required_grantors'],'processing_fee_percent'=>(float)($ex['processing_fee_percent']??0),'allowed_repayment_modes'=>$ex['allowed_repayment_modes']??'salary,standing_order','eligibility_notes'=>$ex['eligibility_notes']??'','status'=>isset($p['status'])?(trim($p['status'])==='inactive'?'inactive':'active'):$ex['status']];
                $result=updateLoanType($conn,$pid,$d,$userId);
                if($result!==true) return ['ok'=>false,'message'=>"Update failed: {$result}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','loans',$pid,"[Chatbot] Updated product #{$pid}",$ex,$d);
                return ['ok'=>true,'message'=>"✅ Product #{$pid} '{$d['name']}' updated.",'data'=>null];
            },
        ],

        'toggle_loan_product_status' => [
            'description'   => 'Activate or deactivate a loan product. Params: product_id, status(active|inactive).',
            'params'        => ['product_id','status'],
            'allowed_roles' => $adminRoles,
            'module'=>'loans','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $pid=(int)($p['product_id']??0);$ns=chatbotFuzzyMatch(trim($p['status']??''),['active','inactive'])??'active';
                if($pid<=0) return ['ok'=>false,'message'=>'Provide product_id.','data'=>null];
                if(!in_array($ns,['active','inactive'],true)) return ['ok'=>false,'message'=>'status must be active or inactive.','data'=>null];
                $ex=selectLoanTypeById($conn,$pid);if(!$ex||!is_array($ex)) return ['ok'=>false,'message'=>"Product #{$pid} not found.",'data'=>null];
                $result=toggleLoanTypeStatus($conn,$pid,$ns);
                if($result!==true) return ['ok'=>false,'message'=>"Failed: {$result}",'data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'update','loans',$pid,"[Chatbot] Toggled product #{$pid} to {$ns}",['status'=>$ex['status']],['status'=>$ns]);
                return ['ok'=>true,'message'=>"✅ '{$ex['name']}' is now {$ns}.",'data'=>null];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  GUARANTOR TOOLS
        // ══════════════════════════════════════════════════════
        'add_guarantor' => [
            'description'   => 'Add a guarantor to a loan. Params: loan_id, guarantor_member_id OR guarantor_reg_no OR guarantor_name_search.',
            'params'        => ['loan_id','guarantor_member_id','guarantor_reg_no','guarantor_name_search'],
            'allowed_roles' => $financeRoles,
            'module'=>'loans','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $loanId=(int)($p['loan_id']??0);if($loanId<=0) return ['ok'=>false,'message'=>'Provide loan_id.','data'=>null];
                $loan=selectLoanById($conn,$loanId);if(!$loan||!is_array($loan)) return ['ok'=>false,'message'=>"Loan #{$loanId} not found.",'data'=>null];
                $gp=['member_id'=>$p['guarantor_member_id']??'','reg_no'=>$p['guarantor_reg_no']??'','name_search'=>$p['guarantor_name_search']??''];
                $res=chatbotResolveMember($conn,$gp,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>"Guarantor: ".$res['error'],'data'=>null];
                $gMember=$res['member'];$gUserId=(int)$gMember['user_id'];
                $chk=$conn->prepare("SELECT id FROM loan_grantors WHERE loan_id=? AND user_id=? LIMIT 1");
                $exists=null;if($chk){$chk->bind_param('ii',$loanId,$gUserId);$chk->execute();$exists=stmt_fetch_assoc($chk);$chk->close();}
                if(!empty($exists)) return ['ok'=>false,'message'=>"{$gMember['name']} is already a guarantor.",'data'=>null];
                $stmt=$conn->prepare("INSERT INTO loan_grantors (loan_id,user_id,name,status,created_at) VALUES (?,?,?,'pending',NOW())");
                if(!$stmt) return ['ok'=>false,'message'=>'Insert failed.','data'=>null];
                $stmt->bind_param('iis',$loanId,$gUserId,$gMember['name']);$stmt->execute();$newId=$stmt->insert_id;$stmt->close();
                if(!$newId) return ['ok'=>false,'message'=>'Failed to add guarantor.','data'=>null];
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','loans',$loanId,"[Chatbot] Added guarantor {$gMember['name']} to #{$loanId}",[],['grantor_id'=>$newId]);
                if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$gUserId,'Guarantor Request',"You have been added as guarantor for loan #{$loanId} of {$loan['member_name']}.",'info','./?page=my_grantor_requests');}catch(Throwable $e){}
                return ['ok'=>true,'message'=>"✅ {$gMember['name']} added as guarantor for Loan #{$loanId}. They have been notified.",'data'=>['grantor_id'=>$newId]];
            },
        ],

        // ══════════════════════════════════════════════════════
        //  BRANCH/MEETING/NOTIFICATION WRITE TOOLS
        // ══════════════════════════════════════════════════════
        'create_branch' => [
            'description'   => 'Create a new branch. Params: name, phone, region_id, district_id, address.',
            'params'        => ['name','phone','region_id','district_id','address'],
            'allowed_roles' => $adminRoles,
            'module'=>'branches','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $name=trim($p['name']??'');if(!$name) return ['ok'=>false,'message'=>'name required.','data'=>null];
                $phone=chatbotNormalisePhone(trim($p['phone']??''));$regionId=(int)($p['region_id']??0);$districtId=(int)($p['district_id']??0);$address=trim($p['address']??'');
                if($regionId<=0) return ['ok'=>false,'message'=>'region_id required.','data'=>null];
                $stmt=$conn->prepare("INSERT INTO branches (name,phone,region,district,address,created_by,created_at) VALUES (?,?,?,?,?,?,NOW())");
                if(!$stmt) return ['ok'=>false,'message'=>'Insert failed: '.$conn->error,'data'=>null];
                $stmt->bind_param('ssiiis',$name,$phone,$regionId,$districtId,$address,$userId);
                if(!$stmt->execute()) return ['ok'=>false,'message'=>'Insert failed: '.$stmt->error,'data'=>null];
                $newId=$stmt->insert_id;$stmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','branches',$newId,"[Chatbot] Created branch {$name}",[],['name'=>$name,'phone'=>$phone]);
                return ['ok'=>true,'message'=>"✅ Branch '{$name}' created (ID:{$newId}).",'data'=>['branch_id'=>$newId]];
            },
        ],

        'create_meeting' => [
            'description'   => 'Create a meeting record. Params: title, date(required), venue, type, agenda.',
            'params'        => ['title','date','venue','type','agenda'],
            'allowed_roles' => $staffRoles,
            'module'=>'meetings','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $title=trim($p['title']??'');$rawDate=trim($p['date']??'');
                if(!$title) return ['ok'=>false,'message'=>'title required.','data'=>null];
                $date=chatbotParseDate($rawDate);if(!$date) return ['ok'=>false,'message'=>'Provide a valid date.','data'=>null];
                $venue=trim($p['venue']??'');$type=trim($p['type']??'general');$agenda=trim($p['agenda']??'');
                $stmt=$conn->prepare("INSERT INTO meetings (title,date_,venue,type,agenda,created_by,created_at) VALUES (?,?,?,?,?,?,NOW())");
                if(!$stmt) return ['ok'=>false,'message'=>'Insert failed: '.$conn->error,'data'=>null];
                $stmt->bind_param('sssssi',$title,$date,$venue,$type,$agenda,$userId);
                if(!$stmt->execute()) return ['ok'=>false,'message'=>'Insert failed: '.$stmt->error,'data'=>null];
                $newId=$stmt->insert_id;$stmt->close();
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','meetings',$newId,"[Chatbot] Created meeting: {$title} on {$date}",[],[]);
                return ['ok'=>true,'message'=>"✅ Meeting '{$title}' scheduled for {$date}.\nVenue:{$venue} | ID:{$newId}",'data'=>['meeting_id'=>$newId]];
            },
        ],

        'send_member_notification' => [
            'description'   => 'Send a notification to a specific member. Params: member_id OR name_search, title, message, type(info|success|warning|danger).',
            'params'        => ['member_id','reg_no','name_search','title','message','type'],
            'allowed_roles' => $staffRoles,
            'module'=>'notifications','permission'=>'can_create','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                $res=chatbotResolveMember($conn,$p,$userId,$role,$branchId);
                if(isset($res['error'])) return ['ok'=>false,'message'=>$res['error'],'data'=>null];
                $member=$res['member'];$muid=(int)$member['user_id'];
                $title=trim($p['title']??'');$message=trim($p['message']??'');
                if(!$title||!$message) return ['ok'=>false,'message'=>'title and message are required.','data'=>null];
                $type=chatbotFuzzyMatch(strtolower($p['type']??'info'),['info','success','warning','danger'])??'info';
                if(!function_exists('createSystemNotification')) return ['ok'=>false,'message'=>'Notification function not available.','data'=>null];
                try{createSystemNotification($conn,$muid,$title,$message,$type,'./?page=notifications');}catch(Throwable $e){return ['ok'=>false,'message'=>"Notification failed: ".$e->getMessage(),'data'=>null];}
                if(function_exists('logAudit')) logAudit($conn,$userId,'create','notifications',0,"[Chatbot] Notified {$member['name']}: {$title}",[],[]);
                return ['ok'=>true,'message'=>"✅ Notification sent to {$member['name']}:\n\"{$title}\"",'data'=>null];
            },
        ],

        'mark_notification_read' => [
            'description'   => 'Mark a notification as read. Params: notification_id OR all(true to mark all for current user).',
            'params'        => ['notification_id','all'],
            'allowed_roles' => $allRoles,
            'module'=>'notifications','permission'=>'can_edit','is_write'=>true,
            'handler' => function(mysqli $conn, array $p, int $userId, string $role, int $branchId) {
                if(!empty($p['all'])&&$p['all']==='true'){
                    $stmt=$conn->prepare("UPDATE system_notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL");
                    if(!$stmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                    $stmt->bind_param('i',$userId);$stmt->execute();$affected=$stmt->affected_rows;$stmt->close();
                    return ['ok'=>true,'message'=>"✅ Marked {$affected} notification(s) as read.",'data'=>null];
                }
                $nid=(int)($p['notification_id']??0);
                if($nid<=0) return ['ok'=>false,'message'=>'Provide notification_id or all=true.','data'=>null];
                $stmt=$conn->prepare("UPDATE system_notifications SET read_at=NOW() WHERE id=? AND user_id=?");
                if(!$stmt) return ['ok'=>false,'message'=>'Update failed.','data'=>null];
                $stmt->bind_param('ii',$nid,$userId);$stmt->execute();$aff=$stmt->affected_rows;$stmt->close();
                return ['ok'=>true,'message'=>$aff>0?"✅ Notification #{$nid} marked as read.":"Notification not found or not yours.",'data'=>null];
            },
        ],

    ]; // end registry
}

// ═════════════════════════════════════════════════════════════
//  DISPATCH
// ═════════════════════════════════════════════════════════════
function dispatchTool(string $toolName, array $params,
                      mysqli $conn, int $userId, string $userRole, int $branchId): array
{
    $registry=getToolRegistry($conn,$userId,$userRole,$branchId);
    if(!isset($registry[$toolName]))
        return ['ok'=>false,'message'=>"Unknown tool: {$toolName}",'is_write'=>false];
    $tool=$registry[$toolName];
    if(!chatbotUserCan($conn,$userId,$userRole,$tool['module'],$tool['permission'],$tool['allowed_roles']))
        return ['ok'=>false,'message'=>"Permission denied for '{$toolName}'.",'is_write'=>false];
    try {
        $result=($tool['handler'])($conn,$params,$userId,$userRole,$branchId);
        $result['is_write']=$tool['is_write'];
        return $result;
    } catch(Throwable $e) {
        error_log("chatbot_tools dispatchTool({$toolName}): ".$e->getMessage()." in ".$e->getFile().":".$e->getLine());
        return ['ok'=>false,'message'=>'Internal error: '.$e->getMessage(),'is_write'=>$tool['is_write']];
    }
}

// ═════════════════════════════════════════════════════════════
//  BUILD TOOL DESCRIPTIONS for AI system prompt
// ═════════════════════════════════════════════════════════════
function buildToolDescriptions(string $userRole, mysqli $conn, int $userId, int $branchId): string
{
    $registry=getToolRegistry($conn,$userId,$userRole,$branchId);
    $read=[];$write=[];
    foreach($registry as $name=>$tool){
        if(!in_array($userRole,$tool['allowed_roles'],true)&&!in_array('*',$tool['allowed_roles'],true)) continue;
        $line="  • {$name}: {$tool['description']}";
        if($tool['is_write']) $write[]=$line; else $read[]=$line;
    }
    $out='';
    if($read) $out.="[READ — instant]:\n".implode("\n",$read);
    if($write) $out.="\n\n[WRITE — requires yes/no confirm]:\n".implode("\n",$write);
    return $out;
}

// ═════════════════════════════════════════════════════════════
//  PARSE TOOL CALL from AI reply
// ═════════════════════════════════════════════════════════════
function parseToolCall(string $text): ?array
{
    if(!preg_match('/\[TOOL:([a-zA-Z_]{1,60})((?:\|[^|\]]+)*)\]/',$text,$m)) return null;
    $params=[];
    if(!empty($m[2])){
        foreach(explode('|',ltrim($m[2],'|')) as $pair){
            $pair=trim($pair);if($pair==='') continue;
            $eq=strpos($pair,'=');if($eq===false) continue;
            $k=trim(substr($pair,0,$eq));$v=trim(substr($pair,$eq+1));
            if($k!=='') $params[$k]=$v;
        }
    }
    return ['tool'=>$m[1],'params'=>$params,'raw'=>$m[0]];
}

// ═════════════════════════════════════════════════════════════
//  WIZARD — multi-step guided data entry
// ═════════════════════════════════════════════════════════════

function chatbotHandleWizard(string $userMessage, mysqli $conn, int $userId,
                             string $userRole, int $branchId): array
{
    $wizard=chatbotGetWizard();
    if(!$wizard) return ['handled'=>false,'reply'=>''];
    $lower=strtolower(trim($userMessage));
    if(in_array($lower,['cancel','hapana','stop','quit','exit','abort'],true)){
        chatbotClearWizard();
        return ['handled'=>true,'reply'=>"Wizard cancelled. What else can I help you with?"];
    }
    // Safety: ensure wizard has required structure
    if(!isset($wizard['step'],$wizard['fields'],$wizard['type'],$wizard['data'])){
        chatbotClearWizard();
        return ['handled'=>true,'reply'=>"⚠️ Wizard session corrupted. Please start again."];
    }
    $currentStep=$wizard['step'];$fields=$wizard['fields'];

    if($currentStep>=count($fields)){
        if($wizard['awaiting_confirm']??false){
            if(isConfirmation($userMessage)){$wizard['awaiting_confirm']=false;chatbotSetWizard($wizard);return chatbotExecuteWizard($wizard,$conn,$userId,$userRole,$branchId);}
            elseif(isCancellation($userMessage)){chatbotClearWizard();return ['handled'=>true,'reply'=>"Wizard cancelled."];}
            // Re-show summary if they typed something else
            return chatbotWizardSummary($wizard);
        }
        return chatbotExecuteWizard($wizard,$conn,$userId,$userRole,$branchId);
    }

    $field=$fields[$currentStep];$value=trim($userMessage);$error=null;
    switch($field['type']??'text'){
        case 'amount':
            $parsed=chatbotParseAmount($value);if($parsed<=0){$error="Please enter a valid amount (e.g. 500000, 500k, 0.5m).";break;}$value=(string)$parsed;break;
        case 'date':
            $parsed=chatbotParseDate($value);if(!$parsed){$error="Please enter a valid date (e.g. 2025-06-01, 01/06/2025, or 'today').";break;}$value=$parsed;break;
        case 'phone':
            $value=chatbotNormalisePhone($value);if(strlen($value)<9){$error="Please enter a valid phone number.";break;}break;
        case 'nida':
            $clean=preg_replace('/\D/','',$value);if(!chatbotValidateNida($clean)){$error="NIDA must be exactly 20 digits. Got ".strlen($clean).".";break;}$value=$clean;break;
        case 'email':
            if(!filter_var($value,FILTER_VALIDATE_EMAIL)){$error="Please enter a valid email address.";break;}break;
        case 'select':
            $opts=$field['options']??[];$matched=chatbotFuzzyMatch($value,$opts);
            if(!$matched){$error="Please choose one of: ".implode(', ',$opts).".";break;}$value=$matched;break;
        case 'integer':
            if(!is_numeric($value)||(int)$value<=0){$error="Please enter a valid positive number.";break;}$value=(string)(int)$value;break;
        case 'skip_ok':
            if(strtolower($value)==='skip') $value=$field['default']??'';break;
        case 'password':
            if(strlen($value)<8){$error="Password must be at least 8 characters.";break;}break;

        // ── Smart member search: resolves name/reg/phone → stores member_id in data, shows details ──
        case 'member_search':
            $like='%'.trim($value).'%';
            $mStmt=$conn->prepare(
                "SELECT m.id,u.name,m.reg_no,m.phone,m.status FROM members m "
               ."JOIN users u ON u.id=m.user_id "
               ."WHERE (u.name LIKE ? OR m.reg_no LIKE ? OR m.phone LIKE ?) "
               ."AND m.deleted_at IS NULL LIMIT 5"
            );
            $mResults=[];
            if($mStmt){$mStmt->bind_param('sss',$like,$like,$like);$mStmt->execute();$mResults=stmt_fetch_all($mStmt);$mStmt->close();}
            if(empty($mResults)){
                // Show re-prompt with hint so admin knows what to type
                $hint=$field['hint']??'Type a name, reg number, or phone.';
                return ['handled'=>true,'reply'=>"⚠️ No member found matching **'{$value}'**.\nTry a different name, reg number, or phone.\n\n💡 {$hint}"];
            }
            if(count($mResults)===1){
                $found=$mResults[0];
                $statusIcon=['approved'=>'✅','pending'=>'⏳','rejected'=>'❌'][$found['status']]??'•';
                $wizard['data']['member_id']=(string)$found['id'];
                $wizard['data']['_member_display']="{$statusIcon} {$found['name']} | Reg:{$found['reg_no']} | Phone:{$found['phone']}";
                $wizard['step']++;
                if($wizard['step']>=count($fields)){
                    $wizard['awaiting_confirm']=true;chatbotSetWizard($wizard);
                    return chatbotWizardSummary($wizard);
                }
                chatbotSetWizard($wizard);
                $next=$fields[$wizard['step']];
                $progress="Step ".($wizard['step']+1)."/".count($fields);
                return ['handled'=>true,'reply'=>"✅ Member: **{$found['name']}** (Reg:{$found['reg_no']})\n\n[{$progress}] {$next['prompt']}".(!empty($next['hint'])?"\n💡 {$next['hint']}":'')];
            }
            // Multiple matches — list them and keep wizard on same step so admin retries
            $matchLines=["Found ".count($mResults)." members. Please be more specific (type full name or reg number):"];
            foreach($mResults as $r){
                $icon=['approved'=>'✅','pending'=>'⏳','rejected'=>'❌'][$r['status']]??'•';
                $matchLines[]="  {$icon} {$r['name']} | Reg:{$r['reg_no']} | Phone:{$r['phone']}";
            }
            // Do NOT advance step — let admin retry
            return ['handled'=>true,'reply'=>implode("\n",$matchLines)];

        // ── Smart branch search: resolves branch name → stores branch_id ──
        case 'branch_search':
            $branchMap=$field['branch_names']??[];
            if(empty($branchMap)){
                // No branch map loaded — fallback: accept numeric ID
                if(is_numeric($value)&&(int)$value>0){$value=(string)(int)$value;break;}
                $error='No branches available. Contact your system administrator.';break;
            }
            $matched=chatbotFuzzyMatch($value,array_values($branchMap));
            if($matched){
                $bid=array_search($matched,$branchMap,true);
                $value=$bid!==false?(string)$bid:'0';
                if((int)$value<=0){$error='Could not resolve branch ID.';break;}
                break;
            }
            // Fallback: numeric ID typed directly
            if(is_numeric($value)&&isset($branchMap[(int)$value])){
                $value=(string)(int)$value;
                break;
            }
            // Re-show the branch list so admin can pick
            $branchList=implode("\n",array_map(fn($n)=>"  • {$n}",array_values($branchMap)));
            return ['handled'=>true,'reply'=>"⚠️ Branch **'{$value}'** not found.\nPlease type the branch name exactly as shown:\n{$branchList}"];

        // ── Smart product search: resolves loan product name → stores loan_type_id ──
        case 'product_search':
            $productMap=$field['product_names']??[];
            if(empty($productMap)){
                if(is_numeric($value)&&(int)$value>0){$value=(string)(int)$value;break;}
                $error='No loan products available. Please contact your administrator.';break;
            }
            $matched=chatbotFuzzyMatch($value,array_values($productMap));
            if($matched){
                $pid=array_search($matched,$productMap,true);
                $value=$pid!==false?(string)$pid:'0';
                if((int)$value<=0){$error='Could not resolve product ID.';break;}
                break;
            }
            if(is_numeric($value)&&isset($productMap[(int)$value])){
                $value=(string)(int)$value;
                break;
            }
            // Re-show products so admin can pick correctly
            $productList=implode("\n",array_map(fn($n)=>"  • {$n}",array_values($productMap)));
            return ['handled'=>true,'reply'=>"⚠️ Product **'{$value}'** not found.\nPlease type the product name exactly as shown:\n{$productList}"];
    }

    if($error){
        $rehint=!empty($field['hint'])? "\n💡 ".$field['hint'] : '';
        return ['handled'=>true,'reply'=>"[Step ".($currentStep+1)."/".count($fields)."] ".$field['prompt'].$rehint."\n\n⚠️ {$error}"];
    }
    $wizard=chatbotWizardStep($wizard,$value);

    if($wizard['step']>=count($fields)){
        $wizard['awaiting_confirm']=true;chatbotSetWizard($wizard);
        return chatbotWizardSummary($wizard);
    }
    chatbotSetWizard($wizard);
    $next=$fields[$wizard['step']];
    $progress="Step ".($wizard['step']+1)."/".count($fields);
    // For member_search/branch_search/product_search, show the hint inline so admin knows what's expected
    $nextHint='';
    if(!empty($next['hint'])) $nextHint="\n💡 {$next['hint']}";
    return ['handled'=>true,'reply'=>"✅ Got it!\n\n[{$progress}] {$next['prompt']}{$nextHint}"];
}

function chatbotWizardSummary(array $wizard): array
{
    $fields=$wizard['fields'];$data=$wizard['data'];
    $lines=["📋 **Please review before submitting:**\n"];
    foreach($fields as $f){
        // member_search key is 'member_search' but the resolved value is stored under 'member_id'
        if($f['key']==='member_search'){
            $val=$data['_member_display']??($data['member_id']?'ID:'.$data['member_id']:'—');
            $lines[]="• {$f['label']}: **{$val}**";
            continue;
        }
        if($f['key']==='branch_id'){
            $bNames=$f['branch_names']??[];
            $raw=$data['branch_id']??'—';
            $val=$bNames[(int)$raw]??"ID:{$raw}";
            $lines[]="• {$f['label']}: **{$val}**";
            continue;
        }
        if($f['key']==='loan_type_id'){
            $pNames=$f['product_names']??[];
            $raw=$data['loan_type_id']??'—';
            $val=$pNames[(int)$raw]??"ID:{$raw}";
            $lines[]="• {$f['label']}: **{$val}**";
            continue;
        }
        $val=$data[$f['key']]??'—';
        $display=($f['type']==='amount')?'TZS '.number_format((float)$val):($f['type']==='password'?'(hidden)':$val);
        $lines[]="• {$f['label']}: **{$display}**";
    }
    $lines[]="\nType **yes** to submit or **no** to cancel.";
    return ['handled'=>true,'reply'=>implode("\n",$lines)];
}

function chatbotExecuteWizard(array $wizard, mysqli $conn, int $userId,
                              string $userRole, int $branchId): array
{
    chatbotClearWizard();$data=$wizard['data'];$type=$wizard['type'];

    if($type==='loan_application'){
        $amount=(float)($data['amount']??0);$period=(int)($data['period']??0);
        $loanTypeId=(int)($data['loan_type_id']??0);$repMode=$data['repayment_mode']??'salary';
        if(!function_exists('insertLoan')) return ['handled'=>true,'reply'=>"❌ Loan function not available."];
        $newLoanId=insertLoan($conn,$branchId,$userId,$amount,0.0,0.0,$period,'pending',$repMode,null,$loanTypeId>0?$loanTypeId:null);
        if(!is_numeric($newLoanId)||$newLoanId<=0) return ['handled'=>true,'reply'=>"❌ Failed to submit loan. Please try via the web form."];
        if(function_exists('createSystemNotification')) try{createSystemNotification($conn,$userId,'Loan Application Submitted','Your application of TZS '.number_format($amount).' is under review.','info','./?page=my_loan');}catch(Throwable $e){}
        if(function_exists('logAudit')) logAudit($conn,$userId,'create','loans',$newLoanId,"[Chatbot Wizard] Loan",[],['amount'=>$amount,'period'=>$period]);
        return ['handled'=>true,'reply'=>"✅ Loan application **#{$newLoanId}** submitted!\nAmount: TZS ".number_format($amount)." | Period: {$period} months\nYou will be notified when reviewed."];
    }

    if($type==='deposit_savings'){
        $membId=$data['member_id']??'';
        if(!$membId||!is_numeric($membId)||(int)$membId<=0)
            return ['handled'=>true,'reply'=>"❌ Deposit failed: no member resolved. Please restart the wizard."];
        $params=['member_id'=>$membId,'category'=>$data['category']??'saving','amount'=>$data['amount']??0,'date'=>$data['date']??date('Y-m-d'),'description'=>$data['description']??''];
        $result=dispatchTool('deposit_savings',$params,$conn,$userId,$userRole,$branchId);
        return ['handled'=>true,'reply'=>$result['ok']?$result['message']:"❌ ".$result['message']];
    }

    if($type==='register_member'){
        $name=trim($data['name']??'');$email=trim($data['email']??'');$phone=trim($data['phone']??'');
        $gender=trim($data['gender']??'male');$nida=trim($data['nida']??'');$birthdate=trim($data['birthdate']??'');
        $bTarget=(int)($data['branch_id']??$branchId);
        if($bTarget<=0) $bTarget=$branchId; // fall back to session branch if fuzzy match yielded 0
        if(!$name||!$email||!$phone) return ['handled'=>true,'reply'=>"❌ Registration failed: name, email, and phone are required."];
        $chkStmt=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $ex=null;if($chkStmt){$chkStmt->bind_param('s',$email);$chkStmt->execute();$ex=stmt_fetch_assoc($chkStmt);$chkStmt->close();}
        if(!empty($ex)) return ['handled'=>true,'reply'=>"❌ Email {$email} is already in use."];
        $tempPass=bin2hex(random_bytes(4));$hashedPass=password_hash($tempPass,PASSWORD_DEFAULT);
        $regNo='';
        $conn->begin_transaction();
        try{
            $uStmt=$conn->prepare("INSERT INTO users (name,email,password,role,status,created_at) VALUES (?,?,?,'member','active',NOW())");
            if(!$uStmt) throw new Exception("User insert prepare failed.");
            $uStmt->bind_param('sss',$name,$email,$hashedPass);if(!$uStmt->execute()) throw new Exception("User insert: ".$uStmt->error);
            $newUserId=$uStmt->insert_id;$uStmt->close();
            $regNo='MEM/'.date('Y').'/'.str_pad($newUserId,5,'0',STR_PAD_LEFT);$checkNo='CHK/'.date('Y').'/'.$newUserId;
            $newMembId=registerMember($conn,$newUserId,$phone,'',$regNo,$birthdate,1,$bTarget,$gender,$nida,$checkNo);
            if(!is_numeric($newMembId)||$newMembId<=0) throw new Exception("Member insert failed.");
            chatbotEnsureMemberSubAccounts($conn,$newUserId,$bTarget,$name);
            $conn->commit();
            if(function_exists('logAudit')) logAudit($conn,$userId,'create','members',$newMembId,"[Chatbot Wizard] Registered {$name}",[],['user_id'=>$newUserId,'reg_no'=>$regNo]);
            return ['handled'=>true,'reply'=>"✅ **Member registered!**\nName:{$name} | Email:{$email}\nReg:{$regNo} | Temp Password:**{$tempPass}**\nStatus: Pending approval\n\nUse `approve_member` to activate."];
        } catch(Exception $e){$conn->rollback();return ['handled'=>true,'reply'=>"❌ Registration failed: ".$e->getMessage()];}
    }

    if($type==='create_user'){
        $params=['name'=>$data['name']??'','email'=>$data['email']??'','password'=>$data['password']??'','role'=>$data['role']??'member','branch_id'=>$data['branch_id']??$branchId,'phone'=>$data['phone']??'','gender'=>$data['gender']??'male','birthdate'=>$data['birthdate']??'','nida'=>$data['nida']??''];
        $result=dispatchTool('create_user',$params,$conn,$userId,$userRole,$branchId);
        return ['handled'=>true,'reply'=>$result['ok']?"✅ ".$result['message']:"❌ ".$result['message']];
    }

    return ['handled'=>true,'reply'=>"✅ Wizard complete! (type:{$type})"];
}

// ═════════════════════════════════════════════════════════════
//  WIZARD STARTERS
// ═════════════════════════════════════════════════════════════

function chatbotStartLoanWizard(mysqli $conn, int $userId, string $userRole, int $branchId): string
{
    // Pre-load products with names so user picks by name not ID
    $products=selectLoanTypes($conn);
    $productNames=[];$productHint='Type the product name.';
    if(is_array($products)&&count($products)>0){
        $productHint="Available loan products (type the name):\n";
        foreach($products as $prod){
            if(($prod['status']??'active')==='active'){
                $productNames[$prod['id']]=$prod['name'];
                $productHint.="  • {$prod['name']} | Rate:{$prod['interest_rate']}% | TZS ".number_format((float)$prod['min_amount'])."-".number_format((float)$prod['max_amount'])." | {$prod['min_period']}-{$prod['max_period']} months\n";
            }
        }
        $productHint=rtrim($productHint);
    }
    $fields=[
        ['key'=>'amount',         'label'=>'Loan Amount',      'type'=>'amount',         'prompt'=>'How much do you want to borrow? (e.g. 500000 or 500k or 0.5m)','hint'=>'Enter the amount in TZS'],
        ['key'=>'period',         'label'=>'Repayment Period', 'type'=>'integer',        'prompt'=>'How many months to repay?','hint'=>'E.g. 12 for one year'],
        ['key'=>'loan_type_id',   'label'=>'Loan Product',     'type'=>'product_search', 'prompt'=>'Which loan product?','hint'=>$productHint,'product_names'=>$productNames],
        ['key'=>'repayment_mode', 'label'=>'Repayment Mode',   'type'=>'select',         'prompt'=>'Repayment mode?','options'=>['salary','standing_order']],
    ];
    $wizard=['type'=>'loan_application','step'=>0,'fields'=>$fields,'data'=>['user_id'=>$userId,'branch_id'=>$branchId]];
    chatbotSetWizard($wizard);
    $first=$fields[0];
    return "🧙 **Loan Application Wizard** — I'll guide you step by step.\nType **cancel** at any time to stop.\n\n[Step 1/".count($fields)."] {$first['prompt']}\n💡 {$first['hint']}";
}

function chatbotStartDepositWizard(mysqli $conn, int $userId, string $userRole, int $branchId): string
{
    // Pre-fetch recent members so admin can search by name instead of typing an unknown ID
    $adminRoles=['admin','superadmin','super admin'];
    $isAdmin=in_array($userRole,$adminRoles,true);
    $bWhere=(!$isAdmin&&$branchId>0)?"AND m.branch_id={$branchId}":'';
    $recentRows=$conn->query("SELECT m.id,u.name,m.reg_no,m.phone FROM members m JOIN users u ON u.id=m.user_id WHERE m.deleted_at IS NULL AND m.status='approved' {$bWhere} ORDER BY u.name ASC LIMIT 10");
    $memberHint='Type a name, reg number, or phone to search.';
    if($recentRows&&$recentRows->num_rows>0){
        $memberHint="Search by name or reg no. Recent members:\n";
        while($r=$recentRows->fetch_assoc())
            $memberHint.="  • {$r['name']} | Reg:{$r['reg_no']} | Phone:{$r['phone']}\n";
        $memberHint=rtrim($memberHint);
    }

    $fields=[
        ['key'=>'member_search','label'=>'Member',       'type'=>'member_search','prompt'=>'Who are you depositing for?','hint'=>$memberHint],
        ['key'=>'category',    'label'=>'Account Type', 'type'=>'select',       'prompt'=>'Which account? (saving, amana, or share)','options'=>['saving','amana','share'],'hint'=>'Choose the account type'],
        ['key'=>'amount',      'label'=>'Amount',       'type'=>'amount',       'prompt'=>'How much to deposit? (e.g. 50000 or 50k)','hint'=>'Enter the amount in TZS'],
        ['key'=>'date',        'label'=>'Date',         'type'=>'date',         'prompt'=>"Transaction date? (type 'today' for today)",'hint'=>'Format: YYYY-MM-DD or DD/MM/YYYY'],
        ['key'=>'description', 'label'=>'Description',  'type'=>'skip_ok',      'prompt'=>"Description? (or type 'skip' for default)",'hint'=>'E.g. Monthly savings contribution','default'=>'Savings deposit via chatbot'],
    ];
    $wizard=['type'=>'deposit_savings','step'=>0,'fields'=>$fields,'data'=>[]];
    chatbotSetWizard($wizard);
    $first=$fields[0];
    return "🏦 **Savings Deposit Wizard**\nType **cancel** at any time.\n\n[Step 1/".count($fields)."] {$first['prompt']}\n💡 {$first['hint']}";
}

function chatbotStartMemberRegistrationWizard(mysqli $conn, int $userId, string $userRole, int $branchId): string
{
    // Pre-load branches with names so admin picks by name, not ID
    $branches=$conn->query("SELECT id,name FROM branches WHERE deleted_at IS NULL ORDER BY name LIMIT 20");
    $branchNames=[];$branchHint='Type the branch name.';
    if($branches&&$branches->num_rows>0){
        $branchHint="Available branches (type the name):\n";
        while($b=$branches->fetch_assoc()){
            $branchNames[$b['id']]=$b['name'];
            $branchHint.="  • {$b['name']}\n";
        }
        $branchHint=rtrim($branchHint);
    }
    $fields=[
        ['key'=>'name',      'label'=>'Full Name',  'type'=>'text',          'prompt'=>'Full name of the new member:'],
        ['key'=>'email',     'label'=>'Email',      'type'=>'email',         'prompt'=>'Email address:','hint'=>'Will be used as login username'],
        ['key'=>'phone',     'label'=>'Phone',      'type'=>'phone',         'prompt'=>'Phone number:','hint'=>'E.g. 0712345678'],
        ['key'=>'gender',    'label'=>'Gender',     'type'=>'select',        'prompt'=>'Gender?','options'=>['male','female']],
        ['key'=>'birthdate', 'label'=>'Birth Date', 'type'=>'date',          'prompt'=>'Date of birth:','hint'=>'YYYY-MM-DD or DD/MM/YYYY'],
        ['key'=>'nida',      'label'=>'NIDA',       'type'=>'nida',          'prompt'=>'NIDA number (20 digits):','hint'=>'Digits only'],
        ['key'=>'branch_id', 'label'=>'Branch',     'type'=>'branch_search', 'prompt'=>'Which branch?','hint'=>$branchHint,'branch_names'=>$branchNames],
    ];
    $wizard=['type'=>'register_member','step'=>0,'fields'=>$fields,'data'=>[]];
    chatbotSetWizard($wizard);
    $first=$fields[0];
    return "👤 **Member Registration Wizard**\nI'll collect all details and register the member automatically.\nType **cancel** at any time.\n\n[Step 1/".count($fields)."] {$first['prompt']}";
}

function chatbotStartCreateUserWizard(mysqli $conn, int $userId, string $userRole, int $branchId): string
{
    $roles=['member','accountant','manager','chairman','loan comitee','admin'];
    $branches=$conn->query("SELECT id,name FROM branches WHERE deleted_at IS NULL ORDER BY name LIMIT 20");
    $branchNames=[];$branchHint='Type the branch name.';
    if($branches&&$branches->num_rows>0){
        $branchHint="Available branches (type the name):\n";
        while($b=$branches->fetch_assoc()){
            $branchNames[$b['id']]=$b['name'];
            $branchHint.="  • {$b['name']}\n";
        }
        $branchHint=rtrim($branchHint);
    }
    $fields=[
        ['key'=>'name',      'label'=>'Full Name',  'type'=>'text',          'prompt'=>'Full name of the new user:'],
        ['key'=>'email',     'label'=>'Email',      'type'=>'email',         'prompt'=>'Email (used for login):'],
        ['key'=>'password',  'label'=>'Password',   'type'=>'password',      'prompt'=>'Initial password (min 8 chars):','hint'=>'Share this securely with the user'],
        ['key'=>'role',      'label'=>'Role',       'type'=>'select',        'prompt'=>'Role? ('.implode(', ',$roles).')','options'=>$roles],
        ['key'=>'branch_id', 'label'=>'Branch',     'type'=>'branch_search', 'prompt'=>'Which branch?','hint'=>$branchHint,'branch_names'=>$branchNames],
        ['key'=>'phone',     'label'=>'Phone',      'type'=>'phone',         'prompt'=>'Phone number:','hint'=>'E.g. 0712345678'],
        ['key'=>'gender',    'label'=>'Gender',     'type'=>'select',        'prompt'=>'Gender?','options'=>['male','female']],
    ];
    $wizard=['type'=>'create_user','step'=>0,'fields'=>$fields,'data'=>[]];
    chatbotSetWizard($wizard);
    $first=$fields[0];
    return "👤 **Create User Wizard**\nType **cancel** at any time.\n\n[Step 1/".count($fields)."] {$first['prompt']}";
}

// ═════════════════════════════════════════════════════════════
//  CONFIRMATION STORE
// ═════════════════════════════════════════════════════════════
function storePendingToolCall(string $toolName, array $params, string $summary): void
{
    $_SESSION['chatbot_pending_tool']=['tool'=>$toolName,'params'=>$params,'summary'=>$summary,'expires'=>time()+120];
}
function getPendingToolCall(): ?array
{
    $p=$_SESSION['chatbot_pending_tool']??null;
    if(!$p||time()>($p['expires']??0)){unset($_SESSION['chatbot_pending_tool']);return null;}
    return $p;
}
function clearPendingToolCall(): void { unset($_SESSION['chatbot_pending_tool']); }

function isConfirmation(string $msg): bool
{
    $l=strtolower(trim($msg));
    return in_array($l,['yes','y','confirm','ndio','ok','sawa','proceed','execute','do it','ndiyo'],true)
        ||preg_match('/^(yes|confirm|ndio|sawa|proceed|ndiyo)/i',$l);
}
function isCancellation(string $msg): bool
{
    $l=strtolower(trim($msg));
    return in_array($l,['no','n','cancel','hapana','stop','abort','sitaki'],true)
        ||preg_match('/^(no\b|cancel|hapana|stop|abort)/i',$l);
}
