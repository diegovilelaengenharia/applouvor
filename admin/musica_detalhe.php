<?php
// admin/musica_detalhe.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// Helper: detecta plataforma pelo URL e retorna [label, color, bg_alpha, icon_svg]
function detectPlatform(string $url, string $type): array {
    $url = strtolower($url);
    if ($type === 'audio') {
        if (str_contains($url, 'spotify')) {
            return ['Spotify', '#1DB954', 'rgba(29, 185, 84, 0.05)', '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.65 14.42c-.2.32-.62.42-.94.22-2.58-1.58-5.83-1.93-9.65-1.06-.37.08-.73-.15-.81-.52-.08-.37.15-.73.52-.81 4.18-.95 7.76-.54 10.66 1.23.32.2.42.62.22.94zm1.24-2.76c-.25.4-.78.52-1.18.27-2.95-1.81-7.45-2.34-10.94-1.28-.45.14-.93-.12-1.07-.57-.14-.45.12-.93.57-1.07 3.99-1.21 8.96-.62 12.35 1.46.4.25.52.78.27 1.19zm.11-2.87C14.25 8.85 8.84 8.68 5.82 9.57c-.54.16-1.11-.14-1.27-.68-.16-.54.14-1.11.68-1.27 3.48-1.05 9.27-.85 12.93 1.38.47.28.62.89.34 1.36-.28.47-.89.62-1.36.34z"/>'];
        }
        if (str_contains($url, 'deezer')) {
            // Purple Ban: Deezer alterado de roxo (#a238ff) para Slate/Cinza Elegante (#64748b) para contraste em Dark Mode
            return ['Deezer', '#64748b', 'rgba(100, 116, 139, 0.05)', '<path d="M18.81 11.38H22v1.88h-3.19v-1.88zm-4.57 0h3.19v1.88h-3.19v-1.88zM2 11.38h3.19v1.88H2v-1.88zm4.57 0h3.19v1.88H6.57v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88zM18.81 8H22v1.88h-3.19V8zm-4.57 0h3.19v1.88h-3.19V8zm-9.15 3.38H8.28v1.88H5.09v-1.88zm0-3.38H8.28v1.88H5.09V8zm4.57 3.38h3.19v1.88-3.19v-1.88zM9.66 8h3.19v1.88H9.66V8zm0 6.75h3.19v1.88H9.66v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88z"/>'];
        }
        return ['Áudio', '#2E7EED', 'rgba(46, 126, 237, 0.05)', '<path d="M9 18V5l12-2v13M6 15H3a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-2h-3a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/>'];
    }
    if ($type === 'video') {
        if (str_contains($url, 'youtube') || str_contains($url, 'youtu.be')) {
            return ['YouTube', '#FF0000', 'rgba(255, 0, 0, 0.05)', '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/>'];
        }
        return ['Vídeo', '#2E7EED', 'rgba(46, 126, 237, 0.05)', '<path d="M22 8s-2.76-3-6-3H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12c3.24 0 6-3 6-3V8zM16 12l-5 3V9l5 3z"/>'];
    }
    if ($type === 'cifra') {
        return ['Cifra Club', '#F97316', 'rgba(249, 115, 22, 0.05)', '<path d="M9 18h6M7 22h10M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 6v4l3 3"/>'];
    }
    // letra -> Purple Ban: Letras alterado de indigo (#6366f1) para Sky Blue (#0EA5E9)
    return ['Letras', '#0EA5E9', 'rgba(14, 165, 233, 0.05)', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 13h8v1.5H8V13zm0 3h8v1.5H8V16zm0-6h3v1.5H8V10z"/>'];
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: repertorio.php');
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_song') {
    try {
        $stmtDelTags = $pdo->prepare("DELETE FROM song_tags WHERE song_id = ?");
        $stmtDelTags->execute([$id]);
        
        $stmtDel = $pdo->prepare("DELETE FROM songs WHERE id = ?");
        $stmtDel->execute([$id]);
        
        header('Location: repertorio.php');
        exit;
    } catch (Exception $e) {
        die("Erro ao excluir música: " . $e->getMessage());
    }
}

// Buscar Música
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Música não encontrada.");
}

