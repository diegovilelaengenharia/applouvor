<?php
// admin/evento_editar.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

$eventId = $_GET['id'] ?? 0;

// Buscar evento
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: agenda.php");
    exit;
}

// Verificar permissão
if ($event['created_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
    header("Location: evento_detalhe.php?id=$eventId");
    exit;
}

// Buscar membros
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Buscar participantes atuais
$stmtPart = $pdo->prepare("SELECT user_id FROM event_participants WHERE event_id = ?");
$stmtPart->execute([$eventId]);
$currentParticipants = array_column($stmtPart->fetchAll(PDO::FETCH_ASSOC), 'user_id');

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Excluir evento
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$eventId]);
        header("Location: agenda.php");
        exit;
    }
    
    try {
        $startDate = $_POST['start_date'];
        $startTime = $_POST['start_time'];
        $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $allDay = isset($_POST['all_day']) ? 1 : 0;
        
        $startDatetime = $startDate . ' ' . ($allDay ? '00:00:00' : $startTime);
        $endDatetime = null;
        if ($endTime && !$allDay) {
            $endDatetime = $startDate . ' ' . $endTime;
        }
        
        // Atualizar evento
        $stmt = $pdo->prepare("
            UPDATE events SET
                title = ?, description = ?, start_datetime = ?, end_datetime = ?,
                location = ?, event_type = ?, color = ?, all_day = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'] ?? null,
            $startDatetime,
            $endDatetime,
            $_POST['location'] ?? null,
            $_POST['event_type'],
            $_POST['color'] ?? '#047857',
            $allDay,
            $eventId
        ]);
        
        // Atualizar participantes
        $pdo->prepare("DELETE FROM event_participants WHERE event_id = ?")->execute([$eventId]);
        
        if (!empty($_POST['participants'])) {
            $stmtPart = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, notified_at) VALUES (?, ?, NOW())");
            
            foreach ($_POST['participants'] as $userId) {
                $stmtPart->execute([$eventId, $userId]);
                
                // Notificar apenas novos participantes
                if (!in_array($userId, $currentParticipants)) {
                    try {
                        $notificationSystem = new NotificationSystem($pdo);
                        $dateFormatted = date('d/m', strtotime($startDate));
                        $notificationSystem->create(
                            $userId,
                            'event_updated',
                            "Adicionado ao evento: {$_POST['title']}",
                            "Você foi adicionado ao evento em $dateFormatted.",
                            null,
                            "evento_detalhe.php?id=$eventId"
                        );
                    } catch (Throwable $e) {
                        error_log("Erro ao enviar notificação: " . $e->getMessage());
                    }
                }
            }
        }
        
        header("Location: evento_detalhe.php?id=$eventId");
        exit;
        
    } catch (Exception $e) {
        $error = "Erro ao atualizar evento: " . $e->getMessage();
    }
}

// Extrair data e hora para o formulário
$startDate = date('Y-m-d', strtotime($event['start_datetime']));
$startTime = date('H:i', strtotime($event['start_datetime']));
$endTime = $event['end_datetime'] ? date('H:i', strtotime($event['end_datetime'])) : '';

renderAppHeader('Editar Evento');
renderPageHeader('Editar Evento', $event['title']);
?>

<!-- Import CSS -->
<link rel="stylesheet" href="../assets/css/pages/evento-form.css?v=<?= time() ?>">

<div class="event-container">
    <?php if (isset($error)): ?>
        <div class="feedback-message feedback-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="info" width="18"></i>
                Informações Básicas
            </div>
            
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($event['title']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-input"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Data *</label>
                <input type="date" name="start_date" class="form-input" value="<?= $startDate ?>" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="all_day" id="all_day" <?= $event['all_day'] ? 'checked' : '' ?> onchange="toggleAllDay()">
                    <span class="checkbox-label">Evento de dia inteiro</span>
                </label>
            </div>
            
            <div id="time-fields" class="<?= $event['all_day'] ? 'hidden' : '' ?>">
                <div class="time-grid">
                    <div class="form-group">
                        <label class="form-label">Hora Início</label>
                        <input type="time" name="start_time" class="form-input" value="<?= $startTime ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora Fim</label>
                        <input type="time" name="end_time" class="form-input" value="<?= $endTime ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Local</label>
                <input type="text" name="location" class="form-input" value="<?= htmlspecialchars($event['location'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="tag" width="18"></i>
                Tipo de Evento
            </div>
            
            <div class="type-grid">
                <label class="type-option">
                    <input type="radio" name="event_type" value="reuniao" <?= $event['event_type'] === 'reuniao' ? 'checked' : '' ?>>
                    <div class="type-box">📋 Reunião</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="ensaio_extra" <?= $event['event_type'] === 'ensaio_extra' ? 'checked' : '' ?>>
                    <div class="type-box">🎵 Ensaio Extra</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="confraternizacao" <?= $event['event_type'] === 'confraternizacao' ? 'checked' : '' ?>>
                    <div class="type-box">🎉 Confraternização</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="aniversario" <?= $event['event_type'] === 'aniversario' ? 'checked' : '' ?>>
                    <div class="type-box">🎂 Aniversário</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="treinamento" <?= $event['event_type'] === 'treinamento' ? 'checked' : '' ?>>
                    <div class="type-box">📚 Treinamento</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="outro" <?= $event['event_type'] === 'outro' ? 'checked' : '' ?>>
                    <div class="type-box">📌 Outro</div>
                </label>
            </div>
            
            <div class="form-group color-options-group">
                <label class="form-label">Cor do Evento</label>
                <div class="color-options">
                    <?php
                    $colors = ['var(--slate-500)', '#047857', 'var(--yellow-500)', '#ec4899', 'var(--lavender-600)', 'var(--rose-500)'];
                    foreach ($colors as $color):
                    ?>
                        <label class="color-option">
                            <input type="radio" name="color" value="<?= $color ?>" <?= $event['color'] === $color ? 'checked' : '' ?>>
                            <div class="color-swatch" style="background: <?= $color ?>;"></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="users" width="18"></i>
                Participantes
            </div>
            
            <div class="member-list">
                <?php foreach ($allUsers as $user):
                    $isSelected = in_array($user['id'], $currentParticipants);
                ?>
                    <label class="member-item <?= $isSelected ? 'selected' : '' ?>">
                        <input type="checkbox" name="participants[]" value="<?= $user['id'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="member-instrument"><?= htmlspecialchars($user['instrument'] ?: 'Membro') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="actions-bar">
            <button type="submit" class="btn-success flex-2">
                <i data-lucide="save" width="16"></i>
                Salvar Alterações
            </button>
            <button type="submit" name="delete" class="btn-danger flex-1" onclick="return confirm('Tem certeza que deseja excluir este evento?')">
                <i data-lucide="trash-2" width="16"></i>
                Excluir
            </button>
            <a href="evento_detalhe.php?id=<?= $eventId ?>" class="btn-secondary flex-1">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function toggleAllDay() {
    const allDay = document.getElementById('all_day').checked;
    const timeFields = document.getElementById('time-fields');
    
    if (allDay) {
        timeFields.classList.add('hidden');
    } else {
        timeFields.classList.remove('hidden');
    }
}

document.querySelectorAll('.member-item input').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.classList.toggle('selected', this.checked);
    });
});

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
