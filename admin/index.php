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
    <!-- Grid de Navegação -->
    <div class="dashboard-grid">

        <!-- Escalas -->
        <a href="escala.php" class="card-btn">
            <div class="icon-wrapper bg-blue">
                <i data-lucide="calendar" class="icon-lg"></i>
            </div>
            <span>Escalas</span>
        </a>

        <!-- Repertório (Geral) -->
        <a href="repertorio.php" class="card-btn">
            <div class="icon-wrapper bg-purple">
                <i data-lucide="music" class="icon-lg"></i>
            </div>
            <span>Repertório Geral</span>
        </a>

        <!-- Repertório da Semana -->
        <a href="escala.php" class="card-btn">
            <div class="icon-wrapper bg-pink">
                <i data-lucide="list-music" class="icon-lg"></i>
            </div>
            <span>Repertório da Semana</span>
        </a>

        <!-- Membros -->
        <a href="membros.php" class="card-btn">
            <div class="icon-wrapper bg-green">
                <i data-lucide="users" class="icon-lg"></i>
            </div>
            <span>Membros</span>
        </a>

        <!-- Agenda Igreja -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-orange">
                <i data-lucide="church" class="icon-lg"></i>
            </div>
            <span>Agenda Igreja</span>
        </a>

        <!-- Oração -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-red">
                <i data-lucide="heart-handshake" class="icon-lg"></i>
            </div>
            <span>Oração</span>
        </a>

        <!-- Devocionais -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-indigo">
                <i data-lucide="book-open" class="icon-lg"></i>
            </div>
            <span>Devocionais</span>
        </a>

        <!-- Leitura Bíblica -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-teal">
                <i data-lucide="scroll" class="icon-lg"></i>
            </div>
            <span>Leitura Bíblica</span>
        </a>

        <!-- Comunicação: Avisos -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-slate">
                <i data-lucide="newspaper" class="icon-lg"></i>
            </div>
            <span>Avisos</span>
        </a>

        <!-- Comunicação: Indisponibilidades -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-red">
                <i data-lucide="user-x" class="icon-lg"></i>
            </div>
            <span>Indisponibilidades</span>
        </a>

        <!-- Comunicação: Aniversariantes -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-rose">
                <i data-lucide="cake" class="icon-lg"></i>
            </div>
            <span>Aniversariantes</span>
        </a>

        <!-- Configurações -->
        <a href="#" onclick="alert('Em breve')" class="card-btn">
            <div class="icon-wrapper bg-gray">
                <i data-lucide="settings" class="icon-lg"></i>
            </div>
            <span>Configurações</span>
        </a>

    </div>

</div>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 25px;
        max-width: 900px;
        margin: 0 auto;
    }

    .card-btn {
        background-color: var(--bg-secondary);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 24px;
        padding: 25px 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        height: 100%;
        min-height: 180px;
        position: relative;
        overflow: hidden;
    }

    .card-btn:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 5px;
        transition: transform 0.3s ease;
    }

    .card-btn:hover .icon-wrapper {
        transform: scale(1.1);
    }

    .icon-lg {
        width: 32px;
        height: 32px;
        stroke-width: 2px;
    }

    .card-btn span {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 1rem;
        letter-spacing: -0.01em;
    }

    /* Colors */
    .bg-blue {
        background: #EFF6FF;
        color: #3B82F6;
    }

    .bg-purple {
        background: #F3E8FF;
        color: #9333EA;
    }

    .bg-pink {
        background: #FCE7F3;
        color: #EC4899;
    }

    .bg-green {
        background: #ECFDF5;
        color: #10B981;
    }

    .bg-orange {
        background: #FFF7ED;
        color: #F97316;
    }

    .bg-red {
        background: #FEF2F2;
        color: #EF4444;
    }

    .bg-indigo {
        background: #EEF2FF;
        color: #6366F1;
    }

    .bg-teal {
        background: #F0FDFA;
        color: #14B8A6;
    }

    .bg-slate {
        background: #F8FAFC;
        color: #64748B;
    }

    .bg-rose {
        background: #FFF1F2;
        color: #F43F5E;
    }

    .bg-gray {
        background: #F3F4F6;
        color: #4B5563;
    }

    /* Dark Mode Adjustments */
    body.dark-mode .card-btn {
        background: #1E1E1E;
        border-color: #333;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    body.dark-mode .icon-wrapper {
        background: rgba(255, 255, 255, 0.05);
        /* Unified dark background for icons */
    }
</style>

<script>
    // Forçar recarregamento dos ícones caso falhe
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

<?php
renderAppFooter();
?>