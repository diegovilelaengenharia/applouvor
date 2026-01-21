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

    <!-- 1. PERFIL DO USUÁRIO -->
    <div class="settings-section">
        <h3 class="settings-title">
            <i data-lucide="user"></i> Dados Pessoais
        </h3>
        <div class="settings-card">
            <form action="api/update_profile.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Foto e Nome -->
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="position: relative;">
                        <!-- Avatar Preview -->
                        <img src="<?= $_SESSION['user_avatar'] ?? '../assets/img/default_avatar.png' ?>"
                            style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f5f9;">
                        <label for="upload-avatar" style="position: absolute; bottom: 0; right: 0; background: #3b82f6; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid white;">
                            <i data-lucide="camera" style="width: 14px;"></i>
                        </label>
                        <input type="file" id="upload-avatar" name="avatar" style="display: none;">
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 12px;">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?>" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Telefone / WhatsApp</label>
                        <input type="tel" name="phone" placeholder="(35) 99999-9999" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Endereço</label>
                        <input type="text" name="address" placeholder="Rua..." class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label>Função Ministerial</label>
                    <input type="text" value="<?= htmlspecialchars($_SESSION['user_role'] === 'admin' ? 'Líder / Admin' : 'Membro') ?>" disabled class="form-input disabled">
                </div>

                <button type="submit" class="btn-save ripple">Salvar Alterações</button>
            </form>
        </div>
    </div>


    <!-- 2. APARÊNCIA (Dark Mode) -->
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


    <!-- 3. ACESSO RÁPIDO (Dashboard) -->
    <div class="settings-section">
        <h3 class="settings-title">
            <i data-lucide="layout-grid"></i> Dashboard de Acesso
        </h3>
        <div class="settings-card">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                Selecione quais atalhos aparecem na sua tela inicial:
            </p>

            <div class="switch-row petite">
                <span>Escalas</span>
                <label class="toggle-switch small">
                    <input type="checkbox" checked disabled> <!-- Obrigatório -->
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="switch-row petite">
                <span>Repertório</span>
                <label class="toggle-switch small">
                    <input type="checkbox" checked>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="switch-row petite">
                <span>Mural de Avisos</span>
                <label class="toggle-switch small">
                    <input type="checkbox" checked>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="switch-row petite">
                <span>Leitura Bíblica</span>
                <label class="toggle-switch small">
                    <input type="checkbox">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>


    <!-- 4. SOBRE O APP -->
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
        color: var(--text-secondary);
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
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
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
        color: var(--text-secondary);
    }

    .form-input {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.95rem;
        font-family: inherit;
        color: var(--text-primary);
        background: #f8fafc;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
    }

    .form-input.disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .btn-save {
        background: #0f172a;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 8px;
        text-align: center;
        font-size: 0.95rem;
    }

    .btn-save:hover {
        background: #1e293b;
    }

    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        border: 1px solid #e2e8f0;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .btn-outline:hover {
        background: #f8fafc;
        color: var(--text-primary);
        border-color: #cbd5e1;
    }

    /* Toggle Switch Styles */
    .switch-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .switch-row.petite {
        padding: 12px 0;
        border-bottom: 1px solid #f8fafc;
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
        background: #f5f3ff;
        color: #7c3aed;
    }

    .switch-label {
        font-weight: 600;
        color: var(--text-primary);
    }

    .switch-desc {
        font-size: 0.8rem;
        color: var(--text-secondary);
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
        background-color: #cbd5e1;
        transition: .4s;
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
        background-color: #3b82f6;
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