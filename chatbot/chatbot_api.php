<?php
// ============================================================
//  CHATBOT API ENDPOINT  v3 — Full CRUD + Wizard Engine
//  POST: { "message": "...", "session_key": "..." }
//  Returns JSON: { "reply": "...", "navigate_to": "...",
//                  "nav_url": "...", "nav_label": "..." }
// ============================================================

ob_start();
session_start();
header('Content-Type: application/json');

register_shutdown_function(function () {
    $fatal   = error_get_last();
    $isFatal = $fatal && in_array($fatal['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true);
    $level   = ob_get_level();
    $buf     = $level>0?(string)ob_get_contents():'';
    $isJson  = $buf!==''&&json_decode($buf)!==null;
    if($isFatal||($buf!==''&&!$isJson)){
        if($level>0) ob_end_clean();
        if(!headers_sent()) header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error'=>'Something went wrong. Please try again.']);
    }
});

// ── Auth guard ─────────────────────────────────────────────
if(empty($_SESSION['userid'])){
    ob_end_clean();http_response_code(401);
    echo json_encode(['error'=>'Unauthorized. Please log in.']);exit;
}

// ── Parse request ──────────────────────────────────────────
$body=json_decode((string)file_get_contents('php://input'),true);
if(!is_array($body)){
    ob_end_clean();http_response_code(400);
    echo json_encode(['error'=>'Invalid request format.']);exit;
}
$userMessage=trim((string)($body['message']??''));
$sessionKey =trim((string)($body['session_key']??''));

// ── Session key check ──────────────────────────────────────
if(empty($_SESSION['chatbot_session_key'])||!hash_equals($_SESSION['chatbot_session_key'],$sessionKey)){
    ob_end_clean();http_response_code(403);
    echo json_encode(['error'=>'Invalid session key. Please refresh the page.']);exit;
}
if($userMessage===''){ob_end_clean();echo json_encode(['error'=>'Empty message.']);exit;}

// ── Dependencies ───────────────────────────────────────────
require_once '../configs.php';
require_once '../functions/loan_functions.php';
require_once '../functions/member_functions.php';
require_once '../functions/branch_functions.php';
require_once '../functions/role_functions.php';
require_once '../functions/audit_functions.php';
require_once '../functions/min_sub_functions.php';
require_once '../functions/min_transaction_functions.php';
require_once '../functions/notification_functions.php';
require_once '../functions/utilities_functions.php';
require_once 'chatbot_tools.php';

$conn=openConn();
if(!$conn||$conn->connect_errno){
    ob_end_clean();http_response_code(500);
    echo json_encode(['error'=>'Database unavailable.']);exit;
}

// ── User context ───────────────────────────────────────────
$userId    =(int)($_SESSION['userid']    ??0);
$userRole  =strtolower(trim((string)($_SESSION['role']      ??'member')));
$userLevel =strtolower(trim((string)($_SESSION['userlevel'] ??'branch')));
$userName  =trim((string)($_SESSION['username']??'User'));
$branchId  =(int)($_SESSION['branchid']  ??0);
$isAdmin   =in_array($userRole,['admin','superadmin','super admin'],true);

// ── Rate limiting (20 msgs/min) ────────────────────────────
$now=time();
if(!isset($_SESSION['chatbot_rate'])||!is_array($_SESSION['chatbot_rate'])) $_SESSION['chatbot_rate']=[];
$_SESSION['chatbot_rate']=array_filter($_SESSION['chatbot_rate'], function($ts) use ($now){ return $ts>$now-60; });
if(count($_SESSION['chatbot_rate'])>=20){
    ob_end_clean();http_response_code(429);
    echo json_encode(['error'=>'Too many messages. Please slow down.']);exit;
}
$_SESSION['chatbot_rate'][]=$now;

// ── Chatbot settings ───────────────────────────────────────
$settingsRow=$conn->query(
    "SELECT enabled,provider,api_key,model,grok_api_key,grok_model,allowed_roles FROM chatbot_settings LIMIT 1"
)->fetch_assoc()??null;

if(!is_array($settingsRow)||empty($settingsRow['enabled'])){
    ob_end_clean();echo json_encode(['error'=>'Chatbot is currently disabled.']);exit;
}

$allowedRolesList=array_filter(array_map('trim',
    explode(',',strtolower($settingsRow['allowed_roles']??'admin,superadmin,super admin'))));
if(!in_array($userRole,$allowedRolesList,true)){
    ob_end_clean();http_response_code(403);
    echo json_encode(['error'=>'You do not have permission to use the chatbot.']);exit;
}

$provider   =in_array(strtolower($settingsRow['provider']??'gemini'),['gemini','grok'],true)?strtolower($settingsRow['provider']):'gemini';
$geminiKey  =trim((string)($settingsRow['api_key']??''));
$geminiModel=trim((string)($settingsRow['model']??'gemini-2.5-flash'));
$grokKey    =trim((string)($settingsRow['grok_api_key']??''));
$grokModel  =trim((string)($settingsRow['grok_model']??'grok-3-mini'));
if($provider==='grok'&&$grokKey==='') $provider='gemini';
$apiKey=$provider==='grok'?$grokKey:$geminiKey;
$model =$provider==='grok'?$grokModel:$geminiModel;
if($apiKey===''){ob_end_clean();echo json_encode(['error'=>'Chatbot not configured (no API key). Contact the administrator.']);exit;}

// ── Clear command ──────────────────────────────────────────
if($userMessage==='__clear__'){
    unset($_SESSION['chatbot_history']);
    clearPendingToolCall();
    chatbotClearWizard();
    ob_end_clean();echo json_encode(['reply'=>'cleared']);exit;
}

if(mb_strlen($userMessage)>2000) $userMessage=mb_substr($userMessage,0,2000);

// ═════════════════════════════════════════════════════════
//  WIZARD INTENT DETECTION — before anything else
//  If the user asks to apply a loan, deposit, register, etc.
//  and there's no wizard running, start the right wizard
// ═════════════════════════════════════════════════════════
if(!chatbotGetWizard()){
    $lower=strtolower($userMessage);
    $adminRoles=['admin','superadmin','super admin'];
    $financeRoles=array_merge($adminRoles,['accountant']);

    // Loan application wizard
    if(preg_match('/\b(apply|omba|tuma maombi|niomba|apply loan|loan application|niomba mkopo)\b/i',$userMessage)
        &&!preg_match('/\b(list|show|display|view|maombi ya)\b/i',$userMessage)){
        $wizardReply=chatbotStartLoanWizard($conn,$userId,$userRole,$branchId);
        $_SESSION['chatbot_history'][]=['role'=>'user','text'=>$userMessage];
        $_SESSION['chatbot_history'][]=['role'=>'model','text'=>$wizardReply];
        $_SESSION['chatbot_history']=array_slice($_SESSION['chatbot_history'],-20);
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'wizard_start','loan_application');
        ob_end_clean();echo json_encode(['reply'=>$wizardReply],JSON_UNESCAPED_UNICODE);exit;
    }

    // Savings deposit wizard
    if(in_array($userRole,$financeRoles,true)&&
       preg_match('/\b(deposit|weka|ingiza|chapuza pesa|ingiza pesa|amana|weka akiba)\b/i',$userMessage)&&
       !preg_match('/\b(show|view|list|ona|angalia)\b/i',$userMessage)){
        $wizardReply=chatbotStartDepositWizard($conn,$userId,$userRole,$branchId);
        $_SESSION['chatbot_history'][]=['role'=>'user','text'=>$userMessage];
        $_SESSION['chatbot_history'][]=['role'=>'model','text'=>$wizardReply];
        $_SESSION['chatbot_history']=array_slice($_SESSION['chatbot_history'],-20);
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'wizard_start','deposit_savings');
        ob_end_clean();echo json_encode(['reply'=>$wizardReply],JSON_UNESCAPED_UNICODE);exit;
    }

    // Member registration wizard
    if(in_array($userRole,$adminRoles,true)&&
       preg_match('/\b(register member|add member|new member|sajili mwanachama|ongeza mwanachama)\b/i',$userMessage)){
        $wizardReply=chatbotStartMemberRegistrationWizard($conn,$userId,$userRole,$branchId);
        $_SESSION['chatbot_history'][]=['role'=>'user','text'=>$userMessage];
        $_SESSION['chatbot_history'][]=['role'=>'model','text'=>$wizardReply];
        $_SESSION['chatbot_history']=array_slice($_SESSION['chatbot_history'],-20);
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'wizard_start','register_member');
        ob_end_clean();echo json_encode(['reply'=>$wizardReply],JSON_UNESCAPED_UNICODE);exit;
    }
}

