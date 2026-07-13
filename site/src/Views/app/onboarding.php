<?php
$title = "Bem-vindo";
$bodyClass = "p-6";
require __DIR__ . '/../layouts/head.php';

$features = [
    ['calendar_month', 'Acompanhe suas escalas', 'Veja onde você foi escalado, confirme presença e marque indisponibilidades.'],
    ['library_music', 'Repertório e cifras', 'Acesse músicas, tons e o modo palco com rolagem automática durante o culto.'],
    ['groups', 'Conectado ao ministério', 'Avisos da liderança, mural de oração, devocionais e aniversários num só lugar.'],
];
?>

<div class="w-full max-w-md mx-auto flex flex-col min-h-[100dvh] py-4">

    <!-- Pular -->
    <div class="flex justify-end">
        <a href="/dashboard" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface">Pular</a>
    </div>

    <!-- Cabeçalho -->
    <div class="text-center mt-8 mb-10 reveal-item">
        <div class="w-20 h-20 rounded-3xl mx-auto mb-6 flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
            <span class="material-symbols-outlined text-[40px] text-primary fill">church</span>
        </div>
        <h1 class="text-3xl font-bold text-on-surface">Bem-vindo ao app do Louvor</h1>
        <p class="text-sm text-on-surface-variant mt-2">Tudo o que você precisa para servir, num só lugar.</p>
    </div>

    <!-- Destaques -->
    <div class="space-y-4 flex-grow">
        <?php foreach ($features as $i => $f): ?>
            <div class="pib-card p-4 flex items-start gap-4 reveal-item reveal-stagger-<?= $i + 1 ?>">
                <div class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
                    <span class="material-symbols-outlined text-[22px] text-primary"><?= $f[0] ?></span>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-on-surface"><?= htmlspecialchars($f[1]) ?></h3>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed"><?= htmlspecialchars($f[2]) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Ação -->
    <a href="/dashboard" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95 mt-8">
        <span>Começar</span>
        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
    </a>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
