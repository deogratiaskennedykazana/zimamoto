<?php
// ============================================================
//  CHATBOT API ENDPOINT  v2 — with Tool Calling
//  POST: { "message": "...", "session_key": "..." }
//  Returns JSON: { "reply": "...", "navigate_to": "...",
//                  "nav_url": "...", "nav_label": "...",
//                  "tool_result": {...} }
//
//  New in v2:
//  • Tool-calling: AI can query DB and (with confirmation)
//    perform write actions (reject/approve loans, edit members,
//    manage loan products) directly from chat.
//  • Confirmation flow: write tools require a yes/no reply
//    before execution — stored safely in $_SESSION.
//  • All write actions logged to audit_trail table.
// ============================================================

ob_start();
session_start();
header('Content-Type: application/json');

register_shutdown_function(function () {
    $fatal    = error_get_last();
    $isFatal  = $fatal && in_array($fatal['type'],
        [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    $level    = ob_get_level();
    $buffered = $level > 0 ? (string)ob_get_contents() : '';
    $isJson   = $buffered !== '' && json_decode($buffered) !== null;

    if ($isFatal || ($buffered !== '' && !$isJson)) {
        if ($level > 0) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Something went wrong. Please try again.']);
    }
});

// ── 1. Auth guard ─────────────────────────────────────────────
if (empty($_SESSION['userid'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// ── 2. Parse request body ─────────────────────────────────────
$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request format.']);
    exit;
}

$userMessage = trim((string)($body['message']     ?? ''));
$sessionKey  = trim((string)($body['session_key'] ?? ''));

// ── 3. Session key validation ─────────────────────────────────
if (empty($_SESSION['chatbot_session_key']) ||
    !hash_equals($_SESSION['chatbot_session_key'], $sessionKey)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['error' => 'Invalid session key. Please refresh the page.']);
    exit;
}

if ($userMessage === '') {
    ob_end_clean();
    echo json_encode(['error' => 'Empty message.']);
    exit;
}

// ── 4. DB + function files ────────────────────────────────────
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

$conn = openConn();
if (!$conn || $conn->connect_errno) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable. Please try again later.']);
    exit;
}

// ── 5. User context ───────────────────────────────────────────
$userId    = (int)($_SESSION['userid']    ?? 0);
$userRole  = strtolower(trim((string)($_SESSION['role']      ?? 'member')));
$userLevel = strtolower(trim((string)($_SESSION['userlevel'] ?? 'branch')));
$userName  = trim((string)($_SESSION['username'] ?? 'User'));
$branchId  = (int)($_SESSION['branchid']    ?? 0);

// ── 6. Rate limiting ──────────────────────────────────────────
$now = time();
if (!isset($_SESSION['chatbot_rate']) || !is_array($_SESSION['chatbot_rate'])) {
    $_SESSION['chatbot_rate'] = [];
}
$_SESSION['chatbot_rate'] = array_filter($_SESSION['chatbot_rate'], fn($ts) => $ts > $now - 60);
if (count($_SESSION['chatbot_rate']) >= 20) {
    ob_end_clean();
    http_response_code(429);
    echo json_encode(['error' => 'Too many messages. Please slow down.']);
    exit;
}
$_SESSION['chatbot_rate'][] = $now;

// ── 7. Load chatbot settings ──────────────────────────────────
$settingsRow = $conn->query(
    "SELECT enabled, provider, api_key, model, grok_api_key, grok_model, allowed_roles
     FROM chatbot_settings LIMIT 1"
)->fetch_assoc() ?? null;

if (!is_array($settingsRow) || empty($settingsRow['enabled'])) {
    ob_end_clean();
    echo json_encode(['error' => 'Chatbot is currently disabled.']);
    exit;
}

// ── 8. Role access check ──────────────────────────────────────
$allowedRolesList = array_filter(array_map('trim',
    explode(',', strtolower($settingsRow['allowed_roles'] ?? 'admin,superadmin,super admin'))));
if (!in_array($userRole, $allowedRolesList, true)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to use the chatbot.']);
    exit;
}

// ── 9. Provider & key setup ───────────────────────────────────
$provider    = in_array(strtolower($settingsRow['provider']??'gemini'),['gemini','grok'],true)
               ? strtolower($settingsRow['provider']) : 'gemini';
$geminiApiKey = trim((string)($settingsRow['api_key']       ?? ''));
$geminiModel  = trim((string)($settingsRow['model']         ?? 'gemini-3.5-flash'));
$grokApiKey   = trim((string)($settingsRow['grok_api_key']  ?? ''));
$grokModel    = trim((string)($settingsRow['grok_model']    ?? 'grok-4.3'));

$allowedGeminiModels = ['gemini-3.5-flash','gemini-3.1-flash-lite','gemini-3.1-pro-preview'];
if (!in_array($geminiModel, $allowedGeminiModels, true)) $geminiModel = 'gemini-3.5-flash';
if (!in_array($grokModel,   ['grok-4.3'],          true)) $grokModel  = 'grok-4.3';
if ($provider === 'grok' && $grokApiKey === '')           $provider    = 'gemini';

$apiKey = $provider === 'grok' ? $grokApiKey : $geminiApiKey;
$model  = $provider === 'grok' ? $grokModel  : $geminiModel;

if ($apiKey === '') {
    ob_end_clean();
    echo json_encode(['error' => 'Chatbot not configured (no API key). Contact the administrator.']);
    exit;
}

// ── 10. Clear command ─────────────────────────────────────────
if ($userMessage === '__clear__') {
    unset($_SESSION['chatbot_history']);
    clearPendingToolCall();
    ob_end_clean();
    echo json_encode(['reply' => 'cleared']);
    exit;
}

// ── 11. Message length cap ────────────────────────────────────
if (mb_strlen($userMessage) > 2000) $userMessage = mb_substr($userMessage, 0, 2000);

// ═════════════════════════════════════════════════════════════
//  CONFIRMATION FLOW  — check if user is replying yes/no to
//  a pending write-tool confirmation request
// ═════════════════════════════════════════════════════════════
$pending = getPendingToolCall();
if ($pending !== null) {
    if (isConfirmation($userMessage)) {
        // Execute the confirmed write tool
        $result = dispatchTool($pending['tool'], $pending['params'],
                               $conn, $userId, $userRole, $branchId);
        clearPendingToolCall();

        $chatbotReply = $result['ok']
            ? "✅ Done! " . $result['message']
            : "❌ Action failed: " . $result['message'];

        // Log to chatbot_audit
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_write',$pending['tool']);

        // Persist history
        if (!isset($_SESSION['chatbot_history'])||!is_array($_SESSION['chatbot_history'])) {
            $_SESSION['chatbot_history']=[];
        }
        $_SESSION['chatbot_history'][] = ['role'=>'user',  'text'=>$userMessage];
        $_SESSION['chatbot_history'][] = ['role'=>'model', 'text'=>$chatbotReply];
        $_SESSION['chatbot_history']   = array_slice($_SESSION['chatbot_history'],-20);

        ob_end_clean();
        echo json_encode(['reply'=>$chatbotReply], JSON_UNESCAPED_UNICODE);
        exit;

    } elseif (isCancellation($userMessage)) {
        clearPendingToolCall();
        chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_cancelled',$pending['tool']);
        ob_end_clean();
        echo json_encode(['reply'=>"Action cancelled. What else can I help you with?"]);
        exit;
    }
    // Not a yes/no — fall through to normal AI call (user changed their mind / asked something else)
    clearPendingToolCall();
}

// ═════════════════════════════════════════════════════════════
//  BUILD CONTEXT + PROMPT + CALL AI
// ═════════════════════════════════════════════════════════════
$contextData = buildRoleContext($conn, $userId, $userRole, $userLevel, $branchId);
$navMap      = getNavMap($userRole);
$toolDescs   = buildToolDescriptions($userRole, $conn, $userId, $branchId);
$systemPrompt= buildSystemPrompt($userName, $userRole, $userLevel, $contextData, $navMap, $toolDescs);

if (!isset($_SESSION['chatbot_history'])||!is_array($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history']=[];
}
$history   = array_map('normalizeHistoryEntry', $_SESSION['chatbot_history']);
$history[] = ['role'=>'user','text'=>$userMessage];
if (count($history)>20) $history = array_slice($history,-20);

if (!function_exists('curl_init')) {
    ob_end_clean();
    echo json_encode(['error'=>'cURL is not available on this server.']);
    exit;
}

$result = ($provider==='grok')
    ? callGrokApi($apiKey,$model,$systemPrompt,$history)
    : callGeminiApi($apiKey,$model,$systemPrompt,$history);

// Fallback logic
if (!$result['ok']) {
    if ($provider==='grok' && $geminiApiKey!=='') {
        $fb = callGeminiApi($geminiApiKey,$geminiModel,$systemPrompt,$history);
        if ($fb['ok']) { $provider='gemini'; $result=$fb; }
    } elseif ($provider==='gemini') {
        $errLow = strtolower((string)$result['log_error']);
        if ((str_contains($errLow,'high demand')||str_contains($errLow,'timed out')) && $geminiModel!=='gemini-3.1-flash-lite') {
            $fb = callGeminiApi($geminiApiKey,'gemini-3.1-flash-lite',$systemPrompt,$history);
            if ($fb['ok']) { $model='gemini-3.1-flash-lite'; $result=$fb; }
        }
    }
}

if (!$result['ok']) {
    if ($result['log_error']) error_log("chatbot_api: {$provider} error - ".$result['log_error']);
    ob_end_clean();
    chatbotLogAudit($conn,$userId,$userRole,$userMessage,'error',null);
    echo json_encode(['error'=>$result['safe_error']]);
    exit;
}

$replyText = (string)$result['reply'];

// ═════════════════════════════════════════════════════════════
//  TOOL CALL DETECTION
//  AI may embed [TOOL:tool_name|key=val|...] in its reply
// ═════════════════════════════════════════════════════════════
$toolResult  = null;
$toolCalled  = false;
$toolCall    = parseToolCall($replyText);

if ($toolCall !== null) {
    $toolName = $toolCall['tool'];
    $toolParams = $toolCall['params'];
    // Strip the [TOOL:...] token from the displayed reply
    $replyText = trim(str_replace($toolCall['raw'], '', $replyText));

    // Get tool metadata to check if write
    $registry = getToolRegistry($conn, $userId, $userRole, $branchId);

    if (isset($registry[$toolName])) {
        $isWrite = $registry[$toolName]['is_write'];

        if ($isWrite) {
            // Summarize for confirmation prompt
            $paramDesc = implode(', ', array_map(
                fn($k,$v) => "{$k}={$v}", array_keys($toolParams), array_values($toolParams)
            ));
            $summary = "**{$toolName}** with: {$paramDesc}";
            storePendingToolCall($toolName, $toolParams, $summary);

            // Ask user to confirm
            $confirmMsg = ($replyText ? $replyText."\n\n" : '')
                . "⚠️ This action will: **".mb_substr($registry[$toolName]['description'],0,80)."**\n"
                . "Parameters: {$paramDesc}\n\n"
                . "Type **yes** to confirm or **no** to cancel.";
            $replyText = $confirmMsg;
            $toolCalled = true;

        } else {
            // Read tool — execute immediately, no confirmation needed
            $tResult = dispatchTool($toolName, $toolParams, $conn, $userId, $userRole, $branchId);
            chatbotLogAudit($conn,$userId,$userRole,$userMessage,'tool_read',$toolName);

            if ($tResult['ok']) {
                $replyText = ($replyText ? $replyText."\n\n" : '') . $tResult['message'];
            } else {
                $replyText = ($replyText ? $replyText."\n\n" : '')
                           . "⚠️ Could not retrieve that data: " . $tResult['message'];
            }
            $toolResult = $tResult;
            $toolCalled = true;
        }
    }
}

// ═════════════════════════════════════════════════════════════
//  NAVIGATION TOKEN DETECTION
// ═════════════════════════════════════════════════════════════
$navigateTo = null; $navUrl = null; $navLabel = null;
if (preg_match('/\[NAVIGATE:([a-zA-Z0-9_]{1,80})\]/', $replyText, $navMatch)) {
    $slug      = $navMatch[1];
    $replyText = trim(str_replace($navMatch[0], '', $replyText));
    if (isset($navMap[$slug])) {
        $navigateTo = $slug;
        $navUrl     = './?page='.rawurlencode($slug);
        $navLabel   = $navMap[$slug]['label'];
    }
}

// ── Persist history ───────────────────────────────────────────
$history[]                   = ['role'=>'model','text'=>$replyText];
$_SESSION['chatbot_history'] = array_slice($history, -20);

// ── Audit ─────────────────────────────────────────────────────
$action = $toolCalled ? 'tool_read' : ($navigateTo ? 'navigate' : 'answer');
chatbotLogAudit($conn,$userId,$userRole,$userMessage,$action,$navigateTo);

// ── Response ──────────────────────────────────────────────────
ob_end_clean();
$response = ['reply' => $replyText];
if ($navigateTo) {
    $response['navigate_to'] = $navigateTo;
    $response['nav_url']     = $navUrl;
    $response['nav_label']   = $navLabel;
}
if ($toolResult !== null) {
    $response['tool_result'] = ['ok'=>$toolResult['ok'],'message'=>$toolResult['message']];
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;


// ═════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ═════════════════════════════════════════════════════════════

function normalizeHistoryEntry($entry): array
{
    if (!is_array($entry)) return ['role'=>'user','text'=>''];
    $role = (string)($entry['role']??'user');
    $text = isset($entry['text']) ? (string)$entry['text']
          : (string)($entry['parts'][0]['text']??'');
    return ['role'=>$role,'text'=>$text];
}

function httpPostJson(string $url, array $headers, string $body): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw      = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['raw'=>$raw,'curl_error'=>$curlErr,'http_code'=>$httpCode];
}

function callGeminiApi(string $apiKey, string $model, string $systemPrompt, array $history): array
{
    $fail = fn(string $safe, ?string $log=null) =>
        ['ok'=>false,'reply'=>null,'safe_error'=>$safe,'log_error'=>$log];

    $contents = array_map(
        fn($h) => ['role'=>$h['role'],'parts'=>[['text'=>$h['text']]]],
        $history
    );
    $url = "https://generativelanguage.googleapis.com/v1beta/models/"
         . urlencode($model).":generateContent?key=".urlencode($apiKey);

    $payload = [
        'system_instruction' => ['parts'=>[['text'=>$systemPrompt]]],
        'contents'           => $contents,
        'generationConfig'   => ['maxOutputTokens'=>1000,'temperature'=>0.3],
        'safetySettings'     => [
            ['category'=>'HARM_CATEGORY_HARASSMENT',        'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_HATE_SPEECH',       'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ];
    $body = json_encode($payload);
    if ($body === false) return $fail('Failed to build request. Please try again.');

    $res = httpPostJson($url, ['Content-Type: application/json'], $body);
    if ($res['curl_error']!==''||$res['raw']===false) {
        return $fail('Could not reach AI service. Please try again.',$res['curl_error']);
    }
    $resp = json_decode((string)$res['raw'], true);
    if (!is_array($resp)) return $fail('Unexpected response from AI service.','non-JSON: '.substr((string)$res['raw'],0,200));
    if (isset($resp['error'])) {
        $msg  = (string)($resp['error']['message']??'API error');
        $safe = strpos($msg,'quota')!==false||$res['http_code']===429
              ? 'Daily AI limit reached. Try again tomorrow.' : 'AI service error. Please try again.';
        return $fail($safe,$msg);
    }
    $replyText   = $resp['candidates'][0]['content']['parts'][0]['text']??null;
    $finishReason= (string)($resp['candidates'][0]['finishReason']??'UNKNOWN');
    if ($replyText===null||trim((string)$replyText)==='') {
        $safe = $finishReason==='SAFETY'
              ? 'That message was blocked by the safety filter. Please rephrase.'
              : 'AI could not generate a response. Please try again.';
        return $fail($safe,"empty reply, finishReason={$finishReason}");
    }
    return ['ok'=>true,'reply'=>(string)$replyText,'safe_error'=>null,'log_error'=>null];
}

function callGrokApi(string $apiKey, string $model, string $systemPrompt, array $history): array
{
    $fail = fn(string $safe, ?string $log=null) =>
        ['ok'=>false,'reply'=>null,'safe_error'=>$safe,'log_error'=>$log];

    $messages = [['role'=>'system','content'=>$systemPrompt]];
    foreach ($history as $h) {
        $messages[] = ['role'=>$h['role']==='model'?'assistant':'user','content'=>$h['text']];
    }
    $body = json_encode(['model'=>$model,'messages'=>$messages,'max_tokens'=>1000,'temperature'=>0.3]);
    if ($body===false) return $fail('Failed to build request. Please try again.');

    $res = httpPostJson('https://api.x.ai/v1/chat/completions', [
        'Content-Type: application/json',
        'Authorization: Bearer '.$apiKey,
    ], $body);

    if ($res['curl_error']!==''||$res['raw']===false) {
        return $fail('Could not reach AI service. Please try again.',$res['curl_error']);
    }
    $resp = json_decode((string)$res['raw'],true);
    if (!is_array($resp)) return $fail('Unexpected response.','non-JSON: '.substr((string)$res['raw'],0,200));
    if (isset($resp['error'])) {
        $err  = $resp['error'];
        $msg  = is_array($err)?(string)($err['message']??'API error'):(string)$err;
        $safe = stripos($msg,'quota')!==false||stripos($msg,'rate')!==false||$res['http_code']===429
              ? 'Daily AI limit reached. Try again tomorrow.' : 'AI error. Please try again.';
        return $fail($safe,$msg);
    }
    $replyText    = $resp['choices'][0]['message']['content']??null;
    $finishReason = (string)($resp['choices'][0]['finish_reason']??'unknown');
    if ($replyText===null||trim((string)$replyText)==='') {
        $safe = $finishReason==='content_filter'
              ? 'Blocked by safety filter. Please rephrase.' : 'AI could not respond. Try again.';
        return $fail($safe,"empty reply, finish_reason={$finishReason}");
    }
    return ['ok'=>true,'reply'=>(string)$replyText,'safe_error'=>null,'log_error'=>null];
}

function buildRoleContext(mysqli $conn, int $userId, string $role,
                          string $level, int $branchId): string
{
    $lines = [];

    if (in_array($role, ['admin','superadmin','super admin'], true)) {
        $r = $conn->query("SELECT COUNT(*) AS cnt FROM members WHERE deleted_at IS NULL");
        if ($r) { $row=$r->fetch_assoc(); $lines[]="Total members: ".(int)($row['cnt']??0); }

        $r = $conn->query("SELECT status,COUNT(*) AS cnt,COALESCE(SUM(principle),0) AS total
                           FROM loans WHERE deleted_at IS NULL GROUP BY status");
        if ($r) { while($row=$r->fetch_assoc()) {
            $lines[]="Loans [{$row['status']}]: count={$row['cnt']}, total=TZS ".number_format((float)$row['total']);
        }}

        $r = $conn->query("SELECT COUNT(*) AS cnt FROM branches WHERE deleted_at IS NULL");
        if ($r) { $row=$r->fetch_assoc(); $lines[]="Total branches: ".(int)($row['cnt']??0); }

        $r = $conn->query("SELECT u.name,m.created_at FROM members m
                           JOIN users u ON u.id=m.user_id
                           WHERE m.deleted_at IS NULL ORDER BY m.created_at DESC LIMIT 5");
        if ($r) { $recent=[];
            while($row=$r->fetch_assoc()) $recent[]=htmlspecialchars($row['name'])." (".substr($row['created_at'],0,10).")";
            if($recent) $lines[]="Recently registered: ".implode(', ',$recent);
        }

        $r = $conn->query("SELECT name,interest_rate,max_amount FROM loan_types
                           WHERE status='active' AND deleted_at IS NULL LIMIT 10");
        if ($r) { while($row=$r->fetch_assoc()) {
            $lines[]="Loan product: {$row['name']} | Rate: {$row['interest_rate']}% | Max: TZS ".number_format((float)($row['max_amount']??0));
        }}

    } elseif ($role==='accountant') {
        if ($branchId>0) {
            $stmt=$conn->prepare("SELECT COUNT(*) AS cnt FROM members WHERE branch_id=? AND deleted_at IS NULL");
            if($stmt){$stmt->bind_param('i',$branchId);$stmt->execute();$row=stmt_fetch_assoc($stmt);$stmt->close();
                if(is_array($row)) $lines[]="Members in branch: ".(int)($row['cnt']??0);}
            $stmt=$conn->prepare("SELECT status,COUNT(*) AS cnt FROM loans WHERE branch_id=? AND deleted_at IS NULL GROUP BY status");
            if($stmt){$stmt->bind_param('i',$branchId);$stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
                if(is_array($rows)) foreach($rows as $row) $lines[]="Branch loans [{$row['status']}]: {$row['cnt']}";}
        } else {
            $r=$conn->query("SELECT COUNT(*) AS cnt FROM members WHERE deleted_at IS NULL");
            if($r){$row=$r->fetch_assoc();$lines[]="Total members: ".(int)($row['cnt']??0);}
        }
        $r=$conn->query("SELECT name,interest_rate,max_amount FROM loan_types WHERE status='active' AND deleted_at IS NULL LIMIT 10");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Loan product: {$row['name']} | Rate:{$row['interest_rate']}%";

    } elseif ($role==='member') {
        $stmt=$conn->prepare("SELECT u.name,m.reg_no,m.phone,b.name AS branch_name
            FROM members m JOIN users u ON u.id=m.user_id
            LEFT JOIN branches b ON b.id=m.branch_id
            WHERE m.user_id=? AND m.deleted_at IS NULL LIMIT 1");
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$row=stmt_fetch_assoc($stmt);$stmt->close();
            if(is_array($row)){
                $lines[]="Member name: ".htmlspecialchars($row['name']??'');
                $lines[]="Reg number: ".htmlspecialchars($row['reg_no']??'');
                $lines[]="Branch: ".htmlspecialchars($row['branch_name']??'');
            }}
        $stmt=$conn->prepare("SELECT l.principle,l.status,l.created_at,lt.name AS product_name
            FROM loans l LEFT JOIN loan_types lt ON lt.id=l.loan_type
            WHERE l.user_id=? AND l.deleted_at IS NULL ORDER BY l.created_at DESC LIMIT 5");
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$rows=stmt_fetch_all($stmt);$stmt->close();
            if(is_array($rows)) foreach($rows as $row) {
                $lines[]="Your loan: ".htmlspecialchars($row['product_name']??'Loan')." | TZS ".number_format((float)($row['principle']??0))." | {$row['status']}";
            }}
        $r=$conn->query("SELECT name,interest_rate,max_amount FROM loan_types WHERE status='active' AND deleted_at IS NULL LIMIT 8");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Available product: {$row['name']} | Rate:{$row['interest_rate']}%";

    } else {
        $r=$conn->query("SELECT name,interest_rate,max_amount FROM loan_types WHERE status='active' AND deleted_at IS NULL LIMIT 8");
        if($r) while($row=$r->fetch_assoc()) $lines[]="Loan product: {$row['name']} | Rate:{$row['interest_rate']}%";
    }

    return $lines ? implode("\n",$lines) : "No live data available.";
}

function getNavMap(string $role): array
{
    $all = [
        'my_loan'              =>['label'=>'My Loans',             'roles'=>['*']],
        'loan_adviser'         =>['label'=>'Loan Advisor',         'roles'=>['*']],
        'user_profile'         =>['label'=>'My Profile',           'roles'=>['*']],
        'notifications'        =>['label'=>'Notifications',        'roles'=>['*']],
        'my_grantor_requests'  =>['label'=>'Guarantor Requests',   'roles'=>['*']],
        'mfa_setup'            =>['label'=>'Two-Factor Auth',      'roles'=>['*']],
        'apply_user_loan'      =>['label'=>'Apply for Loan',       'roles'=>['member']],
        'loan_applications'    =>['label'=>'Loan Applications',    'roles'=>['admin','superadmin','super admin','accountant','manager','loan comitee']],
        'loan_products'        =>['label'=>'Loan Products',        'roles'=>['admin','superadmin','super admin','accountant']],
        'approved_loan_list'   =>['label'=>'Approved Loans',       'roles'=>['admin','superadmin','super admin','accountant']],
        'pending_loan_list'    =>['label'=>'Pending Loans',        'roles'=>['admin','superadmin','super admin','accountant','loan comitee']],
        'all_member_list'      =>['label'=>'All Members',          'roles'=>['admin','superadmin','super admin','accountant']],
        'register_member'      =>['label'=>'Register Member',      'roles'=>['admin','superadmin','super admin','accountant']],
        'edit_member'          =>['label'=>'Edit Member',          'roles'=>['admin','superadmin','super admin']],
        'branch_list'          =>['label'=>'Branch List',          'roles'=>['admin','superadmin','super admin']],
        'register_branch'      =>['label'=>'Register Branch',      'roles'=>['admin','superadmin','super admin']],
        'transaction_list'     =>['label'=>'Transaction List',     'roles'=>['admin','superadmin','super admin','accountant']],
        'pending_voucher_list' =>['label'=>'Pending Vouchers',     'roles'=>['admin','superadmin','super admin','accountant']],
        'all_budgets'          =>['label'=>'All Budgets',          'roles'=>['admin','superadmin','super admin','accountant','manager']],
        'create_budget'        =>['label'=>'Create Budget',        'roles'=>['admin','superadmin','super admin','accountant']],
        'meeting_list'         =>['label'=>'Meeting List',         'roles'=>['admin','superadmin','super admin','accountant','manager','chairman']],
        'audit_trail'          =>['label'=>'Audit Trail',          'roles'=>['admin','superadmin','super admin']],
        'manage_roles'         =>['label'=>'Manage Roles',         'roles'=>['admin','superadmin','super admin']],
        'assign_user_roles'    =>['label'=>'Assign User Roles',    'roles'=>['admin','superadmin','super admin']],
        'pending_members'      =>['label'=>'Pending Approvals',    'roles'=>['admin','superadmin','super admin']],
        'chatbot_settings'     =>['label'=>'Chatbot Settings',     'roles'=>['admin','superadmin','super admin']],
        'ledger'               =>['label'=>'Ledger List',          'roles'=>['admin','superadmin','super admin','accountant']],
        'Income_statement_form'=>['label'=>'Income Statement',     'roles'=>['admin','superadmin','super admin','accountant']],
        'ledger_report_form'   =>['label'=>'Ledger Report',        'roles'=>['admin','superadmin','super admin','accountant']],
        'subsidiary_list'      =>['label'=>'Subsidiaries',         'roles'=>['admin','superadmin','super admin','accountant']],
        'apply_loan'           =>['label'=>'Apply Loan (Staff)',   'roles'=>['admin','superadmin','super admin','accountant','manager']],
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
    $today    = date('Y-m-d');
    $navList  = '';
    foreach($navMap as $slug=>$info) $navList.="  - {$info['label']} → [NAVIGATE:{$slug}]\n";
    if(!$navList) $navList="  (no navigation available)\n";
    $firstName = explode(' ',trim($userName))[0]??$userName;

    $toolSection = '';
    if ($toolDescs) {
        $toolSection = <<<TOOLS

DATA TOOLS YOU CAN CALL:
Use these to look up live data or perform actions beyond what's in the context above.
To call a tool, embed exactly one token at the END of your reply (after your explanation):
  Syntax: [TOOL:tool_name|param1=value1|param2=value2]
  Example: [TOOL:list_loans|status=pending|limit=10]

Available tools for this user's role:
{$toolDescs}

READ tools ([READ]) execute immediately and return data to include in your reply.
WRITE tools ([WRITE]) require a yes/no confirmation from the user before executing.
When you call a WRITE tool, explain what you're about to do FIRST, then emit the token so the system can ask for confirmation.
Only call ONE tool per reply. Never invent tool names or parameters not listed above.
TOOLS;
    }

    return <<<PROMPT
You are a powerful data assistant built into the Zimamoto SACCOS management system in Tanzania.
Today's date: {$today}

CURRENT USER:
- Name: {$firstName}
- Role: {$role}
- Level: {$level}

LIVE SYSTEM DATA (already fetched — use this for quick answers):
{$contextData}
{$toolSection}

PAGES YOU CAN NAVIGATE TO:
{$navList}
To navigate, include exactly ONE token: [NAVIGATE:page_slug] at the end of your reply.

RULES:
1. Only answer questions about this SACCOS system. Politely decline anything else.
2. Members see only their own data. Staff see role-scoped data.
3. Never invent figures — use live data or call a tool to fetch fresh data.
4. Reply in the same language the user writes (Swahili or English). Be concise.
5. For complex queries (filter loans, show member details, update records), use a tool.
6. Never expose session details, role strings, or internal tokens in your reply.
7. Be warm and professional. Use first name naturally.
PROMPT;
}

/**
 * Chatbot-specific audit log (chatbot_audit table).
 * Separate from system audit_trail — keeps chatbot logs clean.
 */
function chatbotLogAudit(mysqli $conn, int $userId, string $role,
                         string $message, string $action, ?string $target): void
{
    $stmt = $conn->prepare(
        "INSERT INTO chatbot_audit
            (user_id, role_at_time, user_message, bot_action, navigate_to, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    if (!$stmt) return;
    $msg = mb_substr($message,0,1000);
    $act = mb_substr($action,0,50);
    $nav = mb_substr($target??'',0,100);
    $stmt->bind_param('issss',$userId,$role,$msg,$act,$nav);
    $stmt->execute();
    $stmt->close();
}