// ═════════════════════════════════════════════════════════
//  WIZARD CONTINUATION — if a wizard is running, handle it
// ═════════════════════════════════════════════════════════
$wizardResult=chatbotHandleWizard($userMessage,$conn,$userId,$userRole,$branchId);
if($wizardResult['handled']){
    $wizardReply=$wizardResult['reply'];
    $_SESSION['chatbot_history'][]=['role'=>'user','text'=>$userMessage];
    $_SESSION['chatbot_history'][]=['role'=>'model','text'=>$wizardReply];
    $_SESSION['chatbot_history']=array_slice($_SESSION['chatbot_history'],-20);
    chatbotLogAudit($conn,$userId,$userRole,$userMessage,'wizard_step',null);
    ob_end_clean();echo json_encode(['reply'=>$wizardReply],JSON_UNESCAPED_UNICODE);exit;
}

// ═════════════════════════════════════════════════════════
//  CONFIRMATION FLOW — yes/no for pending write tool
// ═════════════════════════════════════════════════════════
$pending=getPendingToolCall();
if($pending!==null){
    if(isConfirmation($userMessage)){
        $result=dispatchTool($pending['tool'],$pending['params'],$conn,$userId,$userRole,$branchId);
        clearPendingToolCall();
        $chatbotReply=$result['ok']?"✅ Done! ".$result['message']:"❌ Action failed: ".$result['message'];
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_write',$pending['tool']);
        $_SESSION['chatbot_history'][]=['role'=>'user','text'=>$userMessage];
        $_SESSION['chatbot_history'][]=['role'=>'model','text'=>$chatbotReply];
        $_SESSION['chatbot_history']=array_slice($_SESSION['chatbot_history'],-20);
        ob_end_clean();echo json_encode(['reply'=>$chatbotReply],JSON_UNESCAPED_UNICODE);exit;
    } elseif(isCancellation($userMessage)){
        clearPendingToolCall();
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_cancelled',$pending['tool']);
        ob_end_clean();echo json_encode(['reply'=>"Action cancelled. What else can I help you with?"]);exit;
    }
    clearPendingToolCall();
}

