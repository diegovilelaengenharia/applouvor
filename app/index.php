<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Louvor PIB - Área do Músico</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo-white.png">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="app-content">
        <div class="container">

            <!-- Hero Section -->
            <div class="hero-section fade-in-up">
                <div class="hero-greeting">
                    Olá, <?= $_SESSION['user_name'] ?>! 👋
                </div>
                <div class="hero-subtitle">
                    Prepare-se para servir com excelência no louvor
                </div>
                <div class="hero-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Próxima escala: Em breve</span>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="section-header fade-in-up-delay-1">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Acesso Rápido
                </h2>
            </div>

            <div class="quick-access-grid fade-in-up-delay-2">
                <!-- Minhas Escalas -->
                <a href="escala.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="card-title">Minhas Escalas</h3>
                    <p class="card-subtitle">Ver datas e confirmar</p>
                </a>

                <!-- Repertório -->
                <a href="repertorio.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18V5l12-2v13"></path>
                            <circle cx="6" cy="18" r="3"></circle>
                            <circle cx="18" cy="16" r="3"></circle>
                        </svg>
                    </div>
                    <h3 class="card-title">Repertório</h3>
                    <p class="card-subtitle">Músicas da semana</p>
                </a>

                <!-- Perfil -->
                <a href="perfil.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3 class="card-title">Meu Perfil</h3>
                    <p class="card-subtitle">Configurações</p>
                </a>

                <!-- Repertório Geral (Em breve) -->
                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Repertório Geral</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <!-- Agenda Igreja (Em breve) -->
                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h3 class="card-title">Agenda Igreja</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <!-- Sugestões -->
                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h3 class="card-title">Sugestões</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>
            </div>

            <!-- Próximas Escalas -->
            <div class="section-header fade-in-up-delay-3" style="margin-top: 40px;">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Próximas Escalas
                </h2>
                <a href="escala.php" class="section-action">Ver todas →</a>
            </div>

            <div class="fade-in-up-delay-3">
                <div class="card-clean" style="text-align: center; padding: 40px 20px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px; color: var(--text-muted);">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p style="color: var(--text-secondary); margin-bottom: 8px;">Nenhuma escala confirmada</p>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Aguarde a próxima programação do líder</p>
                </div>
            </div>

        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>

</html>