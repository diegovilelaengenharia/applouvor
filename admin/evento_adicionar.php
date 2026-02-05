<?php
// admin/evento_adicionar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

// Buscar membros para seleção
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Preparar datas
        $startDate = $_POST['start_date'];
        $startTime = $_POST['start_time'];
        $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $allDay = isset($_POST['all_day']) ? 1 : 0;
        
        $startDatetime = $startDate . ' ' . ($allDay ? '00:00:00' : $startTime);
        $endDatetime = null;
        if ($endTime && !$allDay) {
            $endDatetime = $startDate . ' ' . $endTime;
        }
        
        // Inserir evento
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, start_datetime, end_datetime, location, 
                              event_type, color, all_day, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            $_SESSION['user_id']
        ]);
        
        $eventId = $pdo->lastInsertId();
        
        // Adicionar participantes
        if (!empty($_POST['participants'])) {
            $stmtPart = $pdo->prepare("INSERT INTO event_participants (event_id, user_id, notified_at) VALUES (?, ?, NOW())");
            
            foreach ($_POST['participants'] as $userId) {
                $stmtPart->execute([$eventId, $userId]);
            }
            
            // Enviar notificações
            try {
                $notificationSystem = new NotificationSystem($pdo);
                $dateFormatted = date('d/m', strtotime($startDate));
                $timeFormatted = $allDay ? 'Dia todo' : date('H:i', strtotime($startTime));
                
                foreach ($_POST['participants'] as $uid) {
                    $notificationSystem->create(
                        $uid,
                        'new_event',
                        "Novo evento: {$_POST['title']}",
                        "Você foi convidado para '{$_POST['title']}' em $dateFormatted às $timeFormatted.",
                        null,
                        "evento_detalhe.php?id=$eventId"
                    );
                }
            } catch (Throwable $e) {
                error_log("Erro ao enviar notificações de evento: " . $e->getMessage());
            }
        }
        
        header("Location: agenda.php");
        exit;
        
    } catch (Exception $e) {
        $error = "Erro ao criar evento: " . $e->getMessage();
    }
}

renderAppHeader('Novo Evento');
renderPageHeader('Novo Evento', 'Adicionar compromisso à agenda');
?>

