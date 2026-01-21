<?php
// admin/escala_adicionar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Buscar dados para os selects
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventType = $_POST['event_type'];
    if ($eventType === 'Outro' && !empty($_POST['custom_event_type'])) {
        $eventType = $_POST['custom_event_type'];
    }

    $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_type, notes) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['event_date'], $eventType, $_POST['notes']]);
    $scheduleId = $pdo->lastInsertId();

    if (!empty($_POST['members'])) {
        $stmtMember = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
        foreach ($_POST['members'] as $userId) {
            $stmtMember->execute([$scheduleId, $userId]);
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
?>

<!-- Header Clean -->
<header style="
    background: white; 
    padding: 20px 24px; 
    border-bottom: 1px solid #e2e8f0; 
    margin: -16px -16px 24px -16px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    position: sticky; top: 0; z-index: 20;
">
    <a href="escalas.php" class="ripple" style="
        width: 40px; height: 40px; 
        display: flex; align-items: center; justify-content: center; 
        text-decoration: none; color: #64748b; 
        border-radius: 50%;
        transition: background 0.2s;
    " onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
        <i data-lucide="x"></i>
    </a>

    <div style="text-align: center;">
        <h1 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">Nova Escala</h1>
        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Passo <span id="step-count">1</span> de 3</p>
    </div>

    <div style="width: 40px;"></div> <!-- Spacer para centralizar -->
</header>

<style>
    .wizard-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 16px;
    }

    .wizard-progress {
        display: flex;
        justify-content: space-between;
        margin-bottom: 32px;
        position: relative;
    }

    .wizard-progress::before {
        content: '';
        position: absolute;
        top: 14px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e2e8f0;
        z-index: 0;
    }

    .step-dot {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: white;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        color: #94a3b8;
        position: relative;
        z-index: 1;
        transition: all 0.3s;
    }

    .step-dot.active {
        border-color: #166534;
        background: #166534;
        color: white;
        box-shadow: 0 0 0 4px rgba(22, 101, 52, 0.1);
    }

    .step-dot.completed {
        border-color: #166534;
        background: #166534;
        color: white;
    }

    .step-label {
        position: absolute;
        top: 36px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        white-space: nowrap;
    }

    .step-dot.active+.step-label {
        color: #166534;
    }

    /* Cards e Inputs */
    .form-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        display: none;
        /* Oculto por padrão */
        animation: fadeIn 0.3s ease;
    }

    .form-section.active {
        display: block;
    }

    .input-clean {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 1rem;
        color: #1e293b;
        outline: none;
        transition: border 0.2s;
    }

    .input-clean:focus {
        border-color: #166534;
        box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.1);
    }

    /* Radio Cards */
    .radio-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .radio-option input {
        display: none;
    }

    .radio-box {
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .radio-option input:checked+.radio-box {
        background: #f0fdf4;
        border-color: #166534;
        color: #166534;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(22, 101, 52, 0.1);
    }

    /* List Items */
    .select-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .select-item:hover {
        background: #f8fafc;
    }

    .select-item.selected {
        background: #f0fdf4;
        border-color: #166534;
    }

    .select-item input {
        width: 18px;
        height: 18px;
        accent-color: #166534;
    }

    /* Bottom Actions */
    .actions-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e2e8f0;
        padding: 16px 24px;
        display: flex;
        gap: 12px;
        z-index: 50;
    }

    @media(min-width: 1024px) {
        .actions-bar {
            position: static;
            border: none;
            padding: 0;
            margin-top: 24px;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="wizard-container">

    <!-- Progress Indicator -->
    <div class="wizard-progress">
        <div class="step-dot active" id="dot-1">1 <span class="step-label">Detalhes</span></div>
        <div class="step-dot" id="dot-2">2 <span class="step-label">Equipe</span></div>
        <div class="step-dot" id="dot-3">3 <span class="step-label">Músicas</span></div>
    </div>

    <form method="POST" id="wizardForm">

        <!-- PASSO 1: Detalhes -->
        <div class="form-section active" id="step-1">
            <h2 style="margin: 0 0 24px 0; font-size: 1.25rem; color: #1e293b;">Informações Básicas</h2>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Data do Evento</label>
                <input type="date" name="event_date" id="event_date" class="input-clean" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Tipo de Evento</label>
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

            <div style="margin-bottom: 8px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #475569;">Observações (Opcional)</label>
                <textarea name="notes" class="input-clean" rows="3" placeholder="Ex: Ceia, Visitante Especial..."></textarea>
            </div>
        </div>

        <!-- PASSO 2: Equipe -->
        <div class="form-section" id="step-2">
            <h2 style="margin: 0 0 16px 0; font-size: 1.25rem; color: #1e293b;">Selecionar Equipe</h2>

            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8; width: 18px;"></i>
                <input type="text" onkeyup="filterList('list-members', this.value)" class="input-clean" style="padding-left: 40px;" placeholder="Buscar membro...">
            </div>

            <div id="list-members" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allUsers as $user): ?>
                    <label class="select-item" data-search="<?= strtolower($user['name']) ?>">
                        <input type="checkbox" name="members[]" value="<?= $user['id'] ?>">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($user['instrument'] ?: 'Vocal') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PASSO 3: Músicas -->
        <div class="form-section" id="step-3">
            <h2 style="margin: 0 0 16px 0; font-size: 1.25rem; color: #1e293b;">Selecionar Repertório</h2>

            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8; width: 18px;"></i>
                <input type="text" onkeyup="filterList('list-songs', this.value)" class="input-clean" style="padding-left: 40px;" placeholder="Buscar música...">
            </div>

            <div id="list-songs" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allSongs as $song): ?>
                    <label class="select-item" data-search="<?= strtolower($song['title'] . ' ' . $song['artist']) ?>">
                        <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?> • <span style="font-weight:700; color:#d97706;"><?= $song['tone'] ?></span></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navegação Bottom -->
        <div class="actions-bar">
            <button type="button" id="btn-back" onclick="changeStep(-1)" style="
                flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: white; color: #475569; font-weight: 600; cursor: pointer; display: none;
            ">Voltar</button>

            <button type="button" id="btn-next" onclick="changeStep(1)" style="
                flex: 2; padding: 12px; border: none; border-radius: 10px; background: #166534; color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 101, 52, 0.2);
            ">Próximo</button>

            <button type="submit" id="btn-finish" style="
                flex: 2; padding: 12px; border: none; border-radius: 10px; background: #1e293b; color: white; font-weight: 700; cursor: pointer; display: none; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
            ">Finalizar</button>
        </div>

    </form>
</div>

<div style="height: 100px;"></div> <!-- Spacer final -->

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
            document.getElementById('dot-' + nextStep).classList.add('active');
        } else {
            document.getElementById('dot-' + currentStep).classList.remove('active');
            document.getElementById('dot-' + nextStep).classList.remove('completed');
            document.getElementById('dot-' + nextStep).classList.add('active');
        }

        // Trocar tela
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-' + nextStep).classList.add('active');

        currentStep = nextStep;
        document.getElementById('step-count').innerText = currentStep;

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
</script>

<?php renderAppFooter(); ?>