<?php
// admin/artista_perfil.php - Perfil do Artista
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

$artistName = $_GET['artist'] ?? null;
if (!$artistName) {
    header('Location: repertorio.php?tab=artistas');
    exit;
}

// Atualizar nome do artista (se enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_artist'])) {
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $stmt = $pdo->prepare("UPDATE songs SET artist = ? WHERE artist = ?");
        $stmt->execute([$newName, $artistName]);
        header("Location: artista_perfil.php?artist=" . urlencode($newName));
        exit;
    }
}

// Buscar músicas do artista
$stmt = $pdo->prepare("
    SELECT id, title, artist, tone, bpm, category, created_at
    FROM songs 
    WHERE artist = ?
    ORDER BY title ASC
");
$stmt->execute([$artistName]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$totalSongs = count($songs);
$avgBpm = 0;
$tones = [];
$categories = [];

foreach ($songs as $song) {
    if ($song['bpm']) $avgBpm += $song['bpm'];
    if ($song['tone']) $tones[] = $song['tone'];
    if ($song['category']) $categories[] = $song['category'];
}

$avgBpm = $totalSongs > 0 ? round($avgBpm / $totalSongs) : 0;
$mostUsedTone = !empty($tones) ? array_count_values($tones) : [];
arsort($mostUsedTone);
$mostUsedTone = !empty($mostUsedTone) ? array_key_first($mostUsedTone) : '-';

renderAppHeader('Artista', 'repertorio.php?tab=artistas');
?>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 font-hanken">

    <!-- Header com Navegação -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="repertorio.php?tab=artistas" class="w-10 h-10 bg-ghost-gray hover:bg-outline-variant/20 dark:bg-surface-variant/10 active:scale-95 border border-outline-variant/30 rounded-full flex items-center justify-center transition-all duration-200 shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5 text-on-background"></i>
            </a>
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-on-background tracking-tight leading-tight">Perfil do Artista</h1>
                <p class="text-xs md:text-sm text-secondary mt-1">Estatísticas e repertório musical associado.</p>
            </div>
        </div>
    </div>

    <!-- Bento Banner principal -->
    <div class="bg-gradient-to-br from-worship-blue/10 to-worship-blue/5 border border-worship-blue/20 rounded-3xl p-6 md:p-8 mb-8 relative overflow-hidden backdrop-blur-md">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-2xl bg-worship-blue text-white flex items-center justify-center font-extrabold text-3xl shadow-lg border-2 border-white/10 shrink-0">
                    <?= strtoupper(substr($artistName, 0, 1)) ?>
                </div>
                <div>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-on-background tracking-tight leading-tight"><?= htmlspecialchars($artistName) ?></h2>
                    <div class="mt-2 text-xs md:text-sm text-secondary font-semibold">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-worship-blue/15 text-worship-blue border border-worship-blue/20">
                            <i data-lucide="music-2" class="w-3.5 h-3.5"></i>
                            <span><?= $totalSongs ?> música<?= $totalSongs != 1 ? 's' : '' ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Botão Editar -->
            <button onclick="openEditModal()" class="px-5 py-3 rounded-2xl bg-worship-blue hover:bg-worship-blue-hover text-white flex items-center gap-2 font-bold text-xs uppercase tracking-wider active:scale-95 transition-all duration-200 shadow-lg shadow-worship-blue/25 shrink-0">
                <i data-lucide="edit-2" class="w-4 h-4"></i>
                <span>Editar Artista</span>
            </button>
        </div>

        <!-- Bento Grid de Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8 relative z-10">
            <!-- Card 1: Músicas -->
            <div class="bg-white/40 dark:bg-deep-navy/35 border border-outline-variant/30 rounded-2xl p-5 backdrop-blur-sm hover:border-worship-blue/35 transition-colors duration-300">
                <span class="text-xs font-bold text-secondary uppercase tracking-widest block mb-2">Total de Músicas</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-on-background"><?= $totalSongs ?></span>
                    <span class="text-xs text-secondary font-semibold">canções</span>
                </div>
            </div>

            <!-- Card 2: BPM Médio -->
            <div class="bg-white/40 dark:bg-deep-navy/35 border border-outline-variant/30 rounded-2xl p-5 backdrop-blur-sm hover:border-worship-blue/35 transition-colors duration-300">
                <span class="text-xs font-bold text-secondary uppercase tracking-widest block mb-2">Ritmo Médio (BPM)</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-on-background"><?= $avgBpm ?: '-' ?></span>
                    <span class="text-xs text-secondary font-semibold">bpm</span>
                </div>
            </div>

            <!-- Card 3: Tom Mais Usado -->
            <div class="bg-white/40 dark:bg-deep-navy/35 border border-outline-variant/30 rounded-2xl p-5 backdrop-blur-sm hover:border-worship-blue/35 transition-colors duration-300">
                <span class="text-xs font-bold text-secondary uppercase tracking-widest block mb-2">Tom Frequente</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-on-background"><?= $mostUsedTone ?></span>
                    <span class="text-xs text-secondary font-semibold">mais usado</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de Repertório -->
    <div class="mb-24">
        <h3 class="text-lg font-bold text-on-background mb-4 flex items-center gap-2">
            <i data-lucide="disc" class="w-5 h-5 text-worship-blue"></i>
            <span>Músicas do Artista</span>
        </h3>

        <?php if (empty($songs)): ?>
            <div class="text-center py-12 bg-ghost-gray/10 dark:bg-surface-variant/5 border border-dashed border-outline-variant/30 rounded-3xl">
                <i data-lucide="music-2" class="w-12 h-12 text-secondary/40 mx-auto mb-3"></i>
                <p class="text-secondary font-semibold text-sm">Nenhuma música encontrada para este artista.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($songs as $song): ?>
                    <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="block group active:scale-[0.98] transition-all duration-200">
                        <div class="bg-white dark:bg-deep-navy border border-outline-variant/20 rounded-2xl p-5 hover:border-worship-blue/30 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/20 flex justify-between items-center transition-all duration-300">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-12 h-12 rounded-xl bg-ghost-gray dark:bg-surface-variant/20 border border-outline-variant/30 flex items-center justify-center text-worship-blue shrink-0 shadow-sm group-hover:bg-worship-blue group-hover:text-white transition-all duration-300">
                                    <i data-lucide="music" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-base text-on-background group-hover:text-worship-blue transition-colors truncate">
                                        <?= htmlspecialchars($song['title']) ?>
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <?php if ($song['category']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-ghost-gray dark:bg-surface-variant/30 text-secondary border border-outline-variant/20">
                                                <?= htmlspecialchars($song['category']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($song['tone']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                TOM: <?= $song['tone'] ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($song['bpm']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                                <?= $song['bpm'] ?> BPM
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="text-secondary/60 group-hover:text-worship-blue transition-colors group-hover:translate-x-[2px] transform duration-200">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<!-- Modal Editar Artista (Sacred Bottom-Sheet / Glassmorphic Modal) -->
<div id="editModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white dark:bg-deep-navy border border-outline-variant/30 w-full max-w-md rounded-3xl p-6 shadow-2xl transform scale-95 opacity-0 transition-all duration-350">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-extrabold text-lg text-on-background tracking-tight">Editar Artista</h3>
            <button onclick="closeEditModal()" class="w-8 h-8 rounded-full bg-ghost-gray dark:bg-surface-variant/20 text-secondary hover:text-on-background flex items-center justify-center active:scale-95 transition-all">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_artist" value="1">
            
            <div class="form-group mb-5">
                <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Nome do Artista</label>
                <input type="text" name="new_name" value="<?= htmlspecialchars($artistName) ?>" required class="w-full h-12 px-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/50 font-bold">
            </div>
            
            <div class="bg-amber-500/10 dark:bg-amber-950/20 border border-amber-500/20 rounded-2xl p-4 mb-6">
                <div class="flex items-center gap-2 mb-2 text-amber-600 dark:text-amber-400">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    <span class="text-xs font-extrabold uppercase tracking-wider">Atenção</span>
                </div>
                <p class="text-xs text-amber-600/90 dark:text-amber-300/90 leading-relaxed font-medium">
                    Alterar o nome do artista irá atualizar automaticamente <strong>todas as <?= $totalSongs ?> música<?= $totalSongs != 1 ? 's' : '' ?></strong> vinculadas a ele no banco de dados.
                </p>
            </div>
            
            <div class="flex gap-4">
                <button type="button" onclick="closeEditModal()" class="flex-1 h-12 rounded-xl border border-outline-variant/30 text-on-background font-bold text-sm hover:bg-ghost-gray/50 active:scale-98 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 h-12 rounded-xl bg-worship-blue hover:bg-worship-blue-hover text-white font-bold text-sm active:scale-98 transition-all shadow-lg shadow-worship-blue/15">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal() {
    const modal = document.getElementById('editModal');
    const content = modal.querySelector('div');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    const content = modal.querySelector('div');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

// Fechar ao clicar fora
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
