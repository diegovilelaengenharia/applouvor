/**
 * Sistema de Notificações - JavaScript
 * Gerencia o dropdown de notificações e interações
 */

// Estado global
let notificationsData = [];
let unreadCount = 0;

// Carregar contador de não lidas
async function loadUnreadCount() {
    try {
        const response = await fetch('notifications_api.php?action=count_unread');
        const data = await response.json();

        if (data.success) {
            unreadCount = data.count;
            updateBadge();
        }
    } catch (error) {
        console.error('Erro ao carregar contador:', error);
    }
}

// Atualizar badge
function updateBadge() {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Toggle dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isActive = dropdown.classList.contains('active');

    if (!isActive) {
        loadNotifications();
        dropdown.classList.add('active');
    } else {
        dropdown.classList.remove('active');
    }
}

// Carregar notificações
async function loadNotifications() {
    try {
        const response = await fetch('notifications_api.php?action=list&limit=10');
        const data = await response.json();

        if (data.success) {
            notificationsData = data.notifications;
            renderNotifications();
        }
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

// Renderizar notificações
function renderNotifications() {
    const list = document.getElementById('notificationList');

    if (!notificationsData || notificationsData.length === 0) {
        list.innerHTML = `
            <div class="notification-empty">
                <i data-lucide="bell-off" style="width: 32px; height: 32px; margin: 0 auto 8px; opacity: 0.3;"></i>
                <div>Nenhuma notificação</div>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    list.innerHTML = notificationsData.map(notification => {
        const config = notification.config || { icon: 'bell', color: '#64748b' };
        const unreadClass = !notification.is_read ? 'unread' : '';
        const timeAgo = getTimeAgo(notification.created_at);

        return `
            <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notification.id}, '${notification.link || ''}')">
                <div class="notification-icon" style="background: ${config.color}20; color: ${config.color};">
                    <i data-lucide="${config.icon}" style="width: 18px; height: 18px;"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notification.title)}</div>
                    ${notification.message ? `<div class="notification-message">${escapeHtml(notification.message)}</div>` : ''}
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    }).join('');

    lucide.createIcons();
}

// Marcar como lida ao clicar
async function handleNotificationClick(id, link) {
    try {
        await fetch('notifications_api.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        // Atualizar contador
        unreadCount = Math.max(0, unreadCount - 1);
        updateBadge();

        // Redirecionar se houver link
        if (link) {
            window.location.href = link;
        }
    } catch (error) {
        console.error('Erro ao marcar como lida:', error);
    }
}

// Marcar todas como lidas
async function markAllAsRead() {
    try {
        const response = await fetch('notifications_api.php?action=mark_all_read', {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            unreadCount = 0;
            updateBadge();
            loadNotifications();
        }
    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
    }
}

// Utilitários
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Agora';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m atrás`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d atrás`;

    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('notificationDropdown');
    const btn = document.getElementById('notificationBtn');

    if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});

// Carregar contador ao iniciar
document.addEventListener('DOMContentLoaded', function () {
    loadUnreadCount();

    // Verificar se suporta SW e Push
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        checkNotificationPermission();
    }

    // Atualizar a cada 30 segundos
    setInterval(loadUnreadCount, 30000);
});

// Som de notificação (Beep suave)
const notificationSound = new Audio('data:audio/mp3;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAG1xUAALDkAAXGkAAIjR0oAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvwWAAfDOBsAAAAAt3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3'); // Placeholder curto

// Checar e pedir permissão
function checkNotificationPermission() {
    const btn = document.getElementById('btnEnableNotifications');

    // Debug
    console.log('Verificando permissão de notificação:', Notification.permission);

    if (!btn) {
        console.warn('Botão de ativar notificações não encontrado no DOM');
        return;
    }

    // Se ainda não foi perguntado ('default'), mostra o botão
    if (Notification.permission === 'default') {
        btn.style.display = 'inline-flex';
    } else {
        // Se já foi concedida ('granted') ou negada ('denied'), esconde
        btn.style.display = 'none';

        if (Notification.permission === 'granted') {
            ensurePushSubscription();
        }
    }
}


// UI para pedir permissão (pode ser chamada pelo botão no menu)
function requestNotificationPermission() {
    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            ensurePushSubscription();
            new Audio(notificationSound.src).play().catch(e => console.log('Audio autoplay blocked'));
            if (navigator.vibrate) navigator.vibrate([200]);
            alert('Notificações ativadas com sucesso!');
        }
    });
}

function showPermissionRequestUI() {
    // Implementar um toast ou modal suave se desejar, 
    // ou apenas esperar o usuário clicar no botão "Ativar Notificações" no dropdown
}

// Garantir Inscrição no Push
async function ensurePushSubscription() {
    try {
        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            // Buscar chave pública
            const response = await fetch('notifications_api.php?action=public_key');
            const data = await response.json();

            if (data.success && data.publicKey) {
                const convertedKey = urlBase64ToUint8Array(data.publicKey);

                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey
                });

                // Salvar no backend
                await saveSubscription(subscription);
            }
        }
    } catch (error) {
        console.error('Erro no Push Subscription:', error);
    }
}

async function saveSubscription(subscription) {
    try {
        await fetch('api/push_subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
    } catch (e) {
        console.error('Erro ao salvar subscription:', e);
    }
}

// Utility para VAPID Key
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Modified loadNotifications to play sound if new ones arrived
let lastNotificationId = 0;

async function loadNotifications() {
    try {
        const response = await fetch('notifications_api.php?action=list&limit=10');
        const data = await response.json();

        if (data.success) {
            notificationsData = data.notifications;

            // Check for new notifications to play sound
            if (notificationsData.length > 0) {
                const newestId = notificationsData[0].id;
                if (newestId > lastNotificationId && lastNotificationId !== 0) {
                    playSoundAndVibrate();
                }
                lastNotificationId = newestId;
            } else {
                lastNotificationId = 0;
            }

            renderNotifications();
        }
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

function playSoundAndVibrate() {
    // Tocar som
    notificationSound.play().catch(e => console.log('Audio play error', e));

    // Vibrar
    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
    }
}

