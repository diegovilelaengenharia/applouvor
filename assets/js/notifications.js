/**
 * Sistema de Notificações - JavaScript
 * Gerencia o dropdown de notificações e interações
 */

// Estado global
let notificationsData = [];
let unreadCount = 0;
let lastNotificationId = 0;
// Fallback se a variável não estiver definida
// Determinar API Endpoint corretamente
const currentPath = window.location.pathname;
let apiPrefix = '';

if (currentPath.includes('/admin/')) {
    apiPrefix = ''; // Já estamos em admin/
} else if (currentPath.includes('/app/')) {
    apiPrefix = '../admin/'; // Estamos em app/, voltar e entrar em admin
} else {
    // Root ou outra pasta
    apiPrefix = 'admin/';
}

const API_ENDPOINT = (typeof NOTIFICATIONS_API_BASE !== 'undefined')
    ? NOTIFICATIONS_API_BASE + 'notifications_api.php'
    : apiPrefix + 'notifications_api.php';

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
                    <div class="notification-item ${unreadClass}" onclick="openNotificationDetail(${notification.id})">
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

// Atualizar estilo do botão ao invés de badge
function updateBadge() {
    const btn = document.getElementById('notificationBtnDesktop');
    if (!btn) return;

    if (unreadCount > 0) {
        // Estilo Yellow/Amber quando tem notificações
        btn.style.background = '#fef3c7';
        btn.style.color = '#d97706';
        btn.style.borderColor = '#fcd34d';

        // Se quiser animar ou mudar o icone, pode fazer aqui
        // btn.classList.add('has-notifications');
    } else {
        // Estilo padrão (reset)
        btn.style.background = ''; // Volta ao CSS original (.header-action-btn background)
        btn.style.color = '';      // Volta ao CSS original
        btn.style.borderColor = ''; // Volta ao CSS original
    }
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


// Modal Detalhes
function openNotificationModal(notification) {
    if (!notification) return;

    // Populate Modal
    document.getElementById('notifDetailTitle').textContent = notification.title;
    document.getElementById('notifDetailMessage').textContent = notification.message || '';
    document.getElementById('notifDetailDate').textContent = getTimeAgo(notification.created_at);

    // Icon Logic
    const config = notification.config || { icon: 'bell', color: '#64748b' };
    const iconContainer = document.getElementById('notifDetailIcon');
    iconContainer.style.backgroundColor = config.color + '20';
    iconContainer.style.color = config.color;
    iconContainer.innerHTML = `<i data-lucide="${config.icon}" style="width: 24px; height: 24px;"></i>`;
    if (window.lucide) window.lucide.createIcons({ root: iconContainer });

    // Link Logic
    const linkBtn = document.getElementById('notifDetailLink');
    if (notification.link) {
        linkBtn.style.display = 'flex';
        linkBtn.href = notification.link;
    } else {
        linkBtn.style.display = 'none';
    }

    // Show Modal
    const modal = document.getElementById('notificationDetailModal');
    if (modal) modal.style.display = 'flex';

    // Mark as read without redirecting
    if (!notification.is_read) {
        handleNotificationClick(notification.id, null);

        // Update local state if in dropdown data
        notification.is_read = 1;

        // Update UI in Dropdown
        renderNotifications();

        // Update UI in Full Page (if exists)
        const row = document.querySelector(`.notification-item[data-id="${notification.id}"]`);
        if (row) row.classList.remove('unread');

        // Update Stats
        loadDashboardStats();
    }
}

function openNotificationDetail(id) {
    const notification = notificationsData.find(n => n.id == id);
    if (notification) {
        openNotificationModal(notification);
    }
}

function closeNotificationDetail() {
    const modal = document.getElementById('notificationDetailModal');
    if (modal) modal.style.display = 'none';
}

// Carregar estatísticas do dashboard
async function loadDashboardStats() {
    // Só executa se os elementos existirem (estamos na página de notificações)
    const elTotal = document.getElementById('stat-total');
    if (!elTotal) return;

    try {
        const response = await fetch(`${API_ENDPOINT}?action=stats`);
        const data = await response.json();

        if (data.success && data.stats) {
            elTotal.innerText = data.stats.total;
            document.getElementById('stat-unread').innerText = data.stats.unread;
            document.getElementById('stat-read').innerText = data.stats.read;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}


// Carregar contador ao iniciar e configurar polling
document.addEventListener('DOMContentLoaded', function () {
    loadUnreadCount();
    loadDashboardStats();

    // Polling a cada 60 segundos (reduzido de 30s)
    setInterval(loadUnreadCount, 60000);
});
