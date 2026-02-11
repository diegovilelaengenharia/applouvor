<?php
// admin/escala_adicionar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

// Buscar dados para os selects
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventType = $_POST['event_type'];
    if ($eventType === 'Outro' && !empty($_POST['custom_event_type'])) {
        $eventType = $_POST['custom_event_type'];
    }

    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_type, notes, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_POST['event_date'], $eventType, $_POST['notes']]);
    $scheduleId = $pdo->lastInsertId();

    if (!empty($_POST['members'])) {
        $stmtMember = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
        foreach ($_POST['members'] as $userId) {
            $stmtMember->execute([$scheduleId, $userId]);
        }

        // Notificar membros escalados
        try {
            $notificationSystem = new NotificationSystem($pdo);
            $dateFormatted = date('d/m', strtotime($_POST['event_date']));
            $typeFormatted = $eventType;
            
            foreach ($_POST['members'] as $uid) {
                // Não notificar quem criou (opcional, mas geralmente útil para confirmar)
                // Se quiser notificar todos escalados, remova a verificação abaixo se o criador estiver na lista
                // if ($uid == $_SESSION['user_id']) continue; 
                
                $notificationSystem->create(
                    $uid,
                    'new_escala',
                    "Escalado: $dateFormatted - $typeFormatted",
                    "Você foi escalado para $typeFormatted no dia $dateFormatted. Toque para ver detalhes.",
                    null, // Data
                    "escala_detalhe.php?id=$scheduleId"
                );
            }
        } catch (Throwable $e) {
            error_log("Erro ao enviar notificações de escala: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    if (!empty($_POST['songs'])) {
        $stmtSong = $pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, order_index) VALUES (?, ?, ?)");
        foreach ($_POST['songs'] as $index => $songId) {
            $stmtSong->execute([$scheduleId, $songId, $index + 1]);
        }
    }

    header("Location: escalas.php"); // Corrigido redirect para escalas.php (plural)
    exit;
}

renderAppHeader('Nova Escala');
renderPageHeader('Nova Escala', 'Configure os detalhes do evento');
?>

<link rel="stylesheet" href="../assets/css/pages/escala-adicionar.css">

