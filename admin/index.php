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
    <div style="
        background: var(--gradient-hero); 
        margin: -24px -16px 32px -16px; 
        padding: 32px 24px 64px 24px; 
        border-radius: 0 0 32px 32px; 
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: visible;
    ">
        <!-- Navigation Row (Right Aligned) -->
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 24px;">
            <div onclick="openSheet('sheet-perfil')" class="ripple" style="
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                background: rgba(255,255,255,0.2); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                overflow: hidden; 
                cursor: pointer;
                border: 2px solid rgba(255,255,255,0.3);
            ">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 700; font-size: 0.9rem; color: white;">
                        <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Gestão Louvor</h1>
                <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Painel de Liderança</p>
                <div style="margin-top: 12px; font-size: 0.9rem; color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; display: inline-block;">
                    Bem-vindo, <?= $_SESSION['user_name'] ?? 'Visitante' ?>!
                </div>
            </div>
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