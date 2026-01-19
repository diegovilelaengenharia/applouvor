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

    <!-- Grid de Navegação (Vazio por enquanto) -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: -20px; padding-bottom: 80px;">
        <!-- Conteúdo será implementado futuramente -->
    </div>

</div>

<?php
renderAppFooter();
?>