<?php
// admin/chat.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Buscar mensagens recentes (últimas 100)
$stmt = $pdo->query("
    SELECT cm.*, u.name as user_name, u.avatar 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC
    LIMIT 100
");
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Buscar todos os usuários para autocomplete de @menções
$stmtUsers = $pdo->query("SELECT id, name FROM users ORDER BY name");
$allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat da Equipe - PIB Oliveira</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }

        .chat-wrapper {
            height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 100%;
            margin: 0 auto;
            background: white;
        }

        .chat-header {
            padding: 16px 20px;
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3);
            position: relative;
            z-index: 10;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .chat-header-content {
            flex: 1;
        }

        .chat-header-content h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .chat-header-content p {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: linear-gradient(to bottom, #f8fafc, #ffffff);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .messages-container::-webkit-scrollbar {
            width: 8px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .messages-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .message {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            font-size: 1rem;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .message.own .message-avatar {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .message-content {
            max-width: 65%;
            background: white;
            padding: 14px 18px;
            border-radius: 18px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .message.own .message-content {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
        }

        .message-author {
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 6px;
            color: #047857;
        }

        .message.own .message-author {
            color: rgba(255, 255, 255, 0.95);
        }

        .message-text {
            font-size: 0.95rem;
            line-height: 1.6;
            word-wrap: break-word;
            color: #1e293b;
        }

        .message.own .message-text {
            color: white;
        }

        /* Markdown styling */
        .message-text strong {
            font-weight: 700;
        }

        .message-text em {
            font-style: italic;
        }

        .message-text code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .message.own .message-text code {
            background: rgba(255, 255, 255, 0.2);
        }

        /* @menção styling */
        .mention {
            background: rgba(4, 120, 87, 0.15);
            color: #047857;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .message.own .mention {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 6px;
            text-align: right;
        }

        .input-container {
            padding: 20px 24px;
            background: white;
            border-top: 2px solid #e2e8f0;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            max-width: 1200px;
            margin: 0 auto;
        }

        .input-box {
            flex: 1;
            position: relative;
        }

        .input-box textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 24px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
            resize: none;
            min-height: 50px;
            max-height: 120px;
        }

        .input-box textarea:focus {
            border-color: #047857;
            background: white;
            box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1);
        }

        .input-hint {
            position: absolute;
            bottom: -20px;
            left: 18px;
            font-size: 0.7rem;
            color: #64748b;
        }

        .send-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 8px rgba(4, 120, 87, 0.3);
            flex-shrink: 0;
        }

        .send-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 6px 14px rgba(4, 120, 87, 0.4);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #64748b;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #334155;
            font-size: 1.2rem;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* Autocomplete para @menções */
        .mentions-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            display: none;
            margin-bottom: 8px;
        }

        .mentions-dropdown.active {
            display: block;
        }

        .mention-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.9rem;
        }

        .mention-item:hover,
        .mention-item.selected {
            background: #f1f5f9;
        }

        .mention-item strong {
            color: #047857;
        }

        @media (max-width: 768px) {
            .messages-container {
                padding: 16px;
            }

            .message-content {
                max-width: 75%;
            }

            .input-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="chat-wrapper">
        <div class="chat-header">
            <button class="back-btn" onclick="window.location.href='index.php'">
                <i data-lucide="arrow-left" style="width: 22px; height: 22px;"></i>
            </button>
            <div class="chat-header-content">
                <h1>💬 Chat da Equipe</h1>
                <p>Converse com os membros do ministério</p>
            </div>
        </div>

        <div class="messages-container" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="message-circle" style="width: 40px; height: 40px; color: #3b82f6;"></i>
                    </div>
                    <h3>Nenhuma mensagem ainda</h3>
                    <p>Seja o primeiro a iniciar a conversa! Use @nome para mencionar alguém.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg):
                    $isOwn = $msg['user_id'] == $userId;
                    $initial = strtoupper(substr($msg['user_name'], 0, 1));
                    $time = date('H:i', strtotime($msg['created_at']));
                ?>
                    <div class="message <?= $isOwn ? 'own' : '' ?>">
                        <div class="message-avatar"><?= $initial ?></div>
                        <div class="message-content">
                            <?php if (!$isOwn): ?>
                                <div class="message-author"><?= htmlspecialchars($msg['user_name']) ?></div>
                            <?php endif; ?>
                            <div class="message-text" data-raw="<?= htmlspecialchars($msg['message']) ?>"></div>
                            <div class="message-time"><?= $time ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="input-container">
            <form class="input-wrapper" id="chatForm">
                <div class="input-box">
                    <div class="mentions-dropdown" id="mentionsDropdown"></div>
                    <textarea
                        id="messageInput"
                        placeholder="Digite sua mensagem... Use @nome para mencionar, **negrito**, *itálico*"
                        rows="1"
                        required
                    ></textarea>
                    <div class="input-hint">Markdown suportado: **negrito** *itálico* `código`</div>
                </div>
                <button type="submit" class="send-btn" id="sendBtn">
                    <i data-lucide="send" style="width: 24px; height: 24px;"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const messagesArea = document.getElementById('messagesArea');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const mentionsDropdown = document.getElementById('mentionsDropdown');
        
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        const currentUserId = <?= $userId ?>;
        const allUsers = <?= json_encode($allUsers) ?>;

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Processar Markdown e @menções
        function processMessage(text) {
            // Converter Markdown
            let processed = marked.parse(text, { breaks: true });
            
            // Processar @menções
            processed = processed.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
            
            return processed;
        }

        // Renderizar mensagens existentes
        document.querySelectorAll('.message-text').forEach(el => {
            const rawText = el.getAttribute('data-raw');
            el.innerHTML = processMessage(rawText);
        });

        // Scroll para o final
        messagesArea.scrollTop = messagesArea.scrollHeight;

        // Autocomplete de @menções
        let mentionStartPos = -1;
        let selectedMentionIndex = 0;

        messageInput.addEventListener('input', function(e) {
            const text = this.value;
            const cursorPos = this.selectionStart;
            
            // Detectar @ seguido de texto
            const beforeCursor = text.substring(0, cursorPos);
            const match = beforeCursor.match(/@(\w*)$/);
            
            if (match) {
                const query = match[1].toLowerCase();
                const filtered = allUsers.filter(u => 
                    u.name.toLowerCase().includes(query)
                );
                
                if (filtered.length > 0) {
                    mentionsDropdown.innerHTML = filtered.map((user, idx) => `
                        <div class="mention-item ${idx === 0 ? 'selected' : ''}" data-index="${idx}" data-name="${user.name}">
                            <strong>@${user.name.split(' ')[0]}</strong> - ${user.name}
                        </div>
                    `).join('');
                    mentionsDropdown.classList.add('active');
                    mentionStartPos = cursorPos - match[0].length;
                    selectedMentionIndex = 0;
                } else {
                    mentionsDropdown.classList.remove('active');
                }
            } else {
                mentionsDropdown.classList.remove('active');
            }
        });

        // Navegação no autocomplete
        messageInput.addEventListener('keydown', function(e) {
            if (!mentionsDropdown.classList.contains('active')) return;
            
            const items = mentionsDropdown.querySelectorAll('.mention-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedMentionIndex = Math.min(selectedMentionIndex + 1, items.length - 1);
                updateSelectedMention(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedMentionIndex = Math.max(selectedMentionIndex - 1, 0);
                updateSelectedMention(items);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (items.length > 0) {
                    e.preventDefault();
                    selectMention(items[selectedMentionIndex].getAttribute('data-name'));
                }
            } else if (e.key === 'Escape') {
                mentionsDropdown.classList.remove('active');
            }
        });

        function updateSelectedMention(items) {
            items.forEach((item, idx) => {
                item.classList.toggle('selected', idx === selectedMentionIndex);
            });
        }

        function selectMention(name) {
            const text = messageInput.value;
            const firstName = name.split(' ')[0];
            const newText = text.substring(0, mentionStartPos) + '@' + firstName + ' ' + text.substring(messageInput.selectionStart);
            messageInput.value = newText;
            messageInput.selectionStart = messageInput.selectionEnd = mentionStartPos + firstName.length + 2;
            mentionsDropdown.classList.remove('active');
            messageInput.focus();
        }

        // Click em item do autocomplete
        mentionsDropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.mention-item');
            if (item) {
                selectMention(item.getAttribute('data-name'));
            }
        });

        // Enviar mensagem
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            sendBtn.disabled = true;

            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                if (response.ok) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    loadMessages();
                }
            } catch (error) {
                console.error('Erro ao enviar mensagem:', error);
            } finally {
                sendBtn.disabled = false;
                messageInput.focus();
            }
        });

        // Carregar apenas NOVAS mensagens
        async function loadMessages() {
            try {
                const response = await fetch(`chat_api.php?since=${lastMessageId}`);
                const newMessages = await response.json();

                if (newMessages.length === 0) return;

                newMessages.forEach(msg => {
                    const isOwn = msg.user_id == currentUserId;
                    const initial = msg.user_name.charAt(0).toUpperCase();
                    const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${isOwn ? 'own' : ''}`;
                    messageDiv.innerHTML = `
                        <div class="message-avatar">${initial}</div>
                        <div class="message-content">
                            ${!isOwn ? `<div class="message-author">${msg.user_name}</div>` : ''}
                            <div class="message-text">${processMessage(msg.message)}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;

                    const emptyState = messagesArea.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();

                    messagesArea.appendChild(messageDiv);
                    lastMessageId = msg.id;
                });

                lucide.createIcons();
                messagesArea.scrollTop = messagesArea.scrollHeight;
            } catch (error) {
                console.error('Erro ao carregar mensagens:', error);
            }
        }

        // Auto-refresh a cada 5 segundos
        setInterval(loadMessages, 5000);

        // Inicializar ícones
        lucide.createIcons();
    </script>
</body>
</html>
