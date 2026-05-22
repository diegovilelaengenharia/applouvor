<?php
// admin/escala_adicionar.php
require_once '../src/helpers/auth.php';
checkAdmin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

// Buscar dados para os selects
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

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

<!-- SACRED MINIMALIST WIZARD FOR ADDING SCHEDULES -->
<main class="max-w-[800px] mx-auto px-margin-mobile md:px-margin-desktop py-6 mb-24 animate-in duration-300">
    
    <!-- Wizard Progress bar and Dots -->
    <div class="flex items-center justify-between mb-10 max-w-[500px] mx-auto relative px-4">
        <!-- Progress track behind dots -->
        <div class="absolute top-[15px] left-0 right-0 h-[2px] bg-outline-variant/30 dark:bg-outline-variant/10 -translate-y-1/2 z-0"></div>
        <div id="progress-bar-line" class="absolute top-[15px] left-0 h-[2px] bg-worship-blue -translate-y-1/2 z-0 transition-all duration-300" style="width: 0%;"></div>

        <!-- Step 1 -->
        <div class="relative z-10 flex flex-col items-center gap-2 cursor-pointer select-none" onclick="goToStep(1)" id="dot-container-1">
            <div id="dot-1" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-worship-blue text-white border-worship-blue shadow-[0_0_15px_rgba(46,126,237,0.3)]">1</div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-worship-blue" id="label-step-1">Detalhes</span>
        </div>

        <!-- Step 2 -->
        <div class="relative z-10 flex flex-col items-center gap-2 cursor-pointer select-none" onclick="goToStep(2)" id="dot-container-2">
            <div id="dot-2" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-white dark:bg-deep-navy text-secondary dark:text-on-surface-variant border-outline-variant/60">2</div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-secondary dark:text-on-surface-variant" id="label-step-2">Participantes</span>
        </div>

        <!-- Step 3 -->
        <div class="relative z-10 flex flex-col items-center gap-2 cursor-pointer select-none" onclick="goToStep(3)" id="dot-container-3">
            <div id="dot-3" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-white dark:bg-deep-navy text-secondary dark:text-on-surface-variant border-outline-variant/60">3</div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-secondary dark:text-on-surface-variant" id="label-step-3">Músicas</span>
        </div>
    </div>

    <form method="POST" id="wizardForm" class="space-y-6">
        <?= App\AuthMiddleware::csrfField() ?>

        <!-- PASSO 1: Detalhes -->
        <div class="bg-white dark:bg-deep-navy border border-outline-variant/60 dark:border-outline-variant/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-sm transition-all duration-300 block" id="step-1">
            <div class="flex items-center gap-2 pb-4 border-b border-outline-variant/40 dark:border-outline-variant/10">
                <i data-lucide="calendar" class="w-5 h-5 text-worship-blue"></i>
                <h2 class="font-headline-md text-lg font-bold text-on-background">Informações do Evento</h2>
            </div>
            
            <div class="space-y-2">
                <label for="event_date" class="block text-xs font-bold text-secondary dark:text-on-surface-variant uppercase tracking-wider">Data do Evento</label>
                <div class="relative">
                    <input type="date" name="event_date" id="event_date" class="w-full bg-ghost-gray dark:bg-black/20 text-on-background border border-outline-variant/60 dark:border-outline-variant/15 rounded-xl px-4 py-3.5 focus:outline-none focus:border-worship-blue focus:ring-1 focus:ring-worship-blue transition-all" value="<?= date('Y-m-d') ?>" required>
                    <i data-lucide="calendar-days" class="absolute right-4 top-3.5 text-secondary dark:text-on-surface-variant w-5 h-5 pointer-events-none"></i>
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-xs font-bold text-secondary dark:text-on-surface-variant uppercase tracking-wider">Tipo de Evento</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="event_type" value="Culto Domingo a Noite" checked onchange="toggleCustomEventType()" class="sr-only peer">
                        <div class="h-full flex items-center justify-center text-center p-3.5 text-xs font-bold uppercase tracking-wider border rounded-xl bg-transparent text-secondary dark:text-on-surface-variant border-outline-variant/60 hover:border-worship-blue/40 peer-checked:border-worship-blue peer-checked:bg-worship-blue/5 peer-checked:text-worship-blue transition-all">
                            Domingo (Noite)
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="event_type" value="Culto Tema Especial" onchange="toggleCustomEventType()" class="sr-only peer">
                        <div class="h-full flex items-center justify-center text-center p-3.5 text-xs font-bold uppercase tracking-wider border rounded-xl bg-transparent text-secondary dark:text-on-surface-variant border-outline-variant/60 hover:border-worship-blue/40 peer-checked:border-worship-blue peer-checked:bg-worship-blue/5 peer-checked:text-worship-blue transition-all">
                            Tema Especial
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="event_type" value="Ensaio" onchange="toggleCustomEventType()" class="sr-only peer">
                        <div class="h-full flex items-center justify-center text-center p-3.5 text-xs font-bold uppercase tracking-wider border rounded-xl bg-transparent text-secondary dark:text-on-surface-variant border-outline-variant/60 hover:border-worship-blue/40 peer-checked:border-worship-blue peer-checked:bg-worship-blue/5 peer-checked:text-worship-blue transition-all">
                            Ensaio
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="event_type" value="Outro" onchange="toggleCustomEventType()" class="sr-only peer">
                        <div class="h-full flex items-center justify-center text-center p-3.5 text-xs font-bold uppercase tracking-wider border rounded-xl bg-transparent text-secondary dark:text-on-surface-variant border-outline-variant/60 hover:border-worship-blue/40 peer-checked:border-worship-blue peer-checked:bg-worship-blue/5 peer-checked:text-worship-blue transition-all">
                            Outro
                        </div>
                    </label>
                </div>
                <div id="customTypeBox" class="hidden animate-in fade-in zoom-in-95 duration-200 mt-3">
                    <input type="text" name="custom_event_type" id="custom_event_input" class="w-full bg-ghost-gray dark:bg-black/20 text-on-background border border-outline-variant/60 dark:border-outline-variant/15 rounded-xl px-4 py-3.5 focus:outline-none focus:border-worship-blue focus:ring-1 focus:ring-worship-blue transition-all" placeholder="Digite o nome do evento...">
                </div>
            </div>

            <div class="space-y-2">
                <label for="notes" class="block text-xs font-bold text-secondary dark:text-on-surface-variant uppercase tracking-wider">Observações</label>
                <textarea name="notes" id="notes" class="w-full bg-ghost-gray dark:bg-black/20 text-on-background border border-outline-variant/60 dark:border-outline-variant/15 rounded-xl px-4 py-3.5 focus:outline-none focus:border-worship-blue focus:ring-1 focus:ring-worship-blue transition-all" rows="4" placeholder="Ex: Culto com Ceia, Visitante Especial..."></textarea>
            </div>
        </div>

        <!-- PASSO 2: Equipe -->
        <div class="bg-white dark:bg-deep-navy border border-outline-variant/60 dark:border-outline-variant/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-sm transition-all duration-300 hidden" id="step-2">
            <div class="flex items-center justify-between pb-4 border-b border-outline-variant/40 dark:border-outline-variant/10">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-worship-blue"></i>
                    <h2 class="font-headline-md text-lg font-bold text-on-background">Selecionar Equipe</h2>
                </div>
                <span class="text-xs font-bold bg-worship-blue/10 text-worship-blue dark:bg-worship-blue/20 dark:text-primary-fixed px-3 py-1 rounded-full uppercase tracking-wider" id="members-selected-badge">0 Selecionados</span>
            </div>
            
            <div class="relative">
                <i data-lucide="search" class="absolute left-4 top-3.5 text-secondary dark:text-on-surface-variant w-5 h-5"></i>
                <input type="text" onkeyup="filterList('list-members', this.value)" class="w-full bg-ghost-gray dark:bg-black/20 text-on-background border border-outline-variant/60 dark:border-outline-variant/15 rounded-xl pl-12 pr-4 py-3.5 focus:outline-none focus:border-worship-blue focus:ring-1 focus:ring-worship-blue transition-all" placeholder="Buscar membro por nome ou instrumento...">
            </div>

            <div id="list-members" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[380px] overflow-y-auto pr-1">
                <?php foreach ($allUsers as $user): 
                    $uPhoto = 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random';
                    $uInstrument = htmlspecialchars($user['instrument'] ?: 'Vocal');
                ?>
                    <label class="select-item cursor-pointer flex items-center gap-3 p-3.5 border border-outline-variant/60 dark:border-outline-variant/10 rounded-xl bg-transparent hover:border-worship-blue/40 dark:hover:bg-black/10 transition-all select-none" data-search="<?= strtolower($user['name'] . ' ' . $uInstrument) ?>">
                        <input type="checkbox" name="members[]" value="<?= $user['id'] ?>" class="sr-only peer" onchange="updateSelectedCount('list-members', 'members-selected-badge')">
                        
                        <!-- Custom indicator checkbox -->
                        <div class="w-5 h-5 rounded-md border border-outline-variant/80 dark:border-outline-variant/30 flex items-center justify-center peer-checked:bg-worship-blue peer-checked:border-worship-blue text-white transition-all shrink-0">
                            <i data-lucide="check" class="w-3.5 h-3.5 stroke-[3]"></i>
                        </div>

                        <img src="<?= $uPhoto ?>" class="w-9 h-9 rounded-full object-cover ring-1 ring-outline-variant/20 shrink-0" alt="<?= htmlspecialchars($user['name']) ?>">

                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-sm text-on-background truncate"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="text-xs text-secondary truncate"><?= $uInstrument ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PASSO 3: Músicas -->
        <div class="bg-white dark:bg-deep-navy border border-outline-variant/60 dark:border-outline-variant/10 rounded-2xl p-6 md:p-8 space-y-6 shadow-sm transition-all duration-300 hidden" id="step-3">
            <div class="flex items-center justify-between pb-4 border-b border-outline-variant/40 dark:border-outline-variant/10">
                <div class="flex items-center gap-2">
                    <i data-lucide="music" class="w-5 h-5 text-worship-blue"></i>
                    <h2 class="font-headline-md text-lg font-bold text-on-background">Selecionar Repertório</h2>
                </div>
                <span class="text-xs font-bold bg-worship-blue/10 text-worship-blue dark:bg-worship-blue/20 dark:text-primary-fixed px-3 py-1 rounded-full uppercase tracking-wider" id="songs-selected-badge">0 Selecionadas</span>
            </div>

            <div class="relative">
                <i data-lucide="search" class="absolute left-4 top-3.5 text-secondary dark:text-on-surface-variant w-5 h-5"></i>
                <input type="text" onkeyup="filterList('list-songs', this.value)" class="w-full bg-ghost-gray dark:bg-black/20 text-on-background border border-outline-variant/60 dark:border-outline-variant/15 rounded-xl pl-12 pr-4 py-3.5 focus:outline-none focus:border-worship-blue focus:ring-1 focus:ring-worship-blue transition-all" placeholder="Buscar música por título ou artista...">
            </div>

            <div id="list-songs" class="grid grid-cols-1 gap-2.5 max-h-[380px] overflow-y-auto pr-1">
                <?php foreach ($allSongs as $song): ?>
                    <label class="select-item cursor-pointer flex items-center justify-between p-3.5 border border-outline-variant/60 dark:border-outline-variant/10 rounded-xl bg-transparent hover:border-worship-blue/40 dark:hover:bg-black/10 transition-all select-none" data-search="<?= strtolower($song['title'] . ' ' . $song['artist']) ?>">
                        <div class="flex items-center gap-3 min-w-0">
                            <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>" class="sr-only peer" onchange="updateSelectedCount('list-songs', 'songs-selected-badge')">
                            
                            <!-- Custom indicator checkbox -->
                            <div class="w-5 h-5 rounded-md border border-outline-variant/80 dark:border-outline-variant/30 flex items-center justify-center peer-checked:bg-worship-blue peer-checked:border-worship-blue text-white transition-all shrink-0">
                                <i data-lucide="check" class="w-3.5 h-3.5 stroke-[3]"></i>
                            </div>

                            <div class="min-w-0">
                                <div class="font-bold text-sm text-on-background truncate"><?= htmlspecialchars($song['title']) ?></div>
                                <div class="text-xs text-secondary truncate"><?= htmlspecialchars($song['artist']) ?></div>
                            </div>
                        </div>

                        <span class="text-[10px] font-bold bg-altar-gold/10 text-altar-gold border border-altar-gold/20 px-2 py-0.5 rounded uppercase tracking-wider shrink-0"><?= $song['tone'] ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Action buttons -->
        <div class="flex items-center gap-4 pt-4 border-t border-outline-variant/30 dark:border-outline-variant/10">
            <button type="button" id="btn-back" onclick="changeStep(-1)" class="flex-1 py-3.5 px-6 rounded-full font-bold text-sm text-secondary dark:text-on-surface-variant hover:bg-ghost-gray dark:hover:bg-surface-variant/40 border border-outline-variant/60 transition-all duration-200 hidden select-none">
                Voltar
            </button>

            <button type="button" id="btn-next" onclick="changeStep(1)" class="flex-1 py-3.5 px-6 rounded-full font-bold text-sm text-white bg-worship-blue hover:brightness-110 shadow-sm active:scale-[0.98] transition-all duration-200 select-none">
                Avançar
            </button>

            <button type="submit" id="btn-finish" class="flex-1 py-3.5 px-6 rounded-full font-bold text-sm text-white bg-emerald-600 hover:brightness-110 shadow-sm active:scale-[0.98] transition-all duration-200 hidden select-none">
                Finalizar Escala
            </button>
        </div>

    </form>
