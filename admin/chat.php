<?php
// admin/chat.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Buscar mensagens recentes (últimas 50)
$stmt = $pdo->query("
    SELECT cm.*, u.name as user_name, u.avatar 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC
    LIMIT 50
");
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

renderAppHeader('Chat');
?>

<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 120px);
        max-width: 800px;
        margin: 0 auto;
        background: var(--bg-surface);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-color);
    }

    .messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f8fafc;
    }

    .message {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        animation: messageIn 0.3s ease;
    }

    @keyframes messageIn {
        from {
            opacity: 0;
            transform: translateY(10px);
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
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--primary);
        flex-shrink: 0;
        font-size: 0.85rem;
    }

    .message-content {
        max-width: 80%;
        background: white;
        padding: 8px 12px;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .message.own .message-content {
        background: var(--primary);
        color: white;
    }

    .message-author {
        font-weight: 700;
        font-size: 0.8rem;
        margin-bottom: 2px;
        color: var(--text-main);
    }

    .message.own .message-author {
        color: rgba(255, 255, 255, 0.9);
    }

    .message-text {
        font-size: 0.9rem;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .message-time {
        font-size: 0.65rem;
        opacity: 0.6;
        margin-top: 2px;
        text-align: right;
    }

    .input-area {
        padding: 12px;
        background: white;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .input-area input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 20px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .input-area input:focus {
        border-color: var(--primary);
    }

    .send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 4px rgba(4, 120, 87, 0.2);
    }

    .send-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(4, 120, 87, 0.3);
    }

    .send-btn:active {
        transform: scale(0.95);
    }

    .empty-state {
        text-align: center;
        padding: 32px 16px;
        color: var(--text-muted);
    }
</style>

<div class="chat-container">
    <div class="messages-area" id="messagesArea">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i data-lucide="message-circle" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.3;"></i>
                <p style="margin: 0; font-size: 1rem;">Nenhuma mensagem ainda. Seja o primeiro a conversar!</p>
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
                        <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                        <div class="message-time"><?= $time ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="input-area" id="chatForm">
        <input
            type="text"
            id="messageInput"
            placeholder="Digite sua mensagem..."
            autocomplete="off"
            required>
        <button type="submit" class="send-btn ripple">
            <i data-lucide="send" style="width: 20px;"></i>
        </button>
    </form>
</div>

<script>
    const messagesArea = document.getElementById('messagesArea');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');

    // Scroll para o final ao carregar
    messagesArea.scrollTop = messagesArea.scrollHeight;

    // Enviar mensagem
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        try {
            const response = await fetch('chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message
                })
            });

            if (response.ok) {
                messageInput.value = '';
                loadMessages();
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
        }
    });

    // Carregar mensagens
    async function loadMessages() {
        try {
            const response = await fetch('chat_api.php');
            const messages = await response.json();

            const currentUserId = <?= $userId ?>;

            messagesArea.innerHTML = messages.length === 0 ?
                '<div class="empty-state"><i data-lucide="message-circle" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.3;"></i><p style="margin: 0; font-size: 1rem;">Nenhuma mensagem ainda. Seja o primeiro a conversar!</p></div>' :
                messages.map(msg => {
                    const isOwn = msg.user_id == currentUserId;
                    const initial = msg.user_name.charAt(0).toUpperCase();
                    const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    return `
                        <div class="message ${isOwn ? 'own' : ''}">
                            <div class="message-avatar">${initial}</div>
                            <div class="message-content">
                                ${!isOwn ? `<div class="message-author">${msg.user_name}</div>` : ''}
                                <div class="message-text">${msg.message.replace(/\n/g, '<br>')}</div>
                                <div class="message-time">${time}</div>
                            </div>
                        </div>
                    `;
                }).join('');

            lucide.createIcons();
            messagesArea.scrollTop = messagesArea.scrollHeight;
        } catch (error) {
            console.error('Erro ao carregar mensagens:', error);
        }
    }

    // Auto-refresh a cada 3 segundos
    setInterval(loadMessages, 3000);
</script>

<?php renderAppFooter(); ?>