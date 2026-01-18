<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Inicia o Shell do Admin
renderAppHeader('Início');
?>
<div class="container">

    <!-- Hero Section Admin -->
    <div class="hero-section fade-in-up" style="background: var(--gradient-hero);">
        <div class="hero-greeting">
            Gestão Louvor
        </div>
        <div class="hero-subtitle">
            Painel Administrativo
        </div>
        <div class="hero-info">
            <span>Bem-vindo, <?= $_SESSION['user_name'] ?>!</span>
        </div>
    </div>

    <!-- Aviso de Em Desenvolvimento -->
    <div class="card-clean fade-in-up-delay-2" style="text-align: center; padding: 60px 20px; margin-top: 20px;">
        <div style="
                width: 80px; 
                height: 80px; 
                background: var(--bg-secondary); 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                margin: 0 auto 24px;
                color: var(--primary-color);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
        </div>

        <h2 style="font-size: 1.5rem; margin-bottom: 12px; color: var(--text-primary);">Em Desenvolvimento</h2>

        <p style="color: var(--text-secondary); line-height: 1.6; max-width: 400px; margin: 0 auto;">
            Use o menu inferior para navegar. <br>
            Novidades em breve! 🚀
        </p>
    </div>

</div>

<?php
renderAppFooter();
?>