// ═════════════════════════════════════════════════════════
//  BUILD CONTEXT + SYSTEM PROMPT + CALL AI
// ═════════════════════════════════════════════════════════
$contextData=buildRoleContext($conn,$userId,$userRole,$userLevel,$branchId);
$navMap     =getNavMap($userRole);
$toolDescs  =buildToolDescriptions($userRole,$conn,$userId,$branchId);
$systemPrompt=buildSystemPrompt($userName,$userRole,$userLevel,$contextData,$navMap,$toolDescs);

if(!isset($_SESSION['chatbot_history'])||!is_array($_SESSION['chatbot_history'])) $_SESSION['chatbot_history']=[];
$history=array_map('normalizeHistoryEntry',$_SESSION['chatbot_history']);
$history[]=['role'=>'user','text'=>$userMessage];
if(count($history)>20) $history=array_slice($history,-20);

if(!function_exists('curl_init')){
    ob_end_clean();echo json_encode(['error'=>'cURL is not available on this server.']);exit;
}

$result=($provider==='grok')
    ?callGrokApi($apiKey,$model,$systemPrompt,$history,$isAdmin)
    :callGeminiApi($apiKey,$model,$systemPrompt,$history,$isAdmin);

// Fallback
if(!$result['ok']){
    if($provider==='grok'&&$geminiKey!==''){
        $fb=callGeminiApi($geminiKey,$geminiModel,$systemPrompt,$history);
        if($fb['ok']){$provider='gemini';$result=$fb;}
    } elseif($provider==='gemini'){
        $errLow=strtolower((string)$result['log_error']);
        if((str_contains($errLow,'high demand')||str_contains($errLow,'timed out'))&&$geminiModel!=='gemini-2.0-flash'){
            $fb=callGeminiApi($geminiKey,$geminiModel,$systemPrompt,$history,$isAdmin);
            if($fb['ok']){$model='gemini-2.0-flash';$result=$fb;}
        }
        if($isAdmin && str_contains(strtolower((string)$result['safe_error']),'safety filter')){
            $fb=callGeminiApi($geminiKey,$geminiModel,$systemPrompt,$history,true);
            if($fb['ok']){$result=$fb;}
        }
    }
}

