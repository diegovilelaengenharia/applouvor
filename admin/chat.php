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

renderAppHeader('Chat da Equipe');
?>

<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 140px);
        max-width: 900px;
        margin: 0 auto;
        background: linear-gradient(to bottom, #ffffff, #f8fafc);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

    .chat-header-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-header-title {
        flex: 1;
    }

    .chat-header-title h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .chat-header-title p {
        margin: 0;
        font-size: 0.75rem;
        opacity: 0.9;
    }

    .messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: #f8fafc;
    }

    .messages-area::-webkit-scrollbar {
        width: 6px;
    }

    .messages-area::-webkit-scrollbar-track {
        background: transparent;
    }

    .messages-area::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .messages-area::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .message {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        animation: messageSlideIn 0.3s ease;
    }

    @keyframes messageSlideIn {
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
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
        font-size: 0.9rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .message.own .message-avatar {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .message-content {
        max-width: 70%;
        background: white;
        padding: 12px 16px;
        border-radius: 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    .message.own .message-content {
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        color: white;
    }

    .message-author {
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 4px;
        color: #047857;
    }

    .message.own .message-author {
        color: rgba(255, 255, 255, 0.95);
    }

    .message-text {
        font-size: 0.95rem;
        line-height: 1.5;
        word-wrap: break-word;
        color: #1e293b;
    }

    .message.own .message-text {
        color: white;
    }

    .message-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 4px;
        text-align: right;
    }

    .input-area {
        padding: 16px 20px;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        align-items: center;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
    }

    .input-area input {
        flex: 1;
        padding: 12px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 24px;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.2s;
        background: #f8fafc;
    }

    .input-area input:focus {
        border-color: #047857;
        background: white;
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }

    .send-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 6px rgba(4, 120, 87, 0.3);
    }

    .send-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 12px rgba(4, 120, 87, 0.4);
    }

    .send-btn:active {
        transform: scale(0.95);
    }

    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #64748b;
    }

    .empty-state-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-state h3 {
        margin: 0 0 8px 0;
        color: #334155;
        font-size: 1.1rem;
    }

    .empty-state p {
        margin: 0;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 120px);
            border-radius: 0;
        }

        .message-content {
            max-width: 80%;
        }
    }
</style>

<div class="chat-container">
    <div class="chat-header">
        <div class="chat-header-icon">
            <i data-lucide="message-circle" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="chat-header-title">
            <h2>Chat da Equipe</h2>
            <p>Converse com os membros do ministério</p>
        </div>
    </div>

    <div class="messages-area" id="messagesArea">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="message-circle" style="width: 32px; height: 32px; color: #3b82f6;"></i>
                </div>
                <h3>Nenhuma mensagem ainda</h3>
                <p>Seja o primeiro a iniciar a conversa!</p>
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
        <button type="submit" class="send-btn">
            <i data-lucide="send" style="width: 22px; height: 22px;"></i>
        </button>
    </form>
</div>

<script>
    const messagesArea = document.getElementById('messagesArea');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;

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
                body: JSON.stringify({ message })
            });

            if (response.ok) {
                messageInput.value = '';
                loadMessages();
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
        }
    });

    // Carregar apenas NOVAS mensagens (evita piscar)
    async function loadMessages() {
        try {
            const response = await fetch(`chat_api.php?since=${lastMessageId}`);
            const newMessages = await response.json();

            if (newMessages.length === 0) return; // Sem novas mensagens

            const currentUserId = <?= $userId ?>;

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
                        <div class="message-text">${msg.message.replace(/\n/g, '<br>')}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;

                // Remover empty state se existir
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

    // Auto-refresh a cada 5 segundos (mais suave)
    setInterval(loadMessages, 5000);
</script>

<?php renderAppFooter(); ?>
