/**
 * Modal de Personalização de Cards do Dashboard
 * Permite usuários escolherem quais cards aparecem e em qual ordem
 */

let dashboardSettings = [];
let allCards = {};

// Inicializar modal
function initDashboardCustomization() {
    loadAllCardsDefinitions();
    
    // Adicionar botão de configuração ao cabeçalho se não existir
    const pageHeader = document.querySelector('.desktop-only-header');
    if (pageHeader && !document.getElementById('btnCustomizeDashboard')) {
        const rightActions = pageHeader.querySelector('div:last-child');
        const btn = document.createElement('button');
        btn.id = 'btnCustomizeDashboard';
        btn.className = 'ripple';
        btn.onclick = openDashboardCustomization;
        btn.style.cssText = `
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; background: var(--bg-surface);
            border: 1px solid var(--border-color); border-radius: 10px;
            cursor: pointer; color: var(--text-muted); margin-right: 8px;
        `;
        btn.innerHTML = '<i data-lucide="settings" style="width: 20px;"></i>';
        rightActions.insertBefore(btn, rightActions.firstChild);
        lucide.createIcons();
    }
}

// Definições de todos os cards disponíveis
function loadAllCardsDefinitions() {
    allCards = {
        // GESTÃO
        escalas: { title: 'Escalas', icon: 'calendar', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        repertorio: { title: 'Repertório', icon: 'music', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        membros: { title: 'Membros', icon: 'users', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        stats_escalas: { title: 'Stats Escalas', icon: 'bar-chart-2', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        stats_repertorio: { title: 'Stats Repertório', icon: 'trending-up', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        relatorios: { title: 'Relatórios', icon: 'file-text', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        agenda: { title: 'Agenda', icon: 'calendar-days', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        indisponibilidades: { title: 'Indisponibilidades', icon: 'calendar-x', category: 'Gestão', color: '#047857', bg: '#ecfdf5' },
        
        // ESPÍRITO
        leitura: { title: 'Leitura Bíblica', icon: 'book-open', category: 'Espírito', color: '#4338ca', bg: '#eef2ff' },
        devocional: { title: 'Devocional', icon: 'sunrise', category: 'Espírito', color: '#4338ca', bg: '#eef2ff' },
        oracao: { title: 'Oração', icon: 'heart', category: 'Espírito', color: '#4338ca', bg: '#eef2ff' },
        config_leitura: { title: 'Config. Leitura', icon: 'settings', category: 'Espírito', color: '#4338ca', bg: '#eef2ff' },
        
        // COMUNICA
        avisos: { title: 'Avisos', icon: 'bell', category: 'Comunica', color: '#ea580c', bg: '#fff7ed' },
        aniversariantes: { title: 'Aniversariantes', icon: 'cake', category: 'Comunica', color: '#ea580c', bg: '#fff7ed' },
        chat: { title: 'Chat', icon: 'message-circle', category: 'Comunica', color: '#ea580c', bg: '#fff7ed' },
        
        // ADMIN
        lider: { title: 'Painel do Líder', icon: 'crown', category: 'Admin', color: '#dc2626', bg: '#fee2e2' },
        perfil: { title: 'Perfil', icon: 'user', category: 'Admin', color: '#dc2626', bg: '#fee2e2' },
        configuracoes: { title: 'Configurações', icon: 'sliders', category: 'Admin', color: '#dc2626', bg: '#fee2e2' },
        monitoramento: { title: 'Monitoramento', icon: 'activity', category: 'Admin', color: '#dc2626', bg: '#fee2e2' },
        pastas: { title: 'Pastas', icon: 'folder', category: 'Admin', color: '#dc2626', bg: '#fee2e2' },
        
        // EXTRAS
        playlists: { title: 'Playlists', icon: 'list-music', category: 'Extras', color: '#64748b', bg: '#f1f5f9' },
        artistas: { title: 'Artistas', icon: 'mic-2', category: 'Extras', color: '#64748b', bg: '#f1f5f9' },
        classificacoes: { title: 'Classificações', icon: 'tags', category: 'Extras', color: '#64748b', bg: '#f1f5f9' }
    };
}

// Abrir modal de personalização
async function openDashboardCustomization() {
    // Buscar configurações atuais
    try {
        const response = await fetch('api/get_dashboard_settings.php');
        const data = await response.json();
        
        if (data.success) {
            dashboardSettings = data.settings;
            renderCustomizationModal();
        } else {
            alert('Erro ao carregar configurações');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao carregar configurações');
    }
}

// Renderizar modal
function renderCustomizationModal() {
    // Remover modal existente se houver
    const existingModal = document.getElementById('dashboardCustomizationModal');
    if (existingModal) existingModal.remove();
    
    // Criar modal
    const modal = document.createElement('div');
    modal.id = 'dashboardCustomizationModal';
    modal.innerHTML = `
        <style>
            #dashboardCustomizationModal {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
                z-index: 9999; display: flex; align-items: center; justify-content: center;
                animation: fadeIn 0.2s;
            }
            
            .customize-modal-content {
                background: var(--bg-surface); width: 90%; max-width: 600px;
                max-height: 90vh; border-radius: 20px; overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
                display: flex; flex-direction: column;
            }
            
            .customize-modal-header {
                padding: 24px; border-bottom: 1px solid var(--border-color);
                display: flex; align-items: center; justify-content: space-between;
            }
            
            .customize-modal-header h2 {
                margin: 0; font-size: 1.25rem; font-weight: 700;
                color: var(--text-main);
            }
            
            .customize-modal-body {
                padding: 20px; overflow-y: auto; flex: 1;
            }
            
            .customize-category {
                margin-bottom: 24px;
            }
            
            .customize-category-title {
                font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
                letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;
                display: flex; align-items: center; gap: 8px;
            }
            
            .customize-card-item {
                background: var(--bg-body); border: 2px solid var(--border-color);
                border-radius: 12px; padding: 12px 16px; margin-bottom: 8px;
                display: flex; align-items: center; gap: 12px;
                cursor: move; transition: all 0.2s;
            }
            
            .customize-card-item:hover {
                border-color: var(--primary); transform: translateX(4px);
            }
            
            .customize-card-item.dragging {
                opacity: 0.5;
            }
            
            .customize-card-drag {
                color: var(--text-muted); cursor: grab;
            }
            
            .customize-card-drag:active {
                cursor: grabbing;
            }
            
            .customize-card-icon {
                width: 36px; height: 36px; border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
            }
            
            .customize-card-info {
                flex: 1;
            }
            
            .customize-card-title {
                font-weight: 600; font-size: 0.9rem; color: var(--text-main);
            }
            
            .customize-card-category {
                font-size: 0.75rem; color: var(--text-muted);
            }
            
            .customize-toggle {
                position: relative; width: 44px; height: 24px;
                background: #e2e8f0; border-radius: 12px;
                cursor: pointer; transition: background 0.2s;
            }
            
            .customize-toggle.active {
                background: var(--primary);
            }
            
            .customize-toggle-slider {
                position: absolute; top: 2px; left: 2px;
                width: 20px; height: 20px; background: white;
                border-radius: 50%; transition: transform 0.2s;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .customize-toggle.active .customize-toggle-slider {
                transform: translateX(20px);
            }
            
            .customize-modal-footer {
                padding: 20px; border-top: 1px solid var(--border-color);
                display: flex; gap: 12px; justify-content: flex-end;
            }
            
            .customize-btn {
                padding: 10px 20px; border-radius: 10px; font-weight: 600;
                cursor: pointer; transition: all 0.2s; border: none;
                font-size: 0.9rem;
            }
            
            .customize-btn-primary {
                background: var(--primary); color: white;
            }
            
            .customize-btn-primary:hover {
                opacity: 0.9; transform: translateY(-1px);
            }
            
            .customize-btn-secondary {
                background: var(--bg-body); color: var(--text-main);
                border: 1px solid var(--border-color);
            }
            
            .customize-btn-secondary:hover {
                background: var(--border-color);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
        
        <div class="customize-modal-content">
            <div class="customize-modal-header">
                <h2>⚙️ Personalizar Visão Geral</h2>
                <button onclick="closeDashboardCustomization()" style="background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 8px;">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>
            
            <div class="customize-modal-body" id="customizeCardsList">
                <!-- Cards serão inseridos aqui -->
            </div>
            
            <div class="customize-modal-footer">
                <button class="customize-btn customize-btn-secondary" onclick="resetDashboardToDefault()">
                    Restaurar Padrão
                </button>
                <button class="customize-btn customize-btn-secondary" onclick="closeDashboardCustomization()">
                    Cancelar
                </button>
                <button class="customize-btn customize-btn-primary" onclick="saveDashboardCustomization()">
                    Salvar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    renderCardsList();
    initDragAndDrop();
    lucide.createIcons();
}

// Renderizar lista de cards
function renderCardsList() {
    const container = document.getElementById('customizeCardsList');
    const categories = {
        'Gestão': [],
        'Espírito': [],
        'Comunica': [],
        'Admin': [],
        'Extras': []
    };
    
    // Agrupar por categoria
    dashboardSettings.forEach(setting => {
        const card = allCards[setting.card_id];
        if (card) {
            categories[card.category].push({...setting, ...card});
        }
    });
    
    // Renderizar por categoria
    let html = '';
    Object.keys(categories).forEach(category => {
        if (categories[category].length === 0) return;
        
        html += `
            <div class="customize-category">
                <div class="customize-category-title">${category}</div>
                <div class="customize-category-cards">
        `;
        
        categories[category].forEach((card, index) => {
            html += `
                <div class="customize-card-item" draggable="true" data-card-id="${card.card_id}" data-order="${card.display_order}">
                    <div class="customize-card-drag">
                        <i data-lucide="grip-vertical" style="width: 18px;"></i>
                    </div>
                    <div class="customize-card-icon" style="background: ${card.bg}; color: ${card.color};">
                        <i data-lucide="${card.icon}" style="width: 18px;"></i>
                    </div>
                    <div class="customize-card-info">
                        <div class="customize-card-title">${card.title}</div>
                        <div class="customize-card-category">${card.category}</div>
                    </div>
                    <div class="customize-toggle ${card.is_visible ? 'active' : ''}" onclick="toggleCardVisibility('${card.card_id}')">
                        <div class="customize-toggle-slider"></div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Toggle visibilidade do card
function toggleCardVisibility(cardId) {
    const setting = dashboardSettings.find(s => s.card_id === cardId);
    if (setting) {
        setting.is_visible = !setting.is_visible;
        
        // Atualizar UI
        const toggle = document.querySelector(`[data-card-id="${cardId}"] .customize-toggle`);
        if (toggle) {
            toggle.classList.toggle('active');
        }
    }
}

// Inicializar drag and drop
function initDragAndDrop() {
    const items = document.querySelectorAll('.customize-card-item');
    let draggedItem = null;
    
    items.forEach(item => {
        item.addEventListener('dragstart', function() {
            draggedItem = this;
            this.classList.add('dragging');
        });
        
        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(this.parentElement, e.clientY);
            if (afterElement == null) {
                this.parentElement.appendChild(draggedItem);
            } else {
                this.parentElement.insertBefore(draggedItem, afterElement);
            }
        });
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.customize-card-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Salvar personalização
async function saveDashboardCustomization() {
    // Atualizar ordem baseado na posição atual
    const items = document.querySelectorAll('.customize-card-item');
    items.forEach((item, index) => {
        const cardId = item.dataset.cardId;
        const setting = dashboardSettings.find(s => s.card_id === cardId);
        if (setting) {
            setting.display_order = index + 1;
        }
    });
    
    // Validar que pelo menos 1 card está visível
    const visibleCount = dashboardSettings.filter(s => s.is_visible).length;
    if (visibleCount === 0) {
        alert('Pelo menos um card deve estar visível!');
        return;
    }
    
    try {
        const response = await fetch('api/save_dashboard_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cards: dashboardSettings })
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeDashboardCustomization();
            location.reload(); // Recarregar página para mostrar mudanças
        } else {
            alert(data.message || 'Erro ao salvar configurações');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar configurações');
    }
}

// Restaurar padrão
function resetDashboardToDefault() {
    if (!confirm('Deseja restaurar as configurações padrão? Isso irá resetar todos os cards.')) {
        return;
    }
    
    // Resetar para configuração padrão
    dashboardSettings.forEach(setting => {
        const defaultVisible = ['escalas', 'repertorio', 'leitura', 'avisos', 'aniversariantes', 'devocional', 'oracao'];
        setting.is_visible = defaultVisible.includes(setting.card_id);
    });
    
    renderCardsList();
    lucide.createIcons();
}

// Fechar modal
function closeDashboardCustomization() {
    const modal = document.getElementById('dashboardCustomizationModal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.2s';
        setTimeout(() => modal.remove(), 200);
    }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCustomization);
} else {
    initDashboardCustomization();
}
