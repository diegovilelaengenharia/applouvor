<?php
// admin/chat.php - Versão Simplificada
require_once '../includes/auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userFirstName = explode(' ', $userName)[0];

// Buscar mensagens recentes (últimas 50)
$stmt = $pdo->query("
    SELECT cm.*, u.name as user_name 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC
    LIMIT 50
");
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - PIB Oliveira</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            height: 100vh;
            overflow: hidden;
        }

        .chat-wrapper {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .chat-header {
            padding: 16px 20px;
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(4, 120, 87, 0.2);
        }

        .back-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .chat-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8fafc;
        }

        .message {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .message.own .message-avatar {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .message-content {
            max-width: 70%;
            background: white;
            padding: 10px 14px;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .message.own .message-content {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
        }

        .message-author {
            font-weight: 700;
            font-size: 0.8rem;
            margin-bottom: 3px;
            color: #047857;
        }

        .message.own .message-author {
            color: rgba(255, 255, 255, 0.95);
        }

        .message-text {
            font-size: 0.9rem;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 0.65rem;
            opacity: 0.6;
            margin-top: 3px;
            text-align: right;
        }

        .input-container {
            padding: 12px 16px;
            background: white;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .input-wrapper input {
            flex: 1;
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            font-size: 0.9rem;
            outline: none;
            background: #f8fafc;
        }

        .input-wrapper input:focus {
            border-color: #047857;
            background: white;
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(4, 120, 87, 0.3);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .message-content {
                max-width: 75%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-wrapper">
        <div class="chat-header">
            <button class="back-btn" onclick="window.location.href='index.php'">
                <i data-lucide="arrow-left" style="width: 20px; height: 20px;"></i>
            </button>
            <h1>💬 Chat da Equipe</h1>
        </div>

        <div class="messages-container" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <p>Nenhuma mensagem ainda. Seja o primeiro!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg):
                    $isOwn = $msg['user_id'] == $userId;
                    $firstName = explode(' ', $msg['user_name'])[0];
                    $initial = strtoupper(substr($firstName, 0, 1));
                    $time = date('H:i', strtotime($msg['created_at']));
                ?>
                    <div class="message <?= $isOwn ? 'own' : '' ?>">
                        <div class="message-avatar"><?= $initial ?></div>
                        <div class="message-content">
                            <?php if (!$isOwn): ?>
                                <div class="message-author"><?= htmlspecialchars($firstName) ?></div>
                            <?php endif; ?>
                            <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="message-time"><?= $time ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form class="input-container" id="chatForm">
            <div class="input-wrapper">
                <input
                    type="text"
                    id="messageInput"
                    placeholder="Digite sua mensagem..."
                    autocomplete="off"
                    required>
                <button type="submit" class="send-btn">
                    <i data-lucide="send" style="width: 20px; height: 20px;"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        const messagesArea = document.getElementById('messagesArea');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        const currentUserId = <?= $userId ?>;

        // Scroll para o final
        messagesArea.scrollTop = messagesArea.scrollHeight;

        // Enviar mensagem
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                if (response.ok) {
                    messageInput.value = '';
                    loadMessages();
                }
            } catch (error) {
                console.error('Erro:', error);
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
                    const firstName = msg.user_name.split(' ')[0];
                    const initial = firstName.charAt(0).toUpperCase();
                    const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${isOwn ? 'own' : ''}`;
                    messageDiv.innerHTML = `
                        <div class="message-avatar">${initial}</div>
                        <div class="message-content">
                            ${!isOwn ? `<div class="message-author">${firstName}</div>` : ''}
                            <div class="message-text">${msg.message.replace(/\n/g, '<br>')}</div>
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
                console.error('Erro:', error);
            }
        }

        // Auto-refresh a cada 5 segundos
        setInterval(loadMessages, 5000);

        // Inicializar ícones
        lucide.createIcons();
    </script>
</body>
</html>
