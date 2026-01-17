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
                    Gestão Louvor
                </div>
                <div class="hero-subtitle">
                    PIB Oliveira (MG)
                </div>
                <div class="hero-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Bem-vindo(a), <?= $_SESSION['user_name'] ?>!</span>
                </div>
            </div>

            <!-- Gestão -->
            <div class="section-header fade-in-up-delay-1">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <layout-grid></layout-grid>
                    </svg>
                    Gestão
                </h2>
            </div>

            <div class="quick-access-grid fade-in-up-delay-2">
                <a href="escala.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="card-title">Escalas</h3>
                    <p class="card-subtitle">Ver datas</p>
                </a>

                <a href="repertorio.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18V5l12-2v13"></path>
                            <circle cx="6" cy="18" r="3"></circle>
                            <circle cx="18" cy="16" r="3"></circle>
                        </svg>
                    </div>
                    <h3 class="card-title">Repertório</h3>
                    <p class="card-subtitle">Músicas</p>
                </a>

                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Membros</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

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
            </div>

            <!-- Espiritualidade -->
            <div class="section-header fade-in-up-delay-2" style="margin-top: 32px;">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                    Espiritualidade
                </h2>
            </div>

            <div class="quick-access-grid fade-in-up-delay-3">
                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Oração</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Devocionais</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Leitura</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>
            </div>

            <!-- Comunicação -->
            <div class="section-header fade-in-up-delay-3" style="margin-top: 32px;">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Comunicação
                </h2>
            </div>

            <div class="quick-access-grid fade-in-up-delay-3">
                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <h3 class="card-title">Avisos</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                    </div>
                    <h3 class="card-title">Indisponível</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>

                <div class="quick-access-card" style="opacity: 0.5; cursor: not-allowed;">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <path d="M8 14h.01"></path>
                            <path d="M12 14h.01"></path>
                            <path d="M16 14h.01"></path>
                            <path d="M8 18h.01"></path>
                            <path d="M12 18h.01"></path>
                            <path d="M16 18h.01"></path>
                        </svg>
                    </div>
                    <h3 class="card-title">Aniversários</h3>
                    <p class="card-subtitle">Em breve</p>
                </div>
            </div>

            <!-- Configurações -->
            <div class="section-header" style="margin-top: 32px;">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                    Configurações
                </h2>
            </div>

            <div class="quick-access-grid">
                <a href="perfil.php" class="quick-access-card">
                    <div class="card-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3 class="card-title">Meu Perfil</h3>
                    <p class="card-subtitle">Editar dados</p>
                </a>
            </div>

        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <!-- Modal de Boas-vindas -->
    <div id="welcomeModal" class="welcome-modal">
        <div class="welcome-modal-content">
            <div class="welcome-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18V5l12-2v13"></path>
                    <circle cx="6" cy="18" r="3"></circle>
                    <circle cx="18" cy="16" r="3"></circle>
                </svg>
            </div>
            <h2 class="welcome-title">Bem-vindo(a), <?= $_SESSION['user_name'] ?>!</h2>
            <p class="welcome-message">
                Que alegria ter você conosco! Sua dedicação em servir ao Reino de Deus através do louvor é uma bênção para nossa igreja.
                Que o Senhor continue capacitando você com seus dons e talentos para glorificar Seu nome.
            </p>
            <p class="welcome-verse">"Cantai ao Senhor um cântico novo, porque ele tem feito maravilhas" - Salmos 98:1</p>
            <button class="btn-gradient-primary" onclick="closeWelcomeModal()">Começar</button>
        </div>
    </div>

    <style>
        .welcome-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .welcome-modal.active {
            display: flex;
        }

        .welcome-modal-content {
            background: var(--bg-secondary);
            border-radius: 24px;
            padding: 40px 32px;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .welcome-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-glow 2s infinite;
        }

        .welcome-icon svg {
            color: white;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        .welcome-message {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .welcome-verse {
            font-size: 0.9rem;
            font-style: italic;
            color: var(--primary-green);
            margin-bottom: 28px;
            padding: 16px;
            background: var(--gradient-soft);
            border-radius: 12px;
            border-left: 4px solid var(--primary-green);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Mostrar modal de boas-vindas apenas uma vez por sessão
        function showWelcomeModal() {
            const hasSeenWelcome = sessionStorage.getItem('hasSeenWelcome');
            if (!hasSeenWelcome) {
                setTimeout(() => {
                    document.getElementById('welcomeModal').classList.add('active');
                }, 500);
            }
        }

        function closeWelcomeModal() {
            document.getElementById('welcomeModal').classList.remove('active');
            sessionStorage.setItem('hasSeenWelcome', 'true');
        }

        // Exibir modal ao carregar a página
        window.addEventListener('load', showWelcomeModal);
    </script>

</body>

</html>