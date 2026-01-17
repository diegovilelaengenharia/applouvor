<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Inicia o Shell
renderAppHeader('Início');
?>
<div class="app-content">
    <div class="container">

        <!-- Hero Section Admin -->
        <div class="hero-section fade-in-up" style="background: var(--gradient-hero);">
            <div class="hero-greeting">
                Painel Administrativo 🎯
            </div>
            <div class="hero-subtitle">
                Gerencie o Ministério de Louvor com excelência
            </div>
            <div class="hero-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Líder: <?= $_SESSION['user_name'] ?></span>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="section-header fade-in-up-delay-1">
            <h2 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Gestão
            </h2>
        </div>

        <div class="quick-access-grid fade-in-up-delay-2">
            <!-- Escalas -->
            <a href="gestao_escala.php" class="quick-access-card">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3 class="card-title">Escalas</h3>
                <p class="card-subtitle">Gerenciar escalas</p>
            </a>

            <!-- Repertório -->
            <a href="gestao_repertorio.php" class="quick-access-card">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18V5l12-2v13"></path>
                        <circle cx="6" cy="18" r="3"></circle>
                        <circle cx="18" cy="16" r="3"></circle>
                    </svg>
                </div>
                <h3 class="card-title">Repertório</h3>
                <p class="card-subtitle">Músicas e cifras</p>
            </a>

            <!-- Membros -->
            <a href="membros.php" class="quick-access-card">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 class="card-title">Membros</h3>
                <p class="card-subtitle">Equipe do louvor</p>
            </a>

            <!-- Agenda (Em breve) -->
            <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h3 class="card-title">Agenda</h3>
                <p class="card-subtitle">Em breve</p>
            </div>

            <!-- Relatórios (Em breve) -->
            <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <h3 class="card-title">Relatórios</h3>
                <p class="card-subtitle">Em breve</p>
            </div>

            <!-- Configurações -->
            <a href="perfil.php" class="quick-access-card">
                <div class="card-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m6-12h-6m-6 0H1m11 6H1m11 6H1"></path>
                    </svg>
                </div>
                <h3 class="card-title">Configurações</h3>
                <p class="card-subtitle">Ajustes gerais</p>
            </a>
        </div>

        <!-- Próximas Ações -->
        <div class="section-header fade-in-up-delay-3" style="margin-top: 40px;">
            <h2 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Próximas Escalas
            </h2>
            <a href="gestao_escala.php" class="section-action">Ver todas →</a>
        </div>

        <div class="fade-in-up-delay-3">
            <div class="card-clean" style="text-align: center; padding: 40px 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px; color: var(--text-muted);">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <p style="color: var(--text-secondary); margin-bottom: 8px;">Nenhuma escala programada</p>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Crie uma nova escala para começar</p>
                <a href="gestao_escala.php" class="btn-gradient-primary" style="margin-top: 20px; text-decoration: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nova Escala
                </a>
            </div>
        </div>

    </div>
</div>

<script>
    // Forçar recarregamento dos ícones caso falhe
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

<?php
renderAppFooter();
?>