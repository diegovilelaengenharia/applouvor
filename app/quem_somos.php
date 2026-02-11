<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

renderAppHeader('Quem Somos');
?>



<?php renderPageHeader('Quem Somos', 'Primeira Igreja Batista em Oliveira-MG'); ?>

<div class="info-grid">
    <!-- Sobre -->
    <div class="info-card animate-in" style="animation-delay: 0.1s;">
        <div class="info-card-header">
            <div class="info-icon" style="background: #047857;">
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
            <div class="info-icon" style="background: #0D6EFD;">
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
            <div class="info-icon" style="background: #6610f2;">
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
            <div class="info-icon" style="background: #d63384;">
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
            <div class="info-icon" style="background: #fd7e14;">
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
