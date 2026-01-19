<?php
// includes/layout.php
// Este arquivo gerencia a estrutura principal (Shell) do App

// Desabilitar cache
require_once __DIR__ . '/no-cache.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderAppHeader($title = 'Louvor PIB')
{
    $avatar = $_SESSION['user_avatar'] ?? null;
    $userInitials = substr($_SESSION['user_name'] ?? 'U', 0, 1);
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

        <!-- PWA Fullscreen & Mobile -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Louvor PIB">

        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="<?= asset('../assets/css/style.css') ?>">
        <!-- PWA Support -->
        <meta name="theme-color" content="#2D7A4F">
        <link rel="manifest" href="../manifest.json">
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('../sw.js');
                });
            }
        </script>

        <!-- Ícones -->
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>

    <body>
        <!-- Header removido conforme solicitado -->

        <!-- Main Content Wrapper -->
        <main class="app-content">
        <?php
    }

    function renderAppFooter()
    {
        $avatar = $_SESSION['user_avatar'] ?? null;
        $userInitials = substr($_SESSION['user_name'] ?? 'U', 0, 1);
        ?>
        </main>

        <!-- Bottom Navigation (5 Categories) -->
        <nav class="bottom-nav-categories">
            <a href="index.php" class="nav-cat-item ripple" id="nav-home">
                <i data-lucide="home"></i>
                <span>Início</span>
            </a>

            <button class="nav-cat-item ripple" onclick="openSheet('sheet-gestao')">
                <i data-lucide="layout-grid"></i>
                <span>Gestão</span>
            </button>
            <button class="nav-cat-item ripple" onclick="openSheet('sheet-espiritualidade')">
                <i data-lucide="heart-handshake"></i>
                <span>Espírito</span>
            </button>
            <button class="nav-cat-item ripple" onclick="openSheet('sheet-comunicacao')">
                <i data-lucide="message-circle"></i>
                <span>Comunica</span>
            </button>


        </nav>

        <!-- Bottom Sheets (Submenus) -->

        <!-- 1. Perfil / Configurações Dropdown -->
        <div id="sheet-perfil" class="profile-dropdown-overlay" onclick="closeSheet(this)">
            <div class="profile-dropdown-content" onclick="event.stopPropagation()">
                <!-- Styles for this dropdown specifically -->
                <style>
                    .profile-dropdown-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 2000;
                        display: none;
                    }

                    .profile-dropdown-overlay.active {
                        display: block;
                    }

                    .profile-dropdown-content {
                        position: absolute;
                        top: 70px;
                        right: 20px;
                        width: 260px;
                        background: var(--bg-secondary);
                        border: 1px solid var(--border-subtle);
                        border-radius: 16px;
                        padding: 16px;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                        transform-origin: top right;
                        animation: scaleIn 0.2s ease;
                    }

                    @keyframes scaleIn {
                        from {
                            opacity: 0;
                            transform: scale(0.95);
                        }

                        to {
                            opacity: 1;
                            transform: scale(1);
                        }
                    }
                </style>

                <!-- Profile Header Compact -->
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-subtle);">
                    <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; background: var(--bg-tertiary); border: 2px solid var(--border-subtle); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($_SESSION['user_avatar'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 1.2rem; font-weight: 800; color: var(--text-secondary);"><?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="overflow: hidden;">
                        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Visitante') ?></h3>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0; text-transform: capitalize;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Membro') ?></p>
                    </div>
                </div>

                <!-- Menu Options Compact -->
                <div style="display: flex; flex-direction: column; gap: 6px;">

                    <!-- Meus Dados -->
                    <a href="perfil.php" class="ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; background: transparent; color: var(--text-primary); text-decoration: none; transition: background 0.1s;">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="user-pen" style="width: 16px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.9rem;">Meus Dados</span>
                    </a>

                    <!-- Trocar Foto -->
                    <a href="perfil.php" class="ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; background: transparent; color: var(--text-primary); text-decoration: none; transition: background 0.1s;">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="camera" style="width: 16px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.9rem;">Trocar Foto</span>
                    </a>

                    <!-- Tema Escuro -->
                    <div id="btn-theme-toggle" class="ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; background: transparent; color: var(--text-primary); cursor: pointer; transition: background 0.1s;">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="moon" style="width: 16px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.9rem;">Aparência</span>
                        <div style="font-size: 0.7rem; color: var(--text-muted); background: var(--bg-tertiary); padding: 2px 8px; border-radius: 10px;">Mudar</div>
                    </div>

                    <div style="height: 1px; background: var(--border-subtle); margin: 6px 0;"></div>

                    <!-- Sair -->
                    <a href="../includes/auth.php?logout=true" class="ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; color: var(--status-error); text-decoration: none; font-weight: 600; transition: background 0.1s;">
                        <i data-lucide="log-out" style="width: 16px;"></i>
                        <span style="font-size: 0.9rem;">Sair</span>
                    </a>

                </div>
            </div>
        </div>

        <!-- 2. Gestão Sheet -->
        <div id="sheet-gestao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Gestão</div>
                <div class="sheet-grid">
                    <a href="lider.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <span>Líder</span>
                    </a>

                    <a href="escala.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span>Escalas</span>
                    </a>
                    <a href="repertorio.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18V5l12-2v13"></path>
                                <circle cx="6" cy="18" r="3"></circle>
                                <circle cx="18" cy="16" r="3"></circle>
                            </svg>
                        </div>
                        <span>Repertório</span>
                    </a>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="membros.php" class="sheet-item ripple">
                            <div class="sheet-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <span>Membros</span>
                        </a>
                        <a href="agenda.php" class="sheet-item ripple">
                            <div class="sheet-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <span>Agenda</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Espiritualidade Sheet -->
        <div id="sheet-espiritualidade" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Espiritualidade</div>
                <div class="sheet-grid">
                    <a href="oracao.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <span>Oração</span>
                    </a>
                    <a href="devocionais.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <span>Devocionais</span>
                    </a>
                    <a href="leitura.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <span>Leitura</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Comunicação Sheet -->
        <div id="sheet-comunicacao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Comunicação</div>
                <div class="sheet-grid">
                    <a href="avisos.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <span>Avisos</span>
                    </a>
                    <a href="indisponibilidade.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </div>
                        <span>Indisponível</span>
                    </a>
                    <a href="aniversarios.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                        <span>Aniversários</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Script Global -->
        <script src="../assets/js/main.js"></script>
        <script>
            lucide.createIcons();

            // Theme Toggle Logic
            const themeBtn = document.getElementById('btn-theme-toggle');
            if (themeBtn) {
                const icon = themeBtn.querySelector('.emoji-icon');
                const text = themeBtn.querySelector('span');

                // Load saved theme
                if (localStorage.getItem('theme') === 'dark') {
                    document.body.classList.add('dark-mode');
                    if (icon) icon.textContent = '☀️';
                    if (text) text.textContent = 'Tema Claro';
                }

                themeBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        if (icon) icon.textContent = '☀️';
                        if (text) text.textContent = 'Tema Claro';
                    } else {
                        localStorage.setItem('theme', 'light');
                        if (icon) icon.textContent = '🌙';
                        if (text) text.textContent = 'Tema Escuro';
                    }
                });
            }

            // Bottom Sheets Logic
            function openSheet(id) {
                // Fechar todos
                document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
                document.querySelectorAll('.profile-dropdown-overlay').forEach(el => el.classList.remove('active'));

                // Abrir o solicitado
                const sheet = document.getElementById(id);
                if (sheet) {
                    sheet.classList.add('active');
                    // Add small vibration if mobile
                    if (navigator.vibrate) navigator.vibrate(50);
                } else {
                    console.error('Sheet not found:', id);
                }
            }

            function closeSheet(element) {
                if (element.classList.contains('bottom-sheet-overlay') || element.classList.contains('profile-dropdown-overlay')) {
                    element.classList.remove('active');
                }
            }
        </script>
    </body>

    </html>
<?php
    }
?>