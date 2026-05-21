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

<!-- Minimalist Styling -->
<main class="max-w-[800px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-32 <?= ($myMemberData && $myMemberData['status'] === 'pending') ? 'pb-40' : '' ?>">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface font-bold">Detalhes da Escala</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mt-2"><?= htmlspecialchars($schedule['event_type']) ?></p>
        </div>
        <a href="escalas.php" class="w-10 h-10 bg-surface-container border border-surface-container-highest text-on-surface rounded-full flex items-center justify-center hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-primary-fixed/30 border border-primary-fixed text-primary-fixed-variant px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-body-md font-bold">Alterações salvas com sucesso!</span>
        </div>
    <?php endif; ?>

    <!-- HERO HEADER CARD -->
    <div class="bg-surface border border-surface-container-highest rounded-[2rem] p-6 shadow-sm mb-8 overflow-hidden relative group">
        <!-- Top accent line -->
        <div class="absolute top-0 left-0 w-full h-1.5 bg-primary"></div>
        
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            
            <div class="w-20 h-20 bg-primary-container rounded-2xl flex flex-col items-center justify-center text-on-primary-container border-2 border-primary/20 flex-shrink-0 shadow-inner">
                <div class="font-display-lg text-3xl font-bold leading-none bg-clip-text text-transparent bg-primary"><?= $date->format('d') ?></div>
                <div class="font-label-sm text-[10px] font-bold uppercase tracking-widest mt-1"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
            </div>
            
            <div class="flex-1">
                <h2 class="font-display-md text-2xl font-bold text-on-surface mb-2"><?= htmlspecialchars($schedule['event_type']) ?></h2>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 bg-surface-container-low border border-surface-container-highest px-3 py-1.5 rounded-full font-label-sm font-bold text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px] text-primary">calendar_month</span>
                        <?= $diaSemana ?>
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-surface-container-low border border-surface-container-highest px-3 py-1.5 rounded-full font-label-sm font-bold text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px] text-primary">schedule</span>
                        <?= substr($schedule['event_time'], 0, 5) ?>
                    </span>
                </div>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
            <div class="hidden md:block">
                <a href="?id=<?= $id ?>&edit=1" class="w-12 h-12 bg-surface-container border border-surface-container-highest text-on-surface rounded-full flex items-center justify-center hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </a>
            </div>
            <?php endif; ?>
            
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="escala_setlist.php?id=<?= (int)$id ?>" class="inline-flex items-center gap-2 bg-primary/10 border border-primary/30 text-primary px-4 py-2 rounded-xl font-label-sm font-bold hover:bg-primary/20 transition-colors">
                <span class="material-symbols-outlined text-[18px]">queue_music</span>
                Acessar Setlist (Modo Escuro/Impressão)
            </a>
            <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
            <a href="?id=<?= $id ?>&edit=1" class="md:hidden inline-flex items-center gap-2 bg-surface-container text-on-surface px-4 py-2 rounded-xl font-label-sm font-bold hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">edit</span>
                Editar Escala
            </a>
            <?php endif; ?>
        </div>
        
        <?php if($schedule['notes']): ?>
        <div class="mt-6 bg-surface-container-lowest border-t border-surface-container-highest -mx-6 -mb-6 p-6">
            <p class="font-body-md text-on-surface-variant leading-relaxed">
                <span class="font-bold text-on-surface">Observações:</span><br>
                <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ================= EDIT MODE ================= -->
    <?php if ($isEditable): ?>
        
        <form method="POST" id="editForm" class="bg-surface border border-primary/30 rounded-3xl p-6 shadow-lg shadow-primary/5 space-y-8 animate-fade-in relative overflow-hidden">
            <!-- Edit mode indicator -->
            <div class="absolute top-0 right-0 bg-primary text-on-primary px-4 py-1 rounded-bl-xl font-label-sm font-bold text-[10px] tracking-wider uppercase flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">edit</span> Modo Edição
            </div>

            <?= App\AuthMiddleware::csrfField() ?>
            <input type="hidden" name="save_changes" value="1">
            
            <!-- 1. Event Info Editor -->
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">info</span>
                        Info Básica
                    </h3>
                    <button type="button" class="text-primary font-label-sm font-bold hover:underline" onclick="openInfoModal()">Editar Tudo</button>
                </div>
                
                <div class="bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 cursor-pointer hover:bg-surface-container-low transition-colors" onclick="openInfoModal()">
                    <div id="summary-type" class="font-headline-md font-bold text-on-surface"><?= htmlspecialchars($schedule['event_type']) ?></div>
                    <div class="flex items-center gap-4 mt-2 font-body-sm text-on-surface-variant">
                        <span id="summary-date" class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_month</span> <?= date('d/m/Y', strtotime($schedule['event_date'])) ?></span>
                        <span id="summary-time" class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">schedule</span> <?= substr($schedule['event_time'], 0, 5) ?></span>
                    </div>
                    <?php if($schedule['notes']): ?>
                    <div id="summary-notes" class="mt-3 pt-3 border-t border-surface-container-highest font-body-sm text-on-surface-variant italic flex gap-2">
                        <span class="material-symbols-outlined text-[14px] mt-0.5">sticky_note_2</span> 
                        <span><?= htmlspecialchars($schedule['notes']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Members Editor -->
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">groups</span>
                        Participantes
                    </h3>
                    <button type="button" class="text-primary font-label-sm font-bold hover:underline" onclick="document.getElementById('modalMembers').classList.remove('hidden'); document.getElementById('modalMembers').classList.add('flex');">Gerenciar</button>
                </div>
                
                <div id="members-bag" class="flex flex-wrap gap-2 min-h-[60px] bg-surface-container-lowest border border-surface-container-highest border-dashed rounded-2xl p-4">
                    <?php foreach($teamIds as $tid): 
                        $uName = ''; foreach($allUsers as $u) if($u['id']==$tid) $uName=$u['name'];
                    ?>
                        <span class="inline-flex items-center gap-2 bg-surface-container border border-surface-container-highest px-3 py-1.5 rounded-full font-label-sm font-bold text-on-surface" id="m-badge-<?= $tid ?>">
                            <?= htmlspecialchars($uName) ?> 
                            <button type="button" class="text-on-surface-variant hover:text-error transition-colors" onclick="removeMember(<?= $tid ?>)">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                            <input type="hidden" name="members[]" value="<?= $tid ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Songs Editor -->
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">queue_music</span>
                        Repertório
                    </h3>
                    <button type="button" class="text-primary font-label-sm font-bold hover:underline" onclick="document.getElementById('modalSongs').classList.remove('hidden'); document.getElementById('modalSongs').classList.add('flex');">Gerenciar</button>
                </div>
                
                <div id="songs-bag" class="space-y-2 min-h-[60px] bg-surface-container-lowest border border-surface-container-highest border-dashed rounded-2xl p-4">
                    <?php foreach($songs as $sg): ?>
                        <div class="flex items-center justify-between bg-surface-container border border-surface-container-highest p-3 rounded-xl" id="s-badge-<?= $sg['song_id'] ?>">
                            <span class="font-label-sm font-bold text-on-surface"><?= htmlspecialchars($sg['title']) ?> <span class="text-on-surface-variant font-normal">- <?= htmlspecialchars($sg['artist']) ?></span></span>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="songs[]" value="<?= $sg['song_id'] ?>">
                                <button type="button" class="text-on-surface-variant hover:text-error transition-colors p-1" onclick="removeSong(<?= $sg['song_id'] ?>)">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Roteiro Editor -->
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-2">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">list_alt</span>
                        Roteiro de Culto
                    </h3>
                    <button type="button" class="text-primary font-label-sm font-bold hover:underline flex items-center gap-1" onclick="openRoteiroModal()">
                        <span class="material-symbols-outlined text-[16px]">add</span> Adicionar Item
                    </button>
                </div>
                
                <div id="roteiro-list" class="space-y-2">
                    <div id="roteiro-empty-state" class="text-center p-8 bg-surface-container-lowest border border-surface-container-highest border-dashed rounded-2xl font-body-sm text-on-surface-variant">
                        Nenhum item no roteiro. Clique em "Adicionar Item".
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-surface-container-highest">
                 <a href="?id=<?= $id ?>" class="py-3 px-4 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold text-center hover:bg-surface-container-high transition-colors">Cancelar</a>
                 <button type="button" onclick="if(confirm('Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.')) document.getElementById('delForm').submit()" class="py-3 px-4 bg-error-container text-error rounded-full font-label-sm font-bold hover:bg-error hover:text-on-error transition-colors text-center">Excluir Escala</button>
                 <button type="submit" class="py-3 px-4 bg-primary text-on-primary rounded-full font-label-sm font-bold shadow-md hover:bg-primary-container hover:text-on-primary-container transition-colors transform active:scale-95 text-center">Salvar Alterações</button>
            </div>
        </form>
        <form id="delForm" method="POST" class="hidden"><input type="hidden" name="delete_schedule" value="1"></form>

    <!-- ================= VIEW MODE ================= -->
    <?php else: ?>
        
        <!-- REHEARSAL CHECK-IN -->
        <?php if ($myMemberData): ?>
        <div class="bg-surface border border-surface-container-highest rounded-[2rem] p-6 shadow-sm mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php if ($myMemberData['is_rehearsed']): ?>
                    <div class="w-12 h-12 bg-green-100 text-green-700 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[24px]">task_alt</span>
                    </div>
                    <div>
                        <h3 class="font-headline-md font-bold text-green-700">Repertório estudado!</h3>
                        <p class="font-body-sm text-on-surface-variant">Você está pronto para o ensaio.</p>
                    </div>
                <?php else: ?>
                    <div class="w-12 h-12 bg-surface-container-highest text-on-surface-variant rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[24px]">headphones</span>
                    </div>
                    <div>
                        <h3 class="font-headline-md font-bold text-on-surface">Prepare-se para ouvir</h3>
                        <p class="font-body-sm text-on-surface-variant">Marque quando tiver estudado as músicas.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <?= App\AuthMiddleware::csrfField() ?>
                <input type="hidden" name="action" value="toggle_rehearsal">
                <input type="hidden" name="state" value="<?= $myMemberData['is_rehearsed'] ? '0' : '1' ?>">
                <button type="submit" class="px-6 py-3 rounded-full font-label-sm font-bold flex items-center gap-2 transition-all transform active:scale-95 <?= $myMemberData['is_rehearsed'] ? 'bg-surface-container border border-surface-container-highest text-on-surface hover:bg-surface-container-high' : 'bg-primary text-on-primary shadow-md hover:bg-primary-container hover:text-on-primary-container' ?>">
                    <?php if ($myMemberData['is_rehearsed']): ?>
                        <span class="material-symbols-outlined text-[20px]">undo</span> Desfazer
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[20px]">check_circle</span> Já estudei
                    <?php endif; ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- PARTICIPANTS SECTION -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="font-headline-md text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">groups</span> Equipe & Ministérios
                </h3>
                <span class="bg-surface-container-highest text-on-surface-variant font-label-sm font-bold px-3 py-1 rounded-full"><?= count($team) ?></span>
            </div>

            <?php if(empty($groupedTeam)): ?>
                <div class="bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-6 text-center font-body-sm text-on-surface-variant">Nenhum participante definido.</div>
            <?php else: ?>
                <div class="space-y-4">
                <?php 
                $ministryIcons = [
                    'Vocal / Vozes' => 'mic',
                    'Harmonia / Cordas' => 'nightlight', // usando icone proximo a guitarra
                    'Ritmo / Percussão' => 'album', // bateria
                    'Som & Apoio' => 'tune' // sliders
                ];
                foreach($groupedTeam as $ministryName => $members): 
                    $minIcon = $ministryIcons[$ministryName] ?? 'groups';
                ?>
                    <div class="bg-surface border border-surface-container-highest rounded-2xl p-5 shadow-sm transition-transform hover:-translate-y-1">
                        <div class="flex items-center justify-between mb-4 border-b border-surface-container-highest pb-3">
                            <h4 class="font-label-md font-bold text-on-surface uppercase tracking-wider flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[20px]"><?= $minIcon ?></span>
                                <?= htmlspecialchars($ministryName) ?>
                            </h4>
                            <span class="bg-surface-container-high text-on-surface-variant font-label-sm font-bold text-[10px] px-2 py-0.5 rounded-full border border-surface-container-highest"><?= count($members) ?></span>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <?php foreach($members as $member):
                                $memberStatus = $member['status'] ?? 'pending';
                                $statusColors = [
                                    'confirmed' => 'bg-green-500',
                                    'declined' => 'bg-red-500',
                                    'absent' => 'bg-red-500',
                                    'pending' => 'bg-yellow-500'
                                ];
                                $sColor = $statusColors[$memberStatus] ?? 'bg-yellow-500';
                                $initials = strtoupper(substr($member['name'], 0, 1));
                                $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
                                
                                $avatarPath = $member['avatar'];
                                if ($avatarPath && strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                    $avatarPath = '../uploads/' . $avatarPath;
                                }
                            ?>
                            <div class="bg-surface-container-lowest border border-surface-container-highest rounded-xl p-3 flex items-center gap-3 hover:bg-surface-container-low transition-colors">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-display-md text-lg font-bold text-white shadow-sm overflow-hidden" style="background: <?= $member['avatar_color'] ?: '#3b82f6' ?>;">
                                        <?php if($avatarPath): ?>
                                            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?= $initials ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-surface <?= $sColor ?>"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-label-sm font-bold text-on-surface truncate" title="<?= htmlspecialchars($member['name']) ?>"><?= htmlspecialchars($member['name']) ?></div>
                                    <div class="font-body-sm text-[10px] text-on-surface-variant truncate" title="<?= htmlspecialchars($instr) ?>"><?= htmlspecialchars($instr) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- REPERTOIRE SECTION -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="font-headline-md text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">queue_music</span> Repertório
                </h3>
                <span class="bg-surface-container-highest text-on-surface-variant font-label-sm font-bold px-3 py-1 rounded-full"><?= count($songs) ?></span>
            </div>

            <div class="bg-surface border border-surface-container-highest rounded-2xl p-4 shadow-sm flex flex-col gap-2">
                <?php if (empty($songs)): ?>
                    <div class="text-center p-4 font-body-sm text-on-surface-variant">Nenhuma música selecionada.</div>
                <?php else: ?>
                    <?php foreach ($songs as $index => $song):
                        $resolvedTone = $customToneMap[(int)$song['song_id']] ?? null;
                        $isCustomTone = !empty($resolvedTone);
                        $toneDisplay  = $isCustomTone ? $resolvedTone : ($song['tone'] ?? '');
                    ?>
                    <div class="flex items-center gap-4 p-3 bg-surface-container-lowest border border-surface-container-highest rounded-xl hover:bg-surface-container-low transition-all">
                        <div class="font-display-md text-on-surface-variant font-bold w-6 text-center shrink-0"><?= $index + 1 ?></div>
                        
                        <div class="w-12 h-12 bg-surface-container-highest rounded-lg flex items-center justify-center shrink-0 shadow-sm border border-surface-container shadow-inner">
                            <span class="font-display-md text-primary font-bold text-xl drop-shadow-sm"><?= strtoupper(substr($song['title'], 0, 1)) ?></span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>" class="font-label-sm font-bold text-on-surface hover:text-primary transition-colors block truncate"><?= htmlspecialchars($song['title']) ?></a>
                            <div class="font-body-sm text-[11px] text-on-surface-variant flex items-center gap-2 mt-0.5">
                                <span class="truncate max-w-[120px] sm:max-w-[200px] inline-block"><?= htmlspecialchars($song['artist']) ?></span>
                                <?php if ($toneDisplay): ?>
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded font-bold text-[10px] <?= $isCustomTone ? 'bg-primary/20 text-primary-fixed-variant' : 'bg-surface-container-highest text-on-surface-variant' ?>">
                                        <?= htmlspecialchars($toneDisplay) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface hover:text-primary border border-transparent hover:border-surface-container-highest transition-all <?= empty($song['link_letra']) ? 'opacity-30 pointer-events-none' : '' ?>" title="Letra">
                                <span class="material-symbols-outlined text-[18px]">format_align_left</span>
                            </a>
                            <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface hover:text-primary border border-transparent hover:border-surface-container-highest transition-all <?= empty($song['link_cifra']) ? 'opacity-30 pointer-events-none' : '' ?>" title="Cifra">
                                <span class="material-symbols-outlined text-[18px]">description</span>
                            </a>
                            <a href="<?= $song['link_video'] ?: ($song['link_audio'] ?: 'https://www.youtube.com/results?search_query='.urlencode($song['title'].' '.$song['artist'])) ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full text-white bg-primary hover:bg-primary-container hover:text-on-primary-container transition-all shadow-sm" title="Ouvir">
                                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ROTEIRO DE CULTO -->
        <?php if (!empty($roteiro)): ?>
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="font-headline-md text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">list_alt</span> Roteiro de Culto
                </h3>
                <span class="bg-surface-container-highest text-on-surface-variant font-label-sm font-bold px-3 py-1 rounded-full"><?= count($roteiro) ?></span>
            </div>

            <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 shadow-sm">
                <div class="relative before:absolute before:inset-y-0 before:left-[19px] before:w-0.5 before:bg-surface-container-highest space-y-6">
                    <?php
                    $roteiroIcons = [
                        'musica'    => 'music_note',
                        'oracao'    => 'pan_tool',
                        'palavra'   => 'menu_book',
                        'anuncio'   => 'campaign',
                        'intervalo' => 'local_cafe',
                        'livre'     => 'more_horiz',
                    ];
                    $roteiroLabels = [
                        'musica'    => 'Música',
                        'oracao'    => 'Oração',
                        'palavra'   => 'Palavra',
                        'anuncio'   => 'Anúncio',
                        'intervalo' => 'Intervalo',
                        'livre'     => 'Livre',
                    ];
                    foreach ($roteiro as $idx => $item):
                        $icon  = $roteiroIcons[$item['item_type']]  ?? 'more_horiz';
                        $label = $roteiroLabels[$item['item_type']] ?? $item['item_type'];
                        $isMusic = $item['item_type'] === 'musica';

                        $displayTitle = ($isMusic && $item['song_title']) ? $item['song_title'] : ($item['title'] ?: $label);

                        $displayTone = null;
                        if ($isMusic) {
                            $displayTone = (!empty($item['custom_tone'])) ? $item['custom_tone'] : ($item['song_tone'] ?? null);
                        }
                    ?>
                    <div class="relative flex gap-4 items-start">
                        <!-- Number / Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 z-10 border-4 border-surface <?= $isMusic ? 'bg-primary text-white' : 'bg-surface-container-highest text-on-surface-variant' ?>">
                            <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 bg-surface-container-lowest border border-surface-container-highest rounded-xl p-4 shadow-sm relative top-1">
                            <h4 class="font-label-md font-bold text-on-surface mb-1"><?= htmlspecialchars($displayTitle) ?></h4>
                            <div class="flex flex-wrap items-center gap-2 font-body-sm text-[11px] text-on-surface-variant font-bold uppercase tracking-wider">
                                <span><?= htmlspecialchars($label) ?></span>
                                <?php if ($isMusic && $item['song_artist']): ?>
                                    <span class="w-1 h-1 bg-on-surface-variant/30 rounded-full"></span>
                                    <span><?= htmlspecialchars($item['song_artist']) ?></span>
                                <?php endif; ?>
                                <?php if ($displayTone): ?>
                                    <span class="bg-primary/10 text-primary px-1.5 py-0.5 rounded"><?= htmlspecialchars($displayTone) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($item['nota_interna']) && $_SESSION['user_role'] === 'admin'): ?>
                            <div class="mt-3 p-3 bg-secondary-container/20 border border-secondary-container text-secondary font-body-sm rounded-lg flex gap-2 italic">
                                <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                                <span><?= htmlspecialchars($item['nota_interna']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMMENTS SECTION -->
        <div class="mb-10" id="comments">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="font-headline-md text-xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">chat</span> Comentários
                </h3>
            </div>
            
            <div class="bg-surface border border-surface-container-highest rounded-[2rem] p-6 shadow-sm">
                
                <div class="space-y-6 mb-6">
                    <?php if(empty($comments)): ?>
                        <div class="text-center py-10 font-body-md text-on-surface-variant">Seja o primeiro a comentar!</div>
                    <?php else: ?>
                        <?php foreach($comments as $cmt): 
                            $isMe = $cmt['user_id'] == $_SESSION['user_id'];
                            $avatar = $cmt['avatar'];
                            if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false && strpos($avatar, 'uploads') === false) {
                                $avatar = '../uploads/' . $avatar;
                            }
                        ?>
                        <div class="flex gap-4 group">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-display-md text-white font-bold shrink-0 overflow-hidden" style="background: <?= $cmt['avatar_color'] ?: '#64748b' ?>;">
                                <?php if($avatar): ?>
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($cmt['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= strtoupper(substr($cmt['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 bg-surface-container-lowest border border-surface-container-highest p-4 rounded-2xl rounded-tl-none relative">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-label-sm font-bold text-on-surface block"><?= htmlspecialchars($cmt['name']) ?></span>
                                        <span class="font-body-sm text-[10px] text-on-surface-variant"><?= date('d/m H:i', strtotime($cmt['created_at'])) ?></span>
                                    </div>
                                    <?php if($isMe || $_SESSION['user_role'] === 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Apagar comentário?');" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <input type="hidden" name="action" value="delete_comment">
                                            <input type="hidden" name="comment_id" value="<?= $cmt['id'] ?>">
                                            <button type="submit" class="text-on-surface-variant hover:text-error p-1">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="font-body-md text-on-surface leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($cmt['comment']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="flex gap-3 items-end">
                    <?= App\AuthMiddleware::csrfField() ?>
                    <input type="hidden" name="action" value="add_comment">
                    <div class="flex-1">
                        <input type="text" name="comment" class="w-full bg-surface-container border border-surface-container-highest rounded-full px-5 py-3.5 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="Escreva uma mensagem..." required autocomplete="off">
                    </div>
                    <button type="submit" class="w-12 h-12 bg-primary text-on-primary rounded-full flex items-center justify-center shrink-0 hover:bg-primary-container hover:text-on-primary-container transition-transform transform active:scale-95 shadow-md">
                        <span class="material-symbols-outlined text-[20px] ml-1">send</span>
                    </button>
                </form>
            </div>
        </div>

    <?php endif; ?>

</main>


<!-- MODALS FOR EDIT MODE -->
<?php if($isEditable): ?>
<!-- Modal Members -->
<div id="modalMembers" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center px-4">
    <div class="bg-surface w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
        <div class="px-6 py-4 border-b border-surface-container-highest flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-headline-md font-bold text-on-surface">Selecionar Participantes</h3>
            <button onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-4 overflow-y-auto flex-1 bg-surface" id="listMembers">
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
            <div class="mb-4 bg-surface-container-lowest rounded-xl overflow-hidden border border-surface-container-highest">
                <div class="px-4 py-2 bg-surface-container border-b border-surface-container-highest font-label-sm font-bold text-on-surface flex items-center gap-2 sticky top-0 z-10">
                    <span class="material-symbols-outlined text-[16px] text-primary"><?= $icon ?></span> <?= htmlspecialchars($role) ?>
                </div>
                <div class="divide-y divide-surface-container-highest">
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
                <label class="flex items-center gap-3 p-3 hover:bg-surface-container-low cursor-pointer transition-colors group">
                    <div class="relative flex items-center">
                        <input type="checkbox" name="temp_members[<?= $u['id'] ?>]" value="<?= htmlspecialchars($role) ?>" 
                               data-user-id="<?= $u['id'] ?>" data-role="<?= htmlspecialchars($role) ?>"
                               <?= $isChecked ? 'checked' : '' ?> onchange="toggleMemberSelection(this)"
                               class="peer w-5 h-5 appearance-none border-2 border-on-surface-variant/30 rounded checked:bg-primary checked:border-primary transition-colors cursor-pointer">
                        <span class="material-symbols-outlined absolute text-white text-[16px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-body-md font-bold text-on-surface group-hover:text-primary transition-colors"><?= htmlspecialchars($u['name']) ?></span>
                        <?php if($role === 'Outros' && $u['instrument']): ?>
                            <span class="font-body-sm text-[10px] text-on-surface-variant"><?= htmlspecialchars($u['instrument']) ?></span>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-4 border-t border-surface-container-highest flex gap-3 bg-surface-container-lowest">
            <button type="button" class="flex-1 py-3 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold hover:bg-surface-container-high transition-colors" onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-primary text-on-primary rounded-full font-label-sm font-bold hover:bg-primary-container hover:text-on-primary-container transition-colors shadow-md" onclick="confirmMemberSelection()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal Songs -->
<div id="modalSongs" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center px-4">
    <div class="bg-surface w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
        <div class="px-6 py-4 border-b border-surface-container-highest flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-headline-md font-bold text-on-surface">Selecionar Músicas</h3>
            <button onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-4 bg-surface-container border-b border-surface-container-highest">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                <input type="text" id="searchSongs" placeholder="Buscar músicas..." onkeyup="filterSongList(this.value)" class="w-full bg-surface border border-surface-container-highest rounded-xl pl-10 pr-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors">
            </div>
        </div>

        <div class="overflow-y-auto flex-1 bg-surface p-2" id="listSongs">
            <div id="emptySongsState" class="text-center py-10 hidden">
                <span class="material-symbols-outlined text-[48px] text-surface-container-highest mb-2">music_off</span>
                <p class="font-body-sm text-on-surface-variant">Nenhuma música encontrada</p>
            </div>
            
            <div class="divide-y divide-surface-container-highest">
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
                <label style="display: <?= $displayStyle ?>;" class="items-center gap-3 p-3 hover:bg-surface-container-low cursor-pointer transition-colors group" data-song-search="<?= strtolower(htmlspecialchars($s['title'].' '.$s['artist'])) ?>">
                    <div class="relative flex items-center">
                        <input type="checkbox" value="<?= $s['id'] ?>" data-title="<?= htmlspecialchars($s['title'].' - '.$s['artist']) ?>" 
                            <?= $isSelected ? 'checked' : '' ?> onchange="toggleSong(this)"
                            class="peer w-5 h-5 appearance-none border-2 border-on-surface-variant/30 rounded checked:bg-primary checked:border-primary transition-colors cursor-pointer">
                        <span class="material-symbols-outlined absolute text-white text-[16px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-body-md font-bold text-on-surface group-hover:text-primary transition-colors truncate"><?= htmlspecialchars($s['title']) ?></span>
                        <span class="font-body-sm text-[10px] text-on-surface-variant truncate"><?= htmlspecialchars($s['artist']) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="p-4 border-t border-surface-container-highest flex gap-3 bg-surface-container-lowest">
            <button type="button" class="flex-1 py-3 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold hover:bg-surface-container-high transition-colors" onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-primary text-on-primary rounded-full font-label-sm font-bold hover:bg-primary-container hover:text-on-primary-container transition-colors shadow-md" onclick="confirmSongSelection()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal Info -->
<div id="modalInfo" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center px-4">
    <div class="bg-surface w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl flex flex-col">
        <div class="px-6 py-4 border-b border-surface-container-highest flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-headline-md font-bold text-on-surface">Editar Informações</h3>
            <button onclick="closeInfoModal(false)" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-6 space-y-4">
            <div>
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Tipo do Evento</label>
                <input type="text" name="event_type" id="input_event_type" form="editForm" value="<?= htmlspecialchars($schedule['event_type']) ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Data</label>
                    <input type="date" name="event_date" id="input_event_date" form="editForm" value="<?= $schedule['event_date'] ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors">
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Horário</label>
                    <input type="time" name="event_time" id="input_event_time" form="editForm" value="<?= $schedule['event_time'] ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors">
                </div>
            </div>
            
            <div>
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Observações</label>
                <textarea name="notes" id="input_notes" form="editForm" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors resize-none" rows="3"><?= htmlspecialchars($schedule['notes']) ?></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-surface-container-highest flex gap-3 bg-surface-container-lowest">
            <button type="button" class="flex-1 py-3 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold hover:bg-surface-container-high transition-colors" onclick="closeInfoModal(false)">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-primary text-on-primary rounded-full font-label-sm font-bold hover:bg-primary-container hover:text-on-primary-container transition-colors shadow-md" onclick="closeInfoModal(true)">Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Roteiro -->
<div id="modalRoteiro" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[60] hidden items-center justify-center px-4">
    <div class="bg-surface w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
        <div class="px-6 py-4 border-b border-surface-container-highest flex justify-between items-center bg-surface-container-lowest">
            <h3 class="font-headline-md font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">format_list_numbered</span> Adicionar Item
            </h3>
            <button onclick="closeRoteiroModal()" class="text-on-surface-variant hover:bg-surface-container-high p-2 rounded-full transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-4">
            <div>
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Tipo do Item</label>
                <select id="roteiro-type" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors appearance-none" onchange="handleRoteiroTypeChange(this.value)">
                    <option value="musica">🎵 Música</option>
                    <option value="oracao">🙏 Oração</option>
                    <option value="palavra">📖 Palavra</option>
                    <option value="anuncio">📢 Anúncio</option>
                    <option value="intervalo">☕ Intervalo</option>
                    <option value="livre">➕ Livre</option>
                </select>
            </div>

            <div id="roteiro-song-group">
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Música da Escala</label>
                <select id="roteiro-song" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors appearance-none" onchange="onRoteiroSongChange(this)">
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

            <div id="roteiro-tone-group">
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Tom customizado <span class="font-normal text-[10px]">(deixe vazio para usar o tom padrão)</span></label>
                <input type="text" id="roteiro-custom-tone" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors" placeholder="Ex: D, Em, F#m..." maxlength="10">
            </div>

            <div id="roteiro-title-group">
                <label id="roteiro-title-label" class="block font-label-sm text-on-surface-variant mb-1 font-bold">Título / Descrição</label>
                <input type="text" id="roteiro-title" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors" placeholder="Ex: Momento de intercessão" maxlength="255">
            </div>

            <div>
                <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Nota interna <span class="font-normal text-[10px]">(só você vê)</span></label>
                <textarea id="roteiro-nota" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:outline-none focus:border-primary transition-colors resize-none" rows="2" placeholder="Ex: Aqui Diego prega os pedidos de oração"></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-surface-container-highest flex gap-3 bg-surface-container-lowest">
            <button type="button" class="flex-1 py-3 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold hover:bg-surface-container-high transition-colors" onclick="closeRoteiroModal()">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-primary text-on-primary rounded-full font-label-sm font-bold hover:bg-primary-container hover:text-on-primary-container transition-colors shadow-md" onclick="submitRoteiroItem()">Adicionar</button>
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
        sp.className = 'inline-flex items-center gap-2 bg-surface-container border border-surface-container-highest px-3 py-1.5 rounded-full font-label-sm font-bold text-on-surface';
        sp.id = 'm-badge-'+userId;
        sp.innerHTML = `${name} <span class="font-normal text-[10px] text-on-surface-variant">(${role})</span> <button type="button" class="text-on-surface-variant hover:text-error transition-colors ml-1" onclick="removeMember(${userId})"><span class="material-symbols-outlined text-[16px]">close</span></button><input type="hidden" name="members[]" value="${userId}">`;
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
        div.className = 'flex items-center justify-between bg-surface-container border border-surface-container-highest p-3 rounded-xl';
        div.id = 's-badge-'+id;
        div.innerHTML = `<span class="font-label-sm font-bold text-on-surface">${title} <span class="text-on-surface-variant font-normal">- ${artist}</span></span><div class="flex items-center gap-2"><input type="hidden" name="songs[]" value="${id}"><button type="button" class="text-on-surface-variant hover:text-error transition-colors p-1" onclick="removeSong(${id})"><span class="material-symbols-outlined text-[18px]">close</span></button></div>`;
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
             document.getElementById('summary-date').innerHTML = `<span class="material-symbols-outlined text-[16px]">calendar_month</span> ${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const t = document.getElementById('input_event_time').value;
        if(t) {
            document.getElementById('summary-time').innerHTML = `<span class="material-symbols-outlined text-[16px]">schedule</span> ${t.substring(0,5)}`;
        }
        const n = document.getElementById('input_notes').value;
        const noteDiv = document.getElementById('summary-notes');
        if(n) {
            if(!noteDiv) {
                const newDiv = document.createElement('div');
                newDiv.id = 'summary-notes';
                newDiv.className = "mt-3 pt-3 border-t border-surface-container-highest font-body-sm text-on-surface-variant italic flex gap-2";
                newDiv.innerHTML = `<span class="material-symbols-outlined text-[14px] mt-0.5">sticky_note_2</span> <span>${n}</span>`;
                document.querySelector('#summary-type').parentElement.appendChild(newDiv);
            } else {
                noteDiv.innerHTML = `<span class="material-symbols-outlined text-[14px] mt-0.5">sticky_note_2</span> <span>${n}</span>`;
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
