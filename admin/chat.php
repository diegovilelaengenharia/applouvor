<?php
// admin/chat.php - Versão Style WhatsApp
require_once '../includes/auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat - PIB Oliveira</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #efeae2; /* WhatsApp Beige */
            height: 100vh;
            height: 100dvh; /* Dynamic Viewport Height for Mobile */
            overflow: hidden;
            display: flex;
            justify-content: center;
        }

        .chat-wrapper {
            width: 100%;
            height: 100%;
            max-width: 600px; /* Limit width on desktop */
            display: flex;
            flex-direction: column;
            background-color: #efeae2;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); /* WhatsApp Doodle Pattern Subtle */
            background-blend-mode: overlay;
            background-size: 400px;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* HEADER */
        .chat-header {
            flex-shrink: 0;
            padding: 10px 16px;
            background-color: #008069; /* WhatsApp Green */
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            z-index: 10;
        }

        .back-btn {
            background: none;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            margin-right: -4px;
        }
        
        .back-btn:active {
            background-color: rgba(255,255,255,0.1);
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            cursor: pointer;
            padding: 4px 0;
        }

        .header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #008069;
        }

        .header-details h1 {
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .header-details span {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 400;
            display: block;
        }

        /* MESSAGES AREA */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px;
            padding-bottom: 80px; /* Espaço para o input fixo */
            display: flex;
            flex-direction: column;
            gap: 4px;
            /* Scroll smooth */
            scroll-behavior: auto; 
            -webkit-overflow-scrolling: touch;
        }
        
        /* ... Styles intermediários ... */

        /* INPUT AREA */
        .input-container {
            position: fixed; /* Fixar no rodapé */
            bottom: 0;
            left: 0;
            right: 0;
            flex-shrink: 0;
            padding: 8px 10px;
            background-color: #f0f2f5;
            display: flex;
            align-items: flex-end; /* Align bottom for multiline */
            gap: 8px;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            z-index: 1000; /* Z-index alto */
            box-shadow: 0 -1px 3px rgba(0,0,0,0.05);
        }

        .input-box {
            flex: 1;
            background: white;
            border-radius: 24px;
            padding: 9px 16px;
            display: flex;
            align-items: center;
            min-height: 42px;
            border: 1px solid white;
        }

        .input-box input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 1rem;
            font-family: inherit;
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #008069;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: transform 0.1s;
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .empty-state {
            background: rgba(255,255,255,0.9);
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px auto;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            font-size: 0.85rem;
            color: #555;
            display: inline-block;
            align-self: center;
        }

        /* Colors for names */
        .color-0 { color: #e542a3; }
        .color-1 { color: #02a698; }
        .color-2 { color: #f29f05; }
        .color-3 { color: #35cd96; }
        .color-4 { color: #6bcbef; }
        .color-5 { color: #e542a3; }
        .color-6 { color: #9194a1; }
        .color-7 { color: #ff6849; }
        .color-8 { color: #3b5998; }
        .color-9 { color: #a98059; }

    </style>
</head>
<body>
    <div class="chat-wrapper">
        <div class="chat-header">
            <button class="back-btn" onclick="window.location.href='index.php'">
                <i data-lucide="arrow-left" style="width: 24px; height: 24px;"></i>
            </button>
            <div class="header-info">
                <div class="header-avatar">
                   <i data-lucide="users" style="width: 24px;"></i>
                </div>
                <div class="header-details">
                    <h1>Equipe Louvor PIB</h1>
                    <span>Toque para ver dados do grupo</span>
                </div>
            </div>
            <button class="back-btn">
                 <i data-lucide="more-vertical" style="width: 24px;"></i>
            </button>
        </div>

        <div class="messages-container" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    🔒 As mensagens são protegidas. Nenhuma mensagem ainda.
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg):
                    $isOwn = $msg['user_id'] == $userId;
                    $firstName = explode(' ', $msg['user_name'])[0];
                    // Generate color index based on user ID
                    $colorIndex = $msg['user_id'] % 10;
                    $time = date('H:i', strtotime($msg['created_at']));
                ?>
                    <div class="message-row <?= $isOwn ? 'own' : '' ?>">
                        <div class="message-bubble">
                            <?php if (!$isOwn): ?>
                                <span class="msg-author-name color-<?= $colorIndex ?>"><?= htmlspecialchars($firstName) ?></span>
                            <?php endif; ?>
                            <span class="msg-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></span>
                            <span class="msg-meta">
                                <?= $time ?>
                                <?php if($isOwn): ?>
                                    <i data-lucide="check-check" style="width: 14px; color: #53bdeb;"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form class="input-container" id="chatForm">
            <div class="input-box">
                <input
                    type="text"
                    id="messageInput"
                    placeholder="Mensagem"
                    autocomplete="off"
                    required>
            </div>
            <button type="submit" class="send-btn">
                <i data-lucide="send" style="width: 22px; height: 22px; margin-left: 2px;"></i>
            </button>
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

        // Handle viewport height on mobile browsers
        function setVh() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        window.addEventListener('resize', setVh);
        setVh();

        // Enviar mensagem
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            // Optimistic UI Update (Adiciona a mensagem imediatamente)
            const now = new Date();
            const time = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            
            const tempDiv = document.createElement('div');
            tempDiv.className = 'message-row own';
            tempDiv.innerHTML = `
                <div class="message-bubble">
                    <span class="msg-text">${message.replace(/\n/g, '<br>')}</span>
                    <span class="msg-meta">
                        ${time}
                        <i data-lucide="clock" style="width: 14px; color: #888;"></i>
                    </span>
                </div>
            `;
            messagesArea.appendChild(tempDiv);
            messagesArea.scrollTop = messagesArea.scrollHeight;
            lucide.createIcons();
            
            messageInput.value = ''; // Limpa input

            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });

                if (response.ok) {
                    // Success (o loadMessages vai confirmar e substituir ou duplicar, 
                    // ideal seria substituir o temp, mas o loadMessages lida via ID)
                    // Para simplificar, vamos remover o temporário quando carregar o real
                    tempDiv.remove(); 
                    loadMessages();
                } else {
                     tempDiv.style.opacity = 0.5;
                     alert('Erro ao enviar');
                }
            } catch (error) {
                console.error('Erro:', error);
                tempDiv.remove();
                alert('Erro de conexão');
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
                    const colorIndex = msg.user_id % 10;
                    const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message-row ${isOwn ? 'own' : ''}`;
                    
                    let html = `<div class="message-bubble">`;
                    if (!isOwn) {
                        html += `<span class="msg-author-name color-${colorIndex}">${firstName}</span>`;
                    }
                    html += `
                        <span class="msg-text">${msg.message.replace(/\n/g, '<br>')}</span>
                        <span class="msg-meta">
                            ${time}
                            ${isOwn ? '<i data-lucide="check-check" style="width: 14px; color: #53bdeb;"></i>' : ''}
                        </span>
                    </div>`;
                    
                    messageDiv.innerHTML = html;

                    // Remover empty state se existir
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

        // Auto-refresh a cada 3 segundos
        setInterval(loadMessages, 3000);

        // Inicializar ícones
        lucide.createIcons();

        // Fix para teclado no mobile (scroll to bottom quando focar)
        messageInput.addEventListener('focus', () => {
            setTimeout(() => {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }, 300);
        });
    </script>
</body>
</html>
