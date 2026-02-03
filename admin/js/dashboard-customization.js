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

// Definições de todos os cards disponíveis (sincronizado com dashboard_cards.php)
function loadAllCardsDefinitions() {
    allCards = {
        // GESTÃO → AZUL
        escalas: { title: 'Escalas', icon: 'calendar', category: 'Gestão', color: '#2563eb', bg: '#eff6ff' },
        repertorio: { title: 'Repertório', icon: 'music', category: 'Gestão', color: '#2563eb', bg: '#eff6ff' },
        membros: { title: 'Membros', icon: 'users', category: 'Gestão', color: '#2563eb', bg: '#eff6ff' },
        agenda: { title: 'Agenda', icon: 'calendar-days', category: 'Gestão', color: '#2563eb', bg: '#eff6ff' },
        ausencias: { title: 'Ausências', icon: 'calendar-x', category: 'Gestão', color: '#2563eb', bg: '#eff6ff' },

        // ESPÍRITO → VERDE
        leitura: { title: 'Leitura Bíblica', icon: 'book-open', category: 'Espírito', color: '#059669', bg: '#ecfdf5' },
        devocional: { title: 'Devocional', icon: 'sunrise', category: 'Espírito', color: '#059669', bg: '#ecfdf5' },
        oracao: { title: 'Oração', icon: 'heart', category: 'Espírito', color: '#059669', bg: '#ecfdf5' },

        // COMUNICAÇÃO → ROXO
        avisos: { title: 'Avisos', icon: 'bell', category: 'Comunica', color: '#7c3aed', bg: '#f5f3ff' },
        aniversarios: { title: 'Aniversários', icon: 'cake', category: 'Comunica', color: '#7c3aed', bg: '#f5f3ff' }
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
                background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
                z-index: 9999; display: flex; align-items: center; justify-content: center;
                animation: fadeIn 0.2s;
            }
            
            .customize-modal-content {
                background: var(--bg-surface); width: 90%; max-width: 500px;
                max-height: 80vh; border-radius: 16px; overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
                display: flex; flex-direction: column;
            }
            
            .customize-modal-header {
                padding: 20px 24px; border-bottom: 1px solid var(--border-color);
                display: flex; align-items: center; justify-content: space-between;
            }
            
            .customize-modal-header h3 {
                margin: 0; font-size: 1.25rem; font-weight: 700;
                color: var(--text-main);
            }
            
            .customize-modal-body {
                padding: 20px 24px; overflow-y: auto; flex: 1;
            }
            
            .customize-description {
                color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;
            }
            
            .customize-grid {
                display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            }
            
            .customize-card-label {
                display: flex; align-items: center; gap: 10px; padding: 12px;
                border: 1px solid var(--border-color); border-radius: 12px;
                cursor: pointer; transition: all 0.2s; background: var(--bg-body);
            }
            
            .customize-card-label:hover {
                border-color: var(--primary); background: var(--bg-surface);
            }
            
            .customize-card-label input {
                width: 18px; height: 18px; accent-color: var(--primary);
            }
            
            .customize-card-icon {
                width: 28px; height: 28px; border-radius: 8px;
                display: flex; align-items: center; justify-content: center;
            }
            
            .customize-card-title {
                font-weight: 500; font-size: 0.85rem; color: var(--text-main);
            }
            
            .customize-modal-footer {
                padding: 16px 24px; border-top: 1px solid var(--border-color);
                display: flex; gap: 12px; justify-content: flex-end;
            }
            
            .customize-btn {
                padding: 10px 20px; border-radius: 8px; font-weight: 600;
                cursor: pointer; transition: all 0.2s; border: none;
                font-size: 0.9rem;
            }
            
            .customize-btn-primary {
                background: var(--primary); color: white;
            }
            
            .customize-btn-primary:hover {
                opacity: 0.9;
            }
            
            .customize-btn-secondary {
                background: transparent; color: var(--text-main);
                border: 1px solid var(--border-color);
            }
            
            .customize-btn-secondary:hover {
                background: var(--bg-body);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
        
        <div class="customize-modal-content">
            <div class="customize-modal-header">
                <h3>Personalizar Acesso Rápido</h3>
                <button onclick="closeDashboardCustomization()" style="background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 8px;">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>
            
            <div class="customize-modal-body">
                <p class="customize-description">Selecione os atalhos que deseja exibir no seu painel.</p>
                <div class="customize-grid" id="customizeCardsList">
                    <!-- Cards serão inseridos aqui -->
                </div>
            </div>
            
            <div class="customize-modal-footer">
                <button class="customize-btn customize-btn-secondary" onclick="closeDashboardCustomization()">
                    Cancelar
                </button>
                <button class="customize-btn customize-btn-primary" onclick="saveDashboardCustomization()">
                    Salvar Alterações
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    renderCardsList();
    lucide.createIcons();
}

// Renderizar lista de cards como checkboxes em grid 2 colunas
function renderCardsList() {
    const container = document.getElementById('customizeCardsList');

    // Renderizar todos os cards como checkboxes
    let html = '';
    dashboardSettings.forEach(setting => {
        const card = allCards[setting.card_id];
        if (!card) return;

        html += `
            <label class="customize-card-label" data-card-id="${setting.card_id}">
                <input type="checkbox" 
                       ${setting.is_visible ? 'checked' : ''} 
                       onchange="toggleCardVisibility('${setting.card_id}')">
                <div class="customize-card-icon" style="background: ${card.bg}; color: ${card.color};">
                    <i data-lucide="${card.icon}" style="width: 16px;"></i>
                </div>
                <span class="customize-card-title">${card.title}</span>
            </label>
        `;
    });

    container.innerHTML = html;
}

// Toggle visibilidade do card
function toggleCardVisibility(cardId) {
    const setting = dashboardSettings.find(s => s.card_id === cardId);
    if (setting) {
        setting.is_visible = !setting.is_visible;
    }
}

// Inicializar drag and drop
function initDragAndDrop() {
    const items = document.querySelectorAll('.customize-card-item');
    let draggedItem = null;

    items.forEach(item => {
        item.addEventListener('dragstart', function () {
            draggedItem = this;
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function () {
            this.classList.remove('dragging');
        });

        item.addEventListener('dragover', function (e) {
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