if(!$result['ok']){
    if($result['log_error']) error_log("chatbot_api: {$provider} error - ".$result['log_error']);
    ob_end_clean();chatbotLogAudit($conn,$userId,$userRole,$userMessage,'error',null);
    echo json_encode(['error'=>$result['safe_error']]);exit;
}

$replyText=(string)$result['reply'];

// ═════════════════════════════════════════════════════════
//  TOOL CALL DETECTION
// ═════════════════════════════════════════════════════════
$toolResult=null;$toolCalled=false;
$toolCall=parseToolCall($replyText);

if($toolCall!==null){
    $toolName=$toolCall['tool'];$toolParams=$toolCall['params'];
    $replyText=trim(str_replace($toolCall['raw'],'',$replyText));
    $registry=getToolRegistry($conn,$userId,$userRole,$branchId);

    if(isset($registry[$toolName])){
        $isWrite=$registry[$toolName]['is_write'];
        if($isWrite){
            $paramDesc=implode(', ',array_map(function($k,$v){ return "{$k}={$v}"; },array_keys($toolParams),array_values($toolParams)));
            $summary="**{$toolName}** with: {$paramDesc}";
            storePendingToolCall($toolName,$toolParams,$summary);
            $toolDesc=mb_substr($registry[$toolName]['description'],0,100);
            $confirmMsg=($replyText?"$replyText\n\n":'')
                ."⚠️ This action will perform: **{$toolDesc}**\n"
                ."Parameters: {$paramDesc}\n\n"
                ."Type **yes** to confirm or **no** to cancel.";
            $replyText=$confirmMsg;$toolCalled=true;
        } else {
            $tResult=dispatchTool($toolName,$toolParams,$conn,$userId,$userRole,$branchId);
            chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_read',$toolName);
            if($tResult['ok']){
                $replyText=($replyText?"$replyText\n\n":'').$tResult['message'];
            } else {
                $replyText=($replyText?"$replyText\n\n":'')."⚠️ Could not retrieve that data: ".$tResult['message'];
            }
            $toolResult=$tResult;$toolCalled=true;
        }
    }
}

// ═════════════════════════════════════════════════════════
//  NAVIGATION TOKEN
// ═════════════════════════════════════════════════════════
$navigateTo=null;$navUrl=null;$navLabel=null;
if(preg_match('/\[NAVIGATE:([a-zA-Z0-9_]{1,80})\]/',$replyText,$navMatch)){
    $slug=$navMatch[1];
    $replyText=trim(str_replace($navMatch[0],'',$replyText));
    if(isset($navMap[$slug])){
        $navigateTo=$slug;
        $navUrl='./?page='.rawurlencode($slug);
        $navLabel=$navMap[$slug]['label'];
    }
}

// ── Persist & respond ──────────────────────────────────────
$history[]=['role'=>'model','text'=>$replyText];
$_SESSION['chatbot_history']=array_slice($history,-20);

$action=$toolCalled?'tool_read':($navigateTo?'navigate':'answer');
chatbotLogAudit($conn,$userId,$userRole,$userMessage,$action,$navigateTo);

ob_end_clean();
$response=['reply'=>$replyText];
if($navigateTo){$response['navigate_to']=$navigateTo;$response['nav_url']=$navUrl;$response['nav_label']=$navLabel;}
if($toolResult!==null) $response['tool_result']=['ok'=>$toolResult['ok'],'message'=>$toolResult['message']];
echo json_encode($response,JSON_UNESCAPED_UNICODE);exit;

// ═════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ═════════════════════════════════════════════════════════

function normalizeHistoryEntry($entry): array
{
    if(!is_array($entry)) return ['role'=>'user','text'=>''];
    $role=(string)($entry['role']??'user');
    $text=isset($entry['text'])?(string)$entry['text']:(string)($entry['parts'][0]['text']??'');
    return ['role'=>$role,'text'=>$text];
}

function httpPostJson(string $url, array $headers, string $body): array
{
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,
        CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
    ]);
    $raw=curl_exec($ch);$curlErr=curl_error($ch);$httpCode=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['raw'=>$raw,'curl_error'=>$curlErr,'http_code'=>$httpCode];
}

