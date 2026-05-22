<?php
// admin/evento_adicionar.php
require_once '../src/helpers/auth.php';
checkAdmin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

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

<!-- Estilos Customizados Dinâmicos -->
<style>
.form-card {
    display: none;
}
.form-card.active {
    display: block;
    animation: cardFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.step-dot {
    transition: all 0.2s ease-out;
}
.step-dot.active {
    background-color: #2E7EED !important;
    border-color: #2E7EED !important;
    color: #ffffff !important;
    box-shadow: 0 0 12px rgba(46, 126, 237, 0.3);
}
.step-dot.completed {
    background-color: #047857 !important;
    border-color: #047857 !important;
    color: #ffffff !important;
}
.step-item.active .step-label {
    color: #ffffff;
    font-weight: 600;
}
.member-item {
    transition: all 0.15s ease;
}
.member-item.selected {
    background-color: rgba(46, 126, 237, 0.06);
    border-color: #2E7EED !important;
}
.member-item.selected .checkbox-custom {
    background-color: #2E7EED;
    border-color: #2E7EED;
}
.hidden-btn {
    display: none !important;
}

@keyframes cardFadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="max-w-3xl mx-auto px-4 py-8">
    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-950/30 border border-red-900/50 text-red-200 text-sm rounded-[2px] flex items-center gap-3">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Wizard Progress -->
    <div class="relative flex justify-between items-center max-w-md mx-auto mb-10 px-4">
        <!-- Linha de fundo contínua -->
        <div class="absolute left-0 right-0 top-1/2 h-[1px] bg-[#26272B] -translate-y-1/2 z-0"></div>
        
        <!-- Step 1 -->
        <div class="relative z-10 flex flex-col items-center gap-2 bg-[#121316] px-3 step-item active" id="dot-container-1">
            <div class="step-dot active w-8 h-8 rounded-[2px] border border-[#26272B] bg-[#18191D] flex items-center justify-center text-xs font-semibold text-gray-400" id="dot-1">1</div>
            <span class="step-label text-[10px] uppercase tracking-wider text-gray-500 transition-colors">Informações</span>
        </div>
        
        <!-- Step 2 -->
        <div class="relative z-10 flex flex-col items-center gap-2 bg-[#121316] px-3 step-item" id="dot-container-2">
            <div class="step-dot w-8 h-8 rounded-[2px] border border-[#26272B] bg-[#18191D] flex items-center justify-center text-xs font-semibold text-gray-400" id="dot-2">2</div>
            <span class="step-label text-[10px] uppercase tracking-wider text-gray-500 transition-colors">Tipo</span>
        </div>
        
        <!-- Step 3 -->
        <div class="relative z-10 flex flex-col items-center gap-2 bg-[#121316] px-3 step-item" id="dot-container-3">
            <div class="step-dot w-8 h-8 rounded-[2px] border border-[#26272B] bg-[#18191D] flex items-center justify-center text-xs font-semibold text-gray-400" id="dot-3">3</div>
            <span class="step-label text-[10px] uppercase tracking-wider text-gray-500 transition-colors">Participantes</span>
        </div>
    </div>
    
    <form method="POST" id="eventForm">
        <!-- Step 1: Informações Básicas -->
        <div class="form-card active bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl" id="step-1">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="info" class="w-4 h-4 text-[#2E7EED]"></i>
                Informações Básicas
            </div>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Título do Evento *</label>
                    <input type="text" name="title" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" placeholder="Ex: Reunião de Planejamento" required>
                </div>
                
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Descrição</label>
                    <textarea name="description" rows="4" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm resize-none" placeholder="Detalhes sobre o evento..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Data *</label>
                        <input type="date" name="start_date" id="start_date" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="flex items-end pb-3.5">
                        <label class="flex items-center gap-3 cursor-pointer group select-none">
                            <input type="checkbox" name="all_day" id="all_day" onchange="toggleAllDay()" class="rounded-none border border-[#26272B] bg-[#121316] text-[#2E7EED] focus:ring-0 focus:ring-offset-0 w-4 h-4 cursor-pointer">
                            <span class="text-xs text-gray-300 group-hover:text-white transition-colors">Evento de dia inteiro</span>
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4" id="time-fields">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Hora Início</label>
                        <input type="time" name="start_time" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="19:00">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Hora Fim</label>
                        <input type="time" name="end_time" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="21:00">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Local</label>
                    <input type="text" name="location" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" placeholder="Ex: Sala de Reuniões">
                </div>
            </div>
        </div>
        
        <!-- Step 2: Tipo e Categoria -->
        <div class="form-card bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl" id="step-2">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="tag" class="w-4 h-4 text-[#047857]"></i>
                Tipo de Evento
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8">
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="reuniao" checked onchange="updateColor('#64748b')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#64748b]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#64748b] peer-checked:bg-[#64748b]/5 peer-checked:text-white">
                        <span>📋</span> Reunião
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="ensaio_extra" onchange="updateColor('#047857')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#047857]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#047857] peer-checked:bg-[#047857]/5 peer-checked:text-white">
                        <span>🎵</span> Ensaio Extra
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="confraternizacao" onchange="updateColor('#d97706')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#d97706]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#d97706] peer-checked:bg-[#d97706]/5 peer-checked:text-white">
                        <span>🎉</span> Confraternização
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="aniversario" onchange="updateColor('#db2777')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#db2777]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#db2777] peer-checked:bg-[#db2777]/5 peer-checked:text-white">
                        <span>🎂</span> Aniversário
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="treinamento" onchange="updateColor('#2E7EED')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#2E7EED]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#2E7EED] peer-checked:bg-[#2E7EED]/5 peer-checked:text-white">
                        <span>📚</span> Treinamento
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="outro" onchange="updateColor('#64748b')" class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#64748b]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#64748b] peer-checked:bg-[#64748b]/5 peer-checked:text-white">
                        <span>📌</span> Outro
                    </div>
                </label>
            </div>
            
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Cor de Identificação</label>
                <div class="flex flex-wrap gap-3">
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#64748b" checked class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#64748b] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#047857" class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#047857] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#d97706" class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#d97706] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#db2777" class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#db2777] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#2E7EED" class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#2E7EED] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="color" value="#e11d48" class="peer sr-only">
                        <div class="w-8 h-8 rounded-[2px] bg-[#e11d48] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110">
                            <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Participantes -->
        <div class="form-card bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl" id="step-3">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="users" class="w-4 h-4 text-[#d97706]"></i>
                Selecionar Participantes
            </div>
            
            <!-- Campo de Busca Minimalista -->
            <div class="relative mb-4">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 w-4 h-4"></i>
                <input type="text" onkeyup="filterMembers(this.value)" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] pl-10 pr-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" placeholder="Buscar membro por nome ou instrumento...">
            </div>
            
            <!-- Lista com Divisores Finos e Rolagem Otimizada -->
            <div class="max-h-[320px] overflow-y-auto divide-y divide-[#26272B]/70 border border-[#26272B] rounded-[2px] bg-[#121316] custom-scrollbar" id="memberList">
                <?php foreach ($allUsers as $user): ?>
                    <label class="member-item flex items-center gap-4 p-3.5 cursor-pointer border border-transparent border-l-2 hover:bg-[#18191D] select-none" data-search="<?= strtolower($user['name'] . ' ' . ($user['instrument'] ?: 'membro')) ?>">
                        <!-- Checkbox Invisível para estilizar o container pai via JS -->
                        <input type="checkbox" name="participants[]" value="<?= $user['id'] ?>" class="sr-only">
                        
                        <!-- Caixa Customizada de Checkbox -->
                        <div class="checkbox-custom w-4.5 h-4.5 border border-[#26272B] bg-[#18191D] flex items-center justify-center transition-all shrink-0 rounded-[1px]">
                            <i data-lucide="check" class="w-3 h-3 text-white"></i>
                        </div>
                        
                        <div class="member-info flex-1">
                            <div class="text-xs font-semibold text-gray-200">
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                            <div class="text-[10px] text-gray-500 font-medium uppercase tracking-wider mt-0.5">
                                <?= htmlspecialchars($user['instrument'] ?: 'Membro') ?>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="flex gap-4 justify-end mt-8">
            <button type="button" id="btn-back" onclick="changeStep(-1)" class="btn-secondary flex-1 md:flex-none md:w-32 bg-transparent border border-[#26272B] hover:bg-[#18191D] text-gray-300 font-semibold text-sm rounded-[2px] py-3 transition-all active:scale-[0.97] will-change-transform hidden-btn">
                Voltar
            </button>
            <button type="button" id="btn-next" onclick="changeStep(1)" class="btn-primary flex-2 md:flex-none md:w-44 bg-[#2E7EED] hover:bg-[#1e66c9] text-white font-semibold text-sm rounded-[2px] py-3 shadow-lg shadow-[#2E7EED]/10 transition-all active:scale-[0.97] will-change-transform text-center">
                Próximo
            </button>
            <button type="submit" id="btn-finish" class="btn-success flex-2 md:flex-none md:w-44 bg-[#047857] hover:bg-[#035f45] text-white font-semibold text-sm rounded-[2px] py-3 shadow-lg shadow-[#047857]/10 transition-all active:scale-[0.97] will-change-transform text-center hidden-btn">
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
    
    if (currentStep > 1) {
        btnBack.classList.remove('hidden-btn');
    } else {
        btnBack.classList.add('hidden-btn');
    }
    
    if (currentStep === totalSteps) {
        btnNext.classList.add('hidden-btn');
        btnFinish.classList.remove('hidden-btn');
    } else {
        btnNext.classList.remove('hidden-btn');
        btnFinish.classList.add('hidden-btn');
    }
}

function toggleAllDay() {
    const allDay = document.getElementById('all_day').checked;
    document.getElementById('time-fields').style.display = allDay ? 'none' : 'grid';
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
document.querySelectorAll('.member-item input[type="checkbox"]').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.classList.toggle('selected', this.checked);
    });
});

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>


