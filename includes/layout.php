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
        <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
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
            <a href="index.php" class="nav-cat-item">
                <i data-lucide="home"></i>
                <span>Início</span>
            </a>

            <button class="nav-cat-item" onclick="openSheet('sheet-gestao')">
                <i data-lucide="layout-grid"></i>
                <span>Gestão</span>
            </button>
            <button class="nav-cat-item" onclick="alert('🚧 Módulo em Manutenção')">
                <i data-lucide="heart-handshake" style="opacity: 0.5;"></i>
                <span style="opacity: 0.5;">Espirito</span>
            </button>
            <button class="nav-cat-item" onclick="alert('🚧 Módulo em Manutenção')">
                <i data-lucide="message-circle" style="opacity: 0.5;"></i>
                <span style="opacity: 0.5;">Comunica</span>
            </button>

            <button class="nav-cat-item" onclick="openSheet('sheet-perfil')">
                <div class="user-avatar-xs" style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden; border: 1px solid currentColor;">
                    <?php if ($avatar): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: currentColor; display:flex; align-items:center; justify-content:center; color: var(--bg-secondary); font-size: 10px; font-weight: bold;"><?= $userInitials ?></div>
                    <?php endif; ?>
                </div>
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
                    <a href="perfil.php" class="sheet-item">
                        <div class="emoji-icon">👤</div><span>Perfil</span>
                    </a>

                    <!-- Botão Modo Noturno -->
                    <div class="sheet-item" id="btn-theme-toggle">
                        <div class="emoji-icon">🌙</div><span>Tema Escuro</span>
                    </div>

                    <!-- Botão Sair -->
                    <a href="../includes/auth.php?logout=true" class="sheet-item" style="border-color: var(--status-error); background: rgba(239, 68, 68, 0.05);">
                        <div class="emoji-icon">🚪</div><span style="color: var(--status-error);">Sair</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Gestão -->
        <div id="sheet-gestao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content">
                <div class="sheet-header">Gestão</div>
                <div class="sheet-grid">
                    <a href="escala.php" class="sheet-item">
                        <div class="emoji-icon">📅</div><span>Escalas</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('🚧 Módulo em Manutenção')">
                        <div class="emoji-icon" style="filter: grayscale(1);">🎼</div><span style="color: var(--text-secondary);">Repertório</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('🚧 Módulo em Manutenção')">
                        <div class="emoji-icon" style="filter: grayscale(1);">👥</div><span style="color: var(--text-secondary);">Membros</span>
                    </a>
                    <a href="#" class="sheet-item" onclick="alert('🚧 Módulo em Manutenção')">
                        <div class="emoji-icon" style="filter: grayscale(1);">⛪</div><span style="color: var(--text-secondary);">Agenda</span>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="#" class="sheet-item" onclick="alert('🚧 Módulo em Manutenção')">
                            <div class="emoji-icon" style="filter: grayscale(1);">🔒</div><span style="color: var(--text-secondary);">Admin</span>
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