function callGeminiApi(string $apiKey, string $model, string $systemPrompt, array $history, bool $relaxSafety=false): array
{
    $fail=function(string $safe,?string $log=null){ return ['ok'=>false,'reply'=>null,'safe_error'=>$safe,'log_error'=>$log]; };
    $contents=array_map(function($h){ return ['role'=>$h['role'],'parts'=>[['text'=>$h['text']]]]; },$history);
    $url="https://generativelanguage.googleapis.com/v1beta/models/".urlencode($model).":generateContent?key=".urlencode($apiKey);
    $payload=['system_instruction'=>['parts'=>[['text'=>$systemPrompt]]],'contents'=>$contents,
              'generationConfig'=>['maxOutputTokens'=>1200,'temperature'=>0.3]];
    if(!$relaxSafety){
        $payload['safetySettings']=[
            ['category'=>'HARM_CATEGORY_HARASSMENT','threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_HATE_SPEECH','threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_SEXUALLY_EXPLICIT','threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_DANGEROUS_CONTENT','threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
        ];
    }
    $body=json_encode($payload);
    if($body===false) return $fail('Failed to build request. Please try again.');
    $res=httpPostJson($url,['Content-Type: application/json'],$body);
    if($res['curl_error']!==''||$res['raw']===false) return $fail('Could not reach AI service.',$res['curl_error']);
    $resp=json_decode((string)$res['raw'],true);
    if(!is_array($resp)) return $fail('Unexpected response.','non-JSON: '.substr((string)$res['raw'],0,200));
    if(isset($resp['error'])){
        $msg=(string)($resp['error']['message']??'API error');
        $safe=strpos($msg,'quota')!==false||$res['http_code']===429?'Daily AI limit reached. Try again tomorrow.':'AI service error. Please try again.';
        return $fail($safe,$msg);
    }
    $replyText=$resp['candidates'][0]['content']['parts'][0]['text']??null;
    $finishReason=(string)($resp['candidates'][0]['finishReason']??'UNKNOWN');
    if($replyText===null||trim((string)$replyText)===''){
        $safe=$finishReason==='SAFETY'?'That message was blocked by the safety filter. Please rephrase.':'AI could not generate a response.';
        return $fail($safe,"empty reply, finishReason={$finishReason}");
    }
    return ['ok'=>true,'reply'=>(string)$replyText,'safe_error'=>null,'log_error'=>null];
}

function callGrokApi(string $apiKey, string $model, string $systemPrompt, array $history, bool $relaxSafety=false): array
{
    $fail=function(string $safe,?string $log=null){ return ['ok'=>false,'reply'=>null,'safe_error'=>$safe,'log_error'=>$log]; };
    $messages=[['role'=>'system','content'=>$systemPrompt]];
    foreach($history as $h) $messages[]=['role'=>$h['role']==='model'?'assistant':'user','content'=>$h['text']];
    $payload=['model'=>$model,'messages'=>$messages,'max_tokens'=>1200,'temperature'=>0.3];
    if($relaxSafety){
        $payload['safety_filter']=['mode'=>'relaxed'];
    }
    $body=json_encode($payload);
    if($body===false) return $fail('Failed to build request.');
    $res=httpPostJson('https://api.x.ai/v1/chat/completions',['Content-Type: application/json','Authorization: Bearer '.$apiKey],$body);
    if($res['curl_error']!==''||$res['raw']===false) return $fail('Could not reach AI service.',$res['curl_error']);
    $resp=json_decode((string)$res['raw'],true);
    if(!is_array($resp)) return $fail('Unexpected response.','non-JSON: '.substr((string)$res['raw'],0,200));
    if(isset($resp['error'])){
        $err=$resp['error'];$msg=is_array($err)?(string)($err['message']??'API error'):(string)$err;
        $safe=stripos($msg,'quota')!==false||$res['http_code']===429?'Daily AI limit reached.':'AI error.';
        return $fail($safe,$msg);
    }
    $replyText=$resp['choices'][0]['message']['content']??null;
    $finishReason=(string)($resp['choices'][0]['finish_reason']??'unknown');
    if($replyText===null||trim((string)$replyText)===''){
        $safe=$finishReason==='content_filter'?'Blocked by safety filter.':'AI could not respond.';
        return $fail($safe,"empty reply, finish_reason={$finishReason}");
    }
    return ['ok'=>true,'reply'=>(string)$replyText,'safe_error'=>null,'log_error'=>null];
}

function buildRoleContext(mysqli $conn, int $userId, string $role, string $level, int $branchId): string
{
    $lines=[];
    $adminRoles=['admin','superadmin','super admin'];
    $isAdmin=in_array($role,$adminRoles,true);
    if(!$isAdmin&&$branchId>0){
        $bWhere="AND branch_id={$branchId}";
    } else {
        $bWhere='';
    }

    if($isAdmin||$role==='accountant'||$role==='manager'||$role==='chairman'){
        $r=$conn->query("SELECT COUNT(*) AS c FROM members WHERE deleted_at IS NULL {$bWhere}");
        if($r){$row=$r->fetch_assoc();$lines[]="Total members: ".(int)($row['c']??0);}
        $r=$conn->query("SELECT status,COUNT(*) AS c,COALESCE(SUM(principle),0) AS t FROM loans WHERE deleted_at IS NULL {$bWhere} GROUP BY status");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Loans [{$row['status']}]: {$row['c']} = TZS ".number_format((float)$row['t']);
        // Savings totals
        $bTxWhere=(!$isAdmin&&$branchId>0)?"AND mt.branch_id={$branchId}":'';
        $r=$conn->query("SELECT ms.category,COALESCE(SUM(mt.amount),0) AS total FROM min_transactions mt JOIN min_subs ms ON ms.id=mt.dr_account WHERE ms.category IN ('saving','amana','share') AND mt.deleted_at IS NULL {$bTxWhere} GROUP BY ms.category");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Total {$row['category']}: TZS ".number_format((float)$row['total']);
        if($isAdmin){
            $r=$conn->query("SELECT COUNT(*) AS c FROM branches WHERE deleted_at IS NULL");
            if($r){$row=$r->fetch_assoc();$lines[]="Total branches: ".(int)($row['c']??0);}
        }
        $r=$conn->query("SELECT u.name,m.created_at FROM members m JOIN users u ON u.id=m.user_id WHERE m.deleted_at IS NULL {$bWhere} ORDER BY m.created_at DESC LIMIT 5");
        if($r){$recent=[];while($row=$r->fetch_assoc()) $recent[]=htmlspecialchars($row['name'])." (".substr($row['created_at'],0,10).")";if($recent)$lines[]="Recent members: ".implode(', ',$recent);}
        $r=$conn->query("SELECT name,interest_rate,max_amount,savings_multiplier FROM loan_types WHERE status='active' AND deleted_at IS NULL LIMIT 10");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Product: {$row['name']} | Rate:{$row['interest_rate']}% | Max:TZS ".number_format((float)$row['max_amount'])." | Multiplier:{$row['savings_multiplier']}x";
    } elseif($role==='member') {
        $stmt=$conn->prepare("SELECT u.name,m.reg_no,m.phone,b.name AS branch_name FROM members m JOIN users u ON u.id=m.user_id LEFT JOIN branches b ON b.id=m.branch_id WHERE m.user_id=? AND m.deleted_at IS NULL LIMIT 1");
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$row=stmt_fetch_assoc($stmt);$stmt->close();
            if(is_array($row)){$lines[]="Your name: ".htmlspecialchars($row['name']??'');$lines[]="Reg no: ".htmlspecialchars($row['reg_no']??'');$lines[]="Branch: ".htmlspecialchars($row['branch_name']??'');}}
        if(function_exists('getMemberTotalSavings')) try{$sav=getMemberTotalSavings($conn,$userId);$lines[]="Your savings: Saving=TZS ".number_format($sav['saving'])." | Amana=TZS ".number_format($sav['amana'])." | Share=TZS ".number_format($sav['share']);}catch(Throwable $e){}
        $stmt=$conn->prepare("SELECT l.principle,l.status,l.created_at,lt.name AS pname FROM loans l LEFT JOIN loan_types lt ON lt.id=l.loan_type WHERE l.user_id=? AND l.deleted_at IS NULL ORDER BY l.created_at DESC LIMIT 5");
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
            if(is_array($rows)) foreach($rows as $row) $lines[]="Your loan: ".htmlspecialchars($row['pname']??'Loan')." | TZS ".number_format((float)$row['principle'])." | {$row['status']}";}
        $r=$conn->query("SELECT name,interest_rate,max_amount FROM loan_types WHERE status='active' AND deleted_at IS NULL LIMIT 8");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Available product: {$row['name']} | Rate:{$row['interest_rate']}%";
    }
    return $lines?implode("\n",$lines):"No live data available.";
}

