<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkLogin();

renderAppHeader('Quem Somos');
?>

<div class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8">
    
    <!-- Hero Header -->
    <div class="text-center py-10 max-w-2xl mx-auto">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-fixed text-primary dark:text-primary-fixed mb-6 shadow-sm">
            <span class="material-symbols-outlined text-[32px] fill">church</span>
        </div>
        <h1 class="font-display-lg text-3xl md:text-4xl font-extrabold text-on-surface mb-3">Quem Somos</h1>
        <p class="font-body-lg text-on-surface-variant">Primeira Igreja Batista em Oliveira-MG</p>
    </div>

    <!-- Bento Grid Layout for About Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Sobre Card -->
        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[24px]">info</span>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Sobre Nós</h3>
            </div>
            <div class="font-body-md text-on-surface-variant leading-relaxed flex-grow">
                <p>O Ministério de Louvor da PIB de Oliveira é dedicado a conduzir a igreja em adoração a Deus através da música e da expressão de louvor. Composto por pessoas que amam e servem ao mesmo Deus, o ministério tem como foco central glorificar a Deus, criando um ambiente de adoração onde o corpo de Cristo pode se conectar profundamente com o Senhor.</p>
            </div>
        </div>

        <!-- Objetivo Card -->
        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-worship-blue/10 text-worship-blue flex items-center justify-center transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[24px]">target</span>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Objetivo</h3>
            </div>
            <div class="font-body-md text-on-surface-variant leading-relaxed flex-grow">
                <p>O principal objetivo do ministério de louvor é facilitar uma adoração genuína e autêntica, permitindo que cada pessoa na congregação tenha um encontro com Deus. Buscamos ser instrumentos para que a igreja experimente a presença do Senhor, através de canções que refletem a verdade bíblica e elevam o nome de Jesus Cristo.</p>
            </div>
        </div>

        <!-- Missão Card -->
        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[24px]">flag</span>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Missão</h3>
            </div>
            <div class="font-body-md text-on-surface-variant leading-relaxed flex-grow">
                <p>Nossa missão é ser uma equipe de adoradores comprometidos com Deus, que, através da música, inspirem a igreja a buscar uma vida de adoração em espírito e em verdade. Queremos impactar vidas por meio de uma adoração transformadora, guiada pela presença do Espírito Santo.</p>
            </div>
        </div>

        <!-- Visão Card -->
        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[24px]">visibility</span>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Visão</h3>
            </div>
            <div class="font-body-md text-on-surface-variant leading-relaxed flex-grow">
                <p>Ser um ministério que busca a excelência em adoração e serviço, formando uma comunidade de filhos de Deus que desfrutem de uma vida em plena comunhão com Deus. Sonhamos em ver a igreja inteira envolvida na adoração verdadeira, experimentando a plenitude de Deus em todas as áreas de nossas vidas.</p>
            </div>
        </div>

        <!-- Base Bíblica Card (Spans across larger space on medium+ screens) -->
        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 md:col-span-2 flex flex-col group relative overflow-hidden">
            <!-- Decorative light glow behind the card -->
            <div class="absolute -right-20 -bottom-20 w-48 h-48 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[24px]">book_open</span>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Base Bíblica</h3>
            </div>
            
            <div class="flex flex-col md:flex-row gap-6 mt-2 flex-grow">
                <div class="md:w-1/3 flex flex-col justify-center border-b md:border-b-0 md:border-r border-surface-container-highest pb-4 md:pb-0 md:pr-6">
                    <div class="font-display-lg text-xl font-extrabold text-primary dark:text-primary-fixed mb-1">Colossenses 3:16-17</div>
                    <div class="font-label-sm text-xs text-on-surface-variant uppercase tracking-wider">Edificação & Crescimento</div>
                </div>
                <div class="md:w-2/3 flex flex-col justify-center">
                    <p class="font-body-lg text-lg italic text-on-surface leading-relaxed relative">
                        <span class="absolute -left-4 -top-3 text-4xl text-on-surface-variant/20 font-serif">“</span>
                        Que a palavra de Cristo habite plenamente em vocês. Ensinem e aconselhem uns aos outros com toda a sabedoria; cantem salmos, hinos e cânticos espirituais a Deus com gratidão no coração. Tudo o que fizerem, seja em palavra, seja em ação, façam-no em nome do Senhor Jesus, dando graças a Deus Pai por meio dele.
                        <span class="text-4xl text-on-surface-variant/20 font-serif">”</span>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Inicializar ícones Lucide se carregados
if (window.lucide) {
    lucide.createIcons();
}
</script>

<?php renderAppFooter(); ?>
