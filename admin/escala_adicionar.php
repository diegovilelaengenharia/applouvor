<?php
// admin/escala_adicionar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Buscar dados para os selects
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determinar tipo de evento (se for "Outro", usar o campo customizado)
    $eventType = $_POST['event_type'];
    if ($eventType === 'Outro' && !empty($_POST['custom_event_type'])) {
        $eventType = $_POST['custom_event_type'];
    }

    // Criar a escala
    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_type, notes) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['event_date'], $eventType, $_POST['notes']]);
    $scheduleId = $pdo->lastInsertId();

    // Adicionar membros (se selecionados)
    if (!empty($_POST['members'])) {
        $stmtMember = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
        foreach ($_POST['members'] as $userId) {
            $stmtMember->execute([$scheduleId, $userId]);
        }
    }

    // Adicionar músicas (se selecionadas)
    if (!empty($_POST['songs'])) {
        $stmtSong = $pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, order_index) VALUES (?, ?, ?)");
        foreach ($_POST['songs'] as $index => $songId) {
            $stmtSong->execute([$scheduleId, $songId, $index + 1]);
        }
    }

    header("Location: escala.php");
    exit;
}

renderAppHeader('Nova Escala');
?>

<style>
    .wizard-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .wizard-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 32px;
        position: relative;
    }

    .wizard-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--border-subtle);
        z-index: 0;
    }

    .wizard-step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .wizard-step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-weight: 700;
        color: var(--text-secondary);
        transition: all 0.3s;
    }

    .wizard-step.active .wizard-step-circle {
        background: var(--accent-interactive);
        border-color: var(--accent-interactive);
        color: white;
    }

    .wizard-step.completed .wizard-step-circle {
        background: var(--status-success);
        border-color: var(--status-success);
        color: white;
    }

    .wizard-step-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 600;
    }

    .wizard-step.active .wizard-step-label {
        color: var(--accent-interactive);
    }

    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
    }

    .member-select-item,
    .song-select-item {
        background: var(--bg-secondary);
        border: 2px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .member-select-item:hover,
    .song-select-item:hover {
        border-color: var(--accent-interactive);
        background: var(--bg-tertiary);
    }

    .member-select-item.selected,
    .song-select-item.selected {
        border-color: var(--accent-interactive);
        background: rgba(45, 122, 79, 0.1);
    }

    .member-select-item input,
    .song-select-item input {
        transform: scale(1.3);
        accent-color: var(--accent-interactive);
    }
</style>

<div class="wizard-container">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <a href="escala.php" class="btn-icon ripple">
            <i data-lucide="x"></i>
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin: 0;">Nova Escala</h1>
    </div>

    <!-- Wizard Steps -->
    <div class="wizard-steps">
        <div class="wizard-step active" id="step-indicator-1">
            <div class="wizard-step-circle">1</div>
            <div class="wizard-step-label">Detalhes</div>
        </div>
        <div class="wizard-step" id="step-indicator-2">
            <div class="wizard-step-circle">2</div>
            <div class="wizard-step-label">Equipe</div>
        </div>
        <div class="wizard-step" id="step-indicator-3">
            <div class="wizard-step-circle">3</div>
            <div class="wizard-step-label">Músicas</div>
        </div>
    </div>

    <form method="POST" id="wizardForm">
        <!-- ETAPA 1: Detalhes -->
        <div class="step-content active" id="step-1">
            <div class="card-clean" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="calendar" style="width: 16px;"></i> Data do Evento
                    </label>
                    <input type="date" name="event_date" id="event_date" class="form-input" required value="<?= date('Y-m-d') ?>" style="font-size: 1.1rem; padding: 14px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="tag" style="width: 16px;"></i> Tipo de Evento
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label class="radio-card">
                            <input type="radio" name="event_type" value="Culto Domingo a Noite" checked onchange="toggleCustomEventType()">
                            <div class="radio-content">Domingo (Noite)</div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="event_type" value="Culto Tema Especial" onchange="toggleCustomEventType()">
                            <div class="radio-content">Tema Especial</div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="event_type" value="Ensaio" onchange="toggleCustomEventType()">
                            <div class="radio-content">Ensaio</div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="event_type" value="Outro" onchange="toggleCustomEventType()">
                            <div class="radio-content">Outro</div>
                        </label>
                    </div>

                    <!-- Campo Customizado (aparece quando seleciona "Outro") -->
                    <div id="customEventTypeContainer" style="display: none; margin-top: 12px; animation: fadeIn 0.3s ease;">
                        <input type="text" name="custom_event_type" id="custom_event_type" class="form-input" placeholder="Digite o tipo de evento..." maxlength="50">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="align-left" style="width: 16px;"></i> Observações
                    </label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Alguma observação especial?" style="resize: none;"></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <a href="escala.php" class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;">Cancelar</a>
                <button type="button" onclick="nextStep(2)" class="btn-primary ripple" style="flex: 2; justify-content: center;">
                    Próximo <i data-lucide="arrow-right" style="width: 18px;"></i>
                </button>
            </div>
        </div>

        <!-- ETAPA 2: Equipe -->
        <div class="step-content" id="step-2">
            <div class="card-clean" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 1.1rem;">Selecione os membros</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($allUsers as $user): ?>
                        <label class="member-select-item">
                            <input type="checkbox" name="members[]" value="<?= $user['id'] ?>">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($user['name']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($user['instrument'] ?: 'Sem instrumento') ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" onclick="prevStep(1)" class="btn-outline ripple" style="flex: 1; justify-content: center;">
                    <i data-lucide="arrow-left" style="width: 18px;"></i> Voltar
                </button>
                <button type="button" onclick="nextStep(3)" class="btn-outline ripple" style="flex: 1; justify-content: center;">Pular</button>
                <button type="button" onclick="nextStep(3)" class="btn-primary ripple" style="flex: 2; justify-content: center;">
                    Próximo <i data-lucide="arrow-right" style="width: 18px;"></i>
                </button>
            </div>
        </div>

        <!-- ETAPA 3: Músicas -->
        <div class="step-content" id="step-3">
            <div class="card-clean" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 1.1rem;">Selecione as músicas</h3>
                <?php if (empty($allSongs)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i data-lucide="music" style="width: 48px; height: 48px; margin-bottom: 12px; color: var(--text-muted);"></i>
                        <p>Nenhuma música cadastrada ainda.</p>
                        <p style="font-size: 0.9rem;">Você pode adicionar depois.</p>
                    </div>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($allSongs as $song): ?>
                            <label class="song-select-item">
                                <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($song['title']) ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($song['artist']) ?>
                                        <?php if ($song['tone']): ?>
                                            • <span style="color: var(--accent-interactive); font-weight: 700;"><?= htmlspecialchars($song['tone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" onclick="prevStep(2)" class="btn-outline ripple" style="flex: 1; justify-content: center;">
                    <i data-lucide="arrow-left" style="width: 18px;"></i> Voltar
                </button>
                <button type="submit" class="btn-primary ripple" style="flex: 2; justify-content: center; box-shadow: var(--shadow-glow);">
                    <i data-lucide="check"></i> Finalizar Escala
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    .radio-card input {
        display: none;
    }

    .radio-card .radio-content {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-subtle);
        padding: 12px;
        border-radius: 12px;
        text-align: center;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .radio-card input:checked+.radio-content {
        background: var(--bg-secondary);
        border-color: var(--accent-interactive);
        color: var(--accent-interactive);
        box-shadow: 0 0 0 2px rgba(45, 122, 79, 0.2);
    }
</style>

<script>
    let currentStep = 1;

    // Mostrar/esconder campo customizado
    function toggleCustomEventType() {
        const selectedType = document.querySelector('input[name="event_type"]:checked').value;
        const customContainer = document.getElementById('customEventTypeContainer');
        const customInput = document.getElementById('custom_event_type');

        if (selectedType === 'Outro') {
            customContainer.style.display = 'block';
            customInput.required = true;
            customInput.focus();
        } else {
            customContainer.style.display = 'none';
            customInput.required = false;
            customInput.value = '';
        }
    }

    function nextStep(step) {
        // Validar etapa 1
        if (currentStep === 1) {
            const dateInput = document.getElementById('event_date');
            if (!dateInput.value) {
                alert('Por favor, selecione uma data.');
                return;
            }
        }

        // Esconder etapa atual
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-indicator-' + currentStep).classList.remove('active');
        document.getElementById('step-indicator-' + currentStep).classList.add('completed');

        // Mostrar próxima etapa
        currentStep = step;
        document.getElementById('step-' + currentStep).classList.add('active');
        document.getElementById('step-indicator-' + currentStep).classList.add('active');
    }

    function prevStep(step) {
        // Esconder etapa atual
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-indicator-' + currentStep).classList.remove('active');

        // Mostrar etapa anterior
        currentStep = step;
        document.getElementById('step-' + currentStep).classList.add('active');
        document.getElementById('step-indicator-' + currentStep).classList.add('active');
        document.getElementById('step-indicator-' + currentStep).classList.remove('completed');
    }

    // Adicionar classe 'selected' aos itens marcados
    document.querySelectorAll('.member-select-item, .song-select-item').forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    });
</script>

<?php renderAppFooter(); ?>