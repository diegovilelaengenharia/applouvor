<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Inicia o Shell
renderAppHeader('Início');
?>
<div class="container" style="padding-top: 20px;">

    <!-- Header Title -->
    <div style="margin-bottom: 30px; text-align: center;">
        <h1 style="font-size: 1.6rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 4px; color: var(--accent-interactive);">App Louvor v1.2</h1>
        <p style="font-size: 1rem; color: var(--text-secondary); font-weight: 500;">PIB Oliveira</p>
    </div>

    <!-- Conteúdo Central -->
    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
        <p style="opacity: 0.7;">Selecione uma opção no menu abaixo para começar.</p>
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

    .icon-emoji {
        font-size: 3.5rem;
        /* Ícones Grandes */
        margin-bottom: 10px;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    .card-btn:hover .icon-emoji {
        transform: scale(1.2) rotate(5deg);
    }

    .card-btn span {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 1rem;
        letter-spacing: -0.01em;
    }

    /* Dark Mode Adjustments */
    body.dark-mode .card-btn {
        background: #1E1E1E;
        border-color: #333;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
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