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
        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="<?= asset('../assets/css/style.css') ?>">
        <!-- Ícones -->
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>

    <body>
        <!-- Top Bar REMOVIDA -->

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

            <button class="nav-cat-item ripple" onclick="openSheet('sheet-perfil')">
                <i data-lucide="settings"></i>
                <span>Config</span>
            </button>
        </nav>

        <!-- Bottom Sheets (Submenus) -->

        <!-- Perfil / Configurações Sheet -->
        <div id="sheet-perfil" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content">
                <div class="sheet-header">Configurações</div>

                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden; border: 3px solid var(--accent-interactive);">
                        <?php if ($avatar): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: var(--bg-tertiary); display:flex; align-items:center; justify-content:center; font-size: 2rem; color: var(--text-secondary);"><?= $userInitials ?></div>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 4px;"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                </div>

                <div class="sheet-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <!-- Botão Perfil -->
                    <a href="perfil.php" class="sheet-item ripple">
                        <div class="emoji-icon">👤</div><span>Perfil</span>
                    </a>

                    <!-- Botão Modo Noturno -->
                    <div class="sheet-item ripple" id="btn-theme-toggle">
                        <div class="emoji-icon">🌙</div><span>Tema Escuro</span>
                    </div>

                    <!-- Botão Sair -->
                    <a href="../includes/auth.php?logout=true" class="sheet-item ripple" style="border-color: var(--status-error); background: rgba(239, 68, 68, 0.05);">
                        <div class="emoji-icon">🚪</div><span style="color: var(--status-error);">Sair</span>
                    </a>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 0.75rem; color: var(--text-muted); opacity: 0.7;">
                App Louvor v1.0.0
            </div>
        </div>
        </div>

        <!-- Gestão -->
        <div id="sheet-gestao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Gestão</div>
                <div class="sheet-grid">
                    <a href="gestao_escala.php" class="sheet-item ripple">
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
                    <a href="gestao_repertorio.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18V5l12-2v13"></path>
                                <circle cx="6" cy="18" r="3"></circle>
                                <circle cx="18" cy="16" r="3"></circle>
                            </svg>
                        </div>
                        <span>Repertório</span>
                    </a>
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
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <span>Agenda</span>
                    </div>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                            <div class="sheet-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <span>Admin</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Espiritualidade -->
        <div id="sheet-espiritualidade" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Espiritualidade</div>
                <div class="sheet-grid">
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <span>Oração</span>
                    </div>
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <span>Devocionais</span>
                    </div>
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <span>Leitura</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comunicação -->
        <div id="sheet-comunicacao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Comunicação</div>
                <div class="sheet-grid">
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <span>Avisos</span>
                    </div>
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </div>
                        <span>Indisponível</span>
                    </div>
                    <div class="sheet-item" style="opacity: 0.5; cursor: not-allowed;">
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
                    </div>
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
                    icon.textContent = '☀️';
                    text.textContent = 'Tema Claro';
                }

                themeBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        icon.textContent = '☀️';
                        text.textContent = 'Tema Claro';
                    } else {
                        localStorage.setItem('theme', 'light');
                        icon.textContent = '🌙';
                        text.textContent = 'Tema Escuro';
                    }
                });
            }

            // Bottom Sheets Logic
            function openSheet(id) {
                document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
                const sheet = document.getElementById(id);
                if (sheet) {
                    sheet.classList.add('active');
                    // Add small vibration if mobile
                    if (navigator.vibrate) navigator.vibrate(50);
                }
            }

            function closeSheet(element) {
                if (element.classList.contains('bottom-sheet-overlay')) {
                    element.classList.remove('active');
                }
            }
        </script>
    </body>

    </html>
<?php
    }
?>