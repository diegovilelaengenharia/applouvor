// assets/js/layout.js

// === TOGGLE NOTIFICATIONS ===
window.toggleNotifications = function(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) {
        console.error('[ERROR] Dropdown não encontrado:', dropdownId);
        return;
    }
    
    // Toggle visibility
    const isVisible = dropdown.classList.contains('active');
    
    // Fechar todos os dropdowns primeiro
    document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
        d.classList.remove('active');
    });
    
    // Abrir se estava fechado
    if (!isVisible) {
        dropdown.classList.add('active');
    }
};

// === TOGGLE PROFILE ===
window.toggleProfileDropdown = function(event, dropdownId) {
    if (event) event.stopPropagation();
    
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) {
        console.error('[ERROR] Dropdown perfil não encontrado:', dropdownId);
        return;
    }
    
    // Toggle visibility
    const isVisible = dropdown.classList.contains('active');
    
    // Fechar todos os dropdowns primeiro
    document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
        d.classList.remove('active');
    });
    
    // Abrir se estava fechado
    if (!isVisible) {
        dropdown.classList.add('active');
    }
};

// === FECHAR AO CLICAR FORA ===
document.addEventListener('click', function(e) {
    // Se clicou fora de qualquer dropdown ou botão, fechar tudo
    if (!e.target.closest('.notification-dropdown') && 
        !e.target.closest('.profile-dropdown') &&
        !e.target.closest('.notification-btn') &&
        !e.target.closest('.profile-avatar-btn') &&
        !e.target.closest('.header-action-btn')) {
        
        document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
            d.classList.remove('active');
        });
    }
});

function closeAllSheets() {
    const overlay = document.getElementById('bs-overlay');
    if (overlay) overlay.classList.remove('active');
    
    // Fecha menus
    document.querySelectorAll('.bottom-sheet').forEach(el => el.classList.remove('active'));

    // Remove active
    document.querySelectorAll('.b-nav-item').forEach(el => el.classList.remove('active'));
}

function openDashboardCustomization() {
    const modal = document.getElementById('dashboardCustomizationModal');
    if (modal) {
        modal.style.display = 'flex';
        // Reiniciar Lucide icons se necessário
        if (window.lucide) lucide.createIcons();
    }
}

function closeDashboardCustomization() {
    const modal = document.getElementById('dashboardCustomizationModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('dashboardCustomizationForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.textContent;
        btnSubmit.textContent = 'Salvando...';
        btnSubmit.disabled = true;
        
        const formData = new FormData(this);
        const selectedCards = [];
        
        formData.getAll('cards[]').forEach((id, index) => {
            selectedCards.push({
                card_id: id,
                is_visible: true,
                display_order: index + 1
            });
        });
        
        // Determinar API URL correto
        const isAdmin = window.location.pathname.includes('/admin/');
        const apiUrl = isAdmin ? 'api/save_dashboard_settings.php' : 'admin/api/save_dashboard_settings.php';
        
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ cards: selectedCards })
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert('Erro ao salvar: ' + (result.message || 'Erro desconhecido'));
                btnSubmit.textContent = originalText;
                btnSubmit.disabled = false;
            }
        } catch (error) {
            console.error(error);
            alert('Erro na comunicação com o servidor.');
            btnSubmit.textContent = originalText;
            btnSubmit.disabled = false;
        }
    });
    
    // Close on click outside
    document.getElementById('dashboardCustomizationModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDashboardCustomization();
    });
});
