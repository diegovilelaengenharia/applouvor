<?php
// admin/aniversarios.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Aniversários');
?>

<!-- Hero Header -->
<div style="
    background: var(--gradient-red); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Aniversários</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<div class="container fade-in-up">
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i data-lucide="gift" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px;">Em breve</h3>
        <p style="color: var(--text-secondary);">Esta funcionalidade estará disponível nas próximas atualizações.</p>
    </div>
</div>

<?php renderAppFooter(); ?>