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
        <!-- Ícones (Lucide ou Heroicons via CDN para agilidade) -->
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>

    <body>
        <!-- Overlay para Sidebar Mobile -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar (Drawer) -->
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-header-card">
                <div class="user-profile-mini">
                    <?php if ($avatar): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                    <?php else: ?>
                        <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:bold;"><?= $userInitials ?></div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600; font-size:1rem;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></div>
                        <div style="font-size:0.8rem; opacity:0.8;"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                    </div>
                    <i data-lucide="chevron-right" style="margin-left:auto; width:20px;"></i>
                </div>
            </div>

            <nav class="sidebar-menu">

                <!-- Principal -->
                <a href="index.php" class="sidebar-link">
                    <i data-lucide="layout-grid"></i> Visão geral
                </a>

                <div style="height:1px; background:var(--border-subtle); margin:10px 0;"></div>
                <div style="padding: 0 16px; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 5px; text-transform: uppercase;">Gestão</div>

                <a href="escala.php" class="sidebar-link">
                    <i data-lucide="calendar"></i> Escalas
                </a>
                <a href="repertorio.php" class="sidebar-link">
                    <i data-lucide="music"></i> Repertório
                </a>
                <a href="membros.php" class="sidebar-link">
                    <i data-lucide="users"></i> Membros
                </a>
                <a href="#" onclick="alert('Página Agenda em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="church"></i> Agenda Igreja
                </a>

                <div style="height:1px; background:var(--border-subtle); margin:10px 0;"></div>
                <div style="padding: 0 16px; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 5px; text-transform: uppercase;">Comunicação</div>

                <a href="#" onclick="alert('Página Avisos em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="newspaper"></i> Avisos
                </a>
                <a href="#" onclick="alert('Página Indisponibilidades em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="user-x"></i> Indisponibilidades
                </a>
                <a href="#" onclick="alert('Página Aniversariantes em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="cake"></i> Aniversariantes
                </a>

                <div style="height:1px; background:var(--border-subtle); margin:10px 0;"></div>
                <div style="padding: 0 16px; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 5px; text-transform: uppercase;">Espiritualidade</div>

                <!-- Agenda Movida -->
                <a href="#" onclick="alert('Página Oração em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="heart-handshake"></i> Oração
                </a>
                <a href="#" onclick="alert('Página Devocionais em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="book-open"></i> Devocionais
                </a>
                <a href="#" onclick="alert('Página Leitura em desenvolvimento')" class="sidebar-link">
                    <i data-lucide="scroll"></i> Leitura Bíblica
                </a>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <div style="height:1px; background:var(--border-subtle); margin:10px 0;"></div>
                    <a href="#" class="sidebar-link">
                        <i data-lucide="lock"></i> Admin
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="sidebar-link">
                    <i data-lucide="settings"></i> Configurações
                </a>
                <button id="theme-toggle-sidebar" class="sidebar-link" style="border:none; text-align:left; width:100%; background:transparent; cursor:pointer;">
                    <i data-lucide="moon"></i> Tema
                </button>
                <a href="../includes/auth.php?logout=true" class="sidebar-link" style="color: var(--status-error);">
                    <i data-lucide="log-out"></i> Sair
                </a>
            </div>
        </aside>

        <!-- Top Bar (Mobile Only) -->
        <header class="mobile-top-bar">
            <button class="btn-ghost" onclick="toggleSidebar()">
                <i data-lucide="menu"></i>
            </button>
            <span class="page-title"><?= htmlspecialchars($title) ?></span>
            <button class="btn-ghost">
                <i data-lucide="refresh-cw"></i>
            </button>
        </header>

        <!-- Main Content Wrapper -->
        <main style="flex:1; width:100%;">
        <?php
    }

    function renderAppFooter()
    {
        $currentInfo = pathinfo($_SERVER['PHP_SELF']);
        $page = $currentInfo['filename'];
        ?>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="#" class="nav-item <?= ($page == 'historico') ? 'active' : '' ?>">
                <i data-lucide="clock"></i>
            </a>
            <a href="escala.php" class="nav-item <?= ($page == 'escala') ? 'active' : '' ?>">
                <i data-lucide="calendar"></i>
            </a>

            <!-- Central FAB item -->
            <div class="nav-item center-fab">
                <a href="gestao_repertorio.php" class="nav-fab-btn <?= ($page == 'gestao_repertorio') ? 'active' : '' ?>">
                    <i data-lucide="music"></i>
                </a>
                <span style="font-size:0.7rem; margin-top:4px;">Repertório</span>
            </div>

            <a href="#" class="nav-item <?= ($page == 'mensagens') ? 'active' : '' ?>">
                <i data-lucide="message-square"></i>
            </a>
            <a href="membros.php" class="nav-item <?= ($page == 'membros') ? 'active' : '' ?>">
                <i data-lucide="users"></i>
            </a>
        </nav>

        <!-- Script Global -->
        <script src="../assets/js/main.js"></script>
        <script>
            // Inicializa Icons
            lucide.createIcons();

            // Binding extra para o botão da sidebar (já que o main.js busca por ID único, vamos adaptar ou adicionar aqui)
            const toggleBtn = document.getElementById('theme-toggle-sidebar');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    const isDark = document.body.classList.contains('dark-mode');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }


            function toggleSidebar() {
                const sidebar = document.getElementById('appSidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                sidebar.classList.toggle('open');
                overlay.classList.toggle('visible');
            }
        </script>
    </body>

    </html>
<?php
    }
?>