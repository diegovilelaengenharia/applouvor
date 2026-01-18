<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

renderAppHeader('Minhas Escalas');
?>

<div class="app-content">
    <div class="container">
        <div class="hero-section fade-in-up">
            <div class="hero-greeting">Minhas Escalas</div>
            <div class="hero-info">
                <span>Construir do zero... 🛠️</span>
            </div>
        </div>

        <!-- Área para vibe coding -->
        <div class="card-clean fade-in-up-delay-1">
            <p style="text-align: center; color: var(--text-secondary);">
                Página limpa para configuração.
            </p>
        </div>
    </div>
</div>

<?php
renderAppFooter();
?>