// --- LÓGICA DE POST: PERSONAL TONES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_tone') {
        $stmtInsert = $pdo->prepare("INSERT INTO song_personal_tones (song_id, user_id, tone, observation) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$id, $_POST['user_id'], $_POST['tone'], $_POST['observation']]);
        header("Location: musica_detalhe.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'delete_tone') {
        $stmtDelete = $pdo->prepare("DELETE FROM song_personal_tones WHERE id = ?");
        $stmtDelete->execute([$_POST['tone_id']]);
        header("Location: musica_detalhe.php?id=$id");
        exit;
    }
}

// Buscar Tags
$stmtTags = $pdo->prepare("
    SELECT t.* 
    FROM tags t 
    JOIN song_tags st ON st.tag_id = t.id 
    WHERE st.song_id = ?
");
$stmtTags->execute([$id]);
$tags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

// QUERIES ADICIONAIS
$stmtTones = $pdo->prepare("
    SELECT spt.*, u.name, u.avatar 
    FROM song_personal_tones spt 
    JOIN users u ON spt.user_id = u.id 
    WHERE spt.song_id = ?
    ORDER BY u.name
");
$stmtTones->execute([$id]);
$personalTones = $stmtTones->fetchAll(PDO::FETCH_ASSOC);

$stmtUsers = $pdo->query("SELECT id, name FROM users ORDER BY name");
$usersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$stmtHistory = $pdo->prepare("SELECT s.event_date FROM schedule_songs ss JOIN schedules s ON ss.schedule_id = s.id WHERE ss.song_id = ? ORDER BY s.event_date DESC");
$stmtHistory->execute([$id]);
$history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Cálculo de Status
$totalExecs = count($history);
$lastPlayed = $history[0]['event_date'] ?? null;
$count90Days = 0;
$today = new DateTime();
$threeMonthsAgo = (clone $today)->modify('-3 months');

$yearsStats = [];
foreach ($history as $h) {
    $dateObj = new DateTime($h['event_date']);
    if ($dateObj >= $threeMonthsAgo) {
        $count90Days++;
    }
    $year = $dateObj->format('Y');
    if (!isset($yearsStats[$year])) {
        $yearsStats[$year] = 0;
    }
    $yearsStats[$year]++;
}

$statusLabel = 'Normal';
$statusBg = 'bg-ghost-gray dark:bg-surface-container text-secondary dark:text-on-surface-variant border-outline-variant/30'; 
$statusIcon = 'check-circle';

if ($totalExecs == 0) {
    $statusLabel = 'Nunca Tocada';
    $statusBg = 'bg-altar-gold/10 text-altar-gold border-altar-gold/20';
    $statusIcon = 'sparkles';
} else {
    $lastDate = new DateTime($lastPlayed);
    $diff = $today->diff($lastDate);
    $monthsDiff = ($diff->y * 12) + $diff->m;

    if ($count90Days >= 3) {
        $statusLabel = 'Alta Rotatividade';
        $statusBg = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        $statusIcon = 'flame';
    } elseif ($monthsDiff >= 3 && $monthsDiff <= 6) {
        $statusLabel = 'Geladeira';
        $statusBg = 'bg-worship-blue/10 text-worship-blue border-worship-blue/20';
        $statusIcon = 'snowflake';
    } elseif ($monthsDiff > 6) {
        $statusLabel = 'Esquecida';
        $statusBg = 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        $statusIcon = 'archive';
    }
}

$musicTones = [
    'C' => 'C (Dó)', 'C#' => 'C# (Dó Sustenido)', 'D' => 'D (Ré)',
    'D#' => 'D# (Ré Sustenido)', 'E' => 'E (Mi)', 'F' => 'F (Fá)',
    'F#' => 'F# (Fá Sustenido)', 'G' => 'G (Sol)', 'G#' => 'G# (Sol Sustenido)',
    'A' => 'A (Lá)', 'A#' => 'A# (Lá Sustenido)', 'B' => 'B (Si)',
    'Cm' => 'Cm (Dó Menor)', 'C#m' => 'C#m (Dó Sustenido Menor)',
    'Dm' => 'Dm (Ré Menor)', 'D#m' => 'D#m (Ré Sustenido Menor)',
    'Em' => 'Em (Mi Menor)', 'Fm' => 'Fm (Fá Menor)',
    'F#m' => 'F#m (Fá Sustenido Menor)', 'Gm' => 'Gm (Sol Menor)',
    'G#m' => 'G#m (Sol Sustenido Menor)', 'Am' => 'Am (Lá Menor)',
    'A#m' => 'A#m (Lá Sustenido Menor)', 'Bm' => 'Bm (Si Menor)'
];

renderAppHeader('Detalhes da Música', 'repertorio.php');
?>

<style>
    .bento-card-detail {
        background: var(--surface-container-lowest, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-card-detail {
        background: var(--surface-container-low, #131417);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .bento-card-detail:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03);
        transform: translateY(-1.5px);
    }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 font-hanken reveal-item">
    
    <!-- Header with Actions (Sacred Bento Style) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 relative">
        <div class="flex gap-5 items-center">
            <div class="w-16 h-16 bg-worship-blue/10 text-worship-blue rounded-2xl flex items-center justify-center shrink-0 border border-worship-blue/20 shadow-sm">
                <i data-lucide="music-2" class="w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-on-background tracking-tight leading-tight"><?= htmlspecialchars($song['title']) ?></h1>
                <div class="flex flex-wrap items-center gap-2.5 mt-2 text-xs md:text-sm text-secondary font-semibold">
                    <a href="artista_perfil.php?artist=<?= urlencode($song['artist']) ?>" class="hover:text-worship-blue transition-colors flex items-center gap-1">
                        <span><?= htmlspecialchars($song['artist']) ?></span>
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-worship-blue"></i>
                    </a>
                    <span class="text-secondary/40">•</span>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $statusBg ?>">
                        <i data-lucide="<?= $statusIcon ?>" class="w-3.5 h-3.5"></i>
                        <span><?= $statusLabel ?></span>
                    </div>
                    <span class="text-secondary/40">•</span>
                    <div class="flex items-center gap-1 text-xs md:text-sm font-semibold" title="Última vez: <?= $lastPlayed ? (new DateTime($lastPlayed))->format('d/m/Y') : 'Nunca' ?>">
                        <i data-lucide="history" class="w-4 h-4 text-secondary/75"></i>
                        <span><?= $totalExecs ?> execuções</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Contextual (Modern Touch) -->
        <div class="relative self-end md:self-center">
            <button onclick="toggleMenu()" id="menu-btn" class="interactive-scale p-3 bg-ghost-gray hover:bg-outline-variant/20 dark:bg-surface-variant/10 active:scale-95 rounded-full transition-all duration-200 border border-outline-variant/30 flex items-center justify-center">
                <i data-lucide="more-vertical" class="w-5 h-5 text-on-background"></i>
            </button>
            
            <div id="dropdown-menu" class="hidden absolute right-0 mt-3 w-52 bg-white dark:bg-deep-navy border border-outline-variant/30 rounded-2xl shadow-xl z-50 overflow-hidden transform origin-top-right transition-all backdrop-blur-md">
                <a href="musica_editar.php?id=<?= $id ?>" class="flex items-center gap-3 px-5 py-4 hover:bg-ghost-gray/40 dark:hover:bg-surface-variant/10 text-xs font-bold text-on-background transition-colors">
                    <i data-lucide="edit-3" class="w-4 h-4 text-worship-blue"></i>
                    <span>Editar Música</span>
                </a>
                <button onclick="confirmDeleteSong()" class="w-full flex items-center gap-3 px-5 py-4 text-rose-500 hover:bg-rose-500/10 transition-colors font-bold text-xs text-left border-t border-outline-variant/10">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Excluir Música</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation (Modern Floating Pills) -->
    <div class="flex bg-ghost-gray dark:bg-surface-variant/10 p-1.5 rounded-full border border-outline-variant/30 w-fit mb-8 overflow-x-auto max-w-full gap-1 hide-scrollbar">
        <button class="tab-btn px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-worship-blue text-white shadow-sm" data-target="tab-info">
            Visão Geral
        </button>
        <button class="tab-btn px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 text-secondary hover:text-worship-blue" data-target="tab-tones">
            Tons por Voz
        </button>
        <button class="tab-btn px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 text-secondary hover:text-worship-blue" data-target="tab-refs">
            Referências
        </button>
    </div>

    <!-- Tab Panels -->
    <div class="tab-content relative">
        
        <!-- TAB: Visão Geral -->
        <div id="tab-info" class="tab-panel block transition-opacity duration-300">
            <!-- Bento Grid de Metadados -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Tom Original -->
                <div class="reveal-item reveal-stagger-1 bento-card-detail rounded-2xl p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-worship-blue/10 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="music-2" class="w-12 h-12"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-secondary/80 mb-2">Tom Original</span>
                    <span class="text-5xl font-extrabold text-worship-blue tracking-tight"><?= $song['tone'] ?: '-' ?></span>
                </div>
                
                <!-- BPM -->
                <div class="reveal-item reveal-stagger-2 bento-card-detail rounded-2xl p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-altar-gold/10 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="gauge" class="w-12 h-12"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-secondary/80 mb-2">BPM</span>
                    <span class="text-5xl font-extrabold text-altar-gold tracking-tight"><?= $song['bpm'] ?: '-' ?></span>
                </div>
                
                <!-- Duração -->
                <div class="reveal-item reveal-stagger-3 bento-card-detail rounded-2xl p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-emerald-500/10 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="clock" class="w-12 h-12"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-secondary/80 mb-2">Duração</span>
                    <span class="text-5xl font-extrabold text-emerald-500 tracking-tight"><?= $song['duration'] ?: '-' ?></span>
                </div>
            </div>

            <!-- Metrônomo Ação -->
            <?php if (!empty($song['bpm'])): ?>
            <div class="flex justify-center mb-8">
                <a href="metronomo.php?bpm=<?= (int)$song['bpm'] ?>" class="interactive-scale inline-flex items-center gap-2.5 px-8 py-3.5 bg-worship-blue/15 hover:bg-worship-blue/20 text-worship-blue border border-worship-blue/20 rounded-xl font-bold text-xs uppercase tracking-wider transition-all duration-300 shadow-sm">
                    <i data-lucide="gauge" class="w-4 h-4 animate-pulse"></i>
                    <span>Abrir no Metrônomo (<?= (int)$song['bpm'] ?> BPM)</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Grid de Tags & Histórico -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Seção de Classificações Bento -->
                <div class="reveal-item reveal-stagger-4 bento-card-detail rounded-2xl p-6 md:p-8">
                    <h3 class="text-md font-bold text-on-background mb-5 flex items-center gap-2">
                        <i data-lucide="tag" class="w-5 h-5 text-worship-blue"></i>
                        <span>Classificações & TAGs</span>
                    </h3>
                    <?php if (!empty($tags)): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <span class="text-[10px] uppercase tracking-wider px-3.5 py-2 rounded-lg font-bold shadow-sm" style="background: <?= $tag['color'] ?>12; color: <?= $tag['color'] ?>; border: 1px solid <?= $tag['color'] ?>25;">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-8 text-center text-secondary border border-dashed border-outline-variant/30 rounded-2xl">
                            <i data-lucide="tag" class="w-8 h-8 mb-2 opacity-30"></i>
                            <p class="text-xs font-semibold">Nenhuma tag cadastrada nesta música.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Histórico Bento Card -->
                <div class="reveal-item reveal-stagger-4 bento-card-detail rounded-2xl p-6 md:p-8">
                    <h3 class="text-md font-bold text-on-background mb-5 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-5 h-5 text-worship-blue"></i>
                        <span>Últimas Execuções</span>
                    </h3>
                    <?php if (!empty($history)): ?>
                        <div class="space-y-3 max-h-[160px] overflow-y-auto pr-1 hide-scrollbar">
                            <?php 
                            $count = 0;
                            foreach ($history as $h): 
                                $count++;
                                if ($count > 3) break; // Exibe no máximo as 3 últimas
                                $dateObj = new DateTime($h['event_date']);
                            ?>
                                <div class="flex items-center justify-between p-3.5 bg-ghost-gray/45 dark:bg-surface-container-lowest border border-outline-variant/20 rounded-xl">
                                    <div class="flex items-center gap-2 text-xs font-bold text-on-background">
                                        <i data-lucide="calendar" class="w-4 h-4 text-secondary"></i>
                                        <span>Culto em <?= $dateObj->format('d/m/Y') ?></span>
                                    </div>
                                    <span class="text-[10px] font-black text-secondary bg-outline-variant/20 px-2.5 py-1 rounded-full uppercase tracking-wider">Executada</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-8 text-center text-secondary border border-dashed border-outline-variant/30 rounded-2xl">
                            <i data-lucide="calendar-x" class="w-8 h-8 mb-2 opacity-30"></i>
                            <p class="text-xs font-semibold">Sem histórico de execução cadastrado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB: Tons por Voz -->
        <div id="tab-tones" class="tab-panel hidden transition-opacity duration-300">
            <div class="bento-card-detail rounded-2xl p-6 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-md font-bold text-on-background flex items-center gap-2">
                        <i data-lucide="mic" class="w-5 h-5 text-worship-blue"></i>
                        <span>Tons Pessoais por Voz</span>
                    </h3>
                    <button onclick="openToneModal()" class="flex items-center gap-1.5 bg-worship-blue/15 hover:bg-worship-blue/20 text-worship-blue px-4 py-2.5 border border-worship-blue/15 rounded-xl font-bold text-xs uppercase tracking-wider active:scale-95 transition-all shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Adicionar Tom</span>
                    </button>
                </div>

                <?php if (!empty($personalTones)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($personalTones as $pt): ?>
                            <div class="reveal-item flex justify-between items-center p-4 bg-ghost-gray/20 dark:bg-surface-container-lowest border border-outline-variant/35 rounded-2xl hover:border-worship-blue/30 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-worship-blue/10 rounded-full flex items-center justify-center font-bold text-worship-blue overflow-hidden border border-worship-blue/25 shrink-0 shadow-sm text-xs">
                                        <?php if ($pt['avatar']): 
                                            $avatarPath = $pt['avatar'];
                                            if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                                $avatarPath = '../uploads/' . $avatarPath;
                                            }
                                        ?>
                                            <img src="<?= htmlspecialchars($avatarPath) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?= strtoupper(substr($pt['name'], 0, 2)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-on-background"><?= htmlspecialchars($pt['name']) ?></div>
                                        <?php if ($pt['observation']): ?>
                                            <div class="text-[10px] text-secondary font-semibold mt-0.5"><?= htmlspecialchars($pt['observation']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="bg-worship-blue text-white px-3.5 py-1.5 rounded-lg font-bold text-xs uppercase tracking-wider shadow-sm"><?= htmlspecialchars($pt['tone']) ?></span>
                                    <form method="POST" onsubmit="return confirm('Remover este tom?')" class="m-0">
                                        <input type="hidden" name="action" value="delete_tone">
                                        <input type="hidden" name="tone_id" value="<?= $pt['id'] ?>">
                                        <button type="submit" class="p-2.5 text-rose-500 hover:bg-rose-500/10 rounded-full transition-colors flex items-center justify-center">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 border border-dashed border-outline-variant/30 rounded-2xl flex flex-col items-center justify-center">
                        <i data-lucide="mic-off" class="w-10 h-10 text-secondary mb-3 opacity-40"></i>
                        <p class="text-sm text-secondary font-bold">Nenhum tom pessoal cadastrado ainda.</p>
                        <p class="text-xs text-secondary/60 mt-1">Defina tons personalizados para cada integrante do time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Referências -->
        <div id="tab-refs" class="tab-panel hidden transition-opacity duration-300">
            <div class="bento-card-detail rounded-2xl p-6 md:p-8">
                <h3 class="text-md font-bold text-on-background mb-6 flex items-center gap-2">
                    <i data-lucide="link-2" class="w-5 h-5 text-worship-blue"></i>
                    <span>Links e Gravações de Referência</span>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    $linkDefs = [
                        ['url' => $song['link_letra'],  'type' => 'letra'],
                        ['url' => $song['link_cifra'],  'type' => 'cifra'],
                        ['url' => $song['link_audio'],  'type' => 'audio'],
                        ['url' => $song['link_video'],  'type' => 'video'],
                    ];
                    foreach ($linkDefs as $lnk):
                        $hasUrl  = !empty($lnk['url']);
                        [$label, $color, $bg, $svgPath] = detectPlatform($hasUrl ? $lnk['url'] : '', $lnk['type']);
                        $href    = $hasUrl ? htmlspecialchars($lnk['url']) : '#';
                        $opacity = $hasUrl ? 'opacity-100 border-outline-variant/30 hover:scale-[1.01] hover:shadow-md' : 'opacity-40 border-dashed border-outline-variant/20 cursor-default';
                    ?>
                        <a href="<?= $href ?>" <?= $hasUrl ? 'target="_blank" rel="noopener"' : 'onclick="return false"' ?>
                           class="interactive-scale flex items-center justify-between p-4.5 rounded-2xl border transition-all duration-300 relative group overflow-hidden <?= $opacity ?>"
                           style="background-color: <?= $bg ?>;">
                            
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-11 h-11 flex items-center justify-center rounded-full bg-white dark:bg-[#1E2024] border shrink-0 shadow-sm" style="border-color: <?= $color ?>20;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                         fill="<?= $lnk['type'] === 'audio' && str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                                         stroke="<?= in_array($lnk['type'], ['letra','cifra','video']) || !str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <?= $svgPath ?>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold truncate" style="color: <?= $color ?>;"><?= $label ?></div>
                                    <div class="text-[10px] mt-0.5 font-bold text-secondary"><?= $hasUrl ? 'Acessar link externo' : 'Não cadastrado' ?></div>
                                </div>
                            </div>
                            <?php if ($hasUrl): ?>
                                <i data-lucide="arrow-right" class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" style="color: <?= $color ?>;"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

</main>

<!-- Modals -->

<!-- Modal Add Tone -->
<div id="toneModal" class="fixed inset-0 z-50 hidden bg-black/55 backdrop-blur-md items-center justify-center px-4 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-container-low border border-outline-variant/30 w-full max-w-md rounded-3xl overflow-hidden shadow-2xl scale-95 transition-all duration-300" id="toneModalContent">
        <div class="px-6 py-5 border-b border-outline-variant/10 flex justify-between items-center bg-ghost-gray/40 dark:bg-surface-container-low">
            <h3 class="text-sm font-black text-on-background flex items-center gap-2 uppercase tracking-wide">
                <i data-lucide="plus-circle" class="w-5 h-5 text-worship-blue"></i>
                <span>Adicionar Tom Pessoal</span>
            </h3>
            <button type="button" class="text-secondary hover:bg-ghost-gray dark:hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center" onclick="closeToneModal()">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_tone">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-secondary uppercase tracking-widest mb-2">Membro do Time</label>
                    <div class="relative">
                        <select name="user_id" required class="w-full bg-ghost-gray/30 dark:bg-surface-variant/5 border border-outline-variant/30 rounded-xl px-4 py-3.5 text-xs font-bold text-on-background focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all appearance-none cursor-pointer">
                            <?php foreach ($usersList as $u): ?>
                                <option class="dark:bg-deep-navy dark:text-white" value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary opacity-75">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-secondary uppercase tracking-widest mb-2">Tom de Preferência</label>
                    <div class="relative">
                        <select name="tone" required class="w-full bg-ghost-gray/30 dark:bg-surface-variant/5 border border-outline-variant/30 rounded-xl px-4 py-3.5 text-xs font-bold text-on-background focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all appearance-none cursor-pointer">
                            <?php foreach ($musicTones as $val => $label): ?>
                                <option class="dark:bg-deep-navy dark:text-white" value="<?= $val ?>" <?= $val == ($song['tone'] ?? 'C') ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-secondary opacity-75">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-secondary uppercase tracking-widest mb-2">Observação <span class="font-bold opacity-60">(Opcional)</span></label>
                    <textarea name="observation" class="w-full bg-ghost-gray/30 dark:bg-surface-variant/5 border border-outline-variant/30 rounded-xl px-4 py-3.5 text-xs font-bold text-on-background focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all resize-none" rows="2" placeholder="Ex: Usa capo na 2ª casa..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3 pt-2">
                <button type="button" class="flex-1 py-3 px-5 border border-outline-variant/30 rounded-xl font-bold text-xs uppercase tracking-wider text-on-background hover:bg-ghost-gray/40 dark:hover:bg-surface-container active:scale-95 transition-all" onclick="closeToneModal()">Cancelar</button>
                <button type="submit" class="flex-1 py-3 px-5 bg-worship-blue text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md hover:bg-worship-blue/90 active:scale-95 transition-all">Salvar Tom</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete Song -->
<div id="deleteSongModal" class="fixed inset-0 z-50 hidden bg-black/55 backdrop-blur-md items-center justify-center px-4 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-container-low border border-outline-variant/30 w-full max-w-md rounded-3xl overflow-hidden shadow-2xl scale-95 transition-all duration-300" id="deleteSongModalContent">
        <div class="px-6 py-5 border-b border-rose-500/10 flex justify-between items-center bg-rose-500/5">
            <h3 class="text-sm font-black text-rose-500 flex items-center gap-2 uppercase tracking-wide">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <span>Excluir Música</span>
            </h3>
            <button type="button" class="text-secondary hover:bg-ghost-gray dark:hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center" onclick="closeDeleteSongModal()">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-4">
            <p class="text-sm text-on-background font-semibold">Tem certeza que deseja excluir permanentemente a música <strong><?= htmlspecialchars($song['title']) ?></strong>?</p>
            
            <div class="text-[10px] text-rose-500 bg-rose-500/5 p-4 rounded-xl border border-rose-500/10 leading-relaxed font-bold">
                Esta ação removerá permanentemente a música do repertório, incluindo todas as classificações, tags associadas e o histórico completo de tons pessoais de todos os membros do time. Esta ação não poderá ser desfeita.
            </div>
            
            <div class="mt-6 flex gap-3 pt-2">
                <button type="button" class="flex-1 py-3 px-5 border border-outline-variant/30 rounded-xl font-bold text-xs uppercase tracking-wider text-on-background hover:bg-ghost-gray/40 dark:hover:bg-surface-container active:scale-95 transition-all" onclick="closeDeleteSongModal()">Cancelar</button>
                <form method="POST" class="m-0 flex-1">
                    <input type="hidden" name="action" value="delete_song">
                    <button type="submit" class="w-full py-3 px-5 bg-rose-500 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md hover:bg-rose-500/90 active:scale-95 transition-all">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching logic
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            
            // Buttons state
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-worship-blue', 'text-white', 'shadow-sm');
                b.classList.add('text-secondary', 'hover:text-worship-blue');
            });
            btn.classList.remove('text-secondary', 'hover:text-worship-blue');
            btn.classList.add('bg-worship-blue', 'text-white', 'shadow-sm');

            // Panels state
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.add('hidden');
                panel.classList.remove('block');
            });
            document.getElementById(target).classList.remove('hidden');
            document.getElementById(target).classList.add('block');
        });
    });

    // Dropdown menu
    function toggleMenu() {
        const menu = document.getElementById('dropdown-menu');
        menu.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('dropdown-menu');
        const btn = document.getElementById('menu-btn');
        if (menu && btn && !menu.classList.contains('hidden') && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // Modals logic
    function openToneModal() {
        const modal = document.getElementById('toneModal');
        const content = document.getElementById('toneModalContent');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    // Fechar ao clicar na overlay do modal de Tom
    document.getElementById('toneModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeToneModal();
        }
    });

    function closeToneModal() {
        const modal = document.getElementById('toneModal');
        const content = document.getElementById('toneModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    }

    function confirmDeleteSong() {
        const menu = document.getElementById('dropdown-menu');
        if (menu) menu.classList.add('hidden');
        
        const modal = document.getElementById('deleteSongModal');
        const content = document.getElementById('deleteSongModalContent');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    // Fechar ao clicar na overlay do modal de Deleção
    document.getElementById('deleteSongModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteSongModal();
        }
    });

    function closeDeleteSongModal() {
        const modal = document.getElementById('deleteSongModal');
        const content = document.getElementById('deleteSongModalContent');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    }
</script>

<?php renderAppFooter(); ?>