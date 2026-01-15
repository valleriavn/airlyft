<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    }
}
?>

<div id="airlyft-chat" class="airlyft-chat">
    <button id="chat-toggle" class="btn btn-primary chat-toggle-btn shadow-lg" type="button">
        <svg class="me-2" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
        </svg>
        <span>Chat with us!</span>
    </button>

    <div id="chat-window" class="chat-window">
        <div class="chat-header">
            <div class="header-content">
                <div class="header-top">
                    <div class="brand-section">
                        <div class="brand-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" />
                            </svg>
                        </div>
                        <div class="brand-info">
                            <h5>Horizon</h5>
                            <p class="status-text">
                                <span class="status-dot"></span>
                                <span id="header-status">Online</span>
                            </p>
                        </div>
                    </div>
                    <button id="chat-close" class="btn-close-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chat-messages">
            <div class="message message-agent">
                <div class="message-content">
                    <div class="message-avatar">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" />
                        </svg>
                    </div>
                    <div class="message-bubble agent-bubble">
                        <?php if ($is_logged_in && $first_name): ?>
                            <p>Hello <strong><?= htmlspecialchars($first_name) ?></strong>! I am Horizon, your AirLyft travel assistant. I am here to provide destination recommendations and assist with your booking. How may I be of service?</p>
                        <?php else: ?>
                            <p>Greetings! I am Horizon, your AirLyft Luxury Travel Assistant. How may I help you with destination recommendations or booking assistance today?</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="initial-quick-replies" id="initial-quick-replies">
                <button class="quick-reply-btn" data-message="I want to book a trip">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2.5-9H19V0h-2v2H7V0H5v2H3.5C2.67 2 2 2.67 2 3.5v17C2 21.33 2.67 22 3.5 22h17c.83 0 1.5-.67 1.5-1.5v-17C22 2.67 21.33 2 20.5 2z" />
                    </svg>
                    Book a Trip
                </button>
                <button class="quick-reply-btn" data-message="Show me destinations">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    View Destinations
                </button>
                <button class="quick-reply-btn" data-message="How much does it cost?">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                    </svg>
                    Check Pricing
                </button>
                <button class="quick-reply-btn" data-message="What aircraft do you have?">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" />
                    </svg>
                    View Aircraft
                </button>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="input-wrapper">
                <input
                    type="text"
                    id="chat-input"
                    class="chat-input"
                    placeholder="Ask about destinations, pricing, or book..."
                    autocomplete="off"
                    maxlength="500">
                <button id="send-btn" class="send-button">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.9429026 L21.714504,14.0454487 C22.6563168,13.5741566 23.1272231,12.6315722 22.9702544,11.6889879 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4776575 C0.994623095,2.10604706 0.837654326,3.0486314 1.15159189,3.99701575 L3.03521743,10.4380088 C3.03521743,10.5951061 3.19218622,10.7522035 3.50612381,10.7522035 L16.6915026,11.5376905 C16.6915026,11.5376905 17.1624089,11.5376905 17.1624089,12.0089827 C17.1624089,12.4744748 16.6915026,12.4744748 16.6915026,12.4744748 Z" />
                    </svg>
                </button>
            </div>


            <div class="chat-footer">
                <p class="footer-text">Powered by <strong>AirLyft Horizon</strong> <span id="setup-link-container" style="display: none;">• <a href="javascript:void(0)" onclick="showAISetupModal()">Setup Guide</a></span></p>
            </div>
        </div>
    </div>

    <div id="ai-setup-modal" class="ai-setup-modal">
        <div class="modal-overlay"></div>
        <div class="modal-card">
            <button class="modal-close" onclick="closeAISetupModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-header-section">
                <div class="header-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.62l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.09-.47 0-.59.22L2.74 8.87c-.12.21-.08.48.1.62l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.1.62l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.48-.12-.62l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
                    </svg>
                </div>
                <h2>Horizon AI Setup</h2>
                <p>Enhance your travel experience with advanced AI</p>
            </div>

            <div class="modal-body-section">
                <div class="setup-section">
                    <h3>🚀 Quick Setup (5 Minutes)</h3>
                    <ol class="setup-steps">
                        <li>
                            <span class="step-badge">1</span>
                            <div>
                                <strong>Download Ollama</strong>
                                <p>Visit <a href="https://ollama.com" target="_blank" rel="noopener">ollama.com</a></p>
                            </div>
                        </li>
                        <li>
                            <span class="step-badge">2</span>
                            <div>
                                <strong>Install & Run</strong>
                                <p>Follow installation wizard</p>
                            </div>
                        </li>
                        <li>
                            <span class="step-badge">3</span>
                            <div>
                                <strong>Pull Models</strong>
                                <code>ollama pull mistral:latest</code>
                            </div>
                        </li>
                        <li>
                            <span class="step-badge">4</span>
                            <div>
                                <strong>Start Server</strong>
                                <code>ollama serve</code>
                            </div>
                        </li>
                        <li>
                            <span class="step-badge">5</span>
                            <div>
                                <strong>Refresh Browser</strong>
                                <p>Horizon will auto-detect</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="system-status">
                    <h3>📊 System Status</h3>
                    <div class="status-cards">
                        <div class="status-card">
                            <span class="status-label">Ollama</span>
                            <span class="status-value" id="modal-ollama-status">Checking...</span>
                        </div>
                        <div class="status-card">
                            <span class="status-label">Models</span>
                            <span class="status-value" id="modal-ai-models">0</span>
                        </div>
                        <div class="status-card">
                            <span class="status-label">Server</span>
                            <span class="status-value">localhost:11434</span>
                        </div>
                        <div class="status-card">
                            <span class="status-label">Mode</span>
                            <span class="status-value" id="modal-ai-mode">Smart</span>
                        </div>
                    </div>
                </div>

                <div class="help-section">
                    <h3>❓ Troubleshooting</h3>
                    <div class="help-items">
                        <div class="help-item">
                            <strong>Can't connect?</strong>
                            <p>Ensure Ollama is running: <code>http://127.0.0.1:11434</code></p>
                        </div>
                        <div class="help-item">
                            <strong>No models?</strong>
                            <p>Pull models via terminal: <code>ollama pull mistral:latest</code></p>
                        </div>
                        <div class="help-item">
                            <strong>Slow responses?</strong>
                            <p>Larger models need more system resources</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer-section">
                <button class="modal-btn secondary" onclick="closeAISetupModal()">Close</button>
                <button class="modal-btn primary" onclick="checkAIStatusModal()">Check Status</button>
            </div>
        </div>
    </div>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --primary: #0052cc;
            --primary-dark: #002d72;
            --primary-light: #e8f0ff;
            --accent: #ff6b35;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --border: #e5e7eb;
            --text: #374151;
            --text-light: #6b7280;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .airlyft-chat {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .chat-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .chat-toggle-btn:hover {
            transform: translateY(-2px);
        }

        .chat-toggle-btn svg {
            flex-shrink: 0;
        }

        .chat-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 420px;
            height: 650px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: scale(0.8) translateY(30px);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            overflow: hidden;
        }

        .chat-window.active {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 24px;
            border-radius: 20px 20px 0 0;
            flex-shrink: 0;
        }

        .header-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .brand-section {
            display: flex;
            gap: 12px;
            align-items: center;
            flex: 1;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .brand-icon svg {
            width: 28px;
            height: 28px;
            filter: brightness(0) invert(1);
        }

        .brand-info h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .status-text {
            margin: 4px 0 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .btn-close-header {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            padding: 0;
        }

        .btn-close-header:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .btn-close-header svg {
            width: 20px;
            height: 20px;
        }

        .header-welcome {
            text-align: left;
        }

        .header-welcome p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
        }

        .header-welcome .subtitle {
            font-size: 13px;
            opacity: 0.85;
            font-weight: 500;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--light);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
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

        .message-content {
            display: flex;
            gap: 12px;
            max-width: 90%;
        }

        .message-user .message-content {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--primary);
        }

        .message-avatar svg {
            width: 20px;
            height: 20px;
        }

        .message-bubble {
            padding: 14px 16px;
            border-radius: 16px;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
        }

        .agent-bubble {
            background: white;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            color: var(--text);
            border-radius: 4px 16px 16px 16px;
        }

        .user-bubble {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px 4px 16px 16px;
        }

        .bubble-header {
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--primary);
        }

        .message-agent .bubble-header {
            color: var(--primary);
        }

        .message-user .bubble-header {
            color: white;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            background: var(--warning);
            color: white;
            font-size: 10px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-status.active {
            background: var(--success);
        }

        .message-bubble p {
            margin: 0 0 8px 0;
        }

        .message-bubble p:last-child {
            margin-bottom: 0;
        }


        .chat-actions {
            padding: 12px 20px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: var(--light);
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .action-btn svg {
            width: 18px;
            height: 18px;
        }

        .chat-input-area {
            padding: 16px 20px;
            background: white;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            font-family: inherit;
            font-weight: 500;
            background: var(--light);
            transition: all 0.2s;
            resize: none;
            max-height: 100px;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .chat-input::placeholder {
            color: var(--text-light);
            font-weight: 500;
        }

        .send-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }

        .send-button:hover:not(:disabled) {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 82, 204, 0.4);
        }

        .send-button:active:not(:disabled) {
            transform: scale(0.95);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .send-button svg {
            width: 20px;
            height: 20px;
        }

        .input-hint {
            margin: 6px 0 0;
            font-size: 11px;
            color: var(--text-light);
            text-align: right;
        }

        .chat-footer {
            padding: 12px 20px;
            background: var(--light);
            border-top: 1px solid var(--border);
            text-align: center;
            flex-shrink: 0;
        }

        .footer-text {
            margin: 0;
            font-size: 11px;
            color: var(--text-light);
            font-weight: 500;
        }

        .footer-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s;
        }

        .footer-text a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .quick-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
            padding: 12px;
            background: var(--primary-light);
            border-radius: 12px;
            animation: slideIn 0.3s ease;
        }

        .quick-action-btn {
            padding: 8px 14px;
            background: var(--primary);
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
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 82, 204, 0.3);
        }

        .initial-quick-replies {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
            padding: 12px;
            background: var(--primary-light);
            border-radius: 12px;
            animation: slideIn 0.3s ease;
        }

        .initial-quick-replies.hidden {
            display: none;
        }

        .quick-reply-btn {
            padding: 12px 16px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .quick-reply-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 8px rgba(0, 82, 204, 0.2);
        }

        .quick-reply-btn:active {
            transform: translateX(2px);
        }

        .quick-reply-btn svg {
            flex-shrink: 0;
            opacity: 0.8;
        }

        .quick-reply-btn:hover svg {
            opacity: 1;
        }

        .ai-setup-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10001;
            align-items: center;
            justify-content: center;
        }

        .ai-setup-modal.active {
            display: flex;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
        }

        .modal-card {
            position: relative;
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            transition: all 0.2s;
            color: var(--text-light);
        }

        .modal-close:hover {
            background: var(--border);
            color: var(--dark);
            transform: rotate(90deg);
        }

        .modal-close svg {
            width: 20px;
            height: 20px;
        }

        .modal-header-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 50px 40px 40px;
            text-align: center;
            position: relative;
        }

        .header-icon {
            font-size: 56px;
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
        }

        .header-icon svg {
            width: 56px;
            height: 56px;
            filter: brightness(0) invert(1);
        }

        .modal-header-section h2 {
            margin: 0 0 8px;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .modal-header-section p {
            margin: 0;
            font-size: 15px;
            opacity: 0.9;
            font-weight: 500;
        }

        .modal-body-section {
            padding: 40px;
        }

        .setup-section {
            margin-bottom: 40px;
        }

        .setup-section h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setup-steps {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .setup-steps li {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 16px;
            background: var(--light);
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            transition: all 0.2s;
        }

        .setup-steps li:hover {
            background: var(--primary-light);
            transform: translateX(4px);
        }

        .step-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .setup-steps strong {
            display: block;
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 4px;
        }

        .setup-steps p {
            margin: 0;
            color: var(--text-light);
            font-size: 13px;
        }

        .setup-steps a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .setup-steps a:hover {
            text-decoration: underline;
        }

        .setup-steps code {
            display: block;
            background: var(--dark);
            color: #f0f9ff;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 4px;
            word-break: break-all;
        }

        .system-status {
            margin-bottom: 40px;
            padding: 24px;
            background: var(--primary-light);
            border-radius: 16px;
            border: 1px solid #d4e5ff;
        }

        .system-status h3 {
            margin: 0 0 16px;
            color: var(--primary);
            font-size: 16px;
            font-weight: 700;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .status-card {
            background: white;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #d4e5ff;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .status-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
        }

        .help-section {
            margin-bottom: 20px;
        }

        .help-section h3 {
            margin: 0 0 16px;
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
        }

        .help-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .help-item {
            padding: 12px;
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            border-radius: 8px;
        }

        .help-item strong {
            display: block;
            color: #92400e;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .help-item p {
            margin: 0;
            color: #78350f;
            font-size: 13px;
        }

        .help-item code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .modal-footer-section {
            display: flex;
            gap: 12px;
            padding: 24px 40px;
            border-top: 1px solid var(--border);
            background: var(--light);
        }

        .modal-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }

        .modal-btn.secondary {
            background: var(--border);
            color: var(--text);
        }

        .modal-btn.secondary:hover {
            background: var(--border);
            transform: translateY(-2px);
        }

        .modal-btn.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }

        .modal-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 82, 204, 0.4);
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

            .message-content {
                max-width: 95%;
            }

            .modal-card {
                width: 95%;
                max-height: 95vh;
            }

            .modal-body-section {
                padding: 24px;
            }

            .modal-header-section {
                padding: 40px 24px 30px;
            }

            .modal-header-section h2 {
                font-size: 24px;
            }

            .suggestions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .status-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .airlyft-chat {
                bottom: 12px;
                right: 12px;
            }

            .chat-toggle-btn {
                padding: 10px 16px;
                font-size: 14px;
            }

            .chat-toggle-btn span {
                display: none;
            }

            .chat-toggle-btn svg {
                margin: 0;
            }

            .chat-window {
                width: 100vw;
                height: 100vh;
            }

            .chat-header {
                padding: 20px;
            }

            .header-welcome p {
                font-size: 13px;
            }

            .modal-header-section {
                padding: 32px 20px 24px;
            }

            .modal-header-section h2 {
                font-size: 20px;
            }

            .header-icon svg {
                width: 48px;
                height: 48px;
            }

            .modal-body-section {
                padding: 20px;
            }

            .modal-footer-section {
                padding: 16px 20px;
                flex-direction: column-reverse;
            }

            .setup-steps li {
                padding: 12px;
                gap: 12px;
            }

            .status-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .suggestions-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
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
            background: var(--border);
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: var(--text-light);
        }

        .modal-card::-webkit-scrollbar {
            width: 8px;
        }

        .modal-card::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-card::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .modal-card::-webkit-scrollbar-thumb:hover {
            background: var(--text-light);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatToggle = document.getElementById('chat-toggle');
            const chatWindow = document.getElementById('chat-window');
            const chatClose = document.getElementById('chat-close');
            const chatMessages = document.getElementById('chat-messages');
            const chatInput = document.getElementById('chat-input');
            const sendBtn = document.getElementById('send-btn');
            const initialWelcomeHTML = chatMessages.innerHTML;

            if (chatToggle) {
                chatToggle.addEventListener('click', () => {
                    chatWindow.classList.toggle('active');
                    if (chatWindow.classList.contains('active')) {
                        chatInput.focus();
                    }
                });
            }

            if (chatClose) {
                chatClose.addEventListener('click', () => {
                    chatWindow.classList.remove('active');
                });
            }

            if (sendBtn) {
                sendBtn.addEventListener('click', sendMessage);
            }
            if (chatInput) {
                chatInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Quick reply button handlers
            const quickReplyBtns = document.querySelectorAll('.quick-reply-btn');
            quickReplyBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const message = this.getAttribute('data-message');
                    if (message && chatInput) {
                        chatInput.value = message;
                        sendMessage();
                    }
                });
            });

            async function sendMessage() {
                const message = chatInput.value.trim();
                if (!message) return;

                // Hide initial quick replies after first user message
                const initialReplies = document.getElementById('initial-quick-replies');
                if (initialReplies && !initialReplies.classList.contains('hidden')) {
                    initialReplies.classList.add('hidden');
                }

                addMessage(message, 'user');
                chatInput.value = '';
                sendBtn.disabled = true;
                showTyping();

                try {
                    const res = await fetch('/airlyft/integrations/aiChat/chatbot.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            message
                        })
                    });

                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        removeTyping();
                        addMessage('Service temporarily unavailable. Please try again.', 'agent');
                        return;
                    }

                    removeTyping();
                    if (data && data.success) {
                        // Remove any existing quick actions before adding new ones
                        const existingActions = document.querySelectorAll('.quick-actions');
                        existingActions.forEach(el => el.remove());

                        addMessage(data.reply || '', 'agent', true);
                        // Only show quick actions if they exist and are meaningful
                        if (data.quick_actions && Array.isArray(data.quick_actions) && data.quick_actions.length > 0) {
                            addQuickActions(data.quick_actions);
                        }
                    } else {
                        addMessage(data?.reply || 'Error occurred. Please try again.', 'agent', true);
                    }
                } catch (err) {
                    removeTyping();
                    addMessage('Connection error. Please check your network.', 'agent');
                    console.error(err);
                } finally {
                    sendBtn.disabled = false;
                }
            }

            function addMessage(text, sender, isHtml = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message message-' + sender;
                const bubble = document.createElement('div');
                bubble.className = 'message-content';

                if (sender === 'agent') {
                    const avatar = document.createElement('div');
                    avatar.className = 'message-avatar';
                    avatar.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>';
                    const msgBubble = document.createElement('div');
                    msgBubble.className = 'message-bubble agent-bubble';
                    msgBubble.innerHTML = `<p>${isHtml ? text : escapeHtml(text).replace(/\n/g, '<br>')}</p>`;
                    bubble.appendChild(avatar);
                    bubble.appendChild(msgBubble);
                } else {
                    const msgBubble = document.createElement('div');
                    msgBubble.className = 'message-bubble user-bubble';
                    msgBubble.innerHTML = `<p>${isHtml ? text : escapeHtml(text).replace(/\n/g, '<br>')}</p>`;
                    bubble.appendChild(msgBubble);
                }

                messageDiv.appendChild(bubble);
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function showTyping() {
                removeTyping();
                const typingDiv = document.createElement('div');
                typingDiv.className = 'message message-agent';
                typingDiv.id = 'typing-indicator';
                typingDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-avatar">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                        </svg>
                    </div>
                    <div class="message-bubble agent-bubble">
                        <p>Horizon is thinking<span style="animation: dots 1.4s infinite;">.</span></p>
                    </div>
                </div>
            `;
                chatMessages.appendChild(typingDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function removeTyping() {
                const typing = document.getElementById('typing-indicator');
                if (typing) typing.remove();
            }


            updateAIStatus();
            setInterval(updateAIStatus, 5000);

            setTimeout(() => {
                updateAIStatus();
            }, 1000);

            async function updateAIStatus() {
                try {
                    const res = await fetch('/airlyft/integrations/aiChat/checkAI.php');
                    const data = await res.json();
                    const isOnline = !!(data?.ollama?.available);
                    const headerStatus = document.getElementById('header-status');
                    const setupLink = document.getElementById('setup-link-container');

                    if (headerStatus) {
                        headerStatus.textContent = isOnline ? 'Ready to assist' : 'Available';
                    }

                    if (setupLink) {
                        setupLink.style.display = isOnline ? 'none' : 'inline';
                    }
                } catch (e) {
                    const setupLink = document.getElementById('setup-link-container');
                    if (setupLink) {
                        setupLink.style.display = 'inline';
                    }
                }
            }
        });

        // Global helper functions
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function showAISetupModal() {
            const modal = document.getElementById('ai-setup-modal');
            if (modal) {
                modal.classList.add('active');
                checkAIStatusModal();
            }
        }

        function closeAISetupModal() {
            const modal = document.getElementById('ai-setup-modal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        async function checkAIStatusModal() {
            try {
                const res = await fetch('/airlyft/integrations/aiChat/checkAI.php');
                const data = await res.json();
                const isOnline = !!(data?.ollama?.available);

                document.getElementById('modal-ollama-status').textContent = isOnline ? '✓ Online' : '✗ Offline';
                document.getElementById('modal-ollama-status').style.color = isOnline ? '#10b981' : '#ef4444';
                document.getElementById('modal-ai-models').textContent = data?.ollama?.models?.length || 0;
                document.getElementById('modal-ai-mode').textContent = isOnline ? 'AI' : 'Smart';
                document.getElementById('modal-ai-mode').style.color = isOnline ? '#10b981' : '#f59e0b';
            } catch (e) {
                console.error('Status check error:', e);
            }
        }

        function addQuickActions(actions) {
            if (!actions || actions.length === 0) return;

            const chatMessages = document.getElementById('chat-messages');
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'quick-actions';

            actions.slice(0, 1).forEach(action => {
                const btn = document.createElement('button');
                btn.className = 'quick-action-btn';
                btn.textContent = action;
                btn.onclick = () => {
                    document.getElementById('chat-input').value = action;
                    document.getElementById('send-btn').click();
                    actionsDiv.remove();
                };
                actionsDiv.appendChild(btn);
            });

            chatMessages.appendChild(actionsDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        document.addEventListener('click', (e) => {
            const modal = document.getElementById('ai-setup-modal');
            if (modal?.classList.contains('active') && e.target.classList.contains('modal-overlay')) {
                closeAISetupModal();
            }
        });
    </script>