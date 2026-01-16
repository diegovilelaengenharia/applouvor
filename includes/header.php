<header class="app-header">
    <div class="container header-content">
        <div class="flex items-center gap-2">
            <img src="../assets/images/logo-white.png" alt="Logo" class="header-logo">
            <div style="line-height: 1.2;">
                <h3 style="font-size: 1rem; margin: 0;">Louvor PIB</h3>
                <span style="font-size: 0.7rem; color: var(--text-secondary);">Área <?= $_SESSION['user_role'] === 'admin' ? 'do Líder' : 'do Membro' ?></span>
            </div>
        </div>

        <div class="user-info">
            <button id="theme-toggle" class="btn-outline" style="border:none; font-size: 1.2rem; cursor: pointer; padding: 5px;">🌙</button>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <a href="../includes/auth.php?logout=true" class="logout-link">Sair</a>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</header>