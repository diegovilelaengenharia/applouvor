<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Inicia o Shell
renderAppHeader('Visão Geral');
?>
<div class="container" style="padding-top: 40px; padding-bottom: 80px;">

    <div style="text-align: center; margin-bottom: 40px;">
        <div style="display:inline-block; height: 40px; border-left: 4px solid var(--status-warning); padding-left: 15px; margin-bottom: 10px;">
            <h1 style="font-size: 2rem; margin: 0; line-height: 1;">Painel do Líder</h1>
        </div>
        <p style="color: var(--text-secondary);">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?>. O que vamos gerenciar hoje?</p>
    </div>

    <!-- Grid de Navegação -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; max-width: 800px; margin: 0 auto;">

        <!-- Escalas -->
        <a href="escala.php" class="card-btn">
            <i data-lucide="calendar" class="icon-lg" style="color: #3B82F6;"></i>
            <span>Escalas</span>
        </a>

        <!-- Repertório (Geral) -->
        <a href="repertorio.php" class="card-btn">
            <i data-lucide="music" class="icon-lg" style="color: #8B5CF6;"></i>
            <span>Repertório Geral</span>
        </a>

        <!-- Repertório da Semana (Atalho para próxima escala se houver) -->
        <a href="escala.php" class="card-btn">
            <i data-lucide="list-music" class="icon-lg" style="color: #EC4899;"></i>
            <span>Repertório da Semana</span>
        </a>

        <!-- Membros -->
        <a href="membros.php" class="card-btn">
            <i data-lucide="users" class="icon-lg" style="color: #10B981;"></i>
            <span>Membros</span>
        </a>

        <!-- Agenda Igreja -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <i data-lucide="church" class="icon-lg" style="color: #F59E0B;"></i>
            <span>Agenda Igreja</span>
        </a>

        <!-- Oração -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <i data-lucide="heart-handshake" class="icon-lg" style="color: #EF4444;"></i>
            <span>Oração</span>
        </a>

        <!-- Devocionais -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <i data-lucide="book-open" class="icon-lg" style="color: #6366F1;"></i>
            <span>Devocionais</span>
        </a>

        <!-- Leitura Bíblica -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <i data-lucide="scroll" class="icon-lg" style="color: #14B8A6;"></i>
            <span>Leitura Bíblica</span>
        </a>

    </div>

</div>

<style>
    .card-btn {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
        text-align: center;
        transition: all 0.2s ease;
        box-shadow: var(--shadow-sm);
        height: 100%;
        min-height: 160px;
    }

    .card-btn:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--accent-blue);
    }

    .icon-lg {
        width: 40px;
        height: 40px;
        margin-bottom: 5px;
    }

    .card-btn span {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.95rem;
    }
</style>

<?php
renderAppFooter();
?>