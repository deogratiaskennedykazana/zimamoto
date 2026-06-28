<?php
// ============================================================
//  CHATBOT WIDGET INCLUDE  v2 — with tool-calling UI
// ============================================================
if (empty($_SESSION['chatbot_session_key'])) {
    $_SESSION['chatbot_session_key'] = bin2hex(random_bytes(16));
}
$chatSessionKey = $_SESSION['chatbot_session_key'];
$userName = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES);
$userRole = strtolower($_SESSION['role'] ?? 'member');
$isAdmin  = in_array($userRole, ['admin','superadmin','super admin'], true);
?>
<style>
#zima-chatbot-bubble {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    width: 52px; height: 52px; border-radius: 50%;
    background: #007bff; color: #fff; border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,.25); font-size: 22px;
    cursor: pointer; display: flex; align-items: center;
    justify-content: center; transition: background .2s, transform .15s;
}
#zima-chatbot-bubble:hover { background: #0056b3; transform: scale(1.08); }
#zima-chatbot-bubble .zima-badge {
    position: absolute; top: -4px; right: -4px;
    background: #dc3545; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px;
    display: none; align-items: center; justify-content: center; font-weight: 700;
}
#zima-chatbot-panel {
    position: fixed; bottom: 92px; right: 20px; z-index: 9998;
    width: 400px; max-width: calc(100vw - 32px);
    background: #fff; border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18);
    display: none; flex-direction: column; overflow: hidden; font-family: inherit;
}
#zima-chatbot-panel.open { display: flex; }
#zima-chat-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: #fff; padding: 12px 16px;
    display: flex; align-items: center; gap: 10px;
}
#zima-chat-header .zima-avatar {
    width: 36px; height: 36px; background: rgba(255,255,255,.2);
    border-radius: 50%; display: flex; align-items: center;
    justify-content: center; font-size: 18px;
}
#zima-chat-header .zima-title { flex: 1; }
#zima-chat-header .zima-title strong { display: block; font-size: 14px; }
#zima-chat-header .zima-title small { font-size: 11px; opacity: .85; }
#zima-chat-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 0; }
#zima-chat-messages {
    flex: 1; overflow-y: auto; padding: 14px 12px;
    background: #f0f4f8; min-height: 280px; max-height: 420px;
    display: flex; flex-direction: column; gap: 10px;
}
.zima-msg {
    max-width: 88%; padding: 9px 13px; border-radius: 14px;
    font-size: 13px; line-height: 1.55; word-break: break-word;
}
.zima-msg.user {
    align-self: flex-end; background: #007bff; color: #fff;
    border-bottom-right-radius: 4px;
}
.zima-msg.bot {
    align-self: flex-start; background: #fff; color: #333;
    border: 1px solid #dde3ea; border-bottom-left-radius: 4px;
}
.zima-msg.bot a { color: #007bff; }
.zima-msg.confirm-msg {
    background: #fff8e1; border-color: #ffc107;
}
.zima-msg.tool-result {
    background: #e8f5e9; border-color: #4caf50;
    font-family: monospace; font-size: 12px; white-space: pre-wrap;
}
.zima-confirm-btns { display: flex; gap: 8px; margin-top: 8px; }
.zima-confirm-btns button {
    flex: 1; padding: 5px; border-radius: 6px; border: none;
    cursor: pointer; font-size: 13px; font-weight: 600;
}
.zima-btn-yes { background: #28a745; color: #fff; }
.zima-btn-yes:hover { background: #1e7e34; }
.zima-btn-no  { background: #dc3545; color: #fff; }
.zima-btn-no:hover  { background: #bd2130; }
.zima-msg .zima-nav-btn {
    display: inline-block; margin-top: 10px; padding: 7px 16px;
    background: linear-gradient(135deg,#007bff,#0056b3); color: #fff !important; border-radius: 8px;
    font-size: 13px; text-decoration: none; font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,91,187,.25); letter-spacing:.2px;
    transition: background .2s, transform .1s;
}
.zima-msg .zima-nav-btn:hover { background: linear-gradient(135deg,#0056b3,#003d82); transform: translateY(-1px); }
.zima-typing {
    align-self: flex-start; padding: 9px 13px; background: #fff;
    border: 1px solid #dde3ea; border-radius: 14px;
    border-bottom-left-radius: 4px; font-size: 13px; color: #888; display: none;
}
.zima-typing.visible { display: block; }
#zima-chat-footer {
    padding: 10px 12px; border-top: 1px solid #e9ecef;
    background: #fff; display: flex; gap: 8px; align-items: center;
}
<?php if ($isAdmin): ?>
#zima-quick-actions {
    padding: 6px 12px; border-bottom: 1px solid #e9ecef;
    background: #f8f9fa; display: flex; flex-wrap: wrap; gap: 5px;
}
.zima-qa-btn {
    font-size: 11px; padding: 3px 8px; border-radius: 12px;
    border: 1px solid #dee2e6; background: #fff; cursor: pointer;
    color: #495057; white-space: nowrap;
}
.zima-qa-btn:hover { background: #007bff; color: #fff; border-color: #007bff; }
<?php endif; ?>
#zima-chat-input {
    flex: 1; border: 1px solid #ced4da; border-radius: 20px;
    padding: 7px 14px; font-size: 13px; outline: none;
    resize: none; max-height: 80px; overflow-y: auto;
}
#zima-chat-input:focus { border-color: #007bff; }
#zima-chat-send {
    width: 36px; height: 36px; border-radius: 50%;
    background: #007bff; color: #fff; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0; transition: background .2s;
}
#zima-chat-send:hover { background: #0056b3; }
#zima-chat-send:disabled { background: #adb5bd; cursor: not-allowed; }
#zima-chat-clear {
    font-size: 11px; color: #6c757d; background: none;
    border: none; cursor: pointer; padding: 4px 8px; text-align: center;
}
#zima-chat-clear:hover { color: #dc3545; }
</style>

<button id="zima-chatbot-bubble" title="Chat with SACCOS Assistant" onclick="zimaChatToggle()">
    <i class="fas fa-robot"></i>
    <span class="zima-badge" id="zima-unread-badge"></span>
</button>

<div id="zima-chatbot-panel">
    <div id="zima-chat-header">
        <div class="zima-avatar"><i class="fas fa-robot"></i></div>
        <div class="zima-title">
            <strong>SACCOS Assistant</strong>
            <small>Hello, <?= $userName ?>! Ask me anything.</small>
        </div>
        <button id="zima-chat-close" onclick="zimaChatToggle()" title="Close">&times;</button>
    </div>

    <?php if ($isAdmin): ?>
    <div id="zima-quick-actions">
        <span style="font-size:10px;color:#6c757d;align-self:center;">Quick:</span>
        <button class="zima-qa-btn" onclick="zimaSend('Show me pending loans')">⏳ Pending Loans</button>
        <button class="zima-qa-btn" onclick="zimaSend('List all members')">👥 All Members</button>
        <button class="zima-qa-btn" onclick="zimaSend('Show all loan products')">📋 Products</button>
        <button class="zima-qa-btn" onclick="zimaSend('Show branches')">🏢 Branches</button>
        <button class="zima-qa-btn" onclick="zimaSend('Show approved loans this month')">✅ Approved</button>
        <button class="zima-qa-btn" onclick="zimaSend('Dashboard stats')">📊 Dashboard</button>
    </div>
    <?php endif; ?>

    <div id="zima-chat-messages">
        <div class="zima-msg bot">
            👋 Habari! I'm your SACCOS data assistant.<br>
            <?php if ($isAdmin): ?>
            As admin, I can:<br>
            • <strong>Query</strong> members, amana, shares, savings, loans (by name or filter)<br>
            • <strong>Approve/reject</strong> loans &amp; member registrations<br>
            • <strong>Deposit</strong> to any member account<br>
            • <strong>Navigate</strong> you to any page<br>
            <small style="color:#888;">Try: <em>"show amana for Musa"</em>, <em>"list all members"</em>, <em>"edit John"</em></small>
            <?php else: ?>
            I can answer questions about your account, loans, and navigate you around the system.<br>
            <?php endif; ?>
            <br>Type in Swahili or English!
        </div>
    </div>

    <div class="zima-typing" id="zima-typing">
        <i class="fas fa-circle-notch fa-spin mr-1"></i> Thinking...
    </div>

    <div id="zima-chat-footer">
        <textarea id="zima-chat-input" placeholder="Ask anything about your SACCOS data..." rows="1"
                  onkeydown="zimaChatKeydown(event)"></textarea>
        <button id="zima-chat-send" onclick="zimaChatSend()" title="Send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
    <button id="zima-chat-clear" onclick="zimaChatClear()">
        <i class="fas fa-trash-alt mr-1"></i>Clear conversation
    </button>
</div>

<script>
(function () {
    var CHAT_SESSION_KEY = <?= json_encode($chatSessionKey) ?>;
    var CHAT_API_URL     = './chatbot/chatbot_api.php';

    var $panel    = document.getElementById('zima-chatbot-panel');
    var $messages = document.getElementById('zima-chat-messages');
    var $input    = document.getElementById('zima-chat-input');
    var $send     = document.getElementById('zima-chat-send');
    var $typing   = document.getElementById('zima-typing');
    var $badge    = document.getElementById('zima-unread-badge');
    var unreadCount = 0;
    var panelOpen   = false;
    var awaitingConfirm = false;

    window.zimaChatToggle = function () {
        panelOpen = !panelOpen;
        $panel.classList.toggle('open', panelOpen);
        if (panelOpen) {
            unreadCount = 0;
            $badge.style.display = 'none';
            $badge.textContent   = '';
            setTimeout(function(){ $input.focus(); }, 100);
            scrollToBottom();
        }
    };

    window.zimaChatKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); zimaChatSend(); }
    };

    // Quick action helper (called from quick-action buttons)
    window.zimaSend = function(msg) {
        $input.value = msg;
        zimaChatSend();
        if (!panelOpen) zimaChatToggle();
    };

    window.zimaChatSend = function () {
        var msg = $input.value.trim();
        if (!msg || $send.disabled) return;

        appendMessage('user', escHtml(msg));
        $input.value = '';
        autoResizeInput();
        $send.disabled = true;
        $typing.classList.add('visible');
        scrollToBottom();

        fetch(CHAT_API_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({message: msg, session_key: CHAT_SESSION_KEY})
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            $typing.classList.remove('visible');
            $send.disabled = false;

            if (data.error) {
                appendMessage('bot', '<span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>'
                    + escHtml(data.error) + '</span>');
                return;
            }

            var replyText = data.reply || '';

            // Detect confirmation prompt from server
            var isConfirmPrompt = replyText.includes('Type **yes** to confirm');
            awaitingConfirm = isConfirmPrompt;

            var html = formatReply(replyText);

            if (data.navigate_to && data.nav_url && data.nav_label) {
                html += '<br><a class="zima-nav-btn" href="' + escAttr(data.nav_url)
                      + '"><i class="fas fa-arrow-right mr-1"></i>Go to: '
                      + escHtml(data.nav_label) + ' →</a>';
            }

            var msgClass = isConfirmPrompt ? 'confirm-msg' : '';
            appendMessage('bot', html, msgClass);

            // Add confirm/cancel buttons if this is a confirmation prompt
            if (isConfirmPrompt) {
                var lastMsg = $messages.lastElementChild;
                var btnDiv  = document.createElement('div');
                btnDiv.className = 'zima-confirm-btns';
                btnDiv.innerHTML = '<button class="zima-btn-yes" onclick="zimaSend(\'yes\')">✅ Confirm</button>'
                                 + '<button class="zima-btn-no"  onclick="zimaSend(\'no\')">❌ Cancel</button>';
                lastMsg.appendChild(btnDiv);
            }

            if (!panelOpen) {
                unreadCount++;
                $badge.textContent  = unreadCount;
                $badge.style.display = 'flex';
            }
        })
        .catch(function(){
            $typing.classList.remove('visible');
            $send.disabled = false;
            appendMessage('bot', '<span class="text-danger">Connection error. Please try again.</span>');
        });
    };

    window.zimaChatClear = function () {
        if (!confirm('Clear this conversation?')) return;
        awaitingConfirm = false;
        var msgs = $messages.querySelectorAll('.zima-msg');
        for (var i = 1; i < msgs.length; i++) msgs[i].remove();
        fetch(CHAT_API_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({message:'__clear__', session_key: CHAT_SESSION_KEY})
        }).catch(function(){});
    };

    // Format reply: bold **text**, newlines→<br>
    function formatReply(text) {
        return escHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function appendMessage(role, html, extraClass) {
        var div = document.createElement('div');
        div.className = 'zima-msg ' + role + (extraClass ? ' ' + extraClass : '');
        div.innerHTML = html;
        $messages.appendChild(div);
        scrollToBottom();
    }

    function scrollToBottom() {
        setTimeout(function(){ $messages.scrollTop = $messages.scrollHeight; }, 50);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function escAttr(str) { return String(str).replace(/"/g,'&quot;'); }

    function autoResizeInput() {
        $input.style.height = 'auto';
        $input.style.height = Math.min($input.scrollHeight, 80) + 'px';
    }
    $input.addEventListener('input', autoResizeInput);
})();
</script>