function getNavMap(string $role): array
{
    $all=[
        'my_loan'              =>['label'=>'My Loans',           'roles'=>['*']],
        'loan_adviser'         =>['label'=>'Loan Advisor',       'roles'=>['*']],
        'user_profile'         =>['label'=>'My Profile',         'roles'=>['*']],
        'notifications'        =>['label'=>'Notifications',      'roles'=>['*']],
        'my_grantor_requests'  =>['label'=>'Guarantor Requests', 'roles'=>['*']],
        'mfa_setup'            =>['label'=>'Two-Factor Auth',    'roles'=>['*']],
        'apply_user_loan'      =>['label'=>'Apply for Loan',     'roles'=>['member']],
        'loan_applications'    =>['label'=>'Loan Applications',  'roles'=>['admin','superadmin','super admin','accountant','manager','loan comitee']],
        'loan_products'        =>['label'=>'Loan Products',      'roles'=>['admin','superadmin','super admin','accountant']],
        'approved_loan_list'   =>['label'=>'Approved Loans',     'roles'=>['admin','superadmin','super admin','accountant']],
        'pending_loan_list'    =>['label'=>'Pending Loans',      'roles'=>['admin','superadmin','super admin','accountant','loan comitee']],
        'all_member_list'      =>['label'=>'All Members',        'roles'=>['admin','superadmin','super admin','accountant']],
        'register_member'      =>['label'=>'Register Member',    'roles'=>['admin','superadmin','super admin','accountant']],
        'edit_member'          =>['label'=>'Edit Member',        'roles'=>['admin','superadmin','super admin']],
        'branch_list'          =>['label'=>'Branch List',        'roles'=>['admin','superadmin','super admin']],
        'register_branch'      =>['label'=>'Register Branch',    'roles'=>['admin','superadmin','super admin']],
        'transaction_list'     =>['label'=>'Transaction List',   'roles'=>['admin','superadmin','super admin','accountant']],
        'pending_voucher_list' =>['label'=>'Pending Vouchers',   'roles'=>['admin','superadmin','super admin','accountant']],
        'all_budgets'          =>['label'=>'All Budgets',        'roles'=>['admin','superadmin','super admin','accountant','manager']],
        'create_budget'        =>['label'=>'Create Budget',      'roles'=>['admin','superadmin','super admin','accountant']],
        'meeting_list'         =>['label'=>'Meeting List',       'roles'=>['admin','superadmin','super admin','accountant','manager','chairman']],
        'audit_trail'          =>['label'=>'Audit Trail',        'roles'=>['admin','superadmin','super admin']],
        'manage_roles'         =>['label'=>'Manage Roles',       'roles'=>['admin','superadmin','super admin']],
        'assign_user_roles'    =>['label'=>'Assign User Roles',  'roles'=>['admin','superadmin','super admin']],
        'pending_members'      =>['label'=>'Pending Approvals',  'roles'=>['admin','superadmin','super admin']],
        'chatbot_settings'     =>['label'=>'Chatbot Settings',   'roles'=>['admin','superadmin','super admin']],
        'ledger'               =>['label'=>'Ledger List',        'roles'=>['admin','superadmin','super admin','accountant']],
        'Income_statement_form'=>['label'=>'Income Statement',   'roles'=>['admin','superadmin','super admin','accountant']],
        'ledger_report_form'   =>['label'=>'Ledger Report',      'roles'=>['admin','superadmin','super admin','accountant']],
        'subsidiary_list'      =>['label'=>'Subsidiaries',       'roles'=>['admin','superadmin','super admin','accountant']],
        'apply_loan'           =>['label'=>'Apply Loan (Staff)', 'roles'=>['admin','superadmin','super admin','accountant','manager']],
    ];
    $allowed=[];
    foreach($all as $slug=>$info){
        if(in_array('*',$info['roles'],true)||in_array($role,$info['roles'],true))
            $allowed[$slug]=$info;
    }
    return $allowed;
}