</main>

<script>
    let currentStep = 1;
    const totalSteps = 3;

    function changeStep(direction) {
        // Validation for step 1 date selection
        if (currentStep === 1 && direction === 1) {
            if (!document.getElementById('event_date').value) {
                alert('Selecione uma data para continuar.');
                return;
            }
        }

        const nextStep = currentStep + direction;
        if (nextStep < 1 || nextStep > totalSteps) return;

        goToStep(nextStep);
    }

    function goToStep(step) {
        // Prevent going forward without completing step 1 date validation
        if (step > 1 && !document.getElementById('event_date').value) {
            alert('Selecione uma data para continuar.');
            return;
        }

        const progressLine = document.getElementById('progress-bar-line');
        const percentage = ((step - 1) / (totalSteps - 1)) * 100;
        progressLine.style.width = percentage + '%';

        // Update progress Dots and Labels
        for (let i = 1; i <= totalSteps; i++) {
            const dot = document.getElementById('dot-' + i);
            const label = document.getElementById('label-step-' + i);
            
            if (i === step) {
                dot.className = "w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-worship-blue text-white border-worship-blue shadow-[0_0_15px_rgba(46,126,237,0.3)]";
                label.className = "text-[10px] font-bold uppercase tracking-wider text-worship-blue";
                dot.innerHTML = i;
            } else if (i < step) {
                dot.className = "w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-emerald-500 text-white border-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.2)]";
                label.className = "text-[10px] font-bold uppercase tracking-wider text-emerald-500";
                dot.innerHTML = '<i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>';
            } else {
                dot.className = "w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border transition-all duration-300 bg-white dark:bg-deep-navy text-secondary dark:text-on-surface-variant border-outline-variant/60";
                label.className = "text-[10px] font-bold uppercase tracking-wider text-secondary dark:text-on-surface-variant";
                dot.innerHTML = i;
            }
        }

        // Switch panels with sliding fade animation
        const currentPanel = document.getElementById('step-' + currentStep);
        const nextPanel = document.getElementById('step-' + step);

        currentPanel.classList.add('hidden');
        currentPanel.classList.remove('block');
        nextPanel.classList.remove('hidden');
        nextPanel.classList.add('block', 'animate-in', 'fade-in', 'zoom-in-95', 'duration-200');

        currentStep = step;
        updateButtons();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        lucide.createIcons();
    }

    function updateButtons() {
        const btnBack = document.getElementById('btn-back');
        const btnNext = document.getElementById('btn-next');
        const btnFinish = document.getElementById('btn-finish');

        if (currentStep === 1) {
            btnBack.classList.add('hidden');
        } else {
            btnBack.classList.remove('hidden');
        }

        if (currentStep === totalSteps) {
            btnNext.classList.add('hidden');
            btnFinish.classList.remove('hidden');
        } else {
            btnNext.classList.remove('hidden');
            btnFinish.classList.add('hidden');
        }
    }

    function toggleCustomEventType() {
        const customBox = document.getElementById('customTypeBox');
        const customInput = document.getElementById('custom_event_input');
        const isOutro = document.querySelector('input[name="event_type"]:checked').value === 'Outro';

        if (isOutro) {
            customBox.classList.remove('hidden');
            customBox.classList.add('block');
            customInput.focus();
            customInput.required = true;
        } else {
            customBox.classList.remove('block');
            customBox.classList.add('hidden');
            customInput.required = false;
        }
    }

    function filterList(listId, term) {
        term = term.toLowerCase();
        const items = document.querySelectorAll(`#${listId} .select-item`);
        items.forEach(item => {
            const text = item.getAttribute('data-search');
            if (text.includes(term)) {
                item.classList.remove('hidden');
                item.classList.add('flex');
            } else {
                item.classList.remove('flex');
                item.classList.add('hidden');
            }
        });
    }

    function updateSelectedCount(listId, badgeId) {
        const checkedCount = document.querySelectorAll(`#${listId} input[type="checkbox"]:checked`).length;
        const badge = document.getElementById(badgeId);
        badge.textContent = checkedCount + (listId.includes('members') ? ' Selecionados' : ' Selecionadas');
        
        // Dynamic card styling based on checked status
        document.querySelectorAll(`#${listId} .select-item`).forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                card.classList.add('border-worship-blue', 'bg-worship-blue/5', 'dark:bg-worship-blue/10');
                card.classList.remove('border-outline-variant/60', 'dark:border-outline-variant/10');
            } else {
                card.classList.remove('border-worship-blue', 'bg-worship-blue/5', 'dark:bg-worship-blue/10');
                card.classList.add('border-outline-variant/60', 'dark:border-outline-variant/10');
            }
        });
    }

    // Initialize counts on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateSelectedCount('list-members', 'members-selected-badge');
        updateSelectedCount('list-songs', 'songs-selected-badge');
    });
</script>

<?php renderAppFooter(); ?>