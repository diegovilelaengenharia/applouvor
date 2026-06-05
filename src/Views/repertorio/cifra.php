<?php 
$title = "Modo Palco - " . htmlspecialchars($song['title']); 
require __DIR__ . '/../layouts/head.php'; 
// Note que NÃO carregamos o top-app-bar nem bottom-nav para tela inteira!
?>

<!-- ========================================================================= -->
<!-- ATENÇÃO DIEGO: COLE AQUI DENTRO O CONTEÚDO HTML DA TELA 10 STITCH -->
<!-- Ela deve ser full screen, fundo escuro, com botão de fechar -->
<!-- ========================================================================= -->

<div class="min-h-screen bg-[#0A0A0B] text-white flex flex-col relative pb-20">
    <!-- Header Fixo (Dark) -->
    <header class="fixed top-0 w-full z-50 bg-[#0A0A0B]/90 backdrop-blur-md border-b border-white/5 safe-top">
        <div class="flex items-center justify-between px-4 h-16 max-w-lg mx-auto w-full">
            <div class="flex items-center gap-3">
                <a href="/musicas/<?= $song['id'] ?>" class="text-white/60 hover:text-white p-2 -ml-2 rounded-full">
                    <span class="material-symbols-outlined text-[24px]">close</span>
                </a>
                <div>
                    <h1 class="font-bold text-lg leading-tight truncate w-[200px]"><?= htmlspecialchars($song['title']) ?></h1>
                    <div class="flex items-center gap-2 text-xs font-bold text-white/50">
                        <span class="text-primary"><?= htmlspecialchars($song['tone'] ?? 'Tom?') ?></span>
                        <span>•</span>
                        <span><?= htmlspecialchars($song['bpm'] ?? '--') ?> BPM</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="p-2 text-white/60 hover:text-white"><span class="material-symbols-outlined">text_decrease</span></button>
                <button class="p-2 text-white/60 hover:text-white"><span class="material-symbols-outlined">text_increase</span></button>
            </div>
        </div>
    </header>

    <!-- Conteúdo da Cifra -->
    <main class="flex-grow pt-24 px-4 pb-24 max-w-lg mx-auto w-full font-mono text-base whitespace-pre leading-relaxed overflow-x-auto text-white/90">
<?php if (!empty($song['cifra_text'])): ?>
<?= htmlspecialchars($song['cifra_text']) ?>
<?php else: ?>
[Intro]
<span class="text-primary font-bold">G</span>  <span class="text-primary font-bold">D/F#</span>  <span class="text-primary font-bold">Em</span>  <span class="text-primary font-bold">C</span>

[Verso 1]
<span class="text-primary font-bold">G</span>                 <span class="text-primary font-bold">D/F#</span>
  Aqui na cifra mockada
<span class="text-primary font-bold">Em</span>                 <span class="text-primary font-bold">C</span>
  No futuro virá do banco de dados

[Refrão]
<span class="text-primary font-bold">G</span>                    <span class="text-primary font-bold">D/F#</span>
  Porque o layout é Sacred Minimalist
<span class="text-primary font-bold">Em</span>                  <span class="text-primary font-bold">C</span>
  E a glória é só d'Ele!
<?php endif; ?>
    </main>

    <!-- Barra Inferior Palco -->
    <div class="fixed bottom-0 w-full z-50 bg-[#121214] border-t border-white/5 safe-bottom">
        <div class="flex justify-between items-center px-4 h-16 max-w-lg mx-auto w-full">
            <button class="flex flex-col items-center justify-center text-white/40 hover:text-primary transition-colors p-2">
                <span class="material-symbols-outlined text-[22px]">swap_vert</span>
                <span class="text-[10px] font-bold mt-1">Transpor</span>
            </button>
            
            <!-- Botão de Autoscroll gigante -->
            <button class="bg-primary hover:bg-primary/90 text-on-primary w-14 h-14 rounded-full flex items-center justify-center -mt-6 shadow-[0_0_20px_rgba(46,126,237,0.3)] border-4 border-[#121214]">
                <span class="material-symbols-outlined text-[28px] ml-1">play_arrow</span>
            </button>
            
            <button class="flex flex-col items-center justify-center text-white/40 hover:text-white transition-colors p-2">
                <span class="material-symbols-outlined text-[22px]">more_horiz</span>
                <span class="text-[10px] font-bold mt-1">Opções</span>
            </button>
        </div>
    </div>
</div>

<script>
// Mock básico para o modo palco evitar tela apagar (WakeLock API)
if ('wakeLock' in navigator) {
    let wakeLock = null;
    const requestWakeLock = async () => {
        try {
            wakeLock = await navigator.wakeLock.request('screen');
        } catch (err) {
            console.log(`${err.name}, ${err.message}`);
        }
    };
    requestWakeLock();
    document.addEventListener('visibilitychange', async () => {
        if (wakeLock !== null && document.visibilityState === 'visible') {
            requestWakeLock();
        }
    });
}
</script>
</body>
</html>
