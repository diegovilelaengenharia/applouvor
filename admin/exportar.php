<?php
// admin/exportar.php
// Página de opções de exportação (Painel Admin)

require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Admin');
?>

<div class="container fade-in-up">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <a href="index.php" class="btn-icon ripple">
            <i data-lucide="arrow-left"></i>
        </a>
        <h1 style="font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin: 0;">Exportar Dados</h1>
    </div>

    <p style="color: var(--text-secondary); margin-bottom: 24px;">
        Escolha quais dados deseja exportar para Excel.
    </p>

    <div style="display: grid; gap: 16px;">

        <!-- Exportar Tudo -->
        <a href="exportar_completo.php?tipo=tudo" class="dash-card ripple" style="text-decoration: none; border-left: 4px solid var(--accent-interactive);">
            <div class="dash-icon-bg" style="background: rgba(45, 122, 79, 0.1); color: var(--accent-interactive);">
                <i data-lucide="database" style="width: 24px;"></i>
            </div>
            <div class="dash-title">Exportar Tudo</div>
            <div class="dash-desc">Membros + Repertório + Escalas</div>
        </a>

        <!-- Exportar Membros -->
        <a href="exportar_completo.php?tipo=membros" class="dash-card ripple" style="text-decoration: none;">
            <div class="dash-icon-bg" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                <i data-lucide="users" style="width: 24px;"></i>
            </div>
            <div class="dash-title">Apenas Membros</div>
            <div class="dash-desc">Lista de contatos e funções</div>
        </a>

        <!-- Exportar Repertório -->
        <a href="exportar_completo.php?tipo=repertorio" class="dash-card ripple" style="text-decoration: none;">
            <div class="dash-icon-bg" style="background: rgba(147, 51, 234, 0.1); color: #9333ea;">
                <i data-lucide="music" style="width: 24px;"></i>
            </div>
            <div class="dash-title">Apenas Repertório</div>
            <div class="dash-desc">Músicas, tons e links</div>
        </a>

        <!-- Exportar Escalas -->
        <a href="exportar_completo.php?tipo=escalas" class="dash-card ripple" style="text-decoration: none;">
            <div class="dash-icon-bg" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">
                <i data-lucide="calendar" style="width: 24px;"></i>
            </div>
            <div class="dash-title">Apenas Escalas</div>
            <div class="dash-desc">Histórico e próximas escalas</div>
        </a>

    </div>
</div>

<?php renderAppFooter(); ?>