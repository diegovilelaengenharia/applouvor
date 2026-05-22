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

<style>
    /* Estilos globais e micro-animações do Sacred Minimalist */
    body {
        font-family: 'Open Sans', system-ui, -apple-system, sans-serif;
        background-color: #121316; /* Deep Dark Background para wow factor */
        color: #E2E2E5;
    }
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Hanken Grotesk', system-ui, -apple-system, sans-serif;
    }
    .bento-card {
        background-color: #1A1B1F;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 8px; /* Cantos nítidos e sofisticados */
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bento-card:hover {
        border-color: rgba(46, 126, 237, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }
    .bento-border-top {
        border-top: 3px solid #2E7EED;
    }
    .bento-border-gold {
        border-top: 3px solid #FFC107;
    }
    .timeline-line::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 24px;
        bottom: 0;
        width: 1px;
        background: rgba(255, 255, 255, 0.1);
    }
    .timeline-item:last-child .timeline-line::before {
        display: none;
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .btn-premium {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .btn-premium:active {
        transform: scale(0.96);
    }
    /* Estilização para scrollbar sutil em modais */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #2E7EED;
    }
</style>

<!-- Top progress bar -->
<div class="fixed top-0 left-0 w-full h-[3px] bg-[#1A1B1F] z-[60]">
    <div class="h-full bg-worship-blue transition-all duration-1000" style="width: 100%;"></div>
</div>

<main class="mt-4 pb-32 max-w-max-width mx-auto px-margin-mobile md:px-margin-desktop space-y-8 pt-4 <?= ($myMemberData && $myMemberData['status'] === 'pending') ? 'pb-40' : '' ?>">
    
    <!-- Top Action Row -->
    <div class="flex items-center justify-between border-b border-white/5 pb-4">
        <div class="flex items-center gap-3">
            <a href="escalas.php" class="btn-premium p-2 rounded-full hover:bg-white/5 text-slate-300 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div class="flex flex-col">
                <span class="text-[10px] font-bold text-worship-blue uppercase tracking-widest leading-none mb-1"><?= htmlspecialchars($schedule['event_type']) ?></span>
                <h1 class="text-2xl font-bold tracking-tight text-white font-headline-md leading-none">Detalhes da Escala</h1>
            </div>
        </div>
        
        <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
        <div class="flex gap-2">
            <a href="?id=<?= $id ?>&edit=1" class="btn-premium px-4 py-2 bg-worship-blue hover:bg-[#1C6ED7] text-white text-xs font-bold rounded flex items-center gap-2 shadow-[0_4px_14px_rgba(46,126,237,0.2)] transition-all">
                <span class="material-symbols-outlined text-[16px]">edit</span>
                Editar Escala
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-3 rounded flex items-center gap-3 shadow-sm animate-fade-in text-sm font-semibold">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span>Alterações salvas com sucesso no banco de dados!</span>
        </div>
    <?php endif; ?>

    <!-- ================= EDIT MODE ================= -->
    <?php if ($isEditable): ?>
        
        <form method="POST" id="editForm" class="bg-[#1A1B1F] border border-white/5 rounded-lg p-6 md:p-8 space-y-8 shadow-xl relative overflow-hidden">
            <!-- Edit mode indicator -->
            <div class="absolute top-0 right-0 bg-worship-blue text-white px-4 py-1 rounded-bl font-bold text-[9px] tracking-wider uppercase flex items-center gap-1">
                <span class="material-symbols-outlined text-[10px]">edit</span> Modo Edição
            </div>

            <?= App\AuthMiddleware::csrfField() ?>
            <input type="hidden" name="save_changes" value="1">
            
            <!-- 1. Event Info Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-headline-md text-sm font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue text-[18px]">info</span>
                        Informações Básicas
                    </h3>
                    <button type="button" class="text-worship-blue text-xs font-bold hover:underline btn-premium" onclick="openInfoModal()">Ajustar Dados</button>
                </div>
                
                <div class="bg-[#121316] border border-white/5 rounded p-4 cursor-pointer hover:bg-white/[0.02] transition-colors" onclick="openInfoModal()">
                    <div id="summary-type" class="font-headline-md font-bold text-white text-base leading-snug"><?= htmlspecialchars($schedule['event_type']) ?></div>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span id="summary-date" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-worship-blue">calendar_month</span> <?= date('d/m/Y', strtotime($schedule['event_date'])) ?></span>
                        <span id="summary-time" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-worship-blue">schedule</span> <?= substr($schedule['event_time'], 0, 5) ?></span>
                    </div>
                    <?php if($schedule['notes']): ?>
                    <div id="summary-notes" class="mt-3 pt-3 border-t border-white/5 text-xs text-slate-400 italic flex gap-2">
                        <span class="material-symbols-outlined text-[14px] mt-0.5 text-worship-blue">sticky_note_2</span> 
                        <span><?= htmlspecialchars($schedule['notes']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Members Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-headline-md text-sm font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue text-[18px]">groups</span>
                        Participantes
                    </h3>
                    <button type="button" class="text-worship-blue text-xs font-bold hover:underline btn-premium" onclick="document.getElementById('modalMembers').classList.remove('hidden'); document.getElementById('modalMembers').classList.add('flex');">Configurar Equipe</button>
                </div>
                
                <div id="members-bag" class="flex flex-wrap gap-2 min-h-[60px] bg-[#121316] border border-white/5 border-dashed rounded p-4">
                    <?php foreach($teamIds as $tid): 
                        $uName = ''; foreach($allUsers as $u) if($u['id']==$tid) $uName=$u['name'];
                    ?>
                        <span class="inline-flex items-center gap-2 bg-[#1A1B1F] border border-white/10 px-3 py-1.5 rounded font-bold text-xs text-white" id="m-badge-<?= $tid ?>">
                            <?= htmlspecialchars($uName) ?> 
                            <button type="button" class="text-slate-400 hover:text-error transition-colors" onclick="removeMember(<?= $tid ?>)">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                            <input type="hidden" name="members[]" value="<?= $tid ?>">
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Songs Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-headline-md text-sm font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue text-[18px]">queue_music</span>
                        Repertório da Setlist
                    </h3>
                    <button type="button" class="text-worship-blue text-xs font-bold hover:underline btn-premium" onclick="document.getElementById('modalSongs').classList.remove('hidden'); document.getElementById('modalSongs').classList.add('flex');">Selecionar Músicas</button>
                </div>
                
                <div id="songs-bag" class="space-y-2 min-h-[60px] bg-[#121316] border border-white/5 border-dashed rounded p-4">
                    <?php foreach($songs as $sg): ?>
                        <div class="flex items-center justify-between bg-[#1A1B1F] border border-white/10 p-3 rounded" id="s-badge-<?= $sg['song_id'] ?>">
                            <span class="text-xs font-bold text-white"><?= htmlspecialchars($sg['title']) ?> <span class="text-slate-400 font-normal">- <?= htmlspecialchars($sg['artist']) ?></span></span>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="songs[]" value="<?= $sg['song_id'] ?>">
                                <button type="button" class="text-slate-400 hover:text-error transition-colors p-1" onclick="removeSong(<?= $sg['song_id'] ?>)">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Roteiro Editor -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/5 pb-2">
                    <h3 class="font-headline-md text-sm font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-worship-blue text-[18px]">format_list_numbered</span>
                        Roteiro Litúrgico
                    </h3>
                    <div class="flex items-center gap-3">
                        <div class="relative inline-block text-left" id="template-dropdown-container">
                            <button type="button" onclick="toggleTemplateDropdown()" class="text-slate-400 hover:text-white text-xs font-bold flex items-center gap-1 hover:underline btn-premium" style="background: none; border: none; cursor: pointer;">
                                <span class="material-symbols-outlined text-[16px] text-altar-gold">auto_awesome</span> Modelos Rápidos
                            </button>
                            <div id="template-dropdown" class="hidden absolute right-0 mt-2 w-64 rounded bg-[#1A1B1F] border border-white/10 shadow-2xl z-50 overflow-hidden divide-y divide-white/5">
                                <button type="button" onclick="applyLiturgyTemplate('celebracao')" class="w-full text-left px-4 py-3 text-xs font-bold text-white hover:bg-white/5 transition-colors flex flex-col gap-0.5">
                                    <span class="text-worship-blue">✨ Celebração & Adoração</span>
                                    <span class="text-[10px] font-normal text-slate-400">Abertura, Louvor (3 canções), Oração, Palavra, Bênção</span>
                                </button>
                                <button type="button" onclick="applyLiturgyTemplate('tradicional')" class="w-full text-left px-4 py-3 text-xs font-bold text-white hover:bg-white/5 transition-colors flex flex-col gap-0.5">
                                    <span class="text-altar-gold">📖 Culto Tradicional</span>
                                    <span class="text-[10px] font-normal text-slate-400">Abertura, Canto Congregacional, Ofertório, Sermão, Bênção</span>
                                </button>
                                <button type="button" onclick="applyLiturgyTemplate('ceia')" class="w-full text-left px-4 py-3 text-xs font-bold text-white hover:bg-white/5 transition-colors flex flex-col gap-0.5">
                                    <span class="text-emerald-400">🍷 Santa Ceia do Senhor</span>
                                    <span class="text-[10px] font-normal text-slate-400">Louvor Inicial, Palavra da Ceia, Pão e Cálice, Encerramento</span>
                                </button>
                            </div>
                        </div>
                        <span class="text-white/10">|</span>
                        <button type="button" class="text-worship-blue text-xs font-bold hover:underline flex items-center gap-0.5 btn-premium" onclick="openRoteiroModal()">
                            <span class="material-symbols-outlined text-[16px]">add</span> Adicionar Item
                        </button>
                    </div>
                </div>
                
                <div id="roteiro-list" class="space-y-2">
                    <div id="roteiro-empty-state" class="text-center p-8 bg-[#121316] border border-white/5 border-dashed rounded text-xs text-slate-400 italic">
                        Nenhum item no roteiro. Utilize um "Modelo Rápido" ou adicione um novo item acima.
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-6 border-t border-white/5">
                 <a href="?id=<?= $id ?>" class="py-3 px-4 bg-white/5 hover:bg-white/10 text-white rounded font-bold text-xs text-center transition-colors btn-premium">Cancelar Edição</a>
                 <button type="button" onclick="if(confirm('Tem certeza que deseja excluir esta escala permanentemente? Esta ação não pode ser revertida.')) document.getElementById('delForm').submit()" class="py-3 px-4 bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500 hover:text-white rounded font-bold text-xs transition-colors text-center btn-premium">Excluir Escala</button>
                 <button type="submit" class="py-3 px-4 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded font-bold text-xs shadow-md transition-all text-center btn-premium">Salvar Alterações</button>
            </div>
        </form>
        <form id="delForm" method="POST" class="hidden"><input type="hidden" name="delete_schedule" value="1"></form>

    <!-- ================= VIEW MODE ================= -->
    <?php else: ?>
        
        <!-- Hero Bento Card -->
        <section class="bento-card bento-border-top overflow-hidden shadow-xl">
            <div class="p-6 md:p-8 flex flex-col md:flex-row gap-6 md:items-center">
                <div class="flex flex-col items-center justify-center bg-[#121316] rounded w-24 h-24 flex-shrink-0 border border-white/5 shadow-inner">
                    <?php
                    $meses = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
                    $mesDisplay = $meses[(int)$date->format('n') - 1];
                    ?>
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider"><?= $mesDisplay ?></span>
                    <span class="text-3xl font-extrabold text-worship-blue leading-none mt-1"><?= $date->format('d') ?></span>
                </div>
                <div class="flex-grow space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 bg-white/5 border border-white/5 rounded-full text-[10px] font-bold text-slate-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px] text-worship-blue">calendar_month</span>
                            <?= $diaSemana ?>
                        </span>
                        <span class="px-2.5 py-1 bg-white/5 border border-white/5 rounded-full text-[10px] font-bold text-slate-300 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px] text-worship-blue">schedule</span>
                            <?= substr($schedule['event_time'], 0, 5) ?>hs
                        </span>
                    </div>
                    <h2 class="text-xl md:text-2xl font-bold tracking-tight text-white font-headline-md leading-snug"><?= htmlspecialchars($schedule['event_type']) ?></h2>
                    
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="escala_setlist.php?id=<?= (int)$id ?>" class="btn-premium bg-worship-blue hover:bg-[#1C6ED7] text-white px-5 py-2.5 rounded font-bold text-xs flex items-center gap-2 shadow-[0_4px_14px_rgba(46,126,237,0.2)] transition-all">
                            <span class="material-symbols-outlined text-[18px]">queue_music</span>
                            Acessar Setlist de Músicas
                        </a>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="?id=<?= $id ?>&edit=1" class="btn-premium border border-white/10 hover:bg-white/5 text-slate-300 hover:text-white px-5 py-2.5 rounded font-bold text-xs transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                            Editar Escala
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($schedule['notes']): ?>
            <div class="border-t border-white/5 p-6 bg-[#121316]/40">
                <details class="group" open>
                    <summary class="flex justify-between items-center cursor-pointer list-none select-none">
                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-2 uppercase tracking-wider font-headline-md">
                            <span class="material-symbols-outlined text-[16px] text-worship-blue">info</span>
                            Observações Gerais do Culto
                        </span>
                        <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                    </summary>
                    <div class="mt-4 text-sm text-slate-300 leading-relaxed whitespace-pre-wrap pl-6 border-l border-worship-blue/20 font-light">
                        <?= htmlspecialchars($schedule['notes']) ?>
                    </div>
                </details>
            </div>
            <?php endif; ?>
        </section>

        <!-- Check-in Action Card -->
        <?php if ($myMemberData): ?>
        <section class="bento-card p-6 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-worship-blue/10 text-worship-blue rounded flex items-center justify-center shrink-0 border border-worship-blue/10">
                    <span class="material-symbols-outlined text-[24px]">headphones</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white font-headline-md">
                        <?= $myMemberData['is_rehearsed'] ? 'Repertório marcado como Estudado!' : 'Preparação para o Culto' ?>
                    </h3>
                    <p class="text-slate-400 text-xs mt-0.5">
                        <?= $myMemberData['is_rehearsed'] ? 'Tudo pronto! Você confirmou o estudo das cifras e arranjos.' : 'Clique no botão ao lado assim que tiver estudado todas as músicas da setlist.' ?>
                    </p>
                </div>
            </div>
            <form method="POST" class="w-full md:w-auto">
                <?= App\AuthMiddleware::csrfField() ?>
                <input type="hidden" name="action" value="toggle_rehearsal">
                <input type="hidden" name="state" value="<?= $myMemberData['is_rehearsed'] ? '0' : '1' ?>">
                <button type="submit" class="w-full md:w-auto btn-premium bg-white/5 border border-white/10 hover:border-worship-blue hover:text-worship-blue text-slate-300 font-bold text-xs px-6 py-2.5 rounded transition-all flex items-center justify-center gap-2">
                    <?php if ($myMemberData['is_rehearsed']): ?>
                        <span class="material-symbols-outlined text-[16px]">undo</span> Desmarcar Estudo
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Já Estudei tudo
                    <?php endif; ?>
                </button>
            </form>
        </section>
        <?php endif; ?>

        <!-- Team Grid -->
        <section class="space-y-4">
            <h3 class="text-base font-bold uppercase tracking-wider text-slate-400 font-headline-md flex items-center gap-2 pl-1">
                <span class="material-symbols-outlined text-worship-blue text-[18px]">groups</span>
                Equipe Ministerial Convocada
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if(empty($groupedTeam)): ?>
                    <div class="col-span-full bg-[#1A1B1F] border border-white/5 rounded p-6 text-center text-xs text-slate-400 italic">Nenhum voluntário escalado ainda.</div>
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
                    <div class="bg-[#1A1B1F] border border-white/5 rounded p-5 space-y-4 shadow-lg hover:border-worship-blue/20 transition-all">
                        <div class="flex justify-between items-center border-b border-white/5 pb-2">
                            <h4 class="font-bold text-[10px] uppercase text-slate-400 tracking-wider flex items-center gap-1.5 font-headline-md">
                                <span class="material-symbols-outlined text-[16px] text-worship-blue font-bold"><?= $minIcon ?></span>
                                <?= htmlspecialchars($ministryName) ?>
                            </h4>
                            <span class="text-[9px] bg-white/5 px-2 py-0.5 rounded border border-white/10 font-bold text-slate-400"><?= count($members) ?> voluntários</span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach($members as $member):
                                $memberStatus = $member['status'] ?? 'pending';
                                $statusColors = [
                                    'confirmed' => 'bg-emerald-500 shadow-emerald-500/20',
                                    'declined' => 'bg-red-500 shadow-red-500/20',
                                    'absent' => 'bg-red-500 shadow-red-500/20',
                                    'pending' => 'bg-amber-500 shadow-amber-500/20'
                                ];
                                $statusLabels = [
                                    'confirmed' => 'Confirmado',
                                    'declined' => 'Recusado/Ausência Justificada',
                                    'absent' => 'Ausente',
                                    'pending' => 'Pendente de Resposta'
                                ];
                                $sColor = $statusColors[$memberStatus] ?? 'bg-amber-500';
                                $sLabel = $statusLabels[$memberStatus] ?? 'Pendente';
                                $initials = strtoupper(substr($member['name'], 0, 2));
                                $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
                                
                                $avatarPath = $member['avatar'];
                                if ($avatarPath && strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                    $avatarPath = '../uploads/' . $avatarPath;
                                }
                            ?>
                            <div class="flex items-center justify-between p-1.5 rounded hover:bg-white/[0.02] transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <?php if($avatarPath): ?>
                                        <img alt="<?= htmlspecialchars($member['name']) ?>" class="w-8 h-8 rounded object-cover border border-white/10 shadow-sm shrink-0" src="<?= htmlspecialchars($avatarPath) ?>"/>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded bg-[#121316] border border-white/5 text-slate-300 flex items-center justify-center font-bold text-xs shadow-sm shrink-0 select-none"><?= $initials ?></div>
                                    <?php endif; ?>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-semibold text-white truncate leading-tight"><?= htmlspecialchars($member['name']) ?></span>
                                        <span class="text-[9px] text-slate-400 font-bold tracking-wider uppercase mt-0.5 leading-none"><?= htmlspecialchars($instr) ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <?php if(isset($member['is_leader']) && $member['is_leader']): ?>
                                        <span class="text-[8px] bg-altar-gold/10 text-altar-gold border border-altar-gold/20 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Líder</span>
                                    <?php endif; ?>
                                    <div class="w-2 h-2 rounded-full <?= $sColor ?> shadow-[0_0_8px_var(--tw-shadow-color)]" title="<?= $sLabel ?>"></div>
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
            <div class="flex justify-between items-end px-1">
                <h3 class="text-base font-bold uppercase tracking-wider text-slate-400 font-headline-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue text-[18px]">queue_music</span>
                    Repertório Selecionado
                </h3>
                <span class="text-[10px] bg-white/5 border border-white/10 px-2 py-0.5 rounded text-slate-400 font-bold uppercase tracking-wider"><?= count($songs) ?> Canções</span>
            </div>
            <div class="space-y-2">
                <?php if (empty($songs)): ?>
                    <div class="bg-[#1A1B1F] border border-white/5 rounded p-6 text-center text-xs text-slate-400 italic">Nenhuma música adicionada a esta setlist.</div>
                <?php else: ?>
                    <?php foreach ($songs as $index => $song):
                        $resolvedTone = $customToneMap[(int)$song['song_id']] ?? null;
                        $isCustomTone = !empty($resolvedTone);
                        $toneDisplay  = $isCustomTone ? $resolvedTone : ($song['tone'] ?? '');
                        $initial = strtoupper(substr($song['title'], 0, 1));
                    ?>
                    <div class="group bg-[#1A1B1F] border border-white/5 rounded p-4 flex items-center gap-4 hover:border-worship-blue/40 transition-all shadow-md">
                        <div class="w-6 text-[10px] font-bold text-slate-400 text-center select-none"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="w-10 h-10 bg-[#121316] text-worship-blue border border-white/5 rounded flex items-center justify-center font-bold text-base shadow-inner shrink-0 select-none"><?= $initial ?></div>
                        <div class="flex-grow min-w-0">
                            <h4 class="font-bold text-xs text-white truncate leading-tight group-hover:text-worship-blue transition-colors">
                                <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>"><?= htmlspecialchars($song['title']) ?></a>
                            </h4>
                            <p class="text-slate-400 text-[10px] mt-0.5 truncate uppercase tracking-wider font-semibold"><?= htmlspecialchars($song['artist']) ?></p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <?php if ($toneDisplay): ?>
                                <span class="px-2 py-1 bg-[#121316] border border-white/10 rounded text-[10px] font-bold <?= $isCustomTone ? 'border-worship-blue text-worship-blue bg-worship-blue/5' : 'text-slate-300' ?>"><?= htmlspecialchars($toneDisplay) ?></span>
                            <?php endif; ?>
                            <div class="flex gap-1">
                                <?php if(!empty($song['link_letra'])): ?>
                                    <a href="<?= htmlspecialchars($song['link_letra']) ?>" target="_blank" class="p-2 hover:bg-white/5 rounded-full text-slate-400 hover:text-white transition-colors" title="Ver Letra"><span class="material-symbols-outlined text-[18px]">lyrics</span></a>
                                <?php endif; ?>
                                <?php if(!empty($song['link_cifra'])): ?>
                                    <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="p-2 hover:bg-white/5 rounded-full text-slate-400 hover:text-white transition-colors" title="Cifras do Cifra Club"><span class="material-symbols-outlined text-[18px]">queue_music</span></a>
                                <?php endif; ?>
                                <a href="<?= $song['link_video'] ?: ($song['link_audio'] ?: 'https://www.youtube.com/results?search_query='.urlencode($song['title'].' '.$song['artist'])) ?>" target="_blank" class="p-2 hover:bg-worship-blue/15 hover:text-worship-blue rounded-full text-slate-400 transition-colors" title="Ouvir Gravação"><span class="material-symbols-outlined text-[18px]">play_arrow</span></a>
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
            <h3 class="text-base font-bold uppercase tracking-wider text-slate-400 font-headline-md flex items-center gap-2 pl-1">
                <span class="material-symbols-outlined text-worship-blue text-[18px]">format_list_numbered</span>
                Cronograma Litúrgico do Culto
            </h3>
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
                    'musica'    => 'bg-worship-blue shadow-worship-blue/20',
                    'oracao'    => 'bg-altar-gold shadow-altar-gold/20',
                    'palavra'   => 'bg-slate-300 shadow-slate-300/20 text-[#121316]',
                    'anuncio'   => 'bg-orange-500 shadow-orange-500/20',
                    'intervalo' => 'bg-amber-600 shadow-amber-600/20',
                    'livre'     => 'bg-slate-600 shadow-slate-600/20',
                ];
                foreach ($roteiro as $idx => $item):
                    $icon  = $roteiroIcons[$item['item_type']]  ?? 'more_horiz';
                    $color = $roteiroColors[$item['item_type']] ?? 'bg-slate-600';
                    $isMusic = $item['item_type'] === 'musica';
                    $displayTitle = ($isMusic && $item['song_title']) ? $item['song_title'] : ($item['title'] ?: $item['item_type']);
                    $displayTone = $isMusic ? ((!empty($item['custom_tone'])) ? $item['custom_tone'] : ($item['song_tone'] ?? null)) : null;
                ?>
                <div class="relative pl-10 pb-6 timeline-item">
                    <div class="timeline-line"></div>
                    <div class="absolute left-0 top-0.5 w-6 h-6 <?= $color ?> rounded-full flex items-center justify-center z-10 shadow-lg">
                        <span class="material-symbols-outlined text-[13px] font-bold"><?= $icon ?></span>
                    </div>
                    <div class="bg-[#1A1B1F] border border-white/5 rounded p-5 shadow-lg">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[9px] font-bold text-worship-blue uppercase tracking-wider font-headline-md"><?= htmlspecialchars($item['item_type']) ?></span>
                            <?php if ($displayTone): ?>
                                <span class="text-[9px] bg-worship-blue/10 text-worship-blue border border-worship-blue/20 px-2 py-0.5 rounded font-bold"><?= htmlspecialchars($displayTone) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm font-bold text-white"><?= htmlspecialchars($displayTitle) ?></p>
                        <?php if ($isMusic && $item['song_artist']): ?>
                            <p class="text-[10px] text-slate-400 mt-0.5 font-bold uppercase tracking-wider"><?= htmlspecialchars($item['song_artist']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['nota_interna']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="mt-3 p-3 bg-white/[0.02] border border-white/5 text-xs text-slate-400 rounded flex gap-2 italic">
                            <span class="material-symbols-outlined text-[14px] shrink-0 text-altar-gold">visibility_off</span>
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
            <h3 class="text-base font-bold uppercase tracking-wider text-slate-400 font-headline-md flex items-center gap-2 pl-1">
                <span class="material-symbols-outlined text-worship-blue text-[18px]">forum</span>
                Mural de Conversas & Alinhamento
            </h3>
            <div class="bg-[#1A1B1F] border border-white/5 rounded overflow-hidden flex flex-col h-[420px] shadow-xl">
                <div class="flex-grow p-6 space-y-6 overflow-y-auto no-scrollbar custom-scrollbar" id="chat-container">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-20 text-xs text-slate-500 italic select-none">Nenhuma mensagem enviada. Seja o primeiro a postar um aviso ou oração!</div>
                    <?php else: ?>
                        <?php foreach($comments as $comment): 
                            $isMyComment = $comment['user_id'] == $_SESSION['user_id'];
                            $timeStr = date('H:i', strtotime($comment['created_at']));
                            $dateStr = date('d/m', strtotime($comment['created_at']));
                            $initials = strtoupper(substr($comment['user_name'], 0, 2));
                            
                            $avatarPath = $comment['user_avatar'];
                            if ($avatarPath && strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                $avatarPath = '../uploads/' . $avatarPath;
                            }
                        ?>
                        <div class="flex gap-4 <?= $isMyComment ? 'flex-row-reverse' : '' ?>">
                            <?php if($avatarPath): ?>
                                <img alt="Avatar" class="w-9 h-9 rounded object-cover border border-white/10 shrink-0 select-none" src="<?= htmlspecialchars($avatarPath) ?>"/>
                            <?php else: ?>
                                <div class="w-9 h-9 rounded bg-[#121316] border border-white/5 text-slate-300 flex items-center justify-center font-bold text-xs shrink-0 select-none"><?= $initials ?></div>
                            <?php endif; ?>
                            
                            <div class="space-y-1 max-w-[75%] flex flex-col <?= $isMyComment ? 'items-end' : 'items-start' ?>">
                                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                    <span class="font-bold text-slate-300"><?= $isMyComment ? 'Você' : htmlspecialchars($comment['user_name']) ?></span>
                                    <span>•</span>
                                    <span><?= $dateStr ?> às <?= $timeStr ?></span>
                                </div>
                                <div class="p-3 rounded-lg border border-white/5 text-xs text-slate-200 leading-relaxed relative group <?= $isMyComment ? 'bg-worship-blue border-worship-blue/10 text-white rounded-tr-none' : 'bg-[#121316] rounded-tl-none' ?>">
                                    <p class="whitespace-pre-wrap"><?= htmlspecialchars($comment['comment']) ?></p>
                                    
                                    <?php if ($isMyComment || $_SESSION['user_role'] === 'admin'): ?>
                                    <form method="POST" class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity bg-[#1A1B1F] rounded border border-white/10 p-0.5 flex shrink-0">
                                        <?= App\AuthMiddleware::csrfField() ?>
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="text-slate-400 hover:text-error transition-colors p-1" onclick="return confirm('Deseja excluir este comentário?')">
                                            <span class="material-symbols-outlined text-[14px]">delete</span>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Input Bar -->
                <div class="p-4 bg-[#121316] border-t border-white/5">
                    <form method="POST" action="" class="flex items-center gap-3 bg-[#1A1B1F] border border-white/5 rounded-full px-4 py-2 focus-within:border-worship-blue transition-all">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="add_comment">
                        <input class="flex-grow border-none focus:ring-0 text-xs bg-transparent text-white placeholder-slate-500 focus:outline-none" placeholder="Escreva um comunicado ou oração no mural..." type="text" name="comment" required autocomplete="off"/>
                        <button type="submit" class="text-worship-blue hover:scale-110 active:scale-95 transition-transform flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[18px]">send</span>
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
<div id="modalMembers" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-[#1A1B1F] w-full max-w-md rounded-lg overflow-hidden shadow-2xl flex flex-col max-h-[80vh] border border-white/5 animate-scale-up">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-[#1A1B1F]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-headline-md">Escalar Voluntários</h3>
            <button onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');" class="text-slate-400 hover:text-white p-2 rounded hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 bg-[#1A1B1F] space-y-4 no-scrollbar custom-scrollbar" id="listMembers">
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
            <div class="bg-[#121316] rounded border border-white/5 overflow-hidden">
                <div class="px-4 py-2.5 bg-white/[0.02] border-b border-white/5 text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 font-headline-md select-none">
                    <span class="material-symbols-outlined text-[14px] text-worship-blue font-bold"><?= $icon ?></span> <?= htmlspecialchars($role) ?>
                </div>
                <div class="divide-y divide-white/5">
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
                <label class="flex items-center gap-3 p-3.5 hover:bg-white/[0.01] cursor-pointer transition-colors group active:scale-[0.99] select-none">
                    <div class="relative flex items-center shrink-0">
                        <input type="checkbox" name="temp_members[<?= $u['id'] ?>]" value="<?= htmlspecialchars($role) ?>" 
                               data-user-id="<?= $u['id'] ?>" data-role="<?= htmlspecialchars($role) ?>"
                               <?= $isChecked ? 'checked' : '' ?> onchange="toggleMemberSelection(this)"
                               class="peer w-4.5 h-4.5 appearance-none border border-white/10 rounded checked:bg-worship-blue checked:border-worship-blue bg-transparent focus:ring-0 transition-all cursor-pointer">
                        <span class="material-symbols-outlined absolute text-white text-[12px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity font-bold">check</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-bold text-xs text-slate-200 group-hover:text-worship-blue transition-colors font-body-md"><?= htmlspecialchars($u['name']) ?></span>
                        <?php if($role === 'Outros' && $u['instrument']): ?>
                            <span class="text-[9px] text-slate-500 mt-0.5 truncate"><?= htmlspecialchars($u['instrument']) ?></span>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-4 border-t border-white/5 flex gap-3 bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white rounded font-bold text-xs transition-colors btn-premium" onclick="document.getElementById('modalMembers').classList.add('hidden'); document.getElementById('modalMembers').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded font-bold text-xs transition-colors btn-premium" onclick="confirmMemberSelection()">Confirmar Seleção</button>
        </div>
    </div>
</div>

<!-- Modal Songs -->
<div id="modalSongs" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-[#1A1B1F] w-full max-w-md rounded-lg overflow-hidden shadow-2xl flex flex-col max-h-[80vh] border border-white/5 animate-scale-up">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-[#1A1B1F]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-headline-md">Adicionar Músicas</h3>
            <button onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');" class="text-slate-400 hover:text-white p-2 rounded hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="p-4 bg-[#121316] border-b border-white/5">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-4 text-slate-500 text-[18px] font-bold">search</span>
                <input type="text" id="searchSongs" placeholder="Filtrar por título, artista..." onkeyup="filterSongList(this.value)" class="w-full bg-[#1A1B1F] border border-white/10 rounded pl-11 pr-4 py-2.5 font-semibold text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-all duration-200">
            </div>
        </div>

        <div class="overflow-y-auto flex-1 bg-[#1A1B1F] p-3 no-scrollbar custom-scrollbar" id="listSongs">
            <div id="emptySongsState" class="text-center py-12 hidden">
                <span class="material-symbols-outlined text-[36px] text-slate-600 mb-2 font-bold">music_off</span>
                <p class="text-xs font-semibold text-slate-400">Nenhuma canção encontrada</p>
            </div>
            
            <div class="divide-y divide-white/5">
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
                <label style="display: <?= $displayStyle ?>;" class="items-center gap-4 p-3.5 hover:bg-white/[0.01] rounded cursor-pointer transition-colors group active:scale-[0.99] select-none" data-song-search="<?= strtolower(htmlspecialchars($s['title'].' '.$s['artist'])) ?>">
                    <div class="relative flex items-center shrink-0">
                        <input type="checkbox" value="<?= $s['id'] ?>" data-title="<?= htmlspecialchars($s['title'].' - '.$s['artist']) ?>" 
                            <?= $isSelected ? 'checked' : '' ?> onchange="toggleSong(this)"
                            class="peer w-4.5 h-4.5 appearance-none border border-white/10 rounded checked:bg-worship-blue checked:border-worship-blue bg-transparent focus:ring-0 transition-all cursor-pointer">
                        <span class="material-symbols-outlined absolute text-white text-[12px] left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity font-bold">check</span>
                    </div>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="font-bold text-xs text-slate-200 group-hover:text-worship-blue transition-colors truncate font-body-md"><?= htmlspecialchars($s['title']) ?></span>
                        <span class="text-[9px] text-slate-500 truncate mt-0.5 uppercase tracking-wider font-semibold"><?= htmlspecialchars($s['artist']) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="p-4 border-t border-white/5 flex gap-3 bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white rounded font-bold text-xs transition-colors btn-premium" onclick="document.getElementById('modalSongs').classList.add('hidden'); document.getElementById('modalSongs').classList.remove('flex');">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded font-bold text-xs transition-colors btn-premium" onclick="confirmSongSelection()">Confirmar Canções</button>
        </div>
    </div>
</div>

<!-- Modal Info -->
<div id="modalInfo" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-[#1A1B1F] w-full max-w-md rounded-lg overflow-hidden shadow-2xl flex flex-col border border-white/5 animate-scale-up">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-[#1A1B1F]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-headline-md">Info do Culto</h3>
            <button onclick="closeInfoModal(false)" class="text-slate-400 hover:text-white p-2 rounded hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="p-6 space-y-5 bg-[#1A1B1F]">
            <div class="flex flex-col gap-1.5">
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Tipo do Evento</label>
                <input type="text" name="event_type" id="input_event_type" form="editForm" value="<?= htmlspecialchars($schedule['event_type']) ?>" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Data</label>
                    <input type="date" name="event_date" id="input_event_date" form="editForm" value="<?= $schedule['event_date'] ?>" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Horário</label>
                    <input type="time" name="event_time" id="input_event_time" form="editForm" value="<?= $schedule['event_time'] ?>" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors">
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Observações da Escala</label>
                <textarea name="notes" id="input_notes" form="editForm" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors resize-none" rows="3"><?= htmlspecialchars($schedule['notes']) ?></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-white/5 flex gap-3 bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white rounded font-bold text-xs transition-colors btn-premium" onclick="closeInfoModal(false)">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded font-bold text-xs transition-colors btn-premium" onclick="closeInfoModal(true)">Reter Alterações</button>
        </div>
    </div>
</div>

<!-- Modal Roteiro -->
<div id="modalRoteiro" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden items-center justify-center px-4 transition-all duration-300">
    <div class="bg-[#1A1B1F] w-full max-w-md rounded-lg overflow-hidden shadow-2xl flex flex-col max-h-[80vh] border border-white/5 animate-scale-up">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-[#1A1B1F]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-headline-md flex items-center gap-2">
                <span class="material-symbols-outlined text-worship-blue font-bold">format_list_numbered</span> Roteiro: Adicionar Item
            </h3>
            <button onclick="closeRoteiroModal()" class="text-slate-400 hover:text-white p-2 rounded hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto space-y-5 bg-[#1A1B1F] no-scrollbar custom-scrollbar">
            <div class="flex flex-col gap-1.5">
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Tipo do Item</label>
                <select id="roteiro-type" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors cursor-pointer" onchange="handleRoteiroTypeChange(this.value)">
                    <option value="musica">🎵 Canção da Setlist</option>
                    <option value="oracao">🙏 Momento de Oração</option>
                    <option value="palavra">📖 Palavra / Sermão</option>
                    <option value="anuncio">📢 Avisos / Campanhas</option>
                    <option value="intervalo">☕ Momento de Intervalo</option>
                    <option value="livre">➕ Elemento Livre</option>
                </select>
            </div>

            <div id="roteiro-song-group" class="flex flex-col gap-1.5">
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Música Vinculada</label>
                <select id="roteiro-song" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors cursor-pointer" onchange="onRoteiroSongChange(this)">
                    <option value="">— Selecionar música —</option>
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
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Tom Customizado <span class="font-normal text-[8px] lowercase">(opcional — deixa vazio para o original)</span></label>
                <input type="text" id="roteiro-custom-tone" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors" placeholder="Ex: C, E, G#m..." maxlength="10">
            </div>

            <div id="roteiro-title-group" class="flex flex-col gap-1.5">
                <label id="roteiro-title-label" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Título / Título Litúrgico</label>
                <input type="text" id="roteiro-title" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors" placeholder="Ex: Prelúdio Instrumental" maxlength="255">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-0.5">Nota Interna do Culto <span class="font-normal text-[8px] lowercase">(visível apenas aos voluntários)</span></label>
                <textarea id="roteiro-nota" class="w-full bg-[#121316] border border-white/5 rounded px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-worship-blue focus:ring-0 transition-colors resize-none" rows="2" placeholder="Informação adicional para o direcionamento..."></textarea>
            </div>
        </div>
        
        <div class="p-4 border-t border-white/5 flex gap-3 bg-[#1A1B1F]">
            <button type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white rounded font-bold text-xs transition-colors btn-premium" onclick="closeRoteiroModal()">Cancelar</button>
            <button type="button" class="flex-1 py-3 bg-worship-blue hover:bg-[#1C6ED7] text-white rounded font-bold text-xs transition-colors btn-premium" onclick="submitRoteiroItem()">Anexar Item</button>
        </div>
    </div>
</div>

<script>
// Lógica para Modais no Modo Edição
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
        sp.className = 'inline-flex items-center gap-2 bg-[#1A1B1F] border border-white/10 px-3 py-1.5 rounded text-xs font-bold text-white transition-all';
        sp.id = 'm-badge-'+userId;
        sp.innerHTML = `${name} <span class="font-normal text-[9px] text-slate-400">(${role})</span> <button type="button" class="text-slate-400 hover:text-error transition-colors ml-1" onclick="removeMember(${userId})"><span class="material-symbols-outlined text-[14px] font-bold">close</span></button><input type="hidden" name="members[]" value="${userId}">`;
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
        div.className = 'flex items-center justify-between bg-[#1A1B1F] border border-white/10 p-3 rounded hover:border-worship-blue/40 transition-colors';
        div.id = 's-badge-'+id;
        div.innerHTML = `<span class="text-xs font-bold text-white">${title} <span class="text-slate-400 font-normal">- ${artist}</span></span><div class="flex items-center gap-2"><input type="hidden" name="songs[]" value="${id}"><button type="button" class="text-slate-400 hover:text-error transition-colors p-1" onclick="removeSong(${id})"><span class="material-symbols-outlined text-[16px] font-bold">close</span></button></div>`;
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
             document.getElementById('summary-date').innerHTML = `<span class="material-symbols-outlined text-[14px] text-worship-blue font-bold">calendar_month</span> ${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const t = document.getElementById('input_event_time').value;
        if(t) {
            document.getElementById('summary-time').innerHTML = `<span class="material-symbols-outlined text-[14px] text-worship-blue font-bold">schedule</span> ${t.substring(0,5)}`;
        }
        const n = document.getElementById('input_notes').value;
        const noteDiv = document.getElementById('summary-notes');
        if(n) {
            if(!noteDiv) {
                const newDiv = document.createElement('div');
                newDiv.id = 'summary-notes';
                newDiv.className = "mt-3 pt-3 border-t border-white/5 text-xs text-slate-400 italic flex gap-2";
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
    <div class="fixed bottom-0 left-0 right-0 bg-[#1A1B1F]/90 backdrop-blur-md border-t border-white/5 px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] flex gap-3 pb-safe-bottom" id="confirm-footer">
        <button class="flex-1 py-3 bg-worship-blue text-white rounded font-bold text-xs shadow-lg hover:bg-[#1C6ED7] transition-all flex items-center justify-center gap-2 btn-premium animate-fade-in" onclick="confirmPresence('confirmed')">
            <span class="material-symbols-outlined text-[18px]">check_circle</span> Confirmar Presença
        </button>
        <button class="flex-1 py-3 bg-transparent border border-red-500/20 text-red-400 rounded font-bold text-xs hover:bg-red-500/5 transition-all flex items-center justify-center gap-2 btn-premium animate-fade-in" onclick="confirmPresence('declined')">
            <span class="material-symbols-outlined text-[18px]">cancel</span> Notificar Ausência
        </button>
    </div>
    <?php elseif ($currentStatus === 'confirmed'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-[#1A1B1F]/90 backdrop-blur-md border-t border-white/5 px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
        <span class="text-xs font-bold text-emerald-400 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-emerald-500">check_circle</span> Presença Confirmada!
        </span>
        <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded text-[10px] font-bold text-slate-300 hover:text-white transition-colors btn-premium" onclick="showConfirmButtons()">Alterar Status</button>
    </div>
    <?php elseif ($currentStatus === 'declined'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-[#1A1B1F]/90 backdrop-blur-md border-t border-white/5 px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
        <span class="text-xs font-bold text-red-400 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-red-500">cancel</span> Ausência Notificada
        </span>
        <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded text-[10px] font-bold text-slate-300 hover:text-white transition-colors btn-premium" onclick="showConfirmButtons()">Alterar Status</button>
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
                var labelText = isConfirmed ? 'Presença Confirmada!' : 'Ausência Notificada';
                var colorClass = isConfirmed ? 'text-emerald-400' : 'text-red-400';
                var iconName = isConfirmed ? 'check_circle' : 'cancel';
                var iconColor = isConfirmed ? 'text-emerald-500' : 'text-red-500';

                document.getElementById('confirm-footer-container').innerHTML = `
                    <div class="fixed bottom-0 left-0 right-0 bg-[#1A1B1F]/90 backdrop-blur-md border-t border-white/5 px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] flex items-center justify-between pb-safe-bottom" id="confirm-footer">
                        <span class="text-xs font-bold ${colorClass} flex items-center gap-2 animate-fade-in">
                            <span class="material-symbols-outlined text-[20px] ${iconColor}">${iconName}</span> ${labelText}
                        </span>
                        <button class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded text-[10px] font-bold text-slate-300 hover:text-white transition-colors btn-premium animate-fade-in" onclick="showConfirmButtons()">Alterar Status</button>
                    </div>`;

                document.querySelector('main').classList.remove('pb-40');
            } else {
                alert('Erro ao salvar no servidor: ' + (data.message || 'Tente novamente.'));
                buttons.forEach(btn => btn.disabled = false);
            }
        })
        .catch(function(err) {
            alert('Erro na conexão. Verifique sua rede e tente novamente.');
            buttons.forEach(btn => btn.disabled = false);
        });
    }

    function showConfirmButtons() {
        document.getElementById('confirm-footer-container').innerHTML = `
            <div class="fixed bottom-0 left-0 right-0 bg-[#1A1B1F]/90 backdrop-blur-md border-t border-white/5 px-margin-mobile py-4 z-40 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] flex gap-3 pb-safe-bottom" id="confirm-footer">
                <button class="flex-1 py-3 bg-worship-blue text-white rounded font-bold text-xs shadow-lg hover:bg-[#1C6ED7] transition-all flex items-center justify-center gap-2 btn-premium animate-fade-in" onclick="confirmPresence('confirmed')">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span> Confirmar Presença
                </button>
                <button class="flex-1 py-3 bg-transparent border border-red-500/20 text-red-400 rounded font-bold text-xs hover:bg-red-500/5 transition-all flex items-center justify-center gap-2 btn-premium animate-fade-in" onclick="confirmPresence('declined')">
                    <span class="material-symbols-outlined text-[18px]">cancel</span> Notificar Ausência
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

    window.toggleTemplateDropdown = function() {
        var dropdown = document.getElementById('template-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    };

    window.applyLiturgyTemplate = function(templateType) {
        var LITURGY_TEMPLATES = {
            celebracao: [
                { item_type: 'livre', title: 'Prelúdio Instrumental / Abertura', nota_interna: 'Boas-vindas e introdução ao culto' },
                { item_type: 'oracao', title: 'Oração Inicial de Adoração', nota_interna: 'Clamor de invocação da presença de Deus' },
                { item_type: 'livre', title: 'Período de Louvor Congregacional', nota_interna: 'Cantar as músicas principais da escala' },
                { item_type: 'oracao', title: 'Momento de Intercessão / Oração da Igreja', nota_interna: 'Apresentar os pedidos da comunidade' },
                { item_type: 'palavra', title: 'Mensagem Pastoral / Pregação da Palavra', nota_interna: 'Edificação e exposição bíblica' },
                { item_type: 'anuncio', title: 'Apelo, Resposta e Avisos Finais', nota_interna: 'Consagração e avisos do boletim da semana' },
                { item_type: 'oracao', title: 'Bênção Apostólica', nota_interna: 'Envio e encerramento em comunhão' }
            ],
            tradicional: [
                { item_type: 'livre', title: 'Prelúdio Instrumental e Abertura Litúrgica', nota_interna: 'Leitura de Salmo Intróito' },
                { item_type: 'oracao', title: 'Oração de Invocação e Gratidão', nota_interna: 'Reconhecimento da soberania e graça divina' },
                { item_type: 'livre', title: 'Canto Congregacional (Hino Tradicional)', nota_interna: 'Uso do Cantor Cristão ou Harpa' },
                { item_type: 'palavra', title: 'Leitura Bíblica Alternada', nota_interna: 'Leitura com a participação de toda a igreja' },
                { item_type: 'livre', title: 'Dedicatória dos Dízimos e Ofertório', nota_interna: 'Canto de ofertório instrumental' },
                { item_type: 'palavra', title: 'Sermão Expositivo', nota_interna: 'Ministração da Palavra de Deus' },
                { item_type: 'oracao', title: 'Oração de Entrega e Bênção Final', nota_interna: 'Agradecimento e envio dos irmãos' }
            ],
            ceia: [
                { item_type: 'livre', title: 'Período de Adoração e Louvor Inicial', nota_interna: 'Foco na cruz e no sacrifício de Cristo' },
                { item_type: 'palavra', title: 'Palavra da Ceia (1 Coríntios 11:23-29)', nota_interna: 'Leitura e reflexão sobre a Ceia do Senhor' },
                { item_type: 'oracao', title: 'Oração de Gratidão pelo Pão (Corpo)', nota_interna: 'Distribuição do Pão à igreja em silêncio' },
                { item_type: 'livre', title: 'Distribuição do Pão (Corpo)', nota_interna: 'Fundo musical instrumental suave' },
                { item_type: 'oracao', title: 'Oração de Gratidão pelo Cálice (Sangue)', nota_interna: 'Distribuição do Cálice à igreja em silêncio' },
                { item_type: 'livre', title: 'Distribuição do Cálice (Sangue)', nota_interna: 'Fundo musical instrumental suave' },
                { item_type: 'anuncio', title: 'Cântico de Encerramento e Avisos do Corpo', nota_interna: 'Saudação fraternal final' }
            ]
        };

        var template = LITURGY_TEMPLATES[templateType];
        if (!template) return;

        if (!confirm('Deseja realmente injetar este modelo de liturgia? Os itens serão inseridos no final do roteiro atual.')) {
            return;
        }

        var dropdown = document.getElementById('template-dropdown');
        if (dropdown) dropdown.classList.add('hidden');

        var promises = template.map(function(item) {
            var payload = {
                action: 'add',
                schedule_id: scheduleId,
                item_type: item.item_type,
                title: item.title,
                nota_interna: item.nota_interna,
                song_id: null,
                custom_tone: null
            };
            return fetch('../api/roteiro.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); });
        });

        Promise.all(promises)
            .then(function(results) {
                var failed = results.filter(function(res) { return !res.success; });
                if (failed.length > 0) {
                    alert('Alguns itens do roteiro não puderam ser importados. Recarregando a lista...');
                }
                loadRoteiro();
            })
            .catch(function(err) {
                alert('Erro de conexão ao sincronizar modelos de roteiro.');
                loadRoteiro();
            });
    };

    document.addEventListener('click', function(e) {
        var dropdown = document.getElementById('template-dropdown');
        var container = document.getElementById('template-dropdown-container');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

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
            if (item.custom_tone) subParts.push('<span class="bg-worship-blue/10 text-worship-blue border border-worship-blue/20 px-1.5 rounded">' + escHtml(item.custom_tone) + '</span>');
            else if (item.song_tone) subParts.push(escHtml(item.song_tone));

            var notaHtml = item.nota_interna
                ? '<div class="mt-2 text-[10px] text-slate-400 font-bold flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px] text-altar-gold">visibility_off</span> ' + escHtml(item.nota_interna) + '</div>'
                : '';

            var el = document.createElement('div');
            el.className = 'roteiro-item flex items-center gap-3 bg-[#1A1B1F] border border-white/5 rounded p-3 shadow-md';
            el.dataset.id  = item.id;
            el.dataset.pos = item.order_position;
            el.innerHTML = `
                <div class="w-10 h-10 bg-[#121316] text-worship-blue border border-white/5 rounded flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px] font-bold">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-white truncate">${escHtml(displayTitle)}</div>
                    <div class="text-[9px] text-slate-400 truncate mt-0.5 uppercase tracking-wider font-semibold">${subParts.join(' <span class="mx-1">•</span> ')}</div>
                    ${notaHtml}
                </div>
                <div class="flex flex-col gap-1 shrink-0">
                    <button type="button" class="w-6 h-6 flex items-center justify-center bg-white/5 text-slate-400 rounded disabled:opacity-30 hover:bg-white/10 hover:text-white transition-colors" onclick="moveRoteiroItem(${idx}, -1)" ${isFirst ? 'disabled' : ''}><span class="material-symbols-outlined text-[14px] font-bold">expand_less</span></button>
                    <button type="button" class="w-6 h-6 flex items-center justify-center bg-white/5 text-slate-400 rounded disabled:opacity-30 hover:bg-white/10 hover:text-white transition-colors" onclick="moveRoteiroItem(${idx}, 1)" ${isLast  ? 'disabled' : ''}><span class="material-symbols-outlined text-[14px] font-bold">expand_more</span></button>
                </div>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-error hover:bg-red-500/10 rounded-full transition-colors ml-1 shrink-0 btn-premium" onclick="deleteRoteiroItem(${item.id})">
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
            if (!data.success) alert('Erro ao ordenar no banco de dados: ' + (data.message || ''));
        });
    }

    function deleteRoteiroItem(itemId) {
        if (!confirm('Deseja realmente remover este item do roteiro?')) return;
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
                alert('Erro ao excluir do servidor: ' + (data.message || ''));
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
        if (titleLabel) titleLabel.innerHTML = isSong ? 'Título alternativo <span class="font-normal text-[8px]">(opcional)</span>' : 'Título / Direcionamento Litúrgico *';
    };

    window.onRoteiroSongChange = function(sel) {
        var tone = sel.options[sel.selectedIndex].getAttribute('data-tone') || '';
        var toneInput = document.getElementById('roteiro-custom-tone');
        if (toneInput) toneInput.placeholder = tone ? 'Ex: ' + tone + ' (original)' : 'Ex: D, Em, F#m...';
    };

    window.submitRoteiroItem = function() {
        var type = document.getElementById('roteiro-type').value;
        var songId = type === 'musica' ? (parseInt(document.getElementById('roteiro-song').value) || null) : null;
        var customTone = type === 'musica' ? (document.getElementById('roteiro-custom-tone').value.trim() || null) : null;
        var title = document.getElementById('roteiro-title').value.trim() || null;
        var nota = document.getElementById('roteiro-nota').value.trim() || null;

        if (type !== 'musica' && !title) {
            alert('Preencha o título litúrgico do item.');
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
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.animate-scale-up {
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.pb-safe-bottom {
    padding-bottom: calc(1rem + env(safe-area-inset-bottom, 16px));
}
</style>

<?php renderAppFooter(); ?>
