<?php
// admin/escala_detalhe.php - Redesign V3 (Standard & Clean) with Engagement Features & Tailwind
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// Helper para ícones de instrumentos
function getInstrumentIcon($instrument) {
    $inst = mb_strtolower($instrument, 'UTF-8');
    if (strpos($inst, 'vocal') !== false || strpos($inst, 'voz') !== false || strpos($inst, 'cantor') !== false) return 'mic';
    if (strpos($inst, 'violão') !== false || strpos($inst, 'guitarra') !== false || strpos($inst, 'guitar') !== false) return 'nightlight'; // Usando moon/nightlight como guitarra
    if (strpos($inst, 'bateria') !== false || strpos($inst, 'cajon') !== false) return 'album';
    if (strpos($inst, 'teclado') !== false || strpos($inst, 'piano') !== false) return 'piano'; 
    if (strpos($inst, 'baixo') !== false) return 'music_note'; 
    if (strpos($inst, 'sax') !== false || strpos($inst, 'sopra') !== false) return 'air';
    return 'music_note'; // Fallback
}

// Helper para classificar em Ministérios
function getMinistryGroup($instrument) {
    $inst = mb_strtolower($instrument, 'UTF-8');
    if (strpos($inst, 'vocal') !== false || strpos($inst, 'voz') !== false || strpos($inst, 'cantor') !== false || strpos($inst, 'ministro') !== false) {
        return 'Vocal / Vozes';
    }
    if (strpos($inst, 'violão') !== false || strpos($inst, 'guitarra') !== false || strpos($inst, 'baixo') !== false || strpos($inst, 'teclado') !== false || strpos($inst, 'piano') !== false || strpos($inst, 'synth') !== false || strpos($inst, 'guitar') !== false) {
        return 'Harmonia / Cordas';
    }
    if (strpos($inst, 'bateria') !== false || strpos($inst, 'drum') !== false || strpos($inst, 'cajon') !== false || strpos($inst, 'percussão') !== false) {
        return 'Ritmo / Percussão';
    }
    return 'Som & Apoio';
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

require_once '../src/classes/ScheduleRepository.php';
require_once '../src/classes/NotificationService.php';
$scheduleRepo = new \App\Repositories\ScheduleRepository($pdo);
$notificationService = new \App\Services\NotificationService($pdo);

// --- ENGAGEMENT ACTIONS (Check-in & Comments) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }
    
    // Toggle Rehearsal
    if ($_POST['action'] === 'toggle_rehearsal') {
        $newState = $_POST['state'] === '1' ? 1 : 0;
        $scheduleRepo->toggleRehearsal($id, $_SESSION['user_id'], $newState);
        header("Location: escala_detalhe.php?id=$id");
        exit;
    }

    // Add Comment
    if ($_POST['action'] === 'add_comment') {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $scheduleRepo->addComment($id, $_SESSION['user_id'], $comment);
        }
        header("Location: escala_detalhe.php?id=$id#comments");
        exit;
    }

    // Delete Comment
    if ($_POST['action'] === 'delete_comment') {
        $cmtId = $_POST['comment_id'];
        $scheduleRepo->deleteComment($cmtId, $_SESSION['user_id'], $_SESSION['user_role'] === 'admin');
        header("Location: escala_detalhe.php?id=$id#comments");
        exit;
    }
}

// --- LOGICA DE POST/SALVAR MANTIDA (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

    if (isset($_POST['delete_schedule']) && $_SESSION['user_role'] === 'admin') {
        try {
            $scheduleRepo->deleteSchedule($id);
            header("Location: escalas.php?msg=deleted");
            exit;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        try {
            $data = [
                'event_type' => $_POST['event_type'],
                'event_date' => $_POST['event_date'],
                'event_time' => $_POST['event_time'],
                'notes'      => $_POST['notes'] ?? ''
            ];
            $members = $_POST['members'] ?? [];
            $songs = $_POST['songs'] ?? [];
            
            $scheduleRepo->updateSchedule($id, $data, $members, $songs);

            try {
                $pushTargets = $scheduleRepo->getPendingParticipantsPushSubscriptions($id);
                if (!empty($pushTargets)) {
                    $notificationService->sendConvocNotification(
                        $id,
                        $_POST['event_type'],
                        $_POST['event_date'],
                        $_POST['event_time'],
                        $pushTargets
                    );
                }
            } catch (Exception $pushEx) {
                error_log('Push convocação falhou: ' . $pushEx->getMessage());
            }

            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

// --- BUSCAR DADOS ---
$schedule = $scheduleRepo->getById($id);
if (!$schedule) die("Escala não encontrada.");

$date = new DateTime($schedule['event_date']);
$diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Buscar Membros
$team = $scheduleRepo->getParticipants($id);
$teamIds = array_column($team, 'user_id');

// Agrupar a equipe por Ministérios/Funções
$groupedTeam = [];
foreach ($team as $member) {
    $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
    $groupName = getMinistryGroup($instr);
    $groupedTeam[$groupName][] = $member;
}
uksort($groupedTeam, function($a, $b) {
    $order = ['Vocal / Vozes' => 1, 'Harmonia / Cordas' => 2, 'Ritmo / Percussão' => 3, 'Som & Apoio' => 4];
    return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
});

// Verificar se sou membro desta escala
$myMemberData = null;
foreach($team as $m) {
    if ($m['user_id'] == $_SESSION['user_id']) {
        $myMemberData = $m;
        break;
    }
}

// Buscar Músicas
$songs = $scheduleRepo->getSongs($id);

// Buscar Comentários
$comments = $scheduleRepo->getComments($id);

// Buscar Roteiro de Culto
$roteiro = $scheduleRepo->getRoteiro($id);

// Mapa de custom_tone por song_id (para override nos cards de repertório)
$customToneMap = [];
foreach ($roteiro as $rItem) {
    if ($rItem['item_type'] === 'musica' && $rItem['song_id'] && !empty($rItem['custom_tone'])) {
        $customToneMap[(int)$rItem['song_id']] = $rItem['custom_tone'];
    }
}

// Listas completas para Edit Mode
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color, avatar FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$isEditable = isset($_GET['edit']) && $_GET['edit'] == '1' && $_SESSION['user_role'] === 'admin';

renderAppHeader('Detalhes da Escala');
?>


<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "surface-container": "#eeeeee",
                      "on-error": "#ffffff",
                      "tertiary": "#755700",
                      "ghost-gray": "#F4F4F5",
                      "surface-container-low": "#f3f3f3",
                      "on-background": "#1a1c1c",
                      "outline-variant": "#c1c6d6",
                      "inverse-surface": "#2f3131",
                      "altar-gold": "#FFC107",
                      "on-primary-fixed-variant": "#004590",
                      "tertiary-fixed": "#ffdf9e",
                      "primary-fixed-dim": "#abc7ff",
                      "secondary-fixed-dim": "#c7c6cb",
                      "on-secondary-fixed": "#1a1b1f",
                      "background": "#f9f9f9",
                      "worship-blue": "#2E7EED",
                      "on-secondary-fixed-variant": "#46464b",
                      "error": "#ba1a1a",
                      "surface-variant": "#e2e2e2",
                      "surface-container-lowest": "#ffffff",
                      "on-tertiary-fixed-variant": "#5b4300",
                      "surface-tint": "#005cbc",
                      "on-primary-fixed": "#001b3f",
                      "on-tertiary": "#ffffff",
                      "surface-bright": "#f9f9f9",
                      "primary": "#0059b8",
                      "surface": "#f9f9f9",
                      "surface-container-high": "#e8e8e8",
                      "inverse-primary": "#abc7ff",
                      "tertiary-fixed-dim": "#fabd00",
                      "deep-navy": "#1A1B1F",
                      "primary-container": "#1872e0",
                      "error-container": "#ffdad6",
                      "on-tertiary-fixed": "#261a00",
                      "on-secondary": "#ffffff",
                      "on-surface": "#1a1c1c",
                      "outline": "#727785",
                      "surface-container-highest": "#e2e2e2",
                      "on-error-container": "#93000a",
                      "on-surface-variant": "#414753",
                      "secondary": "#5e5e63",
                      "surface-dim": "#dadada",
                      "tertiary-container": "#946f00",
                      "on-primary-container": "#fefcff",
                      "secondary-container": "#e0dfe4",
                      "on-tertiary-container": "#fffbff",
                      "on-secondary-container": "#626267",
                      "primary-fixed": "#d7e2ff",
                      "inverse-on-surface": "#f0f1f1",
                      "secondary-fixed": "#e3e2e7",
                      "on-primary": "#ffffff"
              },
              "borderRadius": {
                      "DEFAULT": "0.25rem",
                      "lg": "0.5rem",
                      "xl": "0.75rem",
                      "full": "9999px"
              },
              "spacing": {
                      "unit": "8px",
                      "max-width": "1200px",
                      "margin-mobile": "20px",
                      "margin-desktop": "64px",
                      "gutter": "16px"
              },
              "fontFamily": {
                      "body-lg": ["Open Sans"],
                      "body-md": ["Open Sans"],
                      "headline-md": ["Hanken Grotesk"],
                      "label-sm": ["Open Sans"],
                      "display-lg-mobile": ["Hanken Grotesk"],
                      "lyric-focus": ["Open Sans"],
                      "display-lg": ["Hanken Grotesk"]
              },
              "fontSize": {
                      "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                      "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                      "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                      "label-sm": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700"}],
                      "display-lg-mobile": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700"}],
                      "lyric-focus": ["28px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                      "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}]
              }
            },
          },
        }