<div class="compact-container">
    <!-- Header removido, usando padrão renderPageHeader -->

    <!-- Wizard Progress -->
    <div class="wizard-progress">
        <div class="step-item active" id="dot-container-1">
            <div class="step-dot active" id="dot-1">1</div>
            <span class="step-label">Detalhes</span>
        </div>
        <div class="step-item" id="dot-container-2">
            <div class="step-dot" id="dot-2">2</div>
            <span class="step-label">Participantes</span>
        </div>
        <div class="step-item" id="dot-container-3">
            <div class="step-dot" id="dot-3">3</div>
            <span class="step-label">Músicas</span>
        </div>
    </div>

    <form method="POST" id="wizardForm">

        <!-- PASSO 1: Detalhes -->
        <div class="form-card active" id="step-1" style="--card-color: var(--slate-500); --focus-shadow: rgba(59, 130, 246, 0.1);">
            <div class="card-title"><i data-lucide="calendar" style="width: 16px;"></i> Informações</div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: var(--font-body-sm); color: var(--text-secondary);">Data do Evento</label>
                <div style="position: relative;">
                    <input type="date" name="event_date" id="event_date" class="input-clean" value="<?= date('Y-m-d') ?>" required>
                    <i data-lucide="calendar-days" style="position: absolute; right: 12px; top: 12px; color: var(--text-muted); width: 18px; pointer-events: none;"></i>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: var(--font-body-sm); color: var(--text-secondary);">Tipo de Evento</label>
                <div class="radio-grid">
                    <label class="radio-option">
                        <input type="radio" name="event_type" value="Culto Domingo a Noite" checked onchange="toggleCustomEventType()">
                        <div class="radio-box">Domingo (Noite)</div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="event_type" value="Culto Tema Especial" onchange="toggleCustomEventType()">
                        <div class="radio-box">Tema Especial</div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="event_type" value="Ensaio" onchange="toggleCustomEventType()">
                        <div class="radio-box">Ensaio</div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="event_type" value="Outro" onchange="toggleCustomEventType()">
                        <div class="radio-box">Outro</div>
                    </label>
                </div>
                <div id="customTypeBox" style="display: none; margin-top: 12px;">
                    <input type="text" name="custom_event_type" id="custom_event_input" class="input-clean" placeholder="Digite o nome do evento...">
                </div>
            </div>

            <div>
                 <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: var(--font-body-sm); color: var(--text-secondary);">Observações</label>
                <textarea name="notes" class="input-clean" rows="3" placeholder="Ex: Ceia, Visitante Especial..."></textarea>
            </div>
        </div>

        <!-- PASSO 2: Equipe -->
        <div class="form-card" id="step-2" style="--card-color: var(--sage-500); --focus-shadow: rgba(34, 197, 94, 0.1);">
            <div class="card-title"><i data-lucide="users" style="width: 16px;"></i> Selecionar Equipe</div>
            
            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 18px;"></i>
                <input type="text" onkeyup="filterList('list-members', this.value)" class="input-clean" style="padding-left: 40px;" placeholder="Buscar membro...">
            </div>

            <div id="list-members" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allUsers as $user): ?>
                    <label class="select-item" data-search="<?= strtolower($user['name']) ?>">
                        <input type="checkbox" name="members[]" value="<?= $user['id'] ?>">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: var(--font-body); color: var(--text-main);"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size: var(--font-body-sm); color: var(--text-muted);"><?= htmlspecialchars($user['instrument'] ?: 'Vocal') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PASSO 3: Músicas -->
        <div class="form-card" id="step-3" style="--card-color: var(--yellow-500); --focus-shadow: rgba(245, 158, 11, 0.1);">
            <div class="card-title"><i data-lucide="music" style="width: 16px;"></i> Selecionar Repertório</div>

            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 18px;"></i>
                <input type="text" onkeyup="filterList('list-songs', this.value)" class="input-clean" style="padding-left: 40px;" placeholder="Buscar música...">
            </div>

            <div id="list-songs" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allSongs as $song): ?>
                    <label class="select-item" data-search="<?= strtolower($song['title'] . ' ' . $song['artist']) ?>">
                        <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: var(--font-body); color: var(--text-main);"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: var(--font-body-sm); color: var(--text-muted);"><?= htmlspecialchars($song['artist']) ?> <span style="margin: 0 4px; color: var(--border-color);">|</span> <strong style="color:var(--primary);"><?= $song['tone'] ?></strong></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navegação Bottom -->
        <div class="actions-bar">
            <button type="button" id="btn-back" onclick="changeStep(-1)" style="
                flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-surface); color: var(--text-main); font-weight: 600; cursor: pointer; display: none; font-size: 0.95rem;
            ">Voltar</button>

            <button type="button" id="btn-next" onclick="changeStep(1)" style="
                flex: 2; padding: 12px; border: none; border-radius: 12px; background: var(--primary); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25); font-size: 0.95rem;
            ">Próximo</button>

            <button type="submit" id="btn-finish" style="
                flex: 2; padding: 12px; border: none; border-radius: 12px; background: var(--sage-600); color: white; font-weight: 700; cursor: pointer; display: none; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25); font-size: var(--font-body);
            ">Finalizar Escala</button>
        </div>

    </form>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 3;

    function changeStep(direction) {
        // Validação Passo 1
        if (currentStep === 1 && direction === 1) {
            if (!document.getElementById('event_date').value) {
                alert('Selecione uma data para continuar.');
                return;
            }
        }

        const nextStep = currentStep + direction;
        if (nextStep < 1 || nextStep > totalSteps) return;

        // Atualizar Dots
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

        // Trocar tela
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

    function toggleCustomEventType() {
        const customBox = document.getElementById('customTypeBox');
        const customInput = document.getElementById('custom_event_input');
        const isOutro = document.querySelector('input[name="event_type"]:checked').value === 'Outro';

        customBox.style.display = isOutro ? 'block' : 'none';
        if (isOutro) customInput.focus();
    }

    function filterList(listId, term) {
        term = term.toLowerCase();
        const items = document.querySelectorAll(`#${listId} .select-item`);
        items.forEach(item => {
            const text = item.getAttribute('data-search');
            item.style.display = text.includes(term) ? 'flex' : 'none';
        });
    }

    // Highlight selected checkboxes
    document.querySelectorAll('.select-item input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) this.parentElement.classList.add('selected');
            else this.parentElement.classList.remove('selected');
        });
    });
    
    lucide.createIcons();
</script>

<?php renderAppFooter(); ?>