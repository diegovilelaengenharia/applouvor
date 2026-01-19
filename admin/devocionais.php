<?php
// admin/devocionais.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Devocionais');
?>

<!-- Hero Header -->
<div style="
    background: var(--gradient-blue); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="index.php" class="ripple" style="
            width: 40px; 
            height: 40px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            background: rgba(255,255,255,0.2); 
            text-decoration: none;
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="arrow-left" style="width: 20px;"></i>
        </a>

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
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Devocionais</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<div class="container fade-in-up">
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i data-lucide="book-open" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px;">Em breve</h3>
        <p style="color: var(--text-secondary);">Esta funcionalidade estará disponível nas próximas atualizações.</p>
    </div>
</div>

<?php renderAppFooter(); ?>