</script>

<style>
    .bento-border-top {
        border-top: 4px solid #2E7EED;
    }
    .timeline-line::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 24px;
        bottom: 0;
        width: 2px;
        background: #E2E2E2;
    }
    .timeline-item:last-child .timeline-line::before {
        display: none;
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<!-- Worship Progress Bar at top -->
<div class="fixed top-0 left-0 w-full h-1 bg-ghost-gray z-[60]">
    <div class="h-full bg-worship-blue transition-all duration-1000" style="width: 100%;"></div>
</div>

<main class="mt-8 pb-32 max-w-max-width mx-auto px-margin-mobile md:px-margin-desktop space-y-8 pt-4 <?= ($myMemberData && $myMemberData['status'] === 'pending') ? 'pb-40' : '' ?>">
    
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="escalas.php" class="active:scale-95 transition-transform p-2 rounded-full hover:bg-surface-container-low text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </a>
            <div class="flex flex-col">
                <h1 class="font-headline-md text-headline-md font-bold text-deep-navy">Detalhes da Escala</h1>
                <span class="text-label-sm text-secondary uppercase tracking-widest"><?= htmlspecialchars($schedule['event_type']) ?></span>
            </div>
        </div>
        
        <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
        <div class="flex gap-2">
            <a href="?id=<?= $id ?>&edit=1" class="w-10 h-10 bg-surface-container-lowest border border-surface-variant hover:border-worship-blue hover:text-worship-blue text-on-surface rounded-full flex items-center justify-center hover:bg-surface-container-low transition-all active:scale-95 shadow-sm">
                <span class="material-symbols-outlined text-[20px]">edit</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-body-md font-bold">Alterações salvas com sucesso!</span>
        </div>
    <?php endif; ?>

    <!-- ================= EDIT MODE ================= -->
    <?php if ($isEditable): ?>
        
        <form method="POST" id="editForm" class="bg-surface-container-lowest border border-surface-variant rounded-xl p-6 md:p-8 space-y-8 shadow-sm relative overflow-hidden">
            <!-- Edit mode indicator -->
            <div class="absolute top-0 right-0 bg-worship-blue text-white px-4 py-1.5 rounded-bl-xl font-label-sm font-bold text-[10px] tracking-wider uppercase flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">edit</span> Modo Edição
            </div>

            <?= App\AuthMiddleware::csrfField() ?>
            <input type="hidden" name="save_changes" value="1">
            
            <!-- 1. Event Info Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-surface-variant pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-deep-navy flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue">info</span>
                        Info Básica
                    </h3>
                    <button type="button" class="text-worship-blue font-label-sm font-bold hover:underline" onclick="openInfoModal()">Editar Tudo</button>
                </div>
                
                <div class="bg-ghost-gray border border-outline-variant rounded-xl p-4 cursor-pointer hover:bg-surface-container-low transition-colors" onclick="openInfoModal()">
                    <div id="summary-type" class="font-headline-md font-bold text-deep-navy"><?= htmlspecialchars($schedule['event_type']) ?></div>
                    <div class="flex items-center gap-4 mt-2 font-body-sm text-secondary">
                        <span id="summary-date" class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_month</span> <?= date('d/m/Y', strtotime($schedule['event_date'])) ?></span>
                        <span id="summary-time" class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> <?= substr($schedule['event_time'], 0, 5) ?></span>
                    </div>
                    <?php if($schedule['notes']): ?>
                    <div id="summary-notes" class="mt-3 pt-3 border-t border-outline-variant font-body-sm text-secondary italic flex gap-2">
                        <span class="material-symbols-outlined text-[14px] mt-0.5">sticky_note_2</span> 
                        <span><?= htmlspecialchars($schedule['notes']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Members Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-surface-variant pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-deep-navy flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue">groups</span>
                        Participantes
                    </h3>
                    <button type="button" class="text-worship-blue font-label-sm font-bold hover:underline" onclick="document.getElementById('modalMembers').classList.remove('hidden'); document.getElementById('modalMembers').classList.add('flex');">Gerenciar</button>
                </div>
                
                <div id="members-bag" class="flex flex-wrap gap-2 min-h-[60px] bg-ghost-gray border border-outline-variant border-dashed rounded-xl p-4">
                    <?php foreach($teamIds as $tid): 
                        $uName = ''; foreach($allUsers as $u) if($u['id']==$tid) $uName=$u['name'];
                    ?>
                        <span class="inline-flex items-center gap-2 bg-white border border-outline-variant px-3 py-1.5 rounded-full font-label-sm font-bold text-deep-navy" id="m-badge-<?= $tid ?>">
                            <?= htmlspecialchars($uName) ?> 
                            <button type="button" class="text-secondary hover:text-error transition-colors" onclick="removeMember(<?= $tid ?>)">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                            <input type="hidden" name="members[]" value="<?= $tid ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Songs Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-surface-variant pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-deep-navy flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue">queue_music</span>
                        Repertório
                    </h3>
                    <button type="button" class="text-worship-blue font-label-sm font-bold hover:underline" onclick="document.getElementById('modalSongs').classList.remove('hidden'); document.getElementById('modalSongs').classList.add('flex');">Gerenciar</button>
                </div>
                
                <div id="songs-bag" class="space-y-2 min-h-[60px] bg-ghost-gray border border-outline-variant border-dashed rounded-xl p-4">
                    <?php foreach($songs as $sg): ?>
                        <div class="flex items-center justify-between bg-white border border-outline-variant p-3 rounded-lg" id="s-badge-<?= $sg['song_id'] ?>">
                            <span class="font-label-sm font-bold text-deep-navy"><?= htmlspecialchars($sg['title']) ?> <span class="text-secondary font-normal">- <?= htmlspecialchars($sg['artist']) ?></span></span>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="songs[]" value="<?= $sg['song_id'] ?>">
                                <button type="button" class="text-secondary hover:text-error transition-colors p-1" onclick="removeSong(<?= $sg['song_id'] ?>)">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Roteiro Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-surface-variant pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-deep-navy flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue">list_alt</span>
                        Roteiro de Culto
                    </h3>
                    <button type="button" class="text-worship-blue font-label-sm font-bold hover:underline flex items-center gap-1" onclick="openRoteiroModal()">
                        <span class="material-symbols-outlined text-[16px]">add</span> Adicionar Item
                    </button>
                </div>
                
                <div id="roteiro-list" class="space-y-2">
                    <div id="roteiro-empty-state" class="text-center p-8 bg-ghost-gray border border-outline-variant border-dashed rounded-xl font-body-sm text-secondary">
                        Nenhum item no roteiro. Clique em "Adicionar Item".
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-surface-variant">
                 <a href="?id=<?= $id ?>" class="py-3 px-4 bg-ghost-gray border border-outline-variant text-deep-navy rounded-lg font-label-sm font-bold text-center hover:bg-surface-container transition-colors">Cancelar</a>
                 <button type="button" onclick="if(confirm('Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.')) document.getElementById('delForm').submit()" class="py-3 px-4 bg-error-container/20 text-error rounded-lg font-label-sm font-bold hover:bg-error hover:text-white transition-colors text-center">Excluir Escala</button>
                 <button type="submit" class="py-3 px-4 bg-worship-blue text-white rounded-lg font-label-sm font-bold shadow-md hover:bg-primary-container transition-all transform active:scale-95 text-center">Salvar Alterações</button>
            </div>
        </form>
        <form id="delForm" method="POST" class="hidden"><input type="hidden" name="delete_schedule" value="1"></form>

    <!-- ================= VIEW MODE ================= -->
    <?php else: ?>
        
        <!-- Hero Bento Card -->
        <section class="bg-surface-container-lowest rounded-xl border border-surface-variant bento-border-top overflow-hidden shadow-sm">
            <div class="p-6 md:p-8 flex flex-col md:flex-row gap-6 md:items-center">
                <div class="flex flex-col items-center justify-center bg-ghost-gray rounded-lg w-24 h-24 flex-shrink-0 border border-outline-variant shadow-inner">
                    <span class="text-label-sm text-secondary uppercase font-bold"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></span>
                    <span class="text-display-lg-mobile font-bold text-worship-blue leading-none"><?= $date->format('d') ?></span>
                </div>
                <div class="flex-grow space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-ghost-gray border border-outline-variant rounded-full text-label-sm font-bold text-secondary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-worship-blue">calendar_month</span>
                            <?= $diaSemana ?>
                        </span>
                        <span class="px-3 py-1 bg-ghost-gray border border-outline-variant rounded-full text-label-sm font-bold text-secondary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-worship-blue">schedule</span>
                            <?= substr($schedule['event_time'], 0, 5) ?>
                        </span>
                    </div>
                    <h2 class="font-display-lg-mobile md:text-headline-md font-bold text-deep-navy"><?= htmlspecialchars($schedule['event_type']) ?></h2>
                    
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="escala_setlist.php?id=<?= (int)$id ?>" class="bg-worship-blue text-white px-6 py-2.5 rounded-lg font-bold flex items-center gap-2 active:scale-95 transition-all shadow-sm hover:bg-primary-container">
                            <span class="material-symbols-outlined text-[20px]">queue_music</span>
                            Acessar Setlist
                        </a>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="?id=<?= $id ?>&edit=1" class="border border-deep-navy text-deep-navy px-6 py-2.5 rounded-lg font-bold active:scale-95 transition-colors hover:bg-ghost-gray">
                            Editar Escala
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($schedule['notes']): ?>
            <div class="border-t border-surface-variant p-6 bg-surface-bright">
                <details class="group" open>
                    <summary class="flex justify-between items-center cursor-pointer list-none">
                        <span class="text-label-sm font-bold text-secondary flex items-center gap-2 uppercase">
                            <span class="material-symbols-outlined text-[18px]">info</span>
                            Observações Gerais
                        </span>
                        <span class="material-symbols-outlined transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="mt-4 text-body-md text-on-surface-variant leading-relaxed font-body-md whitespace-pre-wrap">
                        <?= htmlspecialchars($schedule['notes']) ?>
                    </div>
                </details>
            </div>
            <?php endif; ?>
        </section>

        <!-- Check-in Action Card -->
        <?php if ($myMemberData): ?>
        <section class="bg-surface-container-lowest border border-surface-variant rounded-xl p-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-worship-blue/10 text-worship-blue rounded-full flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined">headphones</span>
                </div>
                <div>
                    <h3 class="font-bold text-body-lg text-deep-navy">
                        <?= $myMemberData['is_rehearsed'] ? 'Repertório estudado!' : 'Prepare-se para ouvir' ?>
                    </h3>
                    <p class="text-on-surface-variant text-body-md">
                        <?= $myMemberData['is_rehearsed'] ? 'Você está pronto para o ensaio ministerial.' : 'Marque quando tiver estudado as músicas.' ?>
                    </p>
                </div>
            </div>
            <form method="POST" class="w-full md:w-auto">
                <?= App\AuthMiddleware::csrfField() ?>
                <input type="hidden" name="action" value="toggle_rehearsal">
                <input type="hidden" name="state" value="<?= $myMemberData['is_rehearsed'] ? '0' : '1' ?>">
                <button type="submit" class="w-full md:w-auto bg-white border border-outline-variant hover:border-worship-blue hover:text-worship-blue text-on-surface font-bold px-8 py-3 rounded-lg transition-all active:scale-95 flex items-center justify-center gap-2">
                    <?php if ($myMemberData['is_rehearsed']): ?>
                        <span class="material-symbols-outlined text-[20px]">undo</span> Desfazer
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[20px]">check_circle</span> Já estudei
                    <?php endif; ?>
                </button>
            </form>
        </section>
        <?php endif; ?>

        <!-- Team Grid -->
        <section class="space-y-4">
            <h3 class="font-headline-md text-deep-navy">Equipe Ministerial</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if(empty($groupedTeam)): ?>
                    <div class="col-span-full bg-surface-container-lowest border border-surface-variant rounded-xl p-6 text-center font-body-sm text-secondary">Nenhum participante definido nesta escala.</div>
                <?php else: ?>
                    <?php 
                    $ministryIcons = [
                        'Vocal / Vozes' => 'mic',
                        'Harmonia / Cordas' => 'nightlight', // violão/guitarra
                        'Ritmo / Percussão' => 'album', // bateria
                        'Som & Apoio' => 'tune' // sliders
                    ];
                    foreach($groupedTeam as $ministryName => $members): 
                        $minIcon = $ministryIcons[$ministryName] ?? 'groups';
                    ?>
                    <div class="bg-surface-container-lowest border border-surface-variant rounded-xl p-5 space-y-4 shadow-sm hover:border-worship-blue/40 transition-all">
                        <div class="flex justify-between items-center border-b border-ghost-gray pb-2">
                            <h4 class="font-bold text-label-sm uppercase text-secondary flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-worship-blue"><?= $minIcon ?></span>
                                <?= htmlspecialchars($ministryName) ?>
                            </h4>
                            <span class="text-label-sm bg-ghost-gray px-2 py-0.5 rounded border border-outline-variant font-bold text-secondary"><?= count($members) ?></span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach($members as $member):
                                $memberStatus = $member['status'] ?? 'pending';
                                $statusColors = [
                                    'confirmed' => 'bg-green-500',
                                    'declined' => 'bg-red-500',
                                    'absent' => 'bg-red-500',
                                    'pending' => 'bg-yellow-500'
                                ];
                                $statusLabels = [
                                    'confirmed' => 'Confirmado',
                                    'declined' => 'Recusado',
                                    'absent' => 'Ausente',
                                    'pending' => 'Pendente'
                                ];
                                $sColor = $statusColors[$memberStatus] ?? 'bg-yellow-500';
                                $sLabel = $statusLabels[$memberStatus] ?? 'Pendente';
                                $initials = strtoupper(substr($member['name'], 0, 2));
                                $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
                                
                                $avatarPath = $member['avatar'];
                                if ($avatarPath && strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                    $avatarPath = '../uploads/' . $avatarPath;
                                }
                            ?>
                            <div class="flex items-center justify-between group-hover:bg-ghost-gray p-1 rounded transition-colors">
                                <div class="flex items-center gap-3">
                                    <?php if($avatarPath): ?>
                                        <img alt="<?= htmlspecialchars($member['name']) ?>" class="w-8 h-8 rounded-full object-cover border border-outline-variant shadow-sm" src="<?= htmlspecialchars($avatarPath) ?>"/>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-ghost-gray border border-outline-variant text-deep-navy flex items-center justify-center font-bold text-xs shadow-sm"><?= $initials ?></div>
                                    <?php endif; ?>
                                    <div class="flex flex-col">
                                        <span class="text-body-md font-semibold text-deep-navy"><?= htmlspecialchars($member['name']) ?></span>
                                        <span class="text-[10px] text-secondary font-bold"><?= htmlspecialchars($instr) ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if(isset($member['is_leader']) && $member['is_leader']): ?>
                                        <span class="text-[9px] bg-altar-gold/20 text-tertiary-container px-1.5 py-0.5 rounded font-bold uppercase">Líder</span>
                                    <?php endif; ?>
                                    <div class="w-2.5 h-2.5 rounded-full <?= $sColor ?> shadow-sm" title="<?= $sLabel ?>"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Repertoire Section -->
        <section class="space-y-4">
            <div class="flex justify-between items-end px-2">
                <h3 class="font-headline-md text-deep-navy">Repertório</h3>
                <span class="text-label-sm text-secondary uppercase font-bold"><?= count($songs) ?> Músicas</span>
            </div>
            <div class="space-y-3">
                <?php if (empty($songs)): ?>
                    <div class="bg-surface-container-lowest border border-surface-variant rounded-xl p-6 text-center font-body-sm text-secondary">Nenhuma música adicionada a esta escala.</div>
                <?php else: ?>
                    <?php foreach ($songs as $index => $song):
                        $resolvedTone = $customToneMap[(int)$song['song_id']] ?? null;
                        $isCustomTone = !empty($resolvedTone);
                        $toneDisplay  = $isCustomTone ? $resolvedTone : ($song['tone'] ?? '');
                        $initial = strtoupper(substr($song['title'], 0, 1));
                    ?>
                    <div class="group bg-surface-container-lowest border border-surface-variant rounded-xl p-4 flex items-center gap-4 hover:border-worship-blue transition-all shadow-sm">
                        <div class="w-6 text-label-sm font-bold text-secondary text-center"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="w-12 h-12 bg-deep-navy text-white rounded flex items-center justify-center font-bold text-xl shadow-sm select-none"><?= $initial ?></div>
                        <div class="flex-grow min-w-0">
                            <h4 class="font-bold text-body-md text-deep-navy leading-tight truncate">
                                <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>" class="hover:text-worship-blue transition-colors"><?= htmlspecialchars($song['title']) ?></a>
                            </h4>
                            <p class="text-on-surface-variant text-[14px] truncate"><?= htmlspecialchars($song['artist']) ?></p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <?php if ($toneDisplay): ?>
                                <span class="px-2 py-1 bg-ghost-gray border border-outline-variant rounded text-label-sm font-bold text-deep-navy <?= $isCustomTone ? 'border-worship-blue text-worship-blue bg-worship-blue/5' : '' ?>"><?= htmlspecialchars($toneDisplay) ?></span>
                            <?php endif; ?>
                            <div class="flex gap-1">
                                <?php if(!empty($song['link_letra'])): ?>
                                    <a href="<?= htmlspecialchars($song['link_letra']) ?>" target="_blank" class="p-2 hover:bg-surface-container rounded-full text-on-surface-variant transition-colors" title="Letra"><span class="material-symbols-outlined text-[20px]">lyrics</span></a>
                                <?php endif; ?>
                                <?php if(!empty($song['link_cifra'])): ?>
                                    <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="p-2 hover:bg-surface-container rounded-full text-on-surface-variant transition-colors" title="Cifra"><span class="material-symbols-outlined text-[20px]">queue_music</span></a>
                                <?php endif; ?>
                                <a href="<?= $song['link_video'] ?: ($song['link_audio'] ?: 'https://www.youtube.com/results?search_query='.urlencode($song['title'].' '.$song['artist'])) ?>" target="_blank" class="p-2 hover:bg-worship-blue/10 hover:text-worship-blue rounded-full text-on-surface-variant transition-colors" title="Ouvir"><span class="material-symbols-outlined text-[20px]">play_arrow</span></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Service Timeline (Roteiro) -->
        <?php if (!empty($roteiro)): ?>
        <section class="space-y-4">
            <h3 class="font-headline-md text-deep-navy px-2">Roteiro do Culto</h3>
            <div class="space-y-0 relative">
                <?php
                $roteiroIcons = [
                    'musica'    => 'music_note',
                    'oracao'    => 'pan_tool',
                    'palavra'   => 'menu_book',
                    'anuncio'   => 'campaign',
                    'intervalo' => 'local_cafe',
                    'livre'     => 'more_horiz',
                ];
                $roteiroColors = [
                    'musica'    => 'bg-worship-blue',
                    'oracao'    => 'bg-altar-gold',
                    'palavra'   => 'bg-deep-navy',
                    'anuncio'   => 'bg-orange-500',
                    'intervalo' => 'bg-amber-700',
                    'livre'     => 'bg-secondary',
                ];
                foreach ($roteiro as $idx => $item):
                    $icon  = $roteiroIcons[$item['item_type']]  ?? 'more_horiz';
                    $color = $roteiroColors[$item['item_type']] ?? 'bg-secondary';
                    $isMusic = $item['item_type'] === 'musica';
                    $displayTitle = ($isMusic && $item['song_title']) ? $item['song_title'] : ($item['title'] ?: $item['item_type']);
                    $displayTone = $isMusic ? ((!empty($item['custom_tone'])) ? $item['custom_tone'] : ($item['song_tone'] ?? null)) : null;
                ?>
                <div class="relative pl-10 pb-8 timeline-item">
                    <div class="timeline-line"></div>
                    <div class="absolute left-0 top-0 w-6 h-6 <?= $color ?> rounded-full flex items-center justify-center z-10 shadow-sm">
                        <span class="material-symbols-outlined text-[14px] text-white"><?= $icon ?></span>
                    </div>
                    <div class="bg-surface-container-lowest border border-surface-variant rounded-xl p-5 shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-label-sm font-bold text-worship-blue uppercase tracking-wider text-[10px]"><?= htmlspecialchars($item['item_type']) ?></span>
                            <?php if ($displayTone): ?>
                                <span class="text-label-sm bg-primary/10 text-primary px-2 py-0.5 rounded font-bold"><?= htmlspecialchars($displayTone) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-body-md font-bold text-deep-navy"><?= htmlspecialchars($displayTitle) ?></p>
                        <?php if ($isMusic && $item['song_artist']): ?>
                            <p class="text-body-md text-on-surface-variant text-xs mt-0.5 font-bold"><?= htmlspecialchars($item['song_artist']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['nota_interna']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="mt-3 p-3 bg-secondary-container/20 border border-secondary-container text-secondary font-body-sm rounded-lg flex gap-2 italic">
                            <span class="material-symbols-outlined text-[16px] shrink-0">visibility_off</span>
                            <span><?= htmlspecialchars($item['nota_interna']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Comments Thread -->
        <section class="space-y-4" id="comments">
            <h3 class="font-headline-md text-deep-navy px-2">Conversas</h3>
            <div class="bg-surface-container-lowest border border-surface-variant rounded-xl overflow-hidden flex flex-col h-[400px] shadow-sm">
                <div class="flex-grow p-6 space-y-6 overflow-y-auto no-scrollbar" id="chat-container">
                    <?php if(empty($comments)): ?>
                        <div class="text-center py-10 font-body-md text-on-surface-variant">Nenhuma mensagem publicada. Seja o primeiro!</div>
                    <?php else: ?>
                        <?php foreach($comments as $cmt): 
                            $isMe = $cmt['user_id'] == $_SESSION['user_id'];
                            $avatar = $cmt['avatar'];
                            if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false && strpos($avatar, 'uploads') === false) {
                                $avatar = '../uploads/' . $avatar;
                            }
                        ?>
                        <div class="flex gap-4 <?= $isMe ? 'flex-row-reverse' : '' ?>">
                            <?php if($avatar): ?>
                                <img alt="Foto de <?= htmlspecialchars($cmt['name']) ?>" class="w-10 h-10 rounded-full object-cover shrink-0 border border-outline-variant shadow-sm" src="<?= htmlspecialchars($avatar) ?>" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($cmt['name']) ?>&background=2E7EED&color=fff&bold=true';"/>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-ghost-gray border border-outline-variant text-deep-navy flex items-center justify-center font-bold text-xs shrink-0 shadow-sm"><?= strtoupper(substr($cmt['name'], 0, 2)) ?></div>
                            <?php endif; ?>
                            <div class="space-y-1 max-w-[80%] <?= $isMe ? 'flex flex-col items-end' : '' ?>">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-body-md text-deep-navy"><?= $isMe ? 'Você' : htmlspecialchars($cmt['name']) ?></span>
                                    <span class="text-label-sm text-secondary font-bold text-[10px]"><?= date('d/m H:i', strtotime($cmt['created_at'])) ?></span>
                                </div>
                                <div class="p-3 rounded-xl border border-surface-variant shadow-sm <?= $isMe ? 'bg-worship-blue text-white rounded-tr-none' : 'bg-ghost-gray text-deep-navy rounded-tl-none' ?>">
                                    <p class="text-body-md leading-relaxed whitespace-pre-wrap font-body-md"><?= htmlspecialchars($cmt['comment']) ?></p>
                                </div>
                                <?php if($isMe || $_SESSION['user_role'] === 'admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Deseja realmente apagar este comentário?');" class="mt-1">
                                        <?= App\AuthMiddleware::csrfField() ?>
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= $cmt['id'] ?>">
                                        <button type="submit" class="text-secondary hover:text-error text-xs flex items-center gap-1 active:scale-95 transition-transform">
                                            <span class="material-symbols-outlined text-[14px]">delete</span> Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Input Bar -->
                <div class="p-4 bg-surface-bright border-t border-surface-variant">
                    <form method="POST" class="flex items-center gap-3 bg-white border border-outline-variant rounded-full px-4 py-2 focus-within:border-worship-blue transition-all">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="add_comment">
                        <input class="flex-grow border-none focus:ring-0 text-body-md bg-transparent focus:outline-none" name="comment" placeholder="Escreva uma mensagem..." type="text" required autocomplete="off"/>
                        <button type="submit" class="text-worship-blue hover:scale-110 transition-transform active:scale-95">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

    <?php endif; ?>

</main>



<!-- MODALS FOR EDIT MODE -->
<?php if($isEditable): ?>
<!-- Modal Members -->
<div id="modalMembers" class="fixed inset-0 bg-black/50 backdrop-blur-md z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-white dark:bg-[#1A1B1F] w-full max-w-md rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] border border-[#E2E2E2] dark:border-[#2C2C2E] animate-scale-up">
        <div class="px-6 py-4 border-b border-[#F4F4F5] dark:border-[#2C2C2E] flex justify-between items-center bg-white dark:bg-[#1A1B1F]">
            <h3 class="text-lg font-bold text-deep-navy dark:text-white font-headline-md">Selecionar Participantes</h3>
            <button onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');" class="text-[#71717A] hover:text-deep-navy dark:hover:text-white hover:bg-ghost-gray dark:hover:bg-[#27272A] p-2 rounded-full transition-all duration-200 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 bg-white dark:bg-[#1A1B1F] space-y-4 no-scrollbar" id="listMembers">
            <?php 
            $groupedUsers = [];
            foreach($allUsers as $u) {
                $rawRoles = $u['instrument'] ?: 'Outros';
                $rolesArray = preg_split('/[,\/]/', $rawRoles);
                foreach($rolesArray as $role) {
                    $role = trim($role);
                    if(empty($role)) continue;
                    if(strpos(strtolower($role), 'vocal') !== false || strpos(strtolower($role), 'voz') !== false || strpos(strtolower($role), 'cantor') !== false) {
                        $role = 'Vocal';
                    }
                    $role = ucfirst(strtolower($role));
                    if(!isset($groupedUsers[$role])) $groupedUsers[$role] = [];
                    $ids = array_column($groupedUsers[$role], 'id');
                    if(!in_array($u['id'], $ids)) {
                        $groupedUsers[$role][] = $u;
                    }
                }
            }
            uksort($groupedUsers, function($a, $b) {
                $order = ['Vocal' => 1, 'Violão' => 2, 'Bateria' => 3, 'Teclado' => 4];
                $valA = $order[$a] ?? 999;
                $valB = $order[$b] ?? 999;
                if ($valA === $valB) return strcasecmp($a, $b);
                return $valA <=> $valB;
            });
            
            foreach($groupedUsers as $role => $users): 
                $icon = getInstrumentIcon($role);
            ?>
            <div class="bg-white dark:bg-[#1E1E24] rounded-2xl overflow-hidden border border-[#E2E2E2] dark:border-[#2C2C2E] shadow-sm">
                <div class="px-4 py-3 bg-ghost-gray dark:bg-[#27272A] border-b border-[#E2E2E2] dark:border-[#27272A] text-xs font-bold text-deep-navy dark:text-white uppercase tracking-wider flex items-center gap-2 sticky top-0 z-10 font-body-md">
                    <span class="material-symbols-outlined text-[16px] text-worship-blue font-bold"><?= $icon ?></span> <?= htmlspecialchars($role) ?>
                </div>
                <div class="divide-y divide-[#F4F4F5] dark:divide-[#2C2C2E]">
                <?php foreach($users as $u): 
                    $isChecked = false;
                    if(in_array($u['id'], $teamIds)) {
                         foreach($team as $tm) {
                             if($tm['user_id'] == $u['id']) {
                                 $pRole = $tm['assigned_instrument'];
                                 if($pRole && strcasecmp($pRole, $role) === 0) $isChecked = true;
                                 elseif(!$pRole) { $isChecked = true; }
                                 break;
                             }
                         }
                    }
                ?>
                <label class="flex items-center gap-3 p-3.5 hover:bg-ghost-gray dark:hover:bg-[#27272A] cursor-pointer transition-all duration-200 group active:scale-[0.99]">
                    <div class="relative flex items-center">
                        <input type="checkbox" name="temp_members[<?= $u['id'] ?>]" value="<?= htmlspecialchars($role) ?>" 
                               data-user-id="<?= $u['id'] ?>" data-role="<?= htmlspecialchars($role) ?>"
                               <?= $isChecked ? 'checked' : '' ?> onchange="toggleMemberSelection(this)"
                               class="peer w-5 h-5 appearance-none border-2 border-[#E2E2E2] dark:border-[#3F3F46] rounded-lg checked:bg-worship-blue checked:border-worship-blue transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-worship-blue/20">
                        <span class="material-symbols-outlined absolute text-white text-[16px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity font-bold">check</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-bold text-sm text-[#1A1B1F] dark:text-white group-hover:text-worship-blue transition-colors font-body-md"><?= htmlspecialchars($u['name']) ?></span>
                        <?php if($role === 'Outros' && $u['instrument']): ?>
                            <span class="text-[10px] text-[#71717A] dark:text-[#A1A1AA] mt-0.5"><?= htmlspecialchars($u['instrument']) ?></span>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-4 border-t border-[#F4F4F5] dark:border-[#2C2C2E] flex gap-3 bg-white dark:bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-ghost-gray hover:bg-[#E4E4E7] dark:bg-[#27272A] dark:hover:bg-[#3F3F46] text-deep-navy dark:text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 text-sm" onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 shadow-[0_4px_14px_rgba(46,126,237,0.3)] text-sm" onclick="confirmMemberSelection()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal Songs -->
<div id="modalSongs" class="fixed inset-0 bg-black/50 backdrop-blur-md z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-white dark:bg-[#1A1B1F] w-full max-w-md rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] border border-[#E2E2E2] dark:border-[#2C2C2E] animate-scale-up">
        <div class="px-6 py-4 border-b border-[#F4F4F5] dark:border-[#2C2C2E] flex justify-between items-center bg-white dark:bg-[#1A1B1F]">
            <h3 class="text-lg font-bold text-deep-navy dark:text-white font-headline-md">Selecionar Músicas</h3>
            <button onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');" class="text-[#71717A] hover:text-deep-navy dark:hover:text-white hover:bg-ghost-gray dark:hover:bg-[#27272A] p-2 rounded-full transition-all duration-200 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-4 bg-ghost-gray dark:bg-[#27272A] border-b border-[#E2E2E2] dark:border-[#27272A]">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-4 text-[#71717A] dark:text-[#A1A1AA] text-[20px] font-bold">search</span>
                <input type="text" id="searchSongs" placeholder="Buscar músicas pelo título ou artista..." onkeyup="filterSongList(this.value)" class="w-full bg-white dark:bg-[#1A1B1F] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl pl-12 pr-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#71717A] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200">
            </div>
        </div>

        <div class="overflow-y-auto flex-1 bg-white dark:bg-[#1A1B1F] p-3 no-scrollbar" id="listSongs">
            <div id="emptySongsState" class="text-center py-12 hidden">
                <span class="material-symbols-outlined text-[48px] text-[#A1A1AA] mb-2 font-bold">music_off</span>
                <p class="text-sm font-semibold text-[#71717A] dark:text-[#A1A1AA]">Nenhuma música encontrada</p>
            </div>
            
            <div class="divide-y divide-[#F4F4F5] dark:divide-[#2C2C2E]">
                <?php 
                $selectedSongIds = array_column($songs, 'song_id');
                usort($allSongs, function($a, $b) use ($selectedSongIds) {
                    $aSelected = in_array($a['id'], $selectedSongIds);
                    $bSelected = in_array($b['id'], $selectedSongIds);
                    if ($aSelected && !$bSelected) return -1;
                    if (!$aSelected && $bSelected) return 1;
                    return strcasecmp($a['title'], $b['title']);
                });
                
                foreach($allSongs as $s): 
                    $isSelected = in_array($s['id'], $selectedSongIds);
                    $displayStyle = $isSelected ? 'flex' : 'none';
                ?>
                <label style="display: <?= $displayStyle ?>;" class="items-center gap-4 p-3.5 hover:bg-ghost-gray dark:hover:bg-[#27272A] rounded-xl cursor-pointer transition-all duration-200 group active:scale-[0.99]" data-song-search="<?= strtolower(htmlspecialchars($s['title'].' '.$s['artist'])) ?>">
                    <div class="relative flex items-center shrink-0">
                        <input type="checkbox" value="<?= $s['id'] ?>" data-title="<?= htmlspecialchars($s['title'].' - '.$s['artist']) ?>" 
                            <?= $isSelected ? 'checked' : '' ?> onchange="toggleSong(this)"
                            class="peer w-5 h-5 appearance-none border-2 border-[#E2E2E2] dark:border-[#3F3F46] rounded-lg checked:bg-worship-blue checked:border-worship-blue transition-all cursor-pointer focus:outline-none">
                        <span class="material-symbols-outlined absolute text-white text-[16px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity font-bold">check</span>
                    </div>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="font-bold text-sm text-[#1A1B1F] dark:text-white group-hover:text-worship-blue transition-colors truncate font-body-md"><?= htmlspecialchars($s['title']) ?></span>
                        <span class="text-[10px] text-[#71717A] dark:text-[#A1A1AA] truncate mt-0.5"><?= htmlspecialchars($s['artist']) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="p-4 border-t border-[#F4F4F5] dark:border-[#2C2C2E] flex gap-3 bg-white dark:bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-ghost-gray hover:bg-[#E4E4E7] dark:bg-[#27272A] dark:hover:bg-[#3F3F46] text-deep-navy dark:text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 text-sm" onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 shadow-[0_4px_14px_rgba(46,126,237,0.3)] text-sm" onclick="confirmSongSelection()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal Info -->
<div id="modalInfo" class="fixed inset-0 bg-black/50 backdrop-blur-md z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-white dark:bg-[#1A1B1F] w-full max-w-md rounded-3xl overflow-hidden shadow-2xl flex flex-col border border-[#E2E2E2] dark:border-[#2C2C2E] animate-scale-up">
        <div class="px-6 py-4 border-b border-[#F4F4F5] dark:border-[#2C2C2E] flex justify-between items-center bg-white dark:bg-[#1A1B1F]">
            <h3 class="text-lg font-bold text-deep-navy dark:text-white font-headline-md">Editar Informações</h3>
            <button onclick="closeInfoModal(false)" class="text-[#71717A] hover:text-deep-navy dark:hover:text-white hover:bg-ghost-gray dark:hover:bg-[#27272A] p-2 rounded-full transition-all duration-200 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-6 space-y-5 bg-white dark:bg-[#1A1B1F]">
            <div class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Tipo do Evento</label>
                <input type="text" name="event_type" id="input_event_type" form="editForm" value="<?= htmlspecialchars($schedule['event_type']) ?>" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#A1A1AA] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Data</label>
                    <input type="date" name="event_date" id="input_event_date" form="editForm" value="<?= $schedule['event_date'] ?>" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Horário</label>
                    <input type="time" name="event_time" id="input_event_time" form="editForm" value="<?= $schedule['event_time'] ?>" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200">
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Observações</label>
                <textarea name="notes" id="input_notes" form="editForm" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#A1A1AA] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200 resize-none font-body-md" rows="3"><?= htmlspecialchars($schedule['notes']) ?></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-[#F4F4F5] dark:border-[#2C2C2E] flex gap-3 bg-white dark:bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-ghost-gray hover:bg-[#E4E4E7] dark:bg-[#27272A] dark:hover:bg-[#3F3F46] text-deep-navy dark:text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 text-sm" onclick="closeInfoModal(false)">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 shadow-[0_4px_14px_rgba(46,126,237,0.3)] text-sm" onclick="closeInfoModal(true)">Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Roteiro -->
<div id="modalRoteiro" class="fixed inset-0 bg-black/50 backdrop-blur-md z-[60] hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-white dark:bg-[#1A1B1F] w-full max-w-md rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh] border border-[#E2E2E2] dark:border-[#2C2C2E] animate-scale-up">
        <div class="px-6 py-4 border-b border-[#F4F4F5] dark:border-[#2C2C2E] flex justify-between items-center bg-white dark:bg-[#1A1B1F]">
            <h3 class="text-lg font-bold text-deep-navy dark:text-white font-headline-md flex items-center gap-2">
                <span class="material-symbols-outlined text-worship-blue font-bold">format_list_numbered</span> Adicionar Item
            </h3>
            <button onclick="closeRoteiroModal()" class="text-[#71717A] hover:text-deep-navy dark:hover:text-white hover:bg-ghost-gray dark:hover:bg-[#27272A] p-2 rounded-full transition-all duration-200 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-5 bg-white dark:bg-[#1A1B1F] no-scrollbar">
            <div class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Tipo do Item</label>
                <select id="roteiro-type" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200 cursor-pointer" onchange="handleRoteiroTypeChange(this.value)">
                    <option value="musica">🎵 Música</option>
                    <option value="oracao">🙏 Oração</option>
                    <option value="palavra">📖 Palavra</option>
                    <option value="anuncio">📢 Anúncio</option>
                    <option value="intervalo">☕ Intervalo</option>
                    <option value="livre">➕ Livre</option>
                </select>
            </div>

            <div id="roteiro-song-group" class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Música da Escala</label>
                <select id="roteiro-song" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200 cursor-pointer" onchange="onRoteiroSongChange(this)">
                    <option value="">— selecione uma música —</option>
                    <?php foreach ($songs as $sg): ?>
                    <option value="<?= $sg['song_id'] ?>"
                            data-title="<?= htmlspecialchars($sg['title']) ?>"
                            data-tone="<?= htmlspecialchars($sg['tone'] ?? '') ?>">
                        <?= htmlspecialchars($sg['title']) ?> — <?= htmlspecialchars($sg['artist']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="roteiro-tone-group" class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Tom customizado <span class="font-normal text-[10px] lowercase">(deixe vazio para o padrão)</span></label>
                <input type="text" id="roteiro-custom-tone" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#A1A1AA] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200" placeholder="Ex: D, Em, F#m..." maxlength="10">
            </div>

            <div id="roteiro-title-group" class="flex flex-col gap-1.5">
                <label id="roteiro-title-label" class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Título / Descrição</label>
                <input type="text" id="roteiro-title" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#A1A1AA] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200" placeholder="Ex: Momento de intercessão" maxlength="255">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="block text-xs font-bold text-[#71717A] dark:text-[#A1A1AA] uppercase tracking-wider pl-1 font-body-md">Nota interna <span class="font-normal text-[10px] lowercase">(só você vê)</span></label>
                <textarea id="roteiro-nota" class="w-full bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] rounded-2xl px-4 py-3 font-semibold text-sm text-[#1A1B1F] dark:text-white placeholder-[#A1A1AA] focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/20 transition-all duration-200 resize-none font-body-md" rows="2" placeholder="Ex: Informações adicionais do item..."></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-[#F4F4F5] dark:border-[#2C2C2E] flex gap-3 bg-white dark:bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-ghost-gray hover:bg-[#E4E4E7] dark:bg-[#27272A] dark:hover:bg-[#3F3F46] text-deep-navy dark:text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 text-sm" onclick="closeRoteiroModal()">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded-2xl font-bold transition-all duration-200 active:scale-95 shadow-[0_4px_14px_rgba(46,126,237,0.3)] text-sm" onclick="submitRoteiroItem()">Adicionar</button>
        </div>
    </div>
</div>

<script>
// Logic Functions for Edit Mode Modals
function filterSongList(text) {
    const emptyState = document.getElementById('emptySongsState');
    const labels = document.querySelectorAll('#listSongs label');
    text = text.toLowerCase().trim();
    
    if(text.length === 0) {
        let anyVisible = false;
        labels.forEach(l => {
            const cb = l.querySelector('input[type="checkbox"]');
            if(cb.checked) {
                l.style.display = 'flex';
                anyVisible = true;
            } else {
                l.style.display = 'none';
            }
        });
        emptyState.style.display = anyVisible ? 'none' : 'block';
    } else {
        let anyVisible = false;
        labels.forEach(l => {
            const searchData = l.getAttribute('data-song-search');
            const match = searchData.includes(text);
            l.style.display = match ? 'flex' : 'none';
            if(match) anyVisible = true;
        });
        emptyState.style.display = anyVisible ? 'none' : 'block';
    }
}

function toggleMemberSelection(cb) {
    const userId = cb.getAttribute('data-user-id');
    const isChecked = cb.checked;
    if(isChecked) {
        const allUserChecks = document.querySelectorAll(`input[data-user-id="${userId}"]`);
        allUserChecks.forEach(box => {
            if(box !== cb) box.checked = false;
        });
    }
}

function confirmMemberSelection() {
    const bag = document.getElementById('members-bag');
    bag.innerHTML = '';
    const allChecks = document.querySelectorAll('#listMembers input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const userId = cb.getAttribute('data-user-id');
        const role = cb.getAttribute('data-role');
        const name = cb.nextElementSibling.querySelector('span').textContent;
        const sp = document.createElement('span');
        sp.className = 'inline-flex items-center gap-2 bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] px-3 py-1.5 rounded-full text-xs font-bold text-deep-navy dark:text-white transition-all duration-200 active:scale-95';
        sp.id = 'm-badge-'+userId;
        sp.innerHTML = `${name} <span class="font-normal text-[10px] text-[#71717A] dark:text-[#A1A1AA]">(${role})</span> <button type="button" class="text-[#71717A] hover:text-error transition-colors ml-1" onclick="removeMember(${userId})"><span class="material-symbols-outlined text-[16px] font-bold">close</span></button><input type="hidden" name="members[]" value="${userId}">`;
        bag.appendChild(sp);
    });
    document.getElementById('modalMembers').classList.add('hidden');
    document.getElementById('modalMembers').classList.remove('flex');
}

function removeMember(id) {
    const badge = document.getElementById('m-badge-'+id);
    if(badge) badge.remove();
    const allUserChecks = document.querySelectorAll(`input[data-user-id="${id}"]`);
    allUserChecks.forEach(box => box.checked = false);
}

function toggleSong(cb) {}

function confirmSongSelection() {
    const bag = document.getElementById('songs-bag');
    bag.innerHTML = '';
    const allChecks = document.querySelectorAll('#listSongs input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const id = cb.value;
        const titleData = cb.getAttribute('data-title').split(' - ');
        const title = titleData[0];
        const artist = titleData[1] || '';
        
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between bg-ghost-gray dark:bg-[#27272A] border border-[#E2E2E2] dark:border-[#2C2C2E] p-3 rounded-2xl transition-all duration-200 hover:border-worship-blue';
        div.id = 's-badge-'+id;
        div.innerHTML = `<span class="text-xs font-bold text-[#1A1B1F] dark:text-white">${title} <span class="text-[#71717A] dark:text-[#A1A1AA] font-normal">- ${artist}</span></span><div class="flex items-center gap-2"><input type="hidden" name="songs[]" value="${id}"><button type="button" class="text-[#71717A] hover:text-error transition-colors p-1" onclick="removeSong(${id})"><span class="material-symbols-outlined text-[18px] font-bold">close</span></button></div>`;
        bag.appendChild(div);
    });
    document.getElementById('modalSongs').classList.add('hidden');
    document.getElementById('modalSongs').classList.remove('flex');
}

function removeSong(id) {
    const badge = document.getElementById('s-badge-'+id);
    if(badge) badge.remove();
    const cb = document.querySelector(`#listSongs input[value="${id}"]`);
    if(cb) cb.checked = false;
}

let originalInfo = {};
function openInfoModal() {
    originalInfo = {
        type: document.getElementById('input_event_type').value,
        date: document.getElementById('input_event_date').value,
        time: document.getElementById('input_event_time').value,
        notes: document.getElementById('input_notes').value
    };
    const modal = document.getElementById('modalInfo');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeInfoModal(save) {
    const modal = document.getElementById('modalInfo');
    if (save) {
        document.getElementById('summary-type').innerText = document.getElementById('input_event_type').value;
        const d = document.getElementById('input_event_date').value;  
        if(d) {
             const parts = d.split('-');
             document.getElementById('summary-date').innerHTML = `<span class="material-symbols-outlined text-[16px] text-worship-blue font-bold">calendar_month</span> ${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const t = document.getElementById('input_event_time').value;
        if(t) {
            document.getElementById('summary-time').innerHTML = `<span class="material-symbols-outlined text-[16px] text-worship-blue font-bold">schedule</span> ${t.substring(0,5)}`;
        }
        const n = document.getElementById('input_notes').value;
        const noteDiv = document.getElementById('summary-notes');
        if(n) {
            if(!noteDiv) {
                const newDiv = document.createElement('div');
                newDiv.id = 'summary-notes';
                newDiv.className = "mt-3 pt-3 border-t border-[#E2E2E2] dark:border-[#2C2C2E] text-xs text-[#71717A] dark:text-[#A1A1AA] italic flex gap-2";
                newDiv.innerHTML = `<span class="material-symbols-outlined text-[14px] mt-0.5 text-worship-blue font-bold">sticky_note_2</span> <span>${n}</span>`;
                document.querySelector('#summary-type').parentElement.appendChild(newDiv);
            } else {
                noteDiv.innerHTML = `<span class="material-symbols-outlined text-[14px] mt-0.5 text-worship-blue font-bold">sticky_note_2</span> <span>${n}</span>`;
            }
        } else if (noteDiv) {
            noteDiv.remove();
        }
    } else {
        document.getElementById('input_event_type').value = originalInfo.type;
        document.getElementById('input_event_date').value = originalInfo.date;
        document.getElementById('input_event_time').value = originalInfo.time;
        document.getElementById('input_notes').value = originalInfo.notes;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
<?php endif; ?>

<!-- CONFIRMATION FOOTER LOGIC -->
<?php if ($myMemberData !== null && !$isEditable):
    $currentStatus = $myMemberData['status'];
    $scheduleIdForJs = (int)$id;
?>
<div id="confirm-footer-container">
    <?php if ($currentStatus === 'pending'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-surface/90 backdrop-blur-md border-t border-surface-container-highest px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.05)] flex gap-3 pb-safe-bottom" id="confirm-footer">
        <button class="flex-1 py-3.5 bg-primary text-on-primary rounded-xl font-label-md font-bold shadow-md hover:bg-primary-container hover:text-on-primary-container transition-colors flex items-center justify-center gap-2" onclick="confirmPresence('confirmed')">
            <span class="material-symbols-outlined text-[20px]">check_circle</span> Confirmar
        </button>
        <button class="flex-1 py-3.5 bg-transparent border-2 border-error text-error rounded-xl font-label-md font-bold hover:bg-error-container transition-colors flex items-center justify-center gap-2" onclick="confirmPresence('declined')">
            <span class="material-symbols-outlined text-[20px]">cancel</span> Recusar
        </button>
    </div>
    <?php elseif ($currentStatus === 'confirmed'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-surface/90 backdrop-blur-md border-t border-surface-container-highest px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.05)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
        <span class="font-label-md font-bold text-green-600 flex items-center gap-2">
            <span class="material-symbols-outlined text-[22px]">check_circle</span> Confirmado
        </span>
        <button class="px-4 py-2 bg-surface-container border border-surface-container-highest rounded-lg font-label-sm font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="showConfirmButtons()">Alterar</button>
    </div>
    <?php elseif ($currentStatus === 'declined'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-surface/90 backdrop-blur-md border-t border-surface-container-highest px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.05)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
        <span class="font-label-md font-bold text-error flex items-center gap-2">
            <span class="material-symbols-outlined text-[22px]">cancel</span> Recusado
        </span>
        <button class="px-4 py-2 bg-surface-container border border-surface-container-highest rounded-lg font-label-sm font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="showConfirmButtons()">Alterar</button>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var scheduleId = <?= $scheduleIdForJs ?>;

    function confirmPresence(status) {
        var footer = document.getElementById('confirm-footer');
        var buttons = footer.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = true);

        fetch('../api/confirm_scale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ schedule_id: scheduleId, status: status })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isConfirmed = (status === 'confirmed');
                var labelText = isConfirmed ? 'Confirmado' : 'Recusado';
                var colorClass = isConfirmed ? 'text-green-600' : 'text-error';
                var iconName = isConfirmed ? 'check_circle' : 'cancel';

                document.getElementById('confirm-footer-container').innerHTML = `
                    <div class="fixed bottom-0 left-0 right-0 bg-surface/90 backdrop-blur-md border-t border-surface-container-highest px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.05)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
                        <span class="font-label-md font-bold ${colorClass} flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">${iconName}</span> ${labelText}
                        </span>
                        <button class="px-4 py-2 bg-surface-container border border-surface-container-highest rounded-lg font-label-sm font-bold text-on-surface hover:bg-surface-container-high transition-colors" onclick="showConfirmButtons()">Alterar</button>
                    </div>`;

                document.querySelector('main').classList.remove('pb-40');
            } else {
                alert('Erro ao salvar: ' + (data.message || 'Tente novamente.'));
                buttons.forEach(btn => btn.disabled = false);
            }
        })
        .catch(function(err) {
            alert('Erro de conexão. Tente novamente.');
            buttons.forEach(btn => btn.disabled = false);
        });
    }

    function showConfirmButtons() {
        document.getElementById('confirm-footer-container').innerHTML = `
            <div class="fixed bottom-0 left-0 right-0 bg-surface/90 backdrop-blur-md border-t border-surface-container-highest px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.05)] flex gap-3 pb-safe-bottom" id="confirm-footer">
                <button class="flex-1 py-3.5 bg-primary text-on-primary rounded-xl font-label-md font-bold shadow-md hover:bg-primary-container hover:text-on-primary-container transition-colors flex items-center justify-center gap-2" onclick="confirmPresence('confirmed')">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span> Confirmar
                </button>
                <button class="flex-1 py-3.5 bg-transparent border-2 border-error text-error rounded-xl font-label-md font-bold hover:bg-error-container transition-colors flex items-center justify-center gap-2" onclick="confirmPresence('declined')">
                    <span class="material-symbols-outlined text-[20px]">cancel</span> Recusar
                </button>
            </div>`;
        document.querySelector('main').classList.add('pb-40');
    }

    window.confirmPresence = confirmPresence;
    window.showConfirmButtons = showConfirmButtons;
})();
</script>
<?php endif; ?>

<!-- ROTEIRO EDIT SCRIPT -->
<?php if ($isEditable): ?>
<script>
(function() {
    var scheduleId = <?= (int)$id ?>;
    var roteiroItems = [];

    var ROTEIRO_ICONS = {
        musica: 'music_note', oracao: 'pan_tool', palavra: 'menu_book',
        anuncio: 'campaign', intervalo: 'local_cafe', livre: 'more_horiz'
    };
    var ROTEIRO_LABELS = {
        musica: 'Música', oracao: 'Oração', palavra: 'Palavra',
        anuncio: 'Anúncio', intervalo: 'Intervalo', livre: 'Livre'
    };

    function loadRoteiro() {
        fetch('../api/roteiro.php?schedule_id=' + scheduleId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    roteiroItems = data.data || [];
                    renderRoteiroList();
                }
            });
    }

    function renderRoteiroList() {
        var list = document.getElementById('roteiro-list');
        var emptyState = document.getElementById('roteiro-empty-state');
        if (!list) return;

        var existingItems = list.querySelectorAll('.roteiro-item');
        existingItems.forEach(function(el) { el.remove(); });

        if (roteiroItems.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        roteiroItems.forEach(function(item, idx) {
            var isFirst = idx === 0;
            var isLast  = idx === roteiroItems.length - 1;
            var icon    = ROTEIRO_ICONS[item.item_type] || 'more_horiz';
            var label   = ROTEIRO_LABELS[item.item_type] || item.item_type;

            var displayTitle = item.item_type === 'musica' && item.song_title
                ? item.song_title
                : (item.title || label);

            var subParts = [label];
            if (item.item_type === 'musica' && item.song_artist) subParts.push(item.song_artist);
            if (item.custom_tone) subParts.push('<span class="bg-primary-fixed text-primary-fixed-variant px-1 rounded">' + escHtml(item.custom_tone) + '</span>');
            else if (item.song_tone) subParts.push(escHtml(item.song_tone));

            var notaHtml = item.nota_interna
                ? '<div class="mt-2 text-[10px] text-secondary font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">visibility_off</span> ' + escHtml(item.nota_interna) + '</div>'
                : '';

            var el = document.createElement('div');
            el.className = 'roteiro-item flex items-center gap-3 bg-surface-container border border-surface-container-highest rounded-xl p-3 shadow-sm';
            el.dataset.id  = item.id;
            el.dataset.pos = item.order_position;
            el.innerHTML = `
                <div class="w-10 h-10 bg-surface-container-highest text-on-surface-variant rounded-lg flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-label-sm font-bold text-on-surface truncate">${escHtml(displayTitle)}</div>
                    <div class="font-body-sm text-[10px] text-on-surface-variant truncate mt-0.5">${subParts.join(' <span class="mx-1">•</span> ')}</div>
                    ${notaHtml}
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <button type="button" class="w-6 h-6 flex items-center justify-center bg-surface-container-highest text-on-surface-variant rounded disabled:opacity-30 hover:bg-surface-container-highest hover:text-on-surface transition-colors" onclick="moveRoteiroItem(${idx}, -1)" ${isFirst ? 'disabled' : ''}><span class="material-symbols-outlined text-[16px]">expand_less</span></button>
                    <button type="button" class="w-6 h-6 flex items-center justify-center bg-surface-container-highest text-on-surface-variant rounded disabled:opacity-30 hover:bg-surface-container-highest hover:text-on-surface transition-colors" onclick="moveRoteiroItem(${idx}, 1)" ${isLast  ? 'disabled' : ''}><span class="material-symbols-outlined text-[16px]">expand_more</span></button>
                </div>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:text-error hover:bg-error-container/50 rounded-full transition-colors ml-1 shrink-0" onclick="deleteRoteiroItem(${item.id})">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            `;
            list.appendChild(el);
        });
    }

    function moveRoteiroItem(idx, direction) {
        var newIdx = idx + direction;
        if (newIdx < 0 || newIdx >= roteiroItems.length) return;

        var tmp = roteiroItems[idx];
        roteiroItems[idx] = roteiroItems[newIdx];
        roteiroItems[newIdx] = tmp;

        roteiroItems.forEach(function(item, i) { item.order_position = i; });
        renderRoteiroList();

        var payload = roteiroItems.map(function(item) { return {id: item.id, pos: item.order_position}; });
        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reorder', schedule_id: scheduleId, items: payload})
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.success) alert('Erro ao reordenar: ' + (data.message || ''));
        });
    }

    function deleteRoteiroItem(itemId) {
        if (!confirm('Excluir este item do roteiro?')) return;
        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: itemId, schedule_id: scheduleId})
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                roteiroItems = roteiroItems.filter(function(i) { return i.id !== itemId; });
                roteiroItems.forEach(function(item, i) { item.order_position = i; });
                renderRoteiroList();
            } else {
                alert('Erro ao excluir: ' + (data.message || ''));
            }
        });
    }

    window.openRoteiroModal = function() {
        document.getElementById('roteiro-type').value = 'musica';
        document.getElementById('roteiro-song').value = '';
        document.getElementById('roteiro-custom-tone').value = '';
        document.getElementById('roteiro-title').value = '';
        document.getElementById('roteiro-nota').value = '';
        handleRoteiroTypeChange('musica');
        var m = document.getElementById('modalRoteiro');
        m.classList.remove('hidden');
        m.classList.add('flex');
    };

    window.closeRoteiroModal = function() {
        document.getElementById('modalRoteiro').classList.add('hidden');
        document.getElementById('modalRoteiro').classList.remove('flex');
    };

    window.handleRoteiroTypeChange = function(type) {
        var isSong = type === 'musica';
        document.getElementById('roteiro-song-group').style.display  = isSong ? 'block' : 'none';
        document.getElementById('roteiro-tone-group').style.display  = isSong ? 'block' : 'none';
        var titleLabel = document.getElementById('roteiro-title-label');
        if (titleLabel) titleLabel.innerHTML = isSong ? 'Título alternativo <span class="font-normal text-[10px]">(opcional)</span>' : 'Título / Descrição *';
    };

    window.onRoteiroSongChange = function(sel) {
        var tone = sel.options[sel.selectedIndex].getAttribute('data-tone') || '';
        var toneInput = document.getElementById('roteiro-custom-tone');
        if (toneInput) toneInput.placeholder = tone ? 'Ex: ' + tone + ' (padrão — deixe vazio para manter)' : 'Ex: D, Em, F#m...';
    };

    window.submitRoteiroItem = function() {
        var type = document.getElementById('roteiro-type').value;
        var songId = type === 'musica' ? (parseInt(document.getElementById('roteiro-song').value) || null) : null;
        var customTone = type === 'musica' ? (document.getElementById('roteiro-custom-tone').value.trim() || null) : null;
        var title = document.getElementById('roteiro-title').value.trim() || null;
        var nota = document.getElementById('roteiro-nota').value.trim() || null;

        if (type !== 'musica' && !title) {
            alert('Preencha o título do item.');
            document.getElementById('roteiro-title').focus();
            return;
        }

        var payload = {
            action: 'add',
            schedule_id: scheduleId,
            item_type: type,
            song_id: songId,
            custom_tone: customTone,
            title: title,
            nota_interna: nota
        };

        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                closeRoteiroModal();
                loadRoteiro();
            } else {
                alert('Erro ao adicionar: ' + (data.message || ''));
            }
        });
    };

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    loadRoteiro();
})();
</script>
<?php endif; ?>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.pb-safe-bottom {
    padding-bottom: calc(1rem + env(safe-area-inset-bottom, 16px));
}
</style>

<?php renderAppFooter(); ?>
