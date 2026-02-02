<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

renderAppHeader('Quem Somos');
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, var(--primary) 0%, #065f46 100%);
        color: white;
        padding: 48px 24px;
        text-align: center;
        margin: -24px -24px 32px -24px;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    
    .hero-title {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        text-transform: uppercase;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 12px;
        opacity: 0.95;
    }
    
    .hero-tagline {
        font-style: italic;
        font-size: 1rem;
        margin-bottom: 16px;
        opacity: 0.9;
    }
    
    .hero-location {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.15);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        backdrop-filter: blur(10px);
    }
    
    .info-grid {
        display: grid;
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .info-card {
        background: var(--bg-surface);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 1px solid var(--border-color);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    
    .info-card-header {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid var(--border-color);
    }
    
    .info-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .info-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-content {
        padding: 20px;
        color: var(--text-main);
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    .verse-reference {
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
        font-size: 1.05rem;
    }
    
    .verse-theme {
        font-style: italic;
        color: var(--text-muted);
        margin-bottom: 12px;
        padding: 8px 12px;
        background: var(--bg-hover);
        border-left: 3px solid var(--primary);
        border-radius: 4px;
    }
    
    .verse-text {
        color: var(--text-secondary);
        line-height: 1.8;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }
</style>

<!-- Hero Section -->
<div class="hero-section">
    <h1 class="hero-title">Primeira Igreja Batista</h1>
    <h2 class="hero-subtitle">em Oliveira-MG</h2>
    <p class="hero-tagline">"Uma igreja viva, edificando vidas"</p>
    <div class="hero-location">
        <i data-lucide="map-pin" style="width: 16px;"></i>
        <span>R. José Eduardo Abdo, 105</span>
    </div>
</div>

<div class="info-grid">
    <!-- Sobre -->
    <div class="info-card animate-in" style="animation-delay: 0.1s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: linear-gradient(135deg, #047857 0%, #065f46 100%);">
                <i data-lucide="info" style="width: 24px; color: white;"></i>
            </div>
            <h3 class="info-title">Sobre</h3>
        </div>
        <div class="info-content">
            <p>O Ministério de Louvor da PIB de Oliveira é dedicado a conduzir a igreja em adoração a Deus através da música e da expressão de louvor. Composto por pessoas que amam e servem ao mesmo Deus, o ministério tem como foco central glorificar a Deus, criando um ambiente de adoração onde o corpo de Cristo pode se conectar profundamente com o Senhor.</p>
        </div>
    </div>

    <!-- Objetivo -->
    <div class="info-card animate-in" style="animation-delay: 0.2s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: linear-gradient(135deg, #0D6EFD 0%, #0B5ED7 100%);">
                <i data-lucide="target" style="width: 24px; color: white;"></i>
            </div>
            <h3 class="info-title">Objetivo</h3>
        </div>
        <div class="info-content">
            <p>O principal objetivo do ministério de louvor é facilitar uma adoração genuína e autêntica, permitindo que cada pessoa na congregação tenha um encontro com Deus. Buscamos ser instrumentos para que a igreja experimente a presença do Senhor, através de canções que refletem a verdade bíblica e elevam o nome de Jesus Cristo.</p>
        </div>
    </div>

    <!-- Base Bíblica -->
    <div class="info-card animate-in" style="animation-delay: 0.3s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%);">
                <i data-lucide="book-open" style="width: 24px; color: white;"></i>
            </div>
            <h3 class="info-title">Base Bíblica</h3>
        </div>
        <div class="info-content">
            <div class="verse-reference">Colossenses 3:16-17</div>
            <div class="verse-theme">"Edificação da comunidade e crescimento mútuo da fé"</div>
            <p class="verse-text">"Que a palavra de Cristo habite plenamente em vocês. Ensinem e aconselhem uns aos outros com toda a sabedoria; cantem salmos, hinos e cânticos espirituais a Deus com gratidão no coração. Tudo o que fizerem, seja em palavra, seja em ação, façam‑no em nome do Senhor Jesus, dando graças a Deus Pai por meio dele."</p>
        </div>
    </div>

    <!-- Missão -->
    <div class="info-card animate-in" style="animation-delay: 0.4s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: linear-gradient(135deg, #d63384 0%, #a61e63 100%);">
                <i data-lucide="flag" style="width: 24px; color: white;"></i>
            </div>
            <h3 class="info-title">Missão</h3>
        </div>
        <div class="info-content">
            <p>Nossa missão é ser uma equipe de adoradores comprometidos com Deus, que, através da música, inspirem a igreja a buscar uma vida de adoração em espírito e em verdade. Queremos impactar vidas por meio de uma adoração transformadora, guiada pela presença do Espírito Santo.</p>
        </div>
    </div>

    <!-- Visão -->
    <div class="info-card animate-in" style="animation-delay: 0.5s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: linear-gradient(135deg, #fd7e14 0%, #ca5a02 100%);">
                <i data-lucide="eye" style="width: 24px; color: white;"></i>
            </div>
            <h3 class="info-title">Visão</h3>
        </div>
        <div class="info-content">
            <p>Ser um ministério que busca a excelência em adoração e serviço, formando uma comunidade de filhos de Deus que desfrutem de uma vida em plena comunhão com Deus. Sonhamos em ver a igreja inteira envolvida na adoração verdadeira, experimentando a plenitude de Deus em todas as áreas de nossas vidas.</p>
        </div>
    </div>
</div>

<script>
// Inicializar ícones Lucide
if (window.lucide) {
    lucide.createIcons();
}
</script>

<?php renderAppFooter(); ?>
