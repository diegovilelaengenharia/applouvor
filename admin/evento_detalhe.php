<?php
// admin/evento_detalhe.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

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

<!-- Import CSS External -->
<link rel="stylesheet" href="../assets/css/pages/evento-detalhe.css?v=<?= time() ?>">

<div class="detail-container">

    <!-- FEEDBACK MESSAGE -->
    <?php if ($feedbackText): ?>
    <div class="feedback-message <?= $feedbackClass ?>">
        <?php if($msg === 'confirmed'): ?>
            <i data-lucide="check-circle" width="20"></i>
        <?php else: ?>
            <i data-lucide="info" width="20"></i>
        <?php endif; ?>
        <?= htmlspecialchars($feedbackText) ?>
    </div>
    <script>
        // Auto hide functionality if desired, or just leave it
        setTimeout(function() {
            const msg = document.querySelector('.feedback-message');
            if(msg) msg.style.opacity = '0.5';
        }, 5000);
    </script>
    <?php endif; ?>

    <!-- EVENT SUMMARY CARD -->
    <div class="event-info-card">
        <div class="event-main-row">
            <div class="event-date-box">
                <?php 
                $dateObj = new DateTime($event['start_datetime']);
                ?>
                <div class="event-day"><?= $dateObj->format('d') ?></div>
                <div class="event-month"><?= getMesAbbrev($event['start_datetime']) ?></div>
            </div>
            <div class="event-details">
                <div class="event-type"><?= htmlspecialchars($event['title']) ?></div>
                <div class="event-meta">
                    <i data-lucide="<?= $typeIcons[$event['event_type']] ?? 'calendar' ?>" width="14"></i> <?= $typeLabels[$event['event_type']] ?? 'Evento' ?>
                </div>
                <div class="event-meta mt-1">
                    <i data-lucide="calendar" width="14"></i> <?= formDataPtBr($event['start_datetime']) ?>
                </div>
                <div class="event-meta mt-1">
                    <i data-lucide="clock" width="14"></i> <?= date('H:i', strtotime($event['start_datetime'])) ?> - <?= date('H:i', strtotime($event['end_datetime'])) ?>
                </div>
                <?php if ($event['location']): ?>
                <div class="event-meta mt-1">
                    <i data-lucide="map-pin" width="14"></i> <?= htmlspecialchars($event['location']) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($event['created_by'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
            <div>
                <a href="evento_editar.php?id=<?= $event['id'] ?>" class="btn-icon btn-edit" title="Editar">
                    <i data-lucide="edit-2" width="16"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if($event['description']): ?>
        <div class="event-notes">
            <strong><i data-lucide="align-left" width="14" style="vertical-align:middle"></i> Descrição:</strong><br>
            <?= nl2br(htmlspecialchars($event['description'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- PARTICIPANTS SECTION -->
    <div class="detail-section">
        <div class="section-header">
            <?php
                $confirmedCount = 0;
                foreach ($participants as $p) {
                    if ($p['status'] == 'confirmed') $confirmedCount++;
                }
            ?>
            <div class="section-title">
                Participantes <span class="section-count"><?= count($participants) ?></span>
                <?php if($confirmedCount > 0): ?>
                    <span class="count-confirmed">
                        <i data-lucide="check" width="12" style="vertical-align: middle"></i> <?= $confirmedCount ?> confirmados
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($participants)): ?>
            <p class="empty-state">
                Nenhum participante convidado
            </p>
        <?php else: ?>
            <div class="participant-list-grid">
                <?php foreach ($participants as $p):
                    $statusClass = $p['status']; // confirmed, pending, declined
                    $photo = !empty($p['avatar']) ? $p['avatar'] : null;
                    if($photo && strpos($photo, 'uploads') === false && strpos($photo, 'http') === false) {
                        $photo = '../assets/uploads/' . $photo;
                    }
                    $initials = strtoupper(substr($p['name'], 0, 1));
                ?>
                <div class="member-card status-<?= $p['status'] ?>">
                    <div class="member-avatar" style="background: <?= $p['avatar_color'] ?? '#ccc' ?>;">
                        <?php if($photo): ?>
                            <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <?= $initials ?>
                        <?php endif; ?>
                        <?php if($p['status'] == 'confirmed'): ?>
                            <div class="status-indicator-icon confirmed"><i data-lucide="check" width="10" height="10"></i></div>
                        <?php else: ?>
                            <div class="status-indicator <?= $statusClass ?>"></div>
                        <?php endif; ?>
                    </div>
                    <div class="member-info">
                        <div class="member-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="member-role">
                            <?= htmlspecialchars($p['instrument'] ?: 'Membro') ?>
                            <?php if($p['status'] == 'confirmed'): ?>
                                <span class="badge-confirmed">Confirmado</span>
                            <?php elseif($p['status'] == 'declined'): ?>
                                <span class="badge-declined">Recusou</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Actions -->
    <div class="action-buttons">
        <?php if ($userParticipant): ?>
            <?php if ($userParticipant['status'] !== 'confirmed'): ?>
                <form method="POST" style="flex: 1;">
                    <button type="submit" name="confirm_presence" class="btn-large btn-confirm w-full">
                        <i data-lucide="check-circle" width="18"></i> Confirmar
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($userParticipant['status'] !== 'declined'): ?>
                <form method="POST" style="flex: 1;">
                    <button type="submit" name="decline_presence" class="btn-large btn-decline w-full">
                        <i data-lucide="x-circle" width="18"></i> Recusar
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="agenda.php" class="btn-large btn-back" style="flex: 0;">
            Voltar
        </a>
    </div>

</div>

<script>
lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
