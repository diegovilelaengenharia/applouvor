<?php
require_once '../includes/auth.php';
require_once '../includes/layout.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

renderAppHeader('Configurações');
?>

<div class="app-content">
    <div class="container">

        <!-- Cabeçalho do Perfil -->
        <div class="settings-profile-header">
            <div class="settings-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <?php endif; ?>
            </div>
            <h2 class="settings-name"><?= htmlspecialchars($user['name']) ?></h2>
            <p class="settings-role">Membro do Louvor</p>
        </div>

        <!-- Conta e Perfil -->
        <div class="settings-section">
            <h3 class="settings-section-title">Conta e Perfil</h3>
            <div class="settings-grid">
                <a href="editar_perfil.php" class="settings-item">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Meu Perfil</div>
                        <div class="settings-subtitle">Editar informações pessoais</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>

                <a href="alterar_senha.php" class="settings-item">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Senha</div>
                        <div class="settings-subtitle">Alterar senha de acesso</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Preferências do App -->
        <div class="settings-section">
            <h3 class="settings-section-title">Preferências</h3>
            <div class="settings-grid">
                <div class="settings-item" id="theme-toggle">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Tema Escuro</div>
                        <div class="settings-subtitle">Ativar modo noturno</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="dark-mode-toggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <a href="#" class="settings-item" onclick="alert('🚧 Em breve: Personalizar Acesso Rápido'); return false;">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #E6F4EA 0%, #D4E9E2 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Acesso Rápido</div>
                        <div class="settings-subtitle">Personalizar página inicial</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Ministério de Louvor -->
        <div class="settings-section">
            <h3 class="settings-section-title">Ministério de Louvor</h3>
            <div class="settings-grid">
                <a href="#" class="settings-item" onclick="alert('🚧 Em breve: Marcar Disponibilidade'); return false;">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Disponibilidade</div>
                        <div class="settings-subtitle">Marcar dias disponíveis</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Ajuda e Suporte -->
        <div class="settings-section">
            <h3 class="settings-section-title">Ajuda e Suporte</h3>
            <div class="settings-grid">
                <a href="#" class="settings-item" onclick="alert('🚧 Em breve: Central de Ajuda'); return false;">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Central de Ajuda</div>
                        <div class="settings-subtitle">Perguntas frequentes</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Sobre o App -->
        <div class="settings-section">
            <h3 class="settings-section-title">Sobre</h3>
            <div class="settings-grid">
                <div class="settings-item">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #E5E7EB 0%, #D1D5DB 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title">Versão do App</div>
                        <div class="settings-subtitle">v1.0.0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sair -->
        <div class="settings-section">
            <div class="settings-grid">
                <a href="../includes/auth.php?logout=true" class="settings-item settings-item-danger">
                    <div class="settings-icon" style="background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                    <div class="settings-content">
                        <div class="settings-title" style="color: var(--status-error);">Sair da Conta</div>
                        <div class="settings-subtitle">Encerrar sessão</div>
                    </div>
                    <svg class="settings-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--status-error);">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>

    </div>
</div>

<?php renderAppFooter(); ?>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // Dark Mode Toggle
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        darkModeToggle.checked = true;
    }

    darkModeToggle.addEventListener('change', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    });
</script>

</body>

</html>