<style>
    body { background: var(--bg-body); }
    
    .event-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 16px 12px 140px;
    }
    
    /* Wizard Progress */
    .wizard-progress {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 24px;
        position: relative;
    }
    
    .wizard-progress::before {
        content: '';
        position: absolute;
        top: 14px;
        left: 20px;
        right: 20px;
        height: 2px;
        background: var(--border-color);
        z-index: 0;
    }
    
    .step-item {
        position: relative;
        z-index: 1;
        text-align: center;
    }
    
    .step-dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--bg-surface);
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--text-muted);
        transition: all 0.3s;
        margin: 0 auto 6px;
    }
    
    .step-dot.active {
        border-color: var(--primary);
        background: var(--primary);
        color: white;
        box-shadow: 0 0 0 4px var(--primary-subtle);
        transform: scale(1.1);
    }
    
    .step-dot.completed {
        border-color: var(--primary);
        background: var(--primary);
        color: white;
    }
    
    .step-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    
    .step-item.active .step-label {
        color: var(--primary);
    }
    
    /* Form Card */
    .form-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: hidden;
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .form-card.active {
        display: block;
    }
    
    .form-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--card-color, var(--primary));
        opacity: 0.8;
    }
    
    .card-title {
        font-size: 0.9375rem;
        font-weight: 800;
        color: var(--card-color, var(--text-main));
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Form Fields */
    .form-group {
        margin-bottom: 20px;
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
        border-color: var(--card-color);
        background: var(--bg-surface);
        box-shadow: 0 0 0 3px var(--focus-shadow);
    }
    
    textarea.form-input {
        resize: vertical;
        min-height: 80px;
    }
    
    /* Type Selection */
    .type-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .type-option input {
        display: none;
    }
    
    .type-box {
        padding: 16px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-body);
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .type-option input:checked + .type-box {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: 0 2px 8px rgba(4, 120, 87, 0.15);
    }
    
    /* Member Selection */
    .member-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-body);
    }
    
    .member-item.selected {
        background: var(--primary-subtle);
        border-color: var(--primary);
    }
    
    .member-item input {
        accent-color: var(--primary);
        width: 18px;
        height: 18px;
    }
    
    /* Checkbox */
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: var(--bg-body);
        border-radius: 10px;
        cursor: pointer;
    }
    
    .checkbox-group input {
        accent-color: var(--primary);
        width: 20px;
        height: 20px;
    }
    
    /* Color Picker */
    .color-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .color-option input {
        display: none;
    }
    
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    /* Actions Bar */
    .actions-bar {
        position: fixed;
        bottom: 80px;
        left: 0;
        right: 0;
        background: var(--bg-surface);
        border-top: 1px solid var(--border-color);
        padding: 12px 16px;
        display: flex;
        gap: 12px;
        z-index: 50;
        box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
    }
    
    @media(min-width: 1025px) {
        .actions-bar {
            position: static;
            border: none;
            padding: 0;
            margin-top: 24px;
            background: none;
            box-shadow: none;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="event-container">
    <?php if (isset($error)): ?>
        <div style="background: var(--rose-100); border: 1px solid var(--rose-500); color: var(--rose-700); padding: 12px; border-radius: 10px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Wizard Progress -->
    <div class="wizard-progress">
        <div class="step-item active" id="dot-container-1">
            <div class="step-dot active" id="dot-1">1</div>
            <span class="step-label">Informações</span>
        </div>
        <div class="step-item" id="dot-container-2">
            <div class="step-dot" id="dot-2">2</div>
            <span class="step-label">Tipo</span>
        </div>
        <div class="step-item" id="dot-container-3">
            <div class="step-dot" id="dot-3">3</div>
            <span class="step-label">Participantes</span>
        </div>
    </div>
    
    <form method="POST" id="eventForm">
        <!-- Step 1: Informações Básicas -->
        <div class="form-card active" id="step-1" style="--card-color: var(--slate-500); --focus-shadow: rgba(59, 130, 246, 0.1);">
            <div class="card-title">
                <i data-lucide="info" style="width: 16px;"></i>
                Informações Básicas
            </div>
            
            <div class="form-group">
                <label class="form-label">Título do Evento *</label>
                <input type="text" name="title" class="form-input" placeholder="Ex: Reunião de Planejamento" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-input" placeholder="Detalhes sobre o evento..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Data *</label>
                <input type="date" name="start_date" id="start_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="all_day" id="all_day" onchange="toggleAllDay()">
                    <span style="font-weight: 600; color: var(--text-main);">Evento de dia inteiro</span>
                </label>
            </div>
            
            <div id="time-fields">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Hora Início</label>
                        <input type="time" name="start_time" class="form-input" value="19:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora Fim</label>
                        <input type="time" name="end_time" class="form-input" value="21:00">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Local</label>
                <input type="text" name="location" class="form-input" placeholder="Ex: Sala de Reuniões">
            </div>
        </div>
        
        <!-- Step 2: Tipo e Categoria -->
        <div class="form-card" id="step-2" style="--card-color: #10b981; --focus-shadow: rgba(16, 185, 129, 0.1);">
            <div class="card-title">
                <i data-lucide="tag" style="width: 16px;"></i>
                Tipo de Evento
            </div>
            
            <div class="type-grid">
                <label class="type-option">
                    <input type="radio" name="event_type" value="reuniao" checked onchange="updateColor('var(--slate-500)')">
                    <div class="type-box">📋 Reunião</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="ensaio_extra" onchange="updateColor('#047857')">
                    <div class="type-box">🎵 Ensaio Extra</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="confraternizacao" onchange="updateColor('var(--yellow-500)')">
                    <div class="type-box">🎉 Confraternização</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="aniversario" onchange="updateColor('#ec4899')">
                    <div class="type-box">🎂 Aniversário</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="treinamento" onchange="updateColor('var(--lavender-600)')">
                    <div class="type-box">📚 Treinamento</div>
                </label>
                <label class="type-option">
                    <input type="radio" name="event_type" value="outro" onchange="updateColor('var(--slate-500)')">
                    <div class="type-box">📌 Outro</div>
                </label>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label class="form-label">Cor do Evento</label>
                <div class="color-options">
                    <label class="color-option">
                        <input type="radio" name="color" value="var(--slate-500)" checked>
                        <div class="color-swatch" style="background: var(--slate-500);"></div>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color" value="#047857">
                        <div class="color-swatch" style="background: #047857;"></div>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color" value="var(--yellow-500)">
                        <div class="color-swatch" style="background: var(--yellow-500);"></div>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color" value="#ec4899">
                        <div class="color-swatch" style="background: #ec4899;"></div>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color" value="var(--lavender-600)">
                        <div class="color-swatch" style="background: var(--lavender-600);"></div>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color" value="var(--rose-500)">
                        <div class="color-swatch" style="background: var(--rose-500);"></div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Participantes -->
        <div class="form-card" id="step-3" style="--card-color: var(--yellow-500); --focus-shadow: rgba(245, 158, 11, 0.1);">
            <div class="card-title">
                <i data-lucide="users" style="width: 16px;"></i>
                Selecionar Participantes
            </div>
            
            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 18px;"></i>
                <input type="text" onkeyup="filterMembers(this.value)" class="form-input" style="padding-left: 40px;" placeholder="Buscar membro...">
            </div>
            
            <div class="member-list" id="memberList">
                <?php foreach ($allUsers as $user): ?>
                    <label class="member-item" data-search="<?= strtolower($user['name']) ?>">
                        <input type="checkbox" name="participants[]" value="<?= $user['id'] ?>">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 0.9375rem; color: var(--text-main);">
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">
                                <?= htmlspecialchars($user['instrument'] ?: 'Membro') ?>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="actions-bar">
            <button type="button" id="btn-back" onclick="changeStep(-1)" class="btn-secondary" style="flex: 1; display: none;">
                Voltar
            </button>
            <button type="button" id="btn-next" onclick="changeStep(1)" class="btn-primary" style="flex: 2;">
                Próximo
            </button>
            <button type="submit" id="btn-finish" class="btn-success" style="flex: 2; display: none;">
                Criar Evento
            </button>
        </div>
    </form>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;

function changeStep(direction) {
    // Validação
    if (currentStep === 1 && direction === 1) {
        const title = document.querySelector('input[name="title"]').value;
        const date = document.getElementById('start_date').value;
        if (!title || !date) {
            alert('Preencha o título e a data para continuar.');
            return;
        }
    }
    
    const nextStep = currentStep + direction;
    if (nextStep < 1 || nextStep > totalSteps) return;
    
    // Update dots
    if (direction === 1) {
        document.getElementById('dot-' + currentStep).classList.remove('active');
        document.getElementById('dot-' + currentStep).classList.add('completed');
        document.getElementById('dot-container-' + currentStep).classList.remove('active');
        
        document.getElementById('dot-' + nextStep).classList.add('active');
        document.getElementById('dot-container-' + nextStep).classList.add('active');
    } else {
        document.getElementById('dot-' + currentStep).classList.remove('active');
        document.getElementById('dot-container-' + currentStep).classList.remove('active');
        
        document.getElementById('dot-' + nextStep).classList.remove('completed');
        document.getElementById('dot-' + nextStep).classList.add('active');
        document.getElementById('dot-container-' + nextStep).classList.add('active');
    }
    
    // Change screen
    document.getElementById('step-' + currentStep).classList.remove('active');
    document.getElementById('step-' + nextStep).classList.add('active');
    
    currentStep = nextStep;
    updateButtons();
    window.scrollTo(0, 0);
}

function updateButtons() {
    const btnBack = document.getElementById('btn-back');
    const btnNext = document.getElementById('btn-next');
    const btnFinish = document.getElementById('btn-finish');
    
    btnBack.style.display = currentStep > 1 ? 'block' : 'none';
    
    if (currentStep === totalSteps) {
        btnNext.style.display = 'none';
        btnFinish.style.display = 'block';
    } else {
        btnNext.style.display = 'block';
        btnFinish.style.display = 'none';
    }
}

function toggleAllDay() {
    const allDay = document.getElementById('all_day').checked;
    document.getElementById('time-fields').style.display = allDay ? 'none' : 'block';
}

function updateColor(color) {
    document.querySelector(`input[name="color"][value="${color}"]`).checked = true;
}

function filterMembers(term) {
    term = term.toLowerCase();
    const items = document.querySelectorAll('.member-item');
    items.forEach(item => {
        const text = item.getAttribute('data-search');
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

// Highlight selected
document.querySelectorAll('.member-item input').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.classList.toggle('selected', this.checked);
    });
});

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
