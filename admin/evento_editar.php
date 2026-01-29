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

<style>
    body { background: var(--bg-body); }
    
    .event-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 16px 12px 140px;
    }
    
    .form-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 16px;
    }
    
    .card-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-secondary);
    }
    
    .form-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: 0.9375rem;
        outline: none;
        transition: all 0.2s;
        font-family: 'Inter', sans-serif;
    }
    
    .form-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }
    
    textarea.form-input {
        resize: vertical;
        min-height: 80px;
    }
    
    .type-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .type-option input { display: none; }
    
    .type-box {
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-body);
        font-weight: 600;
        font-size: 0.8125rem;
    }
    
    .type-option input:checked + .type-box {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .color-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .color-option input { display: none; }
    
    .color-swatch {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.2s;
    }
    
    .color-option input:checked + .color-swatch {
        border-color: var(--text-main);
        transform: scale(1.1);
    }
    
    .member-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        background: var(--bg-body);
    }
    
    .member-item.selected {
        background: var(--primary-subtle);
        border-color: var(--primary);
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: var(--bg-body);
        border-radius: 10px;
        cursor: pointer;
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
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
    }
</style>

<div class="event-container">
    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 10px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="info" style="width: 18px;"></i>
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
                    <span style="font-weight: 600;">Evento de dia inteiro</span>
                </label>
            </div>
            
            <div id="time-fields" style="<?= $event['all_day'] ? 'display: none;' : '' ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
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
                <i data-lucide="tag" style="width: 18px;"></i>
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
            
            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label">Cor do Evento</label>
                <div class="color-options">
                    <?php
                    $colors = ['#3b82f6', '#047857', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444'];
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
                <i data-lucide="users" style="width: 18px;"></i>
                Participantes
            </div>
            
            <div class="member-list">
                <?php foreach ($allUsers as $user):
                    $isSelected = in_array($user['id'], $currentParticipants);
                ?>
                    <label class="member-item <?= $isSelected ? 'selected' : '' ?>">
                        <input type="checkbox" name="participants[]" value="<?= $user['id'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 0.875rem;"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($user['instrument'] ?: 'Membro') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn-success" style="flex: 2;">
                <i data-lucide="save" style="width: 16px;"></i>
                Salvar Alterações
            </button>
            <button type="submit" name="delete" class="btn-danger" style="flex: 1;" onclick="return confirm('Tem certeza que deseja excluir este evento?')">
                <i data-lucide="trash-2" style="width: 16px;"></i>
                Excluir
            </button>
            <a href="evento_detalhe.php?id=<?= $eventId ?>" class="btn-secondary" style="flex: 1; text-decoration: none;">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function toggleAllDay() {
    const allDay = document.getElementById('all_day').checked;
    document.getElementById('time-fields').style.display = allDay ? 'none' : 'block';
}

document.querySelectorAll('.member-item input').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.classList.toggle('selected', this.checked);
    });
});

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
