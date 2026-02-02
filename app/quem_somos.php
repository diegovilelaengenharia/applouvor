<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

renderAppHeader('Quem nós somos');
?>

<!-- Church Header Section -->
<div style="text-align: center; margin-bottom: 24px; padding: 0 16px;">
    <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 4px; text-transform: uppercase; letter-spacing: -0.5px;">PRIMEIRA IGREJA BATISTA</h2>
    <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">EM OLIVEIRA-MG</h3>
    <p style="font-style: italic; color: var(--text-muted); font-weight: 500; margin-bottom: 4px;">"Uma igreja viva, edificando vidas"</p>
    <p style="font-size: 0.85rem; color: var(--text-secondary);">
        <i data-lucide="map-pin" style="width: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        R. José Eduardo Abdo, 105
    </p>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr;"> <!-- Force single column for text content -->

    <!-- Card: Sobre -->
    <div class="dashboard-card animate-in" style="animation-delay: 0.1s;">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #047857 0%, #065f46 100%);">
                    <i data-lucide="info" style="width: 20px; color: white;"></i>
                </div>
                SOBRE
            </div>
        </div>
        <div style="padding: 0 16px 16px 16px; color: var(--text-main); line-height: 1.6;">
            <p>O Ministério de Louvor da PIB de Oliveira é dedicado a conduzir a igreja em adoração a Deus através da música e da expressão de louvor. Composto por pessoas que amam e servem ao mesmo Deus, o ministério tem como foco central glorificar a Deus, criando um ambiente de adoração onde o corpo de Cristo pode se conectar profundamente com o Senhor.</p>
        </div>
    </div>

    <!-- Card: Objetivo -->
    <div class="dashboard-card animate-in" style="animation-delay: 0.2s;">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #0D6EFD 0%, #0B5ED7 100%);">
                    <i data-lucide="target" style="width: 20px; color: white;"></i>
                </div>
                OBJETIVO
            </div>
        </div>
        <div style="padding: 0 16px 16px 16px; color: var(--text-main); line-height: 1.6;">
            <p>O principal objetivo do ministério de louvor é facilitar uma adoração genuína e autêntica, permitindo que cada pessoa na congregação tenha um encontro com Deus. Buscamos ser instrumentos para que a igreja experimente a presença do Senhor, através de canções que refletem a verdade bíblica e elevam o nome de Jesus Cristo.</p>
        </div>
    </div>

    <!-- Card: Base Bíblica -->
    <div class="dashboard-card animate-in" style="animation-delay: 0.3s;">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%);">
                    <i data-lucide="book-open" style="width: 20px; color: white;"></i>
                </div>
                BASE BÍBLICA
            </div>
        </div>
        <div style="padding: 0 16px 16px 16px; color: var(--text-main); line-height: 1.6;">
            <h4 style="margin-bottom: 8px; color: var(--primary);">Colossenses 3:16-17</h4>
            <p style="font-style: italic; color: var(--text-muted); margin-bottom: 12px;">“Edificação da comunidade e crescimento mútuo da fé”</p>
            <p>"Que a palavra de Cristo habite plenamente em vocês. Ensinem e aconselhem uns aos outros com toda a sabedoria; cantem salmos, hinos e cânticos espirituais a Deus com gratidão no coração. Tudo o que fizerem, seja em palavra, seja em ação, façam‑no em nome do Senhor Jesus, dando graças a Deus Pai por meio dele."</p>
        </div>
    </div>

    <!-- Card: Missão -->
    <div class="dashboard-card animate-in" style="animation-delay: 0.4s;">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #d63384 0%, #a61e63 100%);">
                    <i data-lucide="flag" style="width: 20px; color: white;"></i>
                </div>
                MISSÃO
            </div>
        </div>
        <div style="padding: 0 16px 16px 16px; color: var(--text-main); line-height: 1.6;">
            <p>Nossa missão é ser uma equipe de adoradores comprometidos com Deus, que, através da música, inspirem a igreja a buscar uma vida de adoração em espírito e em verdade. Queremos impactar vidas por meio de uma adoração transformadora, guiada pela presença do Espírito Santo.</p>
        </div>
    </div>

    <!-- Card: Visão -->
    <div class="dashboard-card animate-in" style="animation-delay: 0.5s;">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fd7e14 0%, #ca5a02 100%);">
                    <i data-lucide="eye" style="width: 20px; color: white;"></i>
                </div>
                VISÃO
            </div>
        </div>
        <div style="padding: 0 16px 16px 16px; color: var(--text-main); line-height: 1.6;">
            <p>Ser um ministério que busca a excelência em adoração e serviço, formando uma comunidade de filhos de Deus que desfrutem de uma vida em plena comunhão com Deus. Sonhamos em ver a igreja inteira envolvida na adoração verdadeira, experimentando a plenitude de Deus em todas as áreas de nossas vidas.</p>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>
