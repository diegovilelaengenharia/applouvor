<?php
// admin/evento_editar.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

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

<!-- Estilos Customizados Dinâmicos -->
<style>
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
</style>

<div class="max-w-3xl mx-auto px-4 py-8">
    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-950/30 border border-red-900/50 text-red-200 text-sm rounded-[2px] flex items-center gap-3">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <!-- Bloco 1: Informações Básicas -->
        <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="info" class="w-4 h-4 text-[#2E7EED]"></i>
                Informações Básicas
            </div>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Título *</label>
                    <input type="text" name="title" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= htmlspecialchars($event['title']) ?>" required>
                </div>
                
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Descrição</label>
                    <textarea name="description" rows="4" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm resize-none" placeholder="Detalhes sobre o evento..."><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Data *</label>
                        <input type="date" name="start_date" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= $startDate ?>" required>
                    </div>
                    
                    <div class="flex items-end pb-3.5">
                        <label class="flex items-center gap-3 cursor-pointer group select-none">
                            <input type="checkbox" name="all_day" id="all_day" <?= $event['all_day'] ? 'checked' : '' ?> onchange="toggleAllDay()" class="rounded-none border border-[#26272B] bg-[#121316] text-[#2E7EED] focus:ring-0 focus:ring-offset-0 w-4 h-4 cursor-pointer">
                            <span class="text-xs text-gray-300 group-hover:text-white transition-colors">Evento de dia inteiro</span>
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 <?= $event['all_day'] ? 'hidden' : '' ?>" id="time-fields">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Hora Início</label>
                        <input type="time" name="start_time" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= $startTime ?>">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Hora Fim</label>
                        <input type="time" name="end_time" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= $endTime ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Local</label>
                    <input type="text" name="location" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] px-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" value="<?= htmlspecialchars($event['location'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <!-- Bloco 2: Tipo de Evento -->
        <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="tag" class="w-4 h-4 text-[#047857]"></i>
                Tipo de Evento
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8">
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="reuniao" <?= $event['event_type'] === 'reuniao' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#64748b]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#64748b] peer-checked:bg-[#64748b]/5 peer-checked:text-white">
                        <span>📋</span> Reunião
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="ensaio_extra" <?= $event['event_type'] === 'ensaio_extra' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#047857]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#047857] peer-checked:bg-[#047857]/5 peer-checked:text-white">
                        <span>🎵</span> Ensaio Extra
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="confraternizacao" <?= $event['event_type'] === 'confraternizacao' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#d97706]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#d97706] peer-checked:bg-[#d97706]/5 peer-checked:text-white">
                        <span>🎉</span> Confraternização
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="aniversario" <?= $event['event_type'] === 'aniversario' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#db2777]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#db2777] peer-checked:bg-[#db2777]/5 peer-checked:text-white">
                        <span>🎂</span> Aniversário
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="treinamento" <?= $event['event_type'] === 'treinamento' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#2E7EED]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#2E7EED] peer-checked:bg-[#2E7EED]/5 peer-checked:text-white">
                        <span>📚</span> Treinamento
                    </div>
                </label>
                
                <label class="relative cursor-pointer group">
                    <input type="radio" name="event_type" value="outro" <?= $event['event_type'] === 'outro' ? 'checked' : '' ?> class="peer sr-only">
                    <div class="w-full h-full bg-[#121316] border border-[#26272B] hover:border-[#64748b]/40 rounded-[2px] py-4 px-3 flex flex-col items-center justify-center gap-2 text-center text-xs font-semibold text-gray-400 transition-all active:scale-[0.97] will-change-transform peer-checked:border-[#64748b] peer-checked:bg-[#64748b]/5 peer-checked:text-white">
                        <span>📌</span> Outro
                    </div>
                </label>
            </div>
            
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Cor de Identificação</label>
                <div class="flex flex-wrap gap-3">
                    <?php
                    $colors = ['#64748b', '#047857', '#d97706', '#db2777', '#2E7EED', '#e11d48'];
                    foreach ($colors as $color):
                        $isColorSelected = ($event['color'] === $color || ($color === '#64748b' && $event['color'] === 'var(--slate-500)') || ($color === '#047857' && $event['color'] === '#047857') || ($color === '#d97706' && $event['color'] === 'var(--yellow-500)') || ($color === '#db2777' && $event['color'] === '#ec4899') || ($color === '#e11d48' && $event['color'] === 'var(--rose-500)'));
                    ?>
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="color" value="<?= $color ?>" <?= $isColorSelected ? 'checked' : '' ?> class="peer sr-only">
                            <div class="w-8 h-8 rounded-[2px] border border-[#26272B] flex items-center justify-center text-white font-bold transition-all group-active:scale-[0.90] peer-checked:border-white peer-checked:scale-110" style="background-color: <?= $color ?>;">
                                <i data-lucide="check" class="w-3.5 h-3.5 hidden group-has-[:checked]:block"></i>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Bloco 3: Participantes -->
        <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 md:p-8 shadow-xl">
            <div class="flex items-center gap-2 text-md font-semibold text-white uppercase tracking-wider mb-6 pb-3 border-b border-[#26272B]">
                <i data-lucide="users" class="w-4 h-4 text-[#d97706]"></i>
                Participantes
            </div>
            
            <!-- Campo de Busca Minimalista Inteligente -->
            <div class="relative mb-4">
                <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 w-4 h-4"></i>
                <input type="text" onkeyup="filterMembers(this.value)" class="w-full bg-[#121316] border border-[#26272B] focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] rounded-[2px] pl-10 pr-4 py-3 text-white placeholder-gray-600 transition-all outline-none text-sm" placeholder="Buscar membro por nome ou instrumento...">
            </div>
            
            <!-- Lista de Membros Premium -->
            <div class="max-h-[320px] overflow-y-auto divide-y divide-[#26272B]/70 border border-[#26272B] rounded-[2px] bg-[#121316] custom-scrollbar" id="memberList">
                <?php foreach ($allUsers as $user):
                    $isSelected = in_array($user['id'], $currentParticipants);
                ?>
                    <label class="member-item flex items-center gap-4 p-3.5 cursor-pointer border border-transparent border-l-2 hover:bg-[#18191D] select-none <?= $isSelected ? 'selected' : '' ?>" data-search="<?= strtolower($user['name'] . ' ' . ($user['instrument'] ?: 'membro')) ?>">
                        <!-- Checkbox Invisível -->
                        <input type="checkbox" name="participants[]" value="<?= $user['id'] ?>" <?= $isSelected ? 'checked' : '' ?> class="sr-only">
                        
                        <!-- Caixa Customizada de Checkbox -->
                        <div class="checkbox-custom w-4.5 h-4.5 border border-[#26272B] bg-[#18191D] flex items-center justify-center transition-all shrink-0 rounded-[1px]">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
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
        
        <!-- Botões de Ação -->
        <div class="flex flex-col md:flex-row gap-3 pt-4">
            <button type="submit" class="w-full md:flex-2 bg-[#047857] hover:bg-[#035f45] text-white font-semibold text-sm rounded-[2px] py-3.5 px-6 shadow-lg shadow-[#047857]/10 transition-all active:scale-[0.97] will-change-transform flex items-center justify-center gap-2 order-1 md:order-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Alterações
            </button>
            <button type="submit" name="delete" class="w-full md:flex-1 bg-red-950/20 hover:bg-red-950/40 text-red-400 hover:text-red-300 border border-red-900/40 rounded-[2px] py-3.5 px-6 transition-all active:scale-[0.97] will-change-transform flex items-center justify-center gap-2 order-2 md:order-1" onclick="return confirm('Tem certeza que deseja excluir este evento definitivamente?')">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Excluir
            </button>
            <a href="evento_detalhe.php?id=<?= $eventId ?>" class="w-full md:flex-1 bg-transparent border border-[#26272B] hover:bg-[#18191D] text-gray-400 hover:text-gray-300 font-semibold text-sm rounded-[2px] py-3.5 px-6 transition-all active:scale-[0.97] will-change-transform text-center flex items-center justify-center order-3">
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

function filterMembers(term) {
    term = term.toLowerCase();
    const items = document.querySelectorAll('.member-item');
    items.forEach(item => {
        const text = item.getAttribute('data-search');
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

document.querySelectorAll('.member-item input[type="checkbox"]').forEach(input => {
    input.addEventListener('change', function() {
        this.parentElement.classList.toggle('selected', this.checked);
    });
});

lucide.createIcons();
</script>

