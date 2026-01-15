<?php
// /integrations/aiChat/chatbotWidget.php - Clean & Responsive Version
// AirLyft Chat Widget - Horizon AI Assistant

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get AI status
$ai_status = ['available' => false, 'mode' => 'fallback', 'models' => []];
$user_id = $_SESSION['user_id'] ?? null;
$is_logged_in = false;
$first_name = null;

if ($user_id) {
    try {
        require_once __DIR__ . '/../../db/connect.php';
        $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $is_logged_in = true;
            $first_name = $row['first_name'];
        }
    } catch (Exception $e) {
        // Silent fail
    }
}
?>

<!-- AirLyft Chat Widget - Horizon -->
<div id="airlyft-chat" class="airlyft-chat">
    <!-- Toggle Button -->
    <button id="chat-toggle" class="chat-toggle" title="Open chat with Horizon">
        <img src="/airlyft/assets/img/icon.png" alt="Horizon AI" class="toggle-icon">
    </button>

    <!-- Chat Window -->
    <div id="chat-window" class="chat-window">
        <!-- Header -->
        <div class="chat-header">
            <div class="header-left">
                <div class="chat-avatar">
                    <img src="/airlyft/assets/img/icon.png" alt="Horizon AI" class="avatar-icon">
                </div>
                <div class="header-info">
                    <h6 class="mb-1">Horizon</h6>
                    <small class="text-muted">
                        <?php if ($is_logged_in && $first_name): ?>
                            <?= htmlspecialchars($first_name) ?> •
                        <?php endif; ?>
                        <span id="ai-status-badge"><?= $ai_status['available'] ? 'Online' : 'Offline' ?></span>
                    </small>
                </div>
            </div>
            <div class="header-right">
                <button id="clear-chat" class="btn-action" title="Clear chat">
                    <i class='bx bx-trash'></i>
                </button>
                <button id="chat-close" class="btn-close">×</button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chat-messages">
            <!-- Welcome Message -->
            <div class="message message-agent">
                <div class="message-bubble">
                    <div class="message-header">
                        <strong>Horizon</strong>
                        <?php if (!$ai_status['available']): ?>
                            <span class="badge badge-warning">Enhanced Mode</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_logged_in && $first_name): ?>
                        <p>Hello <strong><?= htmlspecialchars($first_name) ?></strong>!</p>
                    <?php else: ?>
                        <p>Hello! Welcome to AirLyft.</p>
                    <?php endif; ?>

                    <div id="ai-welcome-status" class="mt-3">
                        <?php if (!$ai_status['available']): ?>
                            <div class="alert alert-info alert-sm" id="ai-warning-msg">
                                <p class="mb-1"><strong>AI Service: OFFLINE</strong></p>
                                <p class="mb-2"><small>Using enhanced response mode</small></p>
                                <small>
                                    <a href="javascript:void(0)" onclick="showAISetup()">Setup Instructions</a> •
                                    <a href="javascript:void(0)" onclick="checkAIStatus()">Check Status</a>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success alert-sm" id="ai-status-msg">
                                <p class="mb-1"><strong>Ollama Online</strong></p>
                                <p class="mb-0"><small>Models: <?= !empty($ai_status['models']) ? htmlspecialchars(implode(', ', $ai_status['models'])) : 'Unknown' ?></small></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <p class="mb-2"><strong>I can help you with:</strong></p>
                        <ul class="list-unstyled mb-3 help-list">
                            <li><svg class="help-icon" viewBox="0 0 24 24" aria-hidden focusable="false"><path fill="#0052cc" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg> AirLyft Destinations - 12 exclusive locations</li>
                            <li><svg class="help-icon help-icon-aircraft" viewBox="0 0 24 24" aria-hidden focusable="false"><path fill="#ff6b35" d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9L2 14v2l8-1.5V21l2-1 2 1v-6.5L21 16z"/></svg> AirLyft Aircraft - 4 luxury types</li>
                            <li><svg class="help-icon" viewBox="0 0 24 24" aria-hidden focusable="false"><path fill="#f77f00" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54h2.96l-4.84 6.32L9.5 13.5H6.54L11.37 7z"/></svg> AirLyft Booking - 8-step process</li>
                        </ul>
                        <p class="small text-muted"><em>I only discuss AirLyft luxury travel services.</em></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="chat-input-area">
            <div class="input-group">
                <input type="text" id="chat-input" class="form-control" placeholder="Ask Horizon..." autocomplete="off">
                <button id="send-btn" class="btn btn-primary" type="button">
                    <i class='bx bx-send'></i>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer">
            <small class="text-muted">
                Horizon •
                <span id="footer-status"><?= $ai_status['available'] ? 'AI Active' : 'Enhanced Mode' ?></span>
                <?php if (!$ai_status['available']): ?>
                    • <a href="javascript:void(0)" onclick="showAISetup()" id="setup-ai-link">Setup</a>
                <?php else: ?>
                    • <a href="javascript:void(0)" onclick="showAISetup()" id="setup-ai-link" style="display: none;">Info</a>
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>

