<?php
// admin/lider.php
// Painel de Liderança (Separado do Index)

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Painel Líder');
?>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="index.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; align-items: center;">`r`n            <?php renderGlobalNavButtons(); ?>`r`n        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Painel Líder</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<div class="container fade-in-up" style="margin-top: 0;">
    <!-- Spacer for the hero separation -->
    <div style="height: 10px;"></div>

    <style>
        .wide-action-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            padding: 20px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .wide-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-interactive);
        }

        .wide-action-btn:active {
            transform: scale(0.98);
        }

        .wide-btn-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gradient-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .wide-action-btn:hover .wide-btn-icon {
            background: var(--gradient-primary);
            color: white;
            transform: scale(1.1) rotate(5deg);
        }

        .wide-btn-content {
            flex: 1;
        }

        .wide-btn-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .wide-btn-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .wide-btn-arrow {
            color: var(--text-muted);
            transition: transform 0.2s;
        }

        .wide-action-btn:hover .wide-btn-arrow {
            transform: translateX(4px);
            color: var(--accent-interactive);
        }
    </style>

    <!-- Lista Vertical de Funcionalidades -->
    <div style="display: flex; flex-direction: column; gap: 12px; padding-bottom: 80px;">

        <!-- Gestão de Avisos -->
        <a href="avisos.php?from=lider" class="wide-action-btn ripple">
            <div class="wide-btn-icon" style="background: rgba(255, 193, 7, 0.1); color: #D97706;">
                <i data-lucide="bell-ring"></i>
            </div>
            <div class="wide-btn-content">
                <div class="wide-btn-title">Gestão de Avisos</div>
                <div class="wide-btn-desc">Criar, editar e gerenciar notificações</div>
            </div>
            <i data-lucide="chevron-right" class="wide-btn-arrow"></i>
        </a>

        <!-- Exportar Dados (Por enquanto só este) -->
        <a href="exportar.php" class="wide-action-btn ripple">
            <div class="wide-btn-icon" style="background: rgba(45, 122, 79, 0.1); color: var(--accent-interactive);">
                <i data-lucide="database"></i>
            </div>
            <div class="wide-btn-content">
                <div class="wide-btn-title">Exportar Dados</div>
                <div class="wide-btn-desc">Baixar planilhas e relatórios</div>
            </div>
            <i data-lucide="chevron-right" class="wide-btn-arrow"></i>
        </a>

    </div>

</div>

<?php renderAppFooter(); ?>