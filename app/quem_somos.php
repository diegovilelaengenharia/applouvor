<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

renderAppHeader('Quem somos nós?');
?>

<style>
    .about-header {
        text-align: center;
        margin-bottom: 32px;
        padding: 32px 20px;
        background: radial-gradient(circle at center, rgba(var(--primary-rgb), 0.1) 0%, transparent 70%);
        border-radius: 24px;
    }
    .church-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: -0.5px;
        margin-bottom: 8px;
    }
    .church-city {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }
    .church-slogan {
        font-style: italic;
        color: var(--text-muted);
        font-weight: 500;
        font-size: 1rem;
        margin-bottom: 8px;
    }
    .church-address {
        font-size: 0.9rem;
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--bg-surface);
        padding: 6px 16px;
        border-radius: 20px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        padding-bottom: 40px;
    }
    
    .value-card {
        background: var(--bg-surface);
        border-radius: 20px;
        padding: 24px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .value-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .value-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 6px;
        background: var(--card-accent);
    }
    
    .value-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        background: var(--card-bg-light);
        color: var(--card-accent);
    }
    
    .value-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .value-text {
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.7;
    }

    /* Accents */
    .accent-green { --card-accent: #059669; --card-bg-light: #ecfdf5; }
    .accent-blue { --card-accent: #2563eb; --card-bg-light: #eff6ff; }
    .accent-purple { --card-accent: #7c3aed; --card-bg-light: #f5f3ff; }
    .accent-pink { --card-accent: #db2777; --card-bg-light: #fdf2f8; }
    .accent-orange { --card-accent: #ea580c; --card-bg-light: #fff7ed; }

    @media (max-width: 640px) {
        .about-header { padding: 24px 16px; }
        .church-name { font-size: 1.25rem; }
        .values-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="compact-container">

    <!-- Church Header Section -->
    <div class="about-header animate-in">
        <h2 class="church-name">Primeira Igreja Batista</h2>
        <h3 class="church-city">Em Oliveira-MG</h3>
        <p class="church-slogan">"Uma igreja viva, edificando vidas"</p>
        <div class="church-address">
            <i data-lucide="map-pin" style="width: 14px;"></i>
            R. José Eduardo Abdo, 105
        </div>
    </div>

    <div class="values-grid">
        
        <!-- Card: Sobre -->
        <div class="value-card accent-green animate-in" style="animation-delay: 0.1s;">
            <div class="value-icon-wrapper">
                <i data-lucide="info" style="width: 24px;"></i>
            </div>
            <h3 class="value-title">Quem Somos</h3>
            <p class="value-text">
                O Ministério de Louvor da PIB de Oliveira é dedicado a conduzir a igreja em adoração a Deus através da música e da expressão de louvor. Composto por pessoas que amam e servem ao mesmo Deus, o ministério tem como foco central glorificar a Deus, criando um ambiente de adoração onde o corpo de Cristo pode se conectar profundamente com o Senhor.
            </p>
        </div>

        <!-- Card: Missão -->
        <div class="value-card accent-pink animate-in" style="animation-delay: 0.2s;">
            <div class="value-icon-wrapper">
                <i data-lucide="flag" style="width: 24px;"></i>
            </div>
            <h3 class="value-title">Missão</h3>
            <p class="value-text">
                Nossa missão é ser uma equipe de adoradores comprometidos com Deus, que, através da música, inspirem a igreja a buscar uma vida de adoração em espírito e em verdade. Queremos impactar vidas por meio de uma adoração transformadora, guiada pela presença do Espírito Santo.
            </p>
        </div>

        <!-- Card: Visão -->
        <div class="value-card accent-orange animate-in" style="animation-delay: 0.3s;">
            <div class="value-icon-wrapper">
                <i data-lucide="eye" style="width: 24px;"></i>
            </div>
            <h3 class="value-title">Visão</h3>
            <p class="value-text">
                Ser um ministério que busca a excelência em adoração e serviço, formando uma comunidade de filhos de Deus que desfrutem de uma vida em plena comunhão com Deus. Sonhamos em ver a igreja inteira envolvida na adoração verdadeira, experimentando a plenitude de Deus em todas as áreas de nossas vidas.
            </p>
        </div>

        <!-- Card: Objetivo -->
        <div class="value-card accent-blue animate-in" style="animation-delay: 0.4s;">
            <div class="value-icon-wrapper">
                <i data-lucide="target" style="width: 24px;"></i>
            </div>
            <h3 class="value-title">Objetivo</h3>
            <p class="value-text">
                O principal objetivo do ministério de louvor é facilitar uma adoração genuína e autêntica, permitindo que cada pessoa na congregação tenha um encontro com Deus. Buscamos ser instrumentos para que a igreja experimente a presença do Senhor, através de canções que refletem a verdade bíblica e elevam o nome de Jesus Cristo.
            </p>
        </div>

        <!-- Card: Base Bíblica -->
        <div class="value-card accent-purple animate-in" style="grid-column: 1 / -1; animation-delay: 0.5s;">
            <div class="value-icon-wrapper">
                <i data-lucide="book-open" style="width: 24px;"></i>
            </div>
            <h3 class="value-title">Base Bíblica: Colossenses 3:16-17</h3>
            <p class="value-text" style="font-style: italic; margin-bottom: 12px; color: var(--primary);">
                “Edificação da comunidade e crescimento mútuo da fé”
            </p>
            <p class="value-text">
                "Que a palavra de Cristo habite plenamente em vocês. Ensinem e aconselhem uns aos outros com toda a sabedoria; cantem salmos, hinos e cânticos espirituais a Deus com gratidão no coração. Tudo o que fizerem, seja em palavra, seja em ação, façam‑no em nome do Senhor Jesus, dando graças a Deus Pai por meio dele."
            </p>
        </div>

    </div>

</div>

<?php renderAppFooter(); ?>
