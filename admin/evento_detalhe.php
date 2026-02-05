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
    SELECT ep.*, u.name, u.instrument, u.avatar
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
    header("Location: evento_detalhe.php?id=$eventId");
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
    header("Location: evento_detalhe.php?id=$eventId");
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

<style>
    body { background: var(--bg-body); }
    
    .detail-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 16px 12px 100px;
    }
    
    /* Event Header */
    .event-header {
        background: <?= $event['color'] ?>40;
        border: 1px solid <?= $event['color'] ?>;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 16px;
        position: relative;
        overflow: hidden;
    }
    
    .event-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: <?= $event['color'] ?>;
    }
    
    .event-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: <?= $event['color'] ?>;
        color: white;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .event-title-large {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 16px;
    }
    
    .event-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-main);
        font-size: 0.9375rem;
    }
    
    .meta-item i {
        color: <?= $event['color'] ?>;
        width: 20px;
    }
    
    /* Info Card */
    .info-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }
    
    .card-title-small {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .description-text {
        color: var(--text-secondary);
        line-height: 1.6;
        white-space: pre-wrap;
    }
    
    /* Participants List */
    .participant-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 8px;
        background: var(--bg-body);
    }
    
    .participant-item.confirmed {
        background: #ecfdf520;
        border-color: #10b981;
    }
    
    .participant-item.declined {
        background: var(--rose-50)20;
        border-color: #f87171;
        opacity: 0.7;
    }
    
    .participant-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .participant-info {
        flex: 1;
    }
    
    .participant-name {
        font-weight: 700;
        font-size: 0.9375rem;
        color: var(--text-main);
    }
    
    .participant-role {
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .status-pending {
        background: var(--yellow-100);
        color: #92400e;
    }
    
    .status-confirmed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-declined {
        background: var(--rose-100);
        color: var(--rose-700);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            position: fixed;
            bottom: 80px;
            left: 0;
            right: 0;
            background: var(--bg-surface);
            border-top: 1px solid var(--border-color);
            padding: 12px 16px;
            margin: 0;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
            z-index: 50;
        }
        
        .detail-container {
            padding-bottom: 180px;
        }
    }
</style>

<div class="detail-container">
    <!-- Event Header -->
    <div class="event-header">
        <div class="event-type-badge">
            <i data-lucide="<?= $typeIcons[$event['event_type']] ?? 'calendar' ?>" style="width: 14px;"></i>
            <?= $typeLabels[$event['event_type']] ?? 'Evento' ?>
        </div>
        
        <h1 class="event-title-large"><?= htmlspecialchars($event['title']) ?></h1>
        
        <div class="event-meta-grid">
            <div class="meta-item">
                <i data-lucide="calendar"></i>
                <span>
                    <?php
                    $date = new DateTime($event['start_datetime']);
                    echo $date->format('d/m/Y');
                    ?>
                </span>
            </div>
            
            <div class="meta-item">
                <i data-lucide="clock"></i>
                <span>
                    <?php
                    if ($event['all_day']) {
                        echo 'Dia todo';
                    } else {
                        $start = new DateTime($event['start_datetime']);
                        $end = $event['end_datetime'] ? new DateTime($event['end_datetime']) : null;
                        echo $start->format('H:i');
                        if ($end) echo ' - ' . $end->format('H:i');
                    }
                    ?>
                </span>
            </div>
            
            <?php if ($event['location']): ?>
            <div class="meta-item">
                <i data-lucide="map-pin"></i>
                <span><?= htmlspecialchars($event['location']) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="meta-item">
                <i data-lucide="user"></i>
                <span>Por <?= htmlspecialchars($event['creator_name'] ?? 'Sistema') ?></span>
            </div>
        </div>
    </div>
    
    <!-- Description -->
    <?php if ($event['description']): ?>
    <div class="info-card">
        <div class="card-title-small">
            <i data-lucide="file-text" style="width: 18px;"></i>
            Descrição
        </div>
        <div class="description-text"><?= htmlspecialchars($event['description']) ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Participants -->
    <div class="info-card">
        <div class="card-title-small">
            <i data-lucide="users" style="width: 18px;"></i>
            Participantes (<?= count($participants) ?>)
        </div>
        
        <?php if (empty($participants)): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 20px;">
                Nenhum participante convidado
            </p>
        <?php else: ?>
            <?php foreach ($participants as $p):
                $statusClass = 'status-' . $p['status'];
                $statusLabel = [
                    'pending' => 'Pendente',
                    'confirmed' => 'Confirmado',
                    'declined' => 'Recusou'
                ];
                
                $photo = !empty($p['avatar']) ? $p['avatar'] : 
                         'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=dcfce7&color=166534';
            ?>
                <div class="participant-item <?= $p['status'] ?>">
                    <img src="<?= $photo ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="participant-avatar">
                    <div class="participant-info">
                        <div class="participant-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="participant-role"><?= htmlspecialchars($p['instrument'] ?: 'Membro') ?></div>
                    </div>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= $statusLabel[$p['status']] ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Actions -->
    <div class="action-buttons">
        <?php if ($userParticipant): ?>
            <?php if ($userParticipant['status'] !== 'confirmed'): ?>
                <form method="POST" style="flex: 1;">
                    <button type="submit" name="confirm_presence" class="btn-success" style="width: 100%;">
                        <i data-lucide="check" style="width: 16px;"></i>
                        Confirmar Presença
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($userParticipant['status'] !== 'declined'): ?>
                <form method="POST" style="flex: 1;">
                    <button type="submit" name="decline_presence" class="btn-danger" style="width: 100%;">
                        <i data-lucide="x" style="width: 16px;"></i>
                        Não Poderei Ir
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($event['created_by'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
            <a href="evento_editar.php?id=<?= $event['id'] ?>" class="btn-primary" style="flex: 1; text-decoration: none;">
                <i data-lucide="edit" style="width: 16px;"></i>
                Editar Evento
            </a>
        <?php endif; ?>
        
        <a href="agenda.php" class="btn-secondary" style="flex: 1; text-decoration: none;">
            <i data-lucide="arrow-left" style="width: 16px;"></i>
            Voltar
        </a>
    </div>
</div>

<script>
lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