function buildSystemPrompt(string $userName, string $role, string $level,
                           string $contextData, array $navMap, string $toolDescs=''): string
{
    $today=date('Y-m-d');
    $navList='';
    foreach($navMap as $slug=>$info) $navList.="  - {$info['label']} → [NAVIGATE:{$slug}]\n";
    if(!$navList) $navList="  (no navigation available)\n";
    $firstName=explode(' ',trim($userName))[0]??$userName;

    $toolSection='';
    if($toolDescs){
        $toolSection=<<<TOOLS

DATA TOOLS — CALL THESE FOR LIVE DATA OR ACTIONS:
Embed exactly ONE tool token at the END of your reply (after your explanation text):
  Syntax: [TOOL:tool_name|param1=value1|param2=value2]
  Example: [TOOL:list_loans|status=pending|limit=10]
  Example: [TOOL:deposit_savings|member_id=5|category=saving|amount=50000|date=today]
  Example: [TOOL:get_member_details|name_search=Amina]

Available tools (role-filtered):
{$toolDescs}

RULES FOR TOOLS:
- READ tools run immediately and embed results in your reply.
- WRITE tools require the user to type "yes" to confirm — always explain what will happen first, then emit the token.
- Only ONE tool per reply.
- Never guess or invent tool names or params — use only what's listed.
- For financial operations (deposit, withdraw, approve loan), ALWAYS show the details first, then emit the WRITE tool token so the user confirms.
- Admin/accountant requests to manage member accounts, savings, loans, and financial data are valid internal operations; do not refuse these as unsafe.
- When user says amounts like "5 million", "500k", "500,000" — pass them as-is; the system normalises automatically.
- When user provides a member name but not ID, use name_search param — the system will fuzzy-find the best match.
TOOLS;
    }

    return <<<PROMPT
You are a highly capable data assistant built into Zimamoto SACCOS management system in Tanzania.
Today's date: {$today}

CURRENT USER:
- Name: {$firstName}
- Role: {$role}
- Level: {$level}

LIVE SYSTEM DATA (use for quick answers — call a tool for fresh/detailed data):
{$contextData}
{$toolSection}

PAGES YOU CAN NAVIGATE TO:
{$navList}
Include ONE navigation token to redirect: [NAVIGATE:page_slug]

CAPABILITIES YOU HAVE (tell users about these when relevant):
- 📊 Read loan lists, member lists, analytics, overdue reports, transaction history
- 💰 Deposit to/withdraw from member savings, amana, share accounts (admins/accountants)
- 🏦 Record loan repayments, mark installments paid
- ✅ Approve/reject loans and member registrations
- 👤 Edit member info, toggle user accounts, reset passwords
- 🧙 Interactive wizards: loan application, savings deposit, member registration (guided step-by-step)
- 📈 Dashboard stats, overdue reports, loan analytics grouped by product/branch/month
- 🔍 Search across members, loans, and branches at once

RULES:
1. Only answer questions about this SACCOS system. Politely decline anything unrelated.
2. Members see only their own data. Staff see role-scoped data only.
3. Never invent figures — always use live context data or call a tool.
4. Reply in the same language the user writes (Swahili or English). Be concise and warm.
5. For any query about specific members, loans, or financial data — call the right tool.
6. For write actions like deposits, approvals, rejections — explain first, then emit the WRITE tool token.
7. Never expose session tokens, role strings, or internal system details.
8. Address the user by first name when appropriate. Be helpful and professional.
9. If the user seems confused about what you can do, summarise your capabilities briefly.
PROMPT;
}

function chatbotLogAudit(mysqli $conn, int $userId, string $role,
                         string $message, string $action, ?string $target): void
{
    $stmt=$conn->prepare("INSERT INTO chatbot_audit (user_id,role_at_time,user_message,bot_action,navigate_to,created_at) VALUES (?,?,?,?,?,NOW())");
    if(!$stmt) return;
    $msg=mb_substr($message,0,1000);$act=mb_substr($action,0,50);$nav=mb_substr($target??'',0,100);
    $stmt->bind_param('issss',$userId,$role,$msg,$act,$nav);
    $stmt->execute();$stmt->close();
}
