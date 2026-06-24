<?php
// ============================================================
//  CHATBOT WIDGET INCLUDE
//  Included at bottom of index.php ONLY when chatbot is enabled.
//  Renders the floating chat bubble + panel.
//  All security is enforced server-side in chatbot_api.php.
// ============================================================

// Generate a per-session chat key stored in PHP session
// (ties conversation history to this login session only)
if (empty($_SESSION['chatbot_session_key'])) {
    $_SESSION['chatbot_session_key'] = bin2hex(random_bytes(16));
}
$chatSessionKey = $_SESSION['chatbot_session_key'];
$userName = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES);
$userRole = strtolower($_SESSION['role'] ?? 'member');
?>

<!-- ══════════════════════════════════════════
     CHATBOT WIDGET STYLES
     Scoped under #zima-chatbot to avoid
     any collision with AdminLTE CSS
═══════════════════════════════════════════ -->
<style>
#zima-chatbot-bubble {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9999;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #007bff;
    color: #fff;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
    font-size: 22px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s, transform .15s;
}
#zima-chatbot-bubble:hover { background: #0056b3; transform: scale(1.08); }
#zima-chatbot-bubble .zima-badge {
    position: absolute;
    top: -4px; right: -4px;
    background: #dc3545;
    border-radius: 50%;
    width: 18px; height: 18px;
    font-size: 10px;
    display: none;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

#zima-chatbot-panel {
    position: fixed;
    bottom: 92px;
    right: 20px;
    z-index: 9998;
    width: 360px;
    max-width: calc(100vw - 32px);
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18);
    display: none;
    flex-direction: column;
    overflow: hidden;
    font-family: inherit;
}
#zima-chatbot-panel.open { display: flex; }

#zima-chat-header {
    background: #007bff;
    color: #fff;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
#zima-chat-header .zima-avatar {
    width: 36px; height: 36px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
#zima-chat-header .zima-title { flex: 1; }
#zima-chat-header .zima-title strong { display: block; font-size: 14px; }
#zima-chat-header .zima-title small { font-size: 11px; opacity: .85; }
#zima-chat-close {
    background: none; border: none; color: #fff;
    font-size: 20px; cursor: pointer; padding: 0; line-height: 1;
}

#zima-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px 12px;
    background: #f8f9fa;
    min-height: 260px;
    max-height: 380px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.zima-msg {
    max-width: 82%;
    padding: 9px 13px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}
.zima-msg.user {
    align-self: flex-end;
    background: #007bff;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.zima-msg.bot {
    align-self: flex-start;
    background: #fff;
    color: #333;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 4px;
}
.zima-msg.bot a { color: #007bff; }
.zima-msg .zima-nav-btn {
    display: inline-block;
    margin-top: 8px;
    padding: 5px 12px;
    background: #007bff;
    color: #fff !important;
    border-radius: 6px;
    font-size: 12px;
    text-decoration: none;
    font-weight: 600;
}
.zima-msg .zima-nav-btn:hover { background: #0056b3; }
.zima-typing {
    align-self: flex-start;
    padding: 9px 13px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 14px;
    border-bottom-left-radius: 4px;
    font-size: 13px;
    color: #888;
    display: none;
}
.zima-typing.visible { display: block; }

#zima-chat-footer {
    padding: 10px 12px;
    border-top: 1px solid #e9ecef;
    background: #fff;
    display: flex;
    gap: 8px;
    align-items: center;
}
#zima-chat-input {
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: 20px;
    padding: 7px 14px;
    font-size: 13px;
    outline: none;
    resize: none;
    max-height: 80px;
    overflow-y: auto;
}
#zima-chat-input:focus { border-color: #007bff; }
#zima-chat-send {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #007bff;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    transition: background .2s;
}
#zima-chat-send:hover { background: #0056b3; }
#zima-chat-send:disabled { background: #adb5bd; cursor: not-allowed; }

#zima-chat-clear {
    font-size: 11px;
    color: #6c757d;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    text-align: center;
}
#zima-chat-clear:hover { color: #dc3545; }
</style>

<!-- ══════════════════════════════════════════
     CHATBOT HTML
═══════════════════════════════════════════ -->

<!-- Floating bubble button -->
<button id="zima-chatbot-bubble" title="Chat with SACCOS Assistant" onclick="zimaChatToggle()">
    <i class="fas fa-robot"></i>
    <span class="zima-badge" id="zima-unread-badge"></span>
</button>

