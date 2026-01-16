<?php
// includes/layout.php
// Este arquivo gerencia a estrutura principal (Shell) do App

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
        <link rel="stylesheet" href="../assets/css/style.css">
        <!-- Ícones -->
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>

    <body>
        <!-- Top Bar (Desktop & Mobile) -->
        <header class="app-header">
            <div class="header-brand">
                <img src="../assets/images/logo-black.png" alt="Logo" style="height: 32px; width: auto; display: none;"> <!-- Oculto se não tiver logo -->
                <span class="page-title"><?= htmlspecialchars($title) ?></span>
            </div>

            <div class="header-actions">
                <button id="theme-toggle" class="btn-icon" title="Alternar Tema">
                    <i data-lucide="moon"></i>
                </button>

                <div class="user-dropdown">
                    <div class="user-avatar-sm">
                        <?php if ($avatar): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?= $userInitials ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                            <span><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></span>
                        </div>
                        <a href="#" class="dropdown-item"><i data-lucide="settings"></i> Configurações</a>
                        <a href="../includes/auth.php?logout=true" class="dropdown-item text-danger"><i data-lucide="log-out"></i> Sair</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Wrapper -->
        <main class="app-content">
        <?php
    }

    function renderAppFooter()
    {
        ?>
        </main>

        <!-- Bottom Navigation (3 Categories) -->
        <nav class="bottom-nav-categories">
            <button class="nav-cat-item" onclick="openSheet('sheet-gestao')">
                <i data-lucide="layout-grid"></i>
                <span>Gestão</span>
            </button>
            <button class="nav-cat-item" onclick="openSheet('sheet-espiritualidade')">
                <i data-lucide="heart-handshake"></i>
                <span>Espiritualidade</span>
            </button>
            <button class="nav-cat-item" onclick="openSheet('sheet-comunicacao')">
                <i data-lucide="message-circle"></i>
                <span>Comunicação</span>
            </button>
        </nav>

        <!-- Bottom Sheets (Submenus) -->

        <!-- Gestão -->
        <div id="sheet-gestao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content">
                <div class="sheet-header">Gestão</div>
                <div class="sheet-grid">
                    <a href="escala.php" class="sheet-item">
                        <div class="emoji-icon">📅</div><span>Escalas</span>
                    </a>
                    <a href="repertorio.php" class="sheet-item">
                        <div class="emoji-icon">🎼</div><span>Repertório</span>
                    </a>
                    <a href="membros.php" class="sheet-item">
                        <div class="emoji-icon">👥</div><span>Membros</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">⛪</div><span>Agenda</span>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="#" class="sheet-item">
                            <div class="emoji-icon">🔒</div><span>Admin</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Espiritualidade -->
        <div id="sheet-espiritualidade" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content">
                <div class="sheet-header">Espiritualidade</div>
                <div class="sheet-grid">
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">🙏</div><span>Oração</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">📖</div><span>Devocionais</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">📜</div><span>Leitura</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Comunicação -->
        <div id="sheet-comunicacao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content">
                <div class="sheet-header">Comunicação</div>
                <div class="sheet-grid">
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">📣</div><span>Avisos</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">🚫</div><span>Indisponível</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('Em breve')">
                        <div class="emoji-icon">🎂</div><span>Aniversários</span>
                    </a>
                </div>
            </div>
        </div>


        <!-- Script Global -->
        <script src="../assets/js/main.js"></script>
        <script>
            lucide.createIcons();

            // Toggle Dropdown Profile
            const userAvatar = document.querySelector('.user-avatar-sm');
            const dropdown = document.querySelector('.dropdown-menu');
            if (userAvatar && dropdown) {
                userAvatar.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                });
                document.addEventListener('click', () => {
                    dropdown.classList.remove('active');
                });
            }

            // Theme Toggle Logic
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) {
                // Load saved theme
                if (localStorage.getItem('theme') === 'dark') {
                    document.body.classList.add('dark-mode');
                }

                themeBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        themeBtn.innerHTML = '<i data-lucide="sun"></i>';
                    } else {
                        localStorage.setItem('theme', 'light');
                        themeBtn.innerHTML = '<i data-lucide="moon"></i>';
                    }
                    lucide.createIcons();
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