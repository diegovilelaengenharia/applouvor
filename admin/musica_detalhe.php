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
            // Purple Ban: Deezer alterado de roxo (#a238ff) para Slate/Escuro (#0F172A)
            return ['Deezer', '#0F172A', 'rgba(15, 23, 42, 0.05)', '<path d="M18.81 11.38H22v1.88h-3.19v-1.88zm-4.57 0h3.19v1.88h-3.19v-1.88zM2 11.38h3.19v1.88H2v-1.88zm4.57 0h3.19v1.88H6.57v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88zM18.81 8H22v1.88h-3.19V8zm-4.57 0h3.19v1.88h-3.19V8zm-9.15 3.38H8.28v1.88H5.09v-1.88zm0-3.38H8.28v1.88H5.09V8zm4.57 3.38h3.19v1.88-3.19v-1.88zM9.66 8h3.19v1.88H9.66V8zm0 6.75h3.19v1.88H9.66v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88z"/>'];
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
$statusBg = 'bg-surface-container text-on-surface-variant border-surface-container-highest'; 
$statusIcon = 'check_circle';

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
        $statusBg = 'bg-error/10 text-error border-error/20';
        $statusIcon = 'local_fire_department';
    } elseif ($monthsDiff >= 3 && $monthsDiff <= 6) {
        $statusLabel = 'Geladeira';
        $statusBg = 'bg-worship-blue/10 text-worship-blue border-worship-blue/20';
        $statusIcon = 'ac_unit';
    } elseif ($monthsDiff > 6) {
        $statusLabel = 'Esquecida';
        $statusBg = 'bg-slate-100 text-slate-500 border-slate-200';
        $statusIcon = 'inventory_2';
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

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 reveal-item">
    <!-- Header with Actions (Sacred Bento Style) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 relative">
        <div class="flex gap-5 items-center">
            <div class="w-16 h-16 bg-worship-blue/10 text-worship-blue rounded-full flex items-center justify-center shrink-0 border border-worship-blue/20 shadow-sm">
                <span class="material-symbols-outlined text-[30px]">music_note</span>
            </div>
            <div>
                <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface font-bold tracking-tight leading-tight"><?= htmlspecialchars($song['title']) ?></h1>
                <div class="flex flex-wrap items-center gap-2.5 mt-2 text-body-md text-on-surface-variant/80">
                    <a href="repertorio.php?q=<?= urlencode($song['artist']) ?>" class="hover:text-worship-blue font-semibold transition-colors flex items-center gap-1">
                        <?= htmlspecialchars($song['artist']) ?>
                        <span class="material-symbols-outlined text-[16px] text-worship-blue">open_in_new</span>
                    </a>
                    <span class="text-outline/40">•</span>
                    <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold border <?= $statusBg ?>">
                        <span class="material-symbols-outlined text-[15px]"><?= $statusIcon ?></span>
                        <?= $statusLabel ?>
                    </div>
                    <span class="text-outline/40">•</span>
                    <div class="flex items-center gap-1 text-sm font-semibold" title="Última vez: <?= $lastPlayed ? (new DateTime($lastPlayed))->format('d/m/Y') : 'Nunca' ?>">
                        <span class="material-symbols-outlined text-[16px] text-outline">history</span>
                        <?= $totalExecs ?> execuções
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Contextual (Modern Touch) -->
        <div class="relative self-end md:self-center">
            <button onclick="toggleMenu()" id="menu-btn" class="interactive-scale p-3 bg-surface-container hover:bg-surface-container-high active:scale-95 rounded-full border border-surface-container-highest shadow-sm transition-all duration-200 flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px]">more_vert</span>
            </button>
            
            <div id="dropdown-menu" class="hidden absolute right-0 mt-3 w-48 bg-surface-container-lowest border border-surface-container-highest rounded-3xl shadow-xl z-50 overflow-hidden transform origin-top-right transition-all">
                <a href="musica_editar.php?id=<?= $id ?>" class="flex items-center gap-3 px-5 py-4 text-on-surface hover:bg-surface-container-low transition-colors font-body-md font-semibold">
                    <span class="material-symbols-outlined text-[20px] text-worship-blue">edit</span>
                    Editar Música
                </a>
                <button onclick="confirmDeleteSong()" class="w-full flex items-center gap-3 px-5 py-4 text-error hover:bg-error-container/10 transition-colors font-body-md font-semibold text-left border-t border-surface-container-highest">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                    Excluir Música
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation (Modern Floating Pills) -->
    <div class="flex space-x-3 mb-8 overflow-x-auto hide-scrollbar pb-2">
        <button class="interactive-scale tab-btn px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all bg-worship-blue text-white shadow-md shadow-worship-blue/10 scale-102" data-target="tab-info">
            Visão Geral
        </button>
        <button class="interactive-scale tab-btn px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all bg-surface-container text-on-surface-variant hover:bg-surface-container-high" data-target="tab-tones">
            Tons por Voz
        </button>
        <button class="interactive-scale tab-btn px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all bg-surface-container text-on-surface-variant hover:bg-surface-container-high" data-target="tab-refs">
            Referências
        </button>
    </div>

    <!-- Tab Panels -->
    <div class="tab-content relative">
        
        <!-- TAB: Visão Geral -->
        <div id="tab-info" class="tab-panel active block transition-opacity duration-300">
            <!-- Bento Grid de Metadados -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Tom Original -->
                <div class="reveal-item reveal-stagger-1 bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md hover:border-worship-blue/20 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-worship-blue/10 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-4xl">music_note</span>
                    </div>
                    <span class="font-label-sm text-[11px] font-extrabold uppercase tracking-widest text-on-surface-variant/80 mb-2">Tom Original</span>
                    <span class="text-5xl font-display-lg text-worship-blue font-bold tracking-tight"><?= $song['tone'] ?: '-' ?></span>
                </div>
                
                <!-- BPM -->
                <div class="reveal-item reveal-stagger-2 bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md hover:border-altar-gold/20 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-altar-gold/10 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-4xl">speed</span>
                    </div>
                    <span class="font-label-sm text-[11px] font-extrabold uppercase tracking-widest text-altar-gold/80 mb-2">BPM</span>
                    <span class="text-5xl font-display-lg text-altar-gold font-bold tracking-tight"><?= $song['bpm'] ?: '-' ?></span>
                </div>
                
                <!-- Duração -->
                <div class="reveal-item reveal-stagger-3 bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md hover:border-emerald-500/20 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute right-4 top-4 text-emerald-500/10 group-hover:scale-110 transition-transform duration-300">
                        <span class="material-symbols-outlined text-4xl">schedule</span>
                    </div>
                    <span class="font-label-sm text-[11px] font-extrabold uppercase tracking-widest text-emerald-500/80 mb-2">Duração</span>
                    <span class="text-5xl font-display-lg text-emerald-500 font-bold tracking-tight"><?= $song['duration'] ?: '-' ?></span>
                </div>
            </div>

            <!-- Metrônomo Ação -->
            <?php if (!empty($song['bpm'])): ?>
            <div class="flex justify-center mb-8">
                <a href="metronomo.php?bpm=<?= (int)$song['bpm'] ?>" class="interactive-scale inline-flex items-center gap-2 px-8 py-3.5 bg-worship-blue/10 text-worship-blue border border-worship-blue/20 hover:bg-worship-blue/20 rounded-full font-bold font-body-md transition-all duration-300 hover:scale-102 shadow-sm">
                    <span class="material-symbols-outlined text-[20px] animate-pulse">speed</span>
                    Abrir no Metrônomo (<?= (int)$song['bpm'] ?> BPM)
                </a>
            </div>
            <?php endif; ?>

            <!-- Seção de Classificações Bento -->
            <div class="reveal-item reveal-stagger-4 bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-8 shadow-sm">
                <h3 class="font-headline-md text-headline-md text-on-surface mb-5 font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue">label</span>
                    Classificações & TAGs
                </h3>
                <?php if (!empty($tags)): ?>
                    <div class="flex flex-wrap gap-2.5">
                        <?php foreach ($tags as $tag): ?>
                            <span class="font-label-sm text-xs uppercase tracking-wider px-4 py-2 rounded-full font-bold shadow-sm" style="background: <?= $tag['color'] ?>12; color: <?= $tag['color'] ?>; border: 1px solid <?= $tag['color'] ?>25;">
                                <?= htmlspecialchars($tag['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center text-on-surface-variant/70 border border-dashed border-surface-container-highest rounded-2xl">
                        <span class="material-symbols-outlined text-3xl mb-2 text-outline/50">label_off</span>
                        <p class="font-body-md text-sm">Nenhuma tag cadastrada nesta música.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Tons por Voz -->
        <div id="tab-tones" class="tab-panel hidden transition-opacity duration-300">
            <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-8 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline-md text-headline-md text-on-surface font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue">keyboard_voice</span>
                        Tons Pessoais
                    </h3>
                    <button onclick="openToneModal()" class="flex items-center gap-2 bg-worship-blue/10 text-worship-blue px-5 py-2.5 border border-worship-blue/15 rounded-full font-bold hover:bg-worship-blue/20 active:scale-95 transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Adicionar Tom
                    </button>
                </div>

                <?php if (!empty($personalTones)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($personalTones as $pt): ?>
                            <div class="reveal-item flex justify-between items-center p-5 bg-surface-container-lowest border border-surface-container-highest rounded-2xl hover:border-worship-blue/30 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-worship-blue/10 rounded-full flex items-center justify-center font-bold text-worship-blue overflow-hidden border border-worship-blue/25 shrink-0 shadow-sm">
                                        <?php if ($pt['avatar']): 
                                            $avatarPath = $pt['avatar'];
                                            if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                                $avatarPath = '../uploads/' . $avatarPath;
                                            }
                                        ?>
                                            <img src="<?= htmlspecialchars($avatarPath) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?= strtoupper(substr($pt['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-body-md font-bold text-on-surface"><?= htmlspecialchars($pt['name']) ?></div>
                                        <?php if ($pt['observation']): ?>
                                            <div class="font-label-sm text-on-surface-variant text-xs mt-0.5"><?= htmlspecialchars($pt['observation']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="bg-worship-blue text-white px-4 py-1.5 rounded-full font-label-sm font-bold text-sm shadow-sm tracking-tight"><?= htmlspecialchars($pt['tone']) ?></span>
                                    <form method="POST" onsubmit="return confirm('Remover este tom?')" class="m-0">
                                        <input type="hidden" name="action" value="delete_tone">
                                        <input type="hidden" name="tone_id" value="<?= $pt['id'] ?>">
                                        <button type="submit" class="p-2.5 text-error hover:bg-error/10 rounded-full transition-colors flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 bg-surface-container-lowest border border-dashed border-surface-container-highest rounded-2xl shadow-sm flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-5xl text-outline/50 mb-4">mic_off</span>
                        <p class="font-body-md text-on-surface-variant/80 font-semibold">Nenhum tom pessoal cadastrado ainda.</p>
                        <p class="text-xs text-on-surface-variant/60 mt-1">Defina tons personalizados para cada integrante do time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: Referências -->
        <div id="tab-refs" class="tab-panel hidden transition-opacity duration-300">
            <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-8 shadow-sm">
                <h3 class="font-headline-md text-headline-md text-on-surface mb-6 font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue">link</span>
                    Links e Gravações de Referência
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
                        $opacity = $hasUrl ? 'opacity-100 border-surface-container-highest hover:scale-[1.01] hover:shadow-md' : 'opacity-40 border-dashed border-surface-container-highest cursor-default';
                    ?>
                        <a href="<?= $href ?>" <?= $hasUrl ? 'target="_blank" rel="noopener"' : 'onclick="return false"' ?>
                           class="interactive-scale flex items-center gap-5 p-5 rounded-2xl border transition-all duration-300 relative group overflow-hidden <?= $opacity ?>"
                           style="background-color: <?= $bg ?>;">
                            
                            <div class="w-12 h-12 flex items-center justify-center rounded-full bg-white border shrink-0 shadow-sm" style="border-color: <?= $color ?>20;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                     fill="<?= $lnk['type'] === 'audio' && str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                                     stroke="<?= in_array($lnk['type'], ['letra','cifra','video']) || !str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <?= $svgPath ?>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-body-md font-bold truncate" style="color: <?= $color ?>;"><?= $label ?></div>
                                <div class="font-label-sm text-xs mt-0.5 font-semibold text-on-surface-variant/80"><?= $hasUrl ? 'Acessar link externo' : 'Não cadastrado' ?></div>
                            </div>
                            <?php if ($hasUrl): ?>
                                <span class="material-symbols-outlined text-[20px] transition-transform duration-300 group-hover:translate-x-1" style="color: <?= $color ?>;">arrow_forward</span>
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
<div id="toneModal" class="fixed inset-0 z-50 hidden bg-black/45 backdrop-blur-md items-center justify-center px-4 transition-opacity duration-300">
    <div class="bg-surface-container-lowest border border-surface-container-highest w-full max-w-md rounded-3xl overflow-hidden shadow-2xl scale-95 transition-all duration-300" id="toneModalContent">
        <div class="px-6 py-5 border-b border-surface-container-highest flex justify-between items-center bg-surface-container-low">
            <h3 class="font-headline-md font-bold text-on-surface flex items-center gap-2 text-lg">
                <span class="material-symbols-outlined text-worship-blue">add_circle</span>
                Adicionar Tom Pessoal
            </h3>
            <button type="button" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center" onclick="closeToneModal()">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_tone">
            
            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant/80 font-bold mb-1.5 text-xs uppercase tracking-wider">Membro do Time</label>
                    <select name="user_id" required class="w-full bg-surface-container border border-surface-container-highest rounded-2xl px-4 py-3.5 font-body-md text-on-surface focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all appearance-none">
                        <?php foreach ($usersList as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant/80 font-bold mb-1.5 text-xs uppercase tracking-wider">Tom de Preferência</label>
                    <select name="tone" required class="w-full bg-surface-container border border-surface-container-highest rounded-2xl px-4 py-3.5 font-body-md text-on-surface focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all appearance-none">
                        <?php foreach ($musicTones as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $val == ($song['tone'] ?? 'C') ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant/80 font-bold mb-1.5 text-xs uppercase tracking-wider">Observação <span class="font-normal text-xs text-on-surface-variant/60">(Opcional)</span></label>
                    <textarea name="observation" class="w-full bg-surface-container border border-surface-container-highest rounded-2xl px-4 py-3.5 font-body-md text-on-surface focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all resize-none" rows="2" placeholder="Ex: Usa capo na 2ª casa..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3 pt-2">
                <button type="button" class="flex-1 py-3 px-5 border border-surface-container-highest rounded-full font-bold text-sm text-on-surface hover:bg-surface-container-high active:scale-95 transition-all" onclick="closeToneModal()">Cancelar</button>
                <button type="submit" class="flex-1 py-3 px-5 bg-worship-blue text-white rounded-full font-bold text-sm shadow-md hover:bg-worship-blue/90 hover:shadow-worship-blue/10 active:scale-95 transition-all">Salvar Tom</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete Song -->
<div id="deleteSongModal" class="fixed inset-0 z-50 hidden bg-black/45 backdrop-blur-md items-center justify-center px-4 transition-opacity duration-300">
    <div class="bg-surface-container-lowest border border-surface-container-highest w-full max-w-md rounded-3xl overflow-hidden shadow-2xl scale-95 transition-all duration-300" id="deleteSongModalContent">
        <div class="px-6 py-5 border-b border-error/15 flex justify-between items-center bg-error/5">
            <h3 class="font-headline-md font-bold text-error flex items-center gap-2 text-lg">
                <span class="material-symbols-outlined">warning</span>
                Excluir Música
            </h3>
            <button type="button" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center" onclick="closeDeleteSongModal()">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-6 space-y-4">
            <p class="font-body-md text-on-surface text-base">Tem certeza que deseja excluir permanentemente a música <strong><?= htmlspecialchars($song['title']) ?></strong>?</p>
            
            <div class="text-xs text-error bg-error/5 p-4 rounded-2xl border border-error/10 leading-relaxed font-semibold">
                Esta ação removerá permanentemente a música do repertório, incluindo todas as classificações, tags associadas e o histórico completo de tons pessoais de todos os membros do time. Esta ação não poderá ser desfeita.
            </div>
            
            <div class="mt-6 flex gap-3 pt-2">
                <button type="button" class="flex-1 py-3 px-5 border border-surface-container-highest rounded-full font-bold text-sm text-on-surface hover:bg-surface-container-high active:scale-95 transition-all" onclick="closeDeleteSongModal()">Cancelar</button>
                <form method="POST" class="m-0 flex-1">
                    <input type="hidden" name="action" value="delete_song">
                    <button type="submit" class="w-full py-3 px-5 bg-error text-on-error rounded-full font-bold text-sm shadow-md hover:bg-error/90 active:scale-95 transition-all">Excluir</button>
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
                b.classList.remove('bg-worship-blue', 'text-white', 'shadow-md', 'shadow-worship-blue/10', 'scale-102');
                b.classList.add('bg-surface-container', 'text-on-surface-variant', 'hover:bg-surface-container-high');
            });
            btn.classList.remove('bg-surface-container', 'text-on-surface-variant', 'hover:bg-surface-container-high');
            btn.classList.add('bg-worship-blue', 'text-white', 'shadow-md', 'shadow-worship-blue/10', 'scale-102');

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