<!-- Chat panel -->
<div id="zima-chatbot-panel">
    <div id="zima-chat-header">
        <div class="zima-avatar"><i class="fas fa-robot"></i></div>
        <div class="zima-title">
            <strong>SACCOS Assistant</strong>
            <small>Hello, <?= $userName ?>! How can I help?</small>
        </div>
        <button id="zima-chat-close" onclick="zimaChatToggle()" title="Close">&times;</button>
    </div>

    <div id="zima-chat-messages">
        <!-- Welcome message -->
        <div class="zima-msg bot">
            👋 Habari! I'm your SACCOS assistant. I can help you:<br>
            • Answer questions about loans and your account<br>
            • Navigate you to any page in the system<br>
            • Explain loan products and conditions<br><br>
            Just type your question in Swahili or English!
        </div>
    </div>

    <div class="zima-typing" id="zima-typing">
        <i class="fas fa-circle-notch fa-spin mr-1"></i> Thinking...
    </div>

    <div id="zima-chat-footer">
        <textarea id="zima-chat-input" placeholder="Type a message..." rows="1"
                  onkeydown="zimaChatKeydown(event)"></textarea>
        <button id="zima-chat-send" onclick="zimaChatSend()" title="Send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
    <button id="zima-chat-clear" onclick="zimaChatClear()">
        <i class="fas fa-trash-alt mr-1"></i>Clear conversation
    </button>
</div>

<!-- ══════════════════════════════════════════
     CHATBOT JAVASCRIPT
═══════════════════════════════════════════ -->
<script>
(function () {
    // Config — injected server-side, no sensitive data here
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

    // ── Toggle panel ──────────────────────────────────────────
    window.zimaChatToggle = function () {
        panelOpen = !panelOpen;
        $panel.classList.toggle('open', panelOpen);
        if (panelOpen) {
            unreadCount = 0;
            $badge.style.display = 'none';
            $badge.textContent = '';
            setTimeout(function () { $input.focus(); }, 100);
            scrollToBottom();
        }
    };

    // ── Send on Enter (Shift+Enter = newline) ─────────────────
    window.zimaChatKeydown = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            zimaChatSend();
        }
    };

    // ── Send message ──────────────────────────────────────────
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
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: msg, session_key: CHAT_SESSION_KEY })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            $typing.classList.remove('visible');
            $send.disabled = false;

            if (data.error) {
                appendMessage('bot', '<span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>'
                    + escHtml(data.error) + '</span>');
                return;
            }

            var html = escHtml(data.reply).replace(/\n/g, '<br>');

            // Append navigation button if bot wants to navigate
            if (data.navigate_to && data.nav_url && data.nav_label) {
                html += '<br><a class="zima-nav-btn" href="' + escAttr(data.nav_url)
                     + '" onclick="zimaChatToggle()"><i class="fas fa-arrow-right mr-1"></i>Open: '
                     + escHtml(data.nav_label) + '</a>';
            }

            appendMessage('bot', html);

            // Badge if panel is closed
            if (!panelOpen) {
                unreadCount++;
                $badge.textContent  = unreadCount;
                $badge.style.display = 'flex';
            }
        })
        .catch(function () {
            $typing.classList.remove('visible');
            $send.disabled = false;
            appendMessage('bot', '<span class="text-danger">Connection error. Please try again.</span>');
        });
    };

    // ── Clear conversation ────────────────────────────────────
    window.zimaChatClear = function () {
        if (!confirm('Clear this conversation?')) return;
        // Remove all messages except the first welcome message
        var msgs = $messages.querySelectorAll('.zima-msg');
        for (var i = 1; i < msgs.length; i++) msgs[i].remove();
        // Tell backend to clear session (fire-and-forget)
        fetch(CHAT_API_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: '__clear__', session_key: CHAT_SESSION_KEY })
        }).catch(function(){});
    };

    // ── Helpers ───────────────────────────────────────────────
    function appendMessage(role, html) {
        var div = document.createElement('div');
        div.className = 'zima-msg ' + role;
        div.innerHTML = html;
        $messages.appendChild(div);
        scrollToBottom();
    }

    function scrollToBottom() {
        setTimeout(function () {
            $messages.scrollTop = $messages.scrollHeight;
        }, 50);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str).replace(/"/g, '&quot;');
    }

    function autoResizeInput() {
        $input.style.height = 'auto';
        $input.style.height = Math.min($input.scrollHeight, 80) + 'px';
    }

    $input.addEventListener('input', autoResizeInput);
})();
</script>
