<?php
// admin/configuracoes.php
require_once '../includes/auth.php';
require_once '../includes/layout.php';

// Mock user data fetch if not already in session/db (handled by layout/auth mostly)
// For updating data, we would handle POST here. For now, laying out the UI.

renderAppHeader('Configurações');
// Custom Header with just title
renderPageHeader('Ajustes e Preferências', 'Personalize sua experiência');
?>

<div style="max-width: 800px; margin: 0 auto; padding-bottom: 60px;">

    <!-- 1. APARÊNCIA (Dark Mode) -->
    <div class="settings-section">
        <h3 class="settings-title">
            <i data-lucide="palette"></i> Aparência
        </h3>
        <div class="settings-card">
            <div class="switch-row">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div class="icon-box purple"><i data-lucide="moon"></i></div>
                    <div>
                        <div class="switch-label">Modo Escuro</div>
                        <div class="switch-desc">Interface confortável para uso noturno.</div>
                    </div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="darkModeToggle" onchange="toggleThemeMode()">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- 2. SOBRE O APP -->
    <div class="settings-section">
        <h3 class="settings-title">
            <i data-lucide="info"></i> Sobre
        </h3>
        <div class="settings-card" style="text-align: center; padding: 32px;">
            <img src="../assets/img/logo_pib_black.png" style="height: 50px; opacity: 0.8; margin-bottom: 16px;">
            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;">App Louvor PIB</div>
            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px;">Versão 1.1.0 (Beta)</div>

            <div style="font-size: 0.85rem; color: #94a3b8; line-height: 1.6;">
                Desenvolvido para organizar e potencializar<br>o ministério de louvor da PIB Oliveira.
                <br><br>
                <strong>Desenvolvedor:</strong> Diego Vilela
            </div>

            <a href="https://wa.me/55359984529577" target="_blank" class="btn-outline ripple" style="margin-top: 24px;">
                <i data-lucide="message-circle"></i> Suporte / Sugestões
            </a>
        </div>
    </div>

</div>

<style>
    /* CSS Específico da Página de Configurações */
    .settings-section {
        margin-bottom: 32px;
    }

    .settings-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .settings-title i {
        width: 18px;
        height: 18px;
    }

    .settings-card {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        padding: 20px;
        box-shadow: var(--shadow-sm);
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
    }

    .form-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
    }

    .form-input {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        outline: none;
        transition: all 0.2s;
    }

    .form-input.disabled {
        background: var(--bg-body);
        color: var(--text-muted);
        cursor: not-allowed;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .btn-save {
        background: var(--primary);
        color: white;
        border: none;
        padding: 12px;
        border-radius: var(--radius-md);
        font-weight: 700;
        cursor: pointer;
        margin-top: 8px;
        text-align: center;
        font-size: 0.95rem;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
    }

    .btn-save:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        background: transparent;
    }

    .btn-outline:hover {
        background: var(--bg-surface);
        color: var(--text-main);
        border-color: var(--primary);
    }

    /* Toggle Switch Styles */
    .switch-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .switch-row.petite {
        padding: 12px 0;
        border-bottom: 1px solid var(--bg-body);
    }

    .switch-row:last-child {
        border-bottom: none;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-box.purple {
        background: var(--bg-body);
        color: var(--text-main);
    }

    .switch-label {
        font-weight: 600;
        color: var(--text-main);
    }

    .switch-desc {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .toggle-switch.small {
        width: 36px;
        height: 20px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--text-muted);
        /* Inactive */
        transition: .4s;
        border-radius: 34px;
        opacity: 0.3;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
    }

    .toggle-switch.small .slider:before {
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
    }

    input:checked+.slider {
        background-color: var(--primary);
        opacity: 1;
    }

    input:checked+.slider:before {
        transform: translateX(24px);
    }

    .toggle-switch.small input:checked+.slider:before {
        transform: translateX(16px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    @media(max-width: 600px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    // Sincronizar estado do Dark Mode Toggle ao carregar
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('darkModeToggle');
        const isDark = document.body.classList.contains('dark-mode');
        if (toggle) toggle.checked = isDark;
    });
</script>

<?php renderAppFooter(); ?>