<!-- Styles - Clean & Responsive -->
<style>
    /* Base Styles */
    .airlyft-chat {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    /* Toggle Button */
    .chat-toggle {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #0052cc 0%, #0047ab 100%);
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(0, 82, 204, 0.35);
        transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 0;
    }

    .chat-toggle:hover {
        transform: scale(1.12) translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 82, 204, 0.45);
    }

    .chat-toggle:active {
        transform: scale(0.98);
    }

    .toggle-icon {
        width: 28px;
        height: 28px;
        object-fit: contain;
        filter: brightness(0) invert(1);
        opacity: 0.95;
    }

    /* Chat Window */
    .chat-window {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 380px;
        height: 580px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        opacity: 0;
        transform: scale(0.75) translateY(30px);
        transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        pointer-events: none;
    }

    .chat-window.active {
        opacity: 1;
        transform: scale(1) translateY(0);
        pointer-events: auto;
    }

    /* Header */
    .chat-header {
        padding: 18px;
        background: linear-gradient(135deg, #0052cc 0%, #0047ab 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .header-left {
        display: flex;
        gap: 14px;
        align-items: center;
        flex: 1;
    }

    .chat-avatar {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 2px solid rgba(255, 255, 255, 0.3);
        overflow: hidden;
    }

    .avatar-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .header-info h6 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: white;
        letter-spacing: 0.3px;
    }

    .header-info small {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 500;
    }

    .header-right {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-action,
    .btn-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 18px;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .btn-action:hover,
    .btn-close:hover {
        opacity: 0.8;
        transform: scale(1.1);
    }

    /* Messages Area */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f8f9fa;
    }

    .message {
        margin-bottom: 16px;
        display: flex;
        animation: slideIn 0.3s ease;
    }

    .message-agent {
        justify-content: flex-start;
    }

    .message-user {
        justify-content: flex-end;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message-bubble {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 12px;
        word-wrap: break-word;
    }

    .message-agent .message-bubble {
        background: #f5f7fa;
        border: 1px solid #e8eef7;
        border-radius: 0 14px 14px 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .message-user .message-bubble {
        background: linear-gradient(135deg, #0052cc 0%, #0047ab 100%);
        color: white;
        border-radius: 14px 0 14px 14px;
        box-shadow: 0 2px 8px rgba(0, 82, 204, 0.2);
    }

    .message-header {
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }

    .message-agent .message-header {
        color: #0052cc;
    }

    .message-bubble p {
        margin: 0 0 8px 0;
        font-size: 14px;
        line-height: 1.5;
        font-weight: 500;
    }

    .message-bubble p:last-child {
        margin-bottom: 0;
    }

    /* Alerts */
    .alert-sm {
        padding: 12px 14px;
        margin: 0;
        border-radius: 10px;
        font-size: 13px;
        border: 1px solid;
        transition: all 0.2s;
    }

    .alert-info {
        background: #eff7ff;
        color: #0052cc;
        border-color: #b3d9ff;
    }

    .alert-success {
        background: #f0f9ff;
        color: #065f46;
        border-color: #a7f3d0;
    }

    /* Help List */
    .help-list li {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        border-bottom: 1px solid #f0f0f0;
    }

    .help-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .help-icon-aircraft {
        width: 18px;
        height: 18px;
        margin-right: 4px;
    }

    .help-list li:last-child {
        border-bottom: none;
    }

    .help-list i {
        font-size: 18px;
        flex-shrink: 0;
        min-width: 24px;
    }

    /* Input Area */
    .chat-input-area {
        padding: 14px 16px;
        background: white;
        border-top: 1px solid #f0f0f0;
        flex-shrink: 0;
    }

    .input-group {
        display: flex;
        gap: 10px;
    }

    .chat-input-area .form-control {
        border-radius: 24px;
        border: 1.5px solid #e5e7eb;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.25s;
        background: #f9fafb;
    }

    .chat-input-area .form-control:focus {
        border-color: #0052cc;
        box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.1);
        background: white;
    }

    .chat-input-area .form-control::placeholder {
        color: #9ca3af;
        font-weight: 500;
    }

    .chat-input-area .btn-primary {
        border-radius: 50%;
        width: 40px;
        height: 40px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(135deg, #0052cc 0%, #0047ab 100%);
        border: none;
        transition: all 0.25s;
        box-shadow: 0 2px 8px rgba(0, 82, 204, 0.2);
    }

    .chat-input-area .btn-primary:hover {
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
    }

    /* Footer */
    .chat-footer {
        padding: 14px 16px;
        background: #f9fafb;
        border-top: 1px solid #f0f0f0;
        text-align: center;
        border-radius: 0 0 16px 16px;
        flex-shrink: 0;
    }

    .chat-footer a {
        color: #0052cc;
        text-decoration: none;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.2s;
    }

    .chat-footer a:hover {
        color: #0047ab;
        text-decoration: underline;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 12px 0;
        padding: 12px;
        background: #f0f7ff;
        border-radius: 10px;
        animation: slideIn 0.3s ease;
    }

    .quick-action-btn {
        padding: 8px 14px;
        background: linear-gradient(135deg, #0052cc 0%, #0047ab 100%);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0, 82, 204, 0.2);
    }

    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 82, 204, 0.3);
    }

    .quick-action-btn:active {
        transform: translateY(0);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .chat-window {
            width: 100vw;
            height: 100vh;
            max-width: 100%;
            bottom: 0;
            right: 0;
            border-radius: 0;
            top: 0;
        }

        .chat-window.active {
            transform: scale(1) translateY(0);
        }

        .message-bubble {
            max-width: 95%;
        }
    }

    @media (max-width: 480px) {
        .airlyft-chat {
            bottom: 10px;
            right: 10px;
        }

        .chat-toggle {
            width: 50px;
            height: 50px;
        }

        .toggle-icon {
            width: 24px;
            height: 24px;
        }

        .chat-window {
            width: 100vw;
            height: 100vh;
            bottom: 0;
            right: 0;
        }

        .chat-header {
            padding: 12px;
        }

        .chat-messages {
            padding: 12px;
        }

        .message-bubble {
            font-size: 13px;
        }
    }

    /* Scrollbar Styling */
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-messages::-webkit-scrollbar-thumb {
        background: #bbb;
        border-radius: 3px;
    }

    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
</style>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM Elements
        const chatToggle = document.getElementById('chat-toggle');
        const chatWindow = document.getElementById('chat-window');
        const chatClose = document.getElementById('chat-close');
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        const clearBtn = document.getElementById('clear-chat');

        // Capture initial welcome HTML so it can be restored after clearing
        const initialWelcomeHTML = chatMessages.innerHTML;

        // Utility to escape and preserve line breaks
        function formatResponseText(text) {
            if (!text) return '';
            const esc = escapeHtml(text);
            return esc.replace(/\r\n|\r|\n/g, '<br>');
        }

        // Toggle Chat
        chatToggle.addEventListener('click', () => {
            chatWindow.classList.toggle('active');
            if (chatWindow.classList.contains('active')) {
                chatInput.focus();
            }
        });

        // Close Chat
        chatClose.addEventListener('click', () => {
            chatWindow.classList.remove('active');
        });

        // Send Message events
        sendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Disable/enable send UI
        function setSending(isSending) {
            sendBtn.disabled = isSending;
            if (isSending) {
                sendBtn.classList.add('sending');
            } else {
                sendBtn.classList.remove('sending');
            }
        }

        // Send Message Function - send JSON and handle errors robustly
        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            addMessage(message, 'user');
            chatInput.value = '';
            setSending(true);
            showTyping();

            try {
                const res = await fetch('/airlyft/integrations/aiChat/chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                // Try parse JSON; handle non-JSON gracefully
                let data;
                const text = await res.text();
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // Non-JSON response from server
                    removeTyping();
                    addMessage('AI Service returned invalid response. Please try again later.', 'agent');
                    console.error('Non-JSON response:', text);
                    return;
                }

                removeTyping();
                if (data && data.success) {
                    // format reply preserving newlines
                    const formatted = formatResponseText(data.reply || '');
                    addMessage(formatted, 'agent', true);
                } else {
                    addMessage(formatResponseText(data.reply || 'Sorry, an error occurred.'), 'agent', true);
                }

                // Quick actions handling
                if (data.quick_actions && data.quick_actions.length > 0) {
                    addQuickActions(data.quick_actions);
                }
            } catch (err) {
                removeTyping();
                addMessage('Connection error. Please try again.', 'agent');
                console.error('Send message error:', err);
            } finally {
                setSending(false);
            }
        }

        // Add Message to Chat
        // if isHtml true, message is already escaped/formatted
        function addMessage(text, sender, isHtml = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message message-' + sender;
            const header = sender === 'agent' ? '<div class="message-header"><strong>Horizon</strong></div>' : '';
            messageDiv.innerHTML = `
            <div class="message-bubble">
                ${header}
                <p>${ isHtml ? text : escapeHtml(text).replace(/\r\n|\r|\n/g, '<br>') }</p>
            </div>
        `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Typing Indicator
        function showTyping() {
            removeTyping();
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message message-agent';
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `
            <div class="message-bubble">
                <p>Horizon is typing<span class="dots"><span>.</span><span>.</span><span>.</span></span></p>
            </div>
        `;
            chatMessages.appendChild(typingDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTyping() {
            const typing = document.getElementById('typing-indicator');
            if (typing) typing.remove();
        }

        // Clear Chat - restore welcome block
        clearBtn.addEventListener('click', () => {
            if (confirm('Clear all messages?')) {
                fetch('/airlyft/integrations/aiChat/clearChat.php', { method: 'POST' })
                    .then(async res => {
                        try { await res.json(); } catch (e) { /* ignore */ }
                    })
                    .finally(() => {
                        chatMessages.innerHTML = initialWelcomeHTML;
                        removeTyping();
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        chatInput.focus();
                    });
            }
        });

        // AI Status update functions
        async function updateAIStatus() {
            const isOnline = await checkOllamaStatus();

            const badge = document.getElementById('ai-status-badge');
            if (badge) badge.textContent = isOnline ? 'Online' : 'Offline';

            const footer = document.getElementById('footer-status');
            if (footer) footer.textContent = isOnline ? 'AI Active' : 'Enhanced Mode';

            const setupLink = document.getElementById('setup-ai-link');
            if (setupLink) setupLink.style.display = isOnline ? 'none' : 'inline';

            const aiWarningMsg = document.getElementById('ai-warning-msg');
            const aiStatusMsg = document.getElementById('ai-status-msg');

            if (isOnline) {
                if (aiWarningMsg) aiWarningMsg.style.display = 'none';
                if (aiStatusMsg) aiStatusMsg.style.display = 'block';
            } else {
                if (aiWarningMsg) aiWarningMsg.style.display = 'block';
                if (aiStatusMsg) aiStatusMsg.style.display = 'none';
            }
        }

        async function checkOllamaStatus() {
            try {
                const res = await fetch('/airlyft/integrations/aiChat/checkAI.php');
                const data = await res.json();
                return !!(data && data.ollama && data.ollama.available);
            } catch (e) {
                return false;
            }
        }

        // Update every 5 seconds
        setInterval(updateAIStatus, 5000);
        updateAIStatus();
    });

    // Global helper functions
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function showAISetup() {
        alert('AI Setup Instructions:\n\n1. Download Ollama from ollama.com\n2. Run: ollama pull gemma3:4b\n3. Run: ollama serve\n4. Refresh this page');
    }

    function checkAIStatus() {
        alert('Checking AI status...');
    }

    // New function to display quick action buttons
    function addQuickActions(actions) {
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'quick-actions';
        
        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = 'quick-action-btn';
            btn.textContent = action;
            btn.onclick = () => {
                chatInput.value = action;
                sendMessage();
                actionsDiv.remove();
            };
            actionsDiv.appendChild(btn);
        });
        
        chatMessages.appendChild(actionsDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
</script>