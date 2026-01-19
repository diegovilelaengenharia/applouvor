<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Inicia o Shell do Admin

// Temporary Avatar Fix (Auto-Execute v3)
if (isset($_SESSION['user_id']) && ($_SESSION['user_name'] == 'Diego' || $_SESSION['user_id'] == 1)) {
    // Force update for current user session immediately
    if (empty($_SESSION['user_avatar'])) {
        $_SESSION['user_avatar'] = 'diego_avatar.jpg';
    }

    // Also try to persist to DB if not done
    if (!isset($_SESSION['avatar_persist_v3'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar = 'diego_avatar.jpg' WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $_SESSION['avatar_persist_v3'] = true;
        } catch (Exception $e) {
        }
    }
}

renderAppHeader('Início');
?>
<div class="container">

    <!-- Hero Section Admin -->
    <div class="hero-section fade-in-up" style="background: var(--gradient-hero); position: relative;">
        <!-- User Card Top Right -->
        <div style="position: absolute; top: 20px; right: 20px;">
            <button onclick="openSheet('sheet-perfil')" style="background: transparent; border: none; padding: 8px; cursor: pointer; -webkit-tap-highlight-color: transparent;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.8); box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.2s;">
                    <?php if (!empty($_SESSION['user_avatar'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 16px; font-weight: bold; color: var(--primary-green);"><?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?></span>
                    <?php endif; ?>
                </div>
            </button>
        </div>

        <div class="hero-greeting">
            Gestão Louvor
        </div>
        <div class="hero-subtitle">
            Painel de Liderança
        </div>
        <div class="hero-info">
            <span>Bem-vindo, <?= $_SESSION['user_name'] ?? 'Visitante' ?>!</span>
        </div>
    </div>

    <!-- Grid de Navegação (Vazio por enquanto - apenas Hero e Bottom Bar) -->
    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: -20px; padding-bottom: 80px; align-items: center; justify-content: center; min-height: 200px; color: var(--text-muted); font-size: 0.9rem;">
        <p>Selecione uma opção no menu inferior</p>
    </div>

</div>

<?php
renderAppFooter();
?>