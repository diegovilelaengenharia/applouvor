// roles.js - Gerenciamento de funções dos membros

class RolesManager {
    constructor() {
        this.selectedRoles = new Set();
        this.allRoles = [];
        this.currentUserId = null;
    }

    // Abrir modal de seleção de funções
    async openRolesSelector(userId) {
        this.currentUserId = userId;
        
        // Buscar funções disponíveis
        await this.loadAllRoles();
        
        // Buscar funções atuais do usuário
        await this.loadUserRoles(userId);
        
        // Renderizar modal
        this.renderModal();
        
        // Mostrar overlay
        document.getElementById('rolesOverlay').classList.add('active');
    }

    // Carregar todas as funções disponíveis
    async loadAllRoles() {
        try {
            const response = await fetch('/admin/api/roles.php');
            const data = await response.json();
            
            if (data.success) {
                this.allRoles = data.roles;
                this.groupedRoles = data.grouped;
            }
        } catch (error) {
            console.error('Erro ao carregar funções:', error);
        }
    }

    // Carregar funções do usuário
    async loadUserRoles(userId) {
        try {
            const response = await fetch(`/admin/api/roles.php?user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.selectedRoles = new Set(data.roles.map(r => r.id));
            }
        } catch (error) {
            console.error('Erro ao carregar funções do usuário:', error);
        }
    }

    // Renderizar modal
    renderModal() {
        const categories = {
            'voz': { name: '🎤 Vozes', icon: '🎤' },
            'cordas': { name: '🎸 Cordas', icon: '🎸' },
            'teclas': { name: '🎹 Teclas', icon: '🎹' },
            'percussao': { name: '🥁 Percussão', icon: '🥁' },
            'sopro': { name: '🎺 Sopro', icon: '🎺' },
            'outros': { name: '🎧 Outros', icon: '🎧' }
        };

        let html = `
            <div class="roles-selector-overlay" id="rolesOverlay">
                <div class="roles-selector">
                    <div class="roles-selector-header">
                        <h3 class="roles-selector-title">Selecionar Funções</h3>
                        <button class="roles-selector-close" onclick="rolesManager.closeModal()">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="roles-selector-body">
        `;

        // Renderizar cada categoria
        for (const [category, info] of Object.entries(categories)) {
            if (this.groupedRoles[category] && this.groupedRoles[category].length > 0) {
                html += `
                    <div class="role-category">
                        <div class="role-category-title">${info.name}</div>
                        <div class="role-items">
                `;

                this.groupedRoles[category].forEach(role => {
                    const isSelected = this.selectedRoles.has(role.id);
                    html += `
                        <div class="role-item ${isSelected ? 'selected' : ''}" 
                             data-role-id="${role.id}"
                             onclick="rolesManager.toggleRole(${role.id})">
                            <span class="role-item-icon">${role.icon}</span>
                            <span class="role-item-name">${role.name}</span>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            }
        }

        html += `
                    </div>
                    <div class="roles-selector-footer">
                        <button class="btn-cancel-roles" onclick="rolesManager.closeModal()">
                            Cancelar
                        </button>
                        <button class="btn-save-roles" onclick="rolesManager.saveRoles()">
                            Salvar Funções
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior se existir
        const existingOverlay = document.getElementById('rolesOverlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }

        // Adicionar ao body
        document.body.insertAdjacentHTML('beforeend', html);

        // Inicializar ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Alternar seleção de função
    toggleRole(roleId) {
        const roleItem = document.querySelector(`[data-role-id="${roleId}"]`);
        
        if (this.selectedRoles.has(roleId)) {
            this.selectedRoles.delete(roleId);
            roleItem.classList.remove('selected');
        } else {
            this.selectedRoles.add(roleId);
            roleItem.classList.add('selected');
        }
    }

    // Salvar funções
    async saveRoles() {
        if (!this.currentUserId) return;

        try {
            // Buscar funções atuais
            const response = await fetch(`/admin/api/roles.php?user_id=${this.currentUserId}`);
            const data = await response.json();
            const currentRoles = new Set(data.roles.map(r => r.id));

            // Funções para adicionar
            const toAdd = [...this.selectedRoles].filter(id => !currentRoles.has(id));
            
            // Funções para remover
            const toRemove = [...currentRoles].filter(id => !this.selectedRoles.has(id));

            // Adicionar novas funções
            for (const roleId of toAdd) {
                await fetch('/admin/api/roles.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: this.currentUserId,
                        role_id: roleId,
                        is_primary: toAdd.length === 1 && currentRoles.size === 0
                    })
                });
            }

            // Remover funções
            for (const roleId of toRemove) {
                await fetch('/admin/api/roles.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: this.currentUserId,
                        role_id: roleId
                    })
                });
            }

            // Fechar modal
            this.closeModal();

            // Recarregar página para mostrar mudanças
            window.location.reload();

        } catch (error) {
            console.error('Erro ao salvar funções:', error);
            alert('Erro ao salvar funções. Tente novamente.');
        }
    }

    // Fechar modal
    closeModal() {
        const overlay = document.getElementById('rolesOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
        }
    }

    // Renderizar badges de funções
    static renderRoleBadges(roles) {
        if (!roles || roles.length === 0) {
            return '<span style="color: var(--text-muted); font-size: 0.85rem;">Sem função definida</span>';
        }

        return roles.map(role => `
            <span class="role-badge ${role.is_primary ? 'primary' : ''}" 
                  style="background: ${role.color}">
                <span class="role-icon">${role.icon}</span>
                <span>${role.name}</span>
            </span>
        `).join('');
    }
}

// Instância global
const rolesManager = new RolesManager();

// Fechar modal ao clicar fora
document.addEventListener('click', (e) => {
    const overlay = document.getElementById('rolesOverlay');
    if (overlay && e.target === overlay) {
        rolesManager.closeModal();
    }
});
