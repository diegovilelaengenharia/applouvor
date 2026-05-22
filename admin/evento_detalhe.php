<?php
// admin/evento_detalhe.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

$eventId = $_GET['id'] ?? 0;

// Buscar evento
$stmt = $pdo->prepare("
    SELECT e.*, u.name as creator_name
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: agenda.php");
    exit;
}

// Buscar participantes
$stmtPart = $pdo->prepare("
    SELECT ep.*, u.name, u.instrument, u.avatar, u.avatar_color
    FROM event_participants ep
    JOIN users u ON ep.user_id = u.id
    WHERE ep.event_id = ?
    ORDER BY ep.status ASC, u.name ASC
");
$stmtPart->execute([$eventId]);
$participants = $stmtPart->fetchAll(PDO::FETCH_ASSOC);

// Confirmar presença
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_presence'])) {
    $stmtConfirm = $pdo->prepare("
        UPDATE event_participants 
        SET status = 'confirmed', responded_at = NOW()
        WHERE event_id = ? AND user_id = ?
    ");
    $stmtConfirm->execute([$eventId, $_SESSION['user_id']]);
    header("Location: evento_detalhe.php?id=$eventId&msg=confirmed");
    exit;
}

// Declinar presença
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_presence'])) {
    $stmtDecline = $pdo->prepare("
        UPDATE event_participants 
        SET status = 'declined', responded_at = NOW()
        WHERE event_id = ? AND user_id = ?
    ");
    $stmtDecline->execute([$eventId, $_SESSION['user_id']]);
    header("Location: evento_detalhe.php?id=$eventId&msg=declined");
    exit;
}

// Verificar se o usuário atual é participante
$userParticipant = null;
foreach ($participants as $p) {
    if ($p['user_id'] == $_SESSION['user_id']) {
        $userParticipant = $p;
        break;
    }
}

// Mensagens de Feedback
$msg = $_GET['msg'] ?? '';
$feedbackClass = '';
$feedbackText = '';

if ($msg === 'confirmed') {
    $feedbackClass = 'feedback-success';
    $feedbackText = 'Presença confirmada com sucesso! 🎸';
} elseif ($msg === 'declined') {
    $feedbackClass = 'feedback-error';
    $feedbackText = 'Ausência registrada. Sentiremos sua falta! 😢';
}

// Função auxiliar para data em PT-BR (Windows safe)
function formDataPtBr($dateString) {
    $timestamp = strtotime($dateString);
    $semana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    $mes = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    
    $dSemana = $semana[date('w', $timestamp)];
    $dMes = $mes[date('n', $timestamp) - 1];
    $dia = date('d', $timestamp);
    $ano = date('Y', $timestamp);
    
    return "$dSemana, $dia de $dMes de $ano";
}

function getMesAbbrev($dateString) {
    $meses = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
    return $meses[date('n', strtotime($dateString)) - 1];
}

// Mapas de tipos e cores
$typeLabels = [
    'reuniao' => 'Reunião',
    'ensaio_extra' => 'Ensaio Extra',
    'confraternizacao' => 'Confraternização',
    'aniversario' => 'Aniversário',
    'treinamento' => 'Treinamento',
    'outro' => 'Outro'
];

$typeIcons = [
    'reuniao' => 'users',
    'ensaio_extra' => 'music',
    'confraternizacao' => 'party-popper',
    'aniversario' => 'cake',
    'treinamento' => 'book-open',
    'outro' => 'calendar'
];

renderAppHeader('Detalhes do Evento');
renderPageHeader($event['title'], '');
?>

<!-- FEEDBACK MESSAGE (Sacred Minimalist) -->
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6 pb-28">

    <?php if ($feedbackText): ?>
    <div class="border rounded-[2px] p-4 flex items-center gap-3 transition-all duration-300 <?= $msg === 'confirmed' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-red-950/20 border-red-900/30 text-red-500' ?>">
        <?php if($msg === 'confirmed'): ?>
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <?php else: ?>
            <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
        <?php endif; ?>
        <span class="text-xs sm:text-sm font-bold uppercase tracking-wide leading-none"><?= htmlspecialchars($feedbackText) ?></span>
    </div>
    <?php endif; ?>

    <!-- EVENT SUMMARY CARD (Bento Style) -->
    <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-5 sm:p-6 shadow-sm reveal-item">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4 sm:gap-5">
                <!-- Data Brutalista -->
                <div class="bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-3 text-center min-w-[70px] flex flex-col justify-center shadow-inner">
                    <?php $dateObj = new DateTime($event['start_datetime']); ?>
                    <div class="text-2xl font-black text-gray-800 dark:text-white leading-none font-outfit"><?= $dateObj->format('d') ?></div>
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#2E7EED] mt-1.5 leading-none"><?= getMesAbbrev($event['start_datetime']) ?></div>
                </div>
                
                <!-- Informações Detalhadas -->
                <div class="space-y-1.5">
                    <h3 class="text-base sm:text-lg font-black text-gray-800 dark:text-white font-outfit tracking-tight uppercase"><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="flex flex-col gap-1.5">
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold flex items-center gap-2">
                            <i data-lucide="<?= $typeIcons[$event['event_type']] ?? 'calendar' ?>" class="w-3.5 h-3.5 text-[#2E7EED]"></i>
                            <span><?= $typeLabels[$event['event_type']] ?? 'Evento' ?></span>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold flex items-center gap-2">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500"></i>
                            <span><?= formDataPtBr($event['start_datetime']) ?></span>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold flex items-center gap-2">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500"></i>
                            <span><?= date('H:i', strtotime($event['start_datetime'])) ?> - <?= date('H:i', strtotime($event['end_datetime'])) ?></span>
                        </div>
                        <?php if ($event['location']): ?>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500"></i>
                            <span class="truncate"><?= htmlspecialchars($event['location']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($event['created_by'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
            <a href="evento_editar.php?id=<?= $event['id'] ?>" class="p-2.5 bg-gray-50 hover:bg-gray-100 dark:bg-[#121316] dark:hover:bg-[#26272B] border border-gray-100 dark:border-[#26272B] rounded-[2px] text-gray-500 dark:text-gray-400 hover:text-[#2E7EED] active:scale-[0.97] will-change-transform transition-all shadow-sm" title="Editar Evento">
                <i data-lucide="edit-2" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
        </div>

        <?php if($event['description']): ?>
        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-[#26272B]">
            <span class="text-xs font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mb-2">
                <i data-lucide="align-left" class="w-3.5 h-3.5"></i> Descrição
            </span>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 font-medium leading-relaxed bg-gray-50/50 dark:bg-[#121316]/30 border border-gray-100 dark:border-[#26272B] p-3 rounded-[2px]">
                <?= nl2br(htmlspecialchars($event['description'])) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- PARTICIPANTS SECTION -->
    <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-5 shadow-sm space-y-4 reveal-item">
        <?php
            $confirmedCount = 0;
            foreach ($participants as $p) {
                if ($p['status'] == 'confirmed') $confirmedCount++;
            }
        ?>
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-[#26272B] pb-3.5">
            <span class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Convidados</span>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-[#26272B] px-2 py-0.5 rounded-[2px] text-gray-500 dark:text-gray-400">Total: <?= count($participants) ?></span>
                <?php if($confirmedCount > 0): ?>
                    <span class="text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-[2px] text-emerald-600 dark:text-emerald-500">Confirmados: <?= $confirmedCount ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($participants)): ?>
            <div class="text-center py-6 text-gray-400 dark:text-gray-500 text-xs font-bold uppercase tracking-wider">
                Nenhum participante convidado
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($participants as $p):
                    $statusClass = $p['status']; // confirmed, pending, declined
                    $photo = !empty($p['avatar']) ? $p['avatar'] : null;
                    if($photo && strpos($photo, 'uploads') === false && strpos($photo, 'http') === false) {
                        $photo = '../uploads/' . $photo;
                    }
                    $initials = strtoupper(substr($p['name'], 0, 1));
                    
                    // Cores litúrgicas sóbrias para status
                    $borderStatus = 'border-gray-100 dark:border-[#26272B]';
                    $badgeStatus = 'bg-gray-100 text-gray-500 dark:bg-[#121316] dark:text-gray-400 border-gray-200 dark:border-[#26272B]';
                    $badgeText = 'Pendente';
                    
                    if($p['status'] == 'confirmed') {
                        $borderStatus = 'border-emerald-500/20';
                        $badgeStatus = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 border-emerald-500/20';
                        $badgeText = 'Confirmado';
                    } elseif ($p['status'] == 'declined') {
                        $borderStatus = 'border-red-900/20';
                        $badgeStatus = 'bg-red-950/20 text-red-500 border border-red-900/30';
                        $badgeText = 'Ausente';
                    }
                ?>
                <div class="bg-gray-50 dark:bg-[#121316] border <?= $borderStatus ?> rounded-[2px] p-3 flex items-center justify-between gap-3 relative shadow-inner">
                    <div class="flex items-center gap-3">
                        <!-- Avatar com cantos retos (Sacred Style) -->
                        <div class="w-10 h-10 rounded-[2px] flex items-center justify-center font-bold text-white uppercase text-xs relative shadow-sm overflow-hidden" style="background: <?= $p['avatar_color'] ?? '#ccc' ?>;">
                            <?php if($photo): ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= $initials ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="min-w-0">
                            <div class="text-xs sm:text-sm font-extrabold text-gray-800 dark:text-white font-outfit truncate"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-0.5"><?= htmlspecialchars($p['instrument'] ?: 'Ministro') ?></div>
                        </div>
                    </div>
                    
                    <span class="text-[8px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-[2px] border <?= $badgeStatus ?>">
                        <?= $badgeText ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Actions (Dual button layout afiado com spring feedback) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 pt-4">
        <div class="flex flex-1 gap-3">
            <?php if ($userParticipant): ?>
                <?php if ($userParticipant['status'] !== 'confirmed'): ?>
                    <form method="POST" class="flex-1">
                        <button type="submit" name="confirm_presence" class="w-full py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-xs uppercase tracking-widest rounded-[2px] shadow-sm active:scale-[0.97] will-change-transform transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Confirmar
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if ($userParticipant['status'] !== 'declined'): ?>
                    <form method="POST" class="flex-1">
                        <button type="submit" name="decline_presence" class="w-full py-3 bg-red-950/20 hover:bg-red-950/40 text-red-500 border border-red-900/30 font-bold text-xs uppercase tracking-widest rounded-[2px] active:scale-[0.97] will-change-transform transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <i data-lucide="x-circle" class="w-4 h-4"></i> Recusar
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <a href="agenda.php" class="py-3 px-6 bg-gray-50 hover:bg-gray-100 dark:bg-[#121316] dark:hover:bg-[#18191D] border border-gray-100 dark:border-[#26272B] text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-widest rounded-[2px] active:scale-[0.97] will-change-transform transition-all text-center cursor-pointer sm:w-auto w-full">
            Voltar para Agenda
        </a>
    </div>

</div>

<script>
lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
