/**
 * Sistema de Notificações - JavaScript
 * Gerencia o dropdown de notificações e interações
 */

// Estado global
let notificationsData = [];
let unreadCount = 0;
let lastNotificationId = 0;
// Fallback se a variável não estiver definida
const apiBase = (typeof NOTIFICATIONS_API_BASE !== 'undefined') ? NOTIFICATIONS_API_BASE : '';
// Determine API Base Path dynamically if variable not set
const IS_ADMIN = window.location.pathname.includes('/admin/');
// If apiBase is empty, try to deduce. apiBase usually comes from layout.php
// But we need a robust fallback.
const API_ENDPOINT = apiBase + 'notifications_api.php';

// Carregar contador de não lidas
async function loadUnreadCount() {
    try {
        const response = await fetch(`${API_ENDPOINT}?action=count_unread`);
        const data = await response.json();

        if (data.success) {
            unreadCount = data.count;
            updateBadge();
        }
    } catch (error) {
        console.error('Erro ao carregar contador:', error);
    }
}

// Carregar notificações (Limit 3 per user request)
async function loadNotifications() {
    try {
        const response = await fetch(`${API_ENDPOINT}?action=list&limit=3`);
        const data = await response.json();

        if (data.success) {
            notificationsData = data.notifications;

            // Check for new notifications to play sound
            if (notificationsData.length > 0) {
                const newestId = notificationsData[0].id;
                // Only play if we have a previous ID to compare against, and it's newer
                if (lastNotificationId !== 0 && newestId > lastNotificationId) {
                    playSoundAndVibrate();
                }
                lastNotificationId = newestId;
            }

            renderNotifications();
        }
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

// Renderizar notificações
function renderNotifications() {
    // Select all lists (desktop and mobile if exists)
    const lists = document.querySelectorAll('.notification-list');

    lists.forEach(list => {
        if (!notificationsData || notificationsData.length === 0) {
            list.innerHTML = `
                <div class="notification-empty">
                    <i data-lucide="bell-off" style="width: 32px; height: 32px; margin: 0 auto 8px; opacity: 0.3;"></i>
                    <div>Nenhuma notificação</div>
                </div>
            `;
        } else {
            list.innerHTML = notificationsData.map(notification => {
                const config = notification.config || { icon: 'bell', color: '#64748b' };
                const unreadClass = !notification.is_read ? 'unread' : '';
                const timeAgo = getTimeAgo(notification.created_at);

                return `
                    <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notification.id}, '${notification.link || ''}')">
                        <div class="notification-icon" style="min-width: 40px; min-height: 40px; background: ${config.color}20; color: ${config.color}; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="${config.icon}" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title-text">${escapeHtml(notification.title)}</div>
                            ${notification.message ? `<div class="notification-text">${escapeHtml(notification.message)}</div>` : ''}
                            <div class="notification-time">
                                <i data-lucide="clock" style="width: 10px; height: 10px;"></i>
                                ${timeAgo}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    });

    // Re-initialize icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Marcar como lida ao clicar
async function handleNotificationClick(id, link) {
    try {
        await fetch(`${API_ENDPOINT}?action=mark_read`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        // Atualizar contador localmente
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
        await fetch(`${API_ENDPOINT}?action=mark_all_read`, {
            method: 'POST'
        });

        // Refresh
        unreadCount = 0;
        updateBadge();
        loadNotifications();

    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
    }
}

// Atualizar badge
function updateBadge() {
    const badges = document.querySelectorAll('.notification-badge');
    badges.forEach(badge => {
        if (unreadCount > 0) {
            badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    });
}

// Toggle dropdown
function toggleNotifications(dropdownId = 'notificationDropdown') {
    let dropdown = document.getElementById(dropdownId);

    // Fallback if specific ID not found
    if (!dropdown) {
        dropdown = document.querySelector('.notification-dropdown');
    }

    if (!dropdown) return;

    const isActive = dropdown.style.display === 'block';

    // Close all other dropdowns
    document.querySelectorAll('.notification-dropdown').forEach(d => {
        d.style.display = 'none';
    });

    if (!isActive) {
        loadNotifications(); // Fetch new data
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}


// Utilitários
function escapeHtml(text) {
    if (!text) return '';
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


// Som de notificação (Beep suave)
const notificationSound = new Audio('data:audio/mp3;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAG1xUAALDkAAXGkAAIjR0oAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvwWAAfDOBsAAAAAt3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3d3');

function playSoundAndVibrate() {
    new Audio(notificationSound.src).play().catch(() => { });
    if (navigator.vibrate) navigator.vibrate([200]);
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', function (e) {
    if (!e.target.closest('.notification-container') &&
        !e.target.closest('#notificationBtnDesktop')) {

        document.querySelectorAll('.notification-dropdown').forEach(d => {
            d.style.display = 'none';
        });
    }
});


// Carregar contador ao iniciar e configurar polling
document.addEventListener('DOMContentLoaded', function () {
    loadUnreadCount();

    // Polling a cada 60 segundos (reduzido de 30s)
    setInterval(loadUnreadCount, 60000);
});
