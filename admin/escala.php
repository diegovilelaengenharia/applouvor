<?php
// admin/escala.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// Processar exclusão em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedules'])) {
    checkAdmin();
    if (!empty($_POST['schedule_ids'])) {
        $placeholders = str_repeat('?,', count($_POST['schedule_ids']) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id IN ($placeholders)");
        $stmt->execute($_POST['schedule_ids']);
    }
    header("Location: escala.php?tab=" . ($_POST['current_tab'] ?? 'next') . "&view=" . ($_POST['current_view'] ?? 'timeline'));
    exit;
}

// Filtros e Visualização
$viewMode = $_GET['view'] ?? 'timeline'; // timeline, list
$tab = $_GET['tab'] ?? 'next';
$loggedUserId = $_SESSION['user_id'] ?? 1;

// Construir Query Dinâmica com Filtros para o Banco de Dados
$sql = "SELECT DISTINCT s.* FROM schedules s";
$joins = [];
$wheres = ["1=1"];
$params = [];

// Filtro: Apenas minhas escalas (onde estou escalado)
if (isset($_GET['filter_my']) && $_GET['filter_my'] == '1') {
    $joins[] = "JOIN schedule_users su_my ON s.id = su_my.schedule_id";
    $wheres[] = "su_my.user_id = ?";
    $params[] = $loggedUserId;
}

// Filtro: Membro específico
if (!empty($_GET['filter_member'])) {
    $joins[] = "JOIN schedule_users su_mem ON s.id = su_mem.schedule_id";
    $wheres[] = "su_mem.user_id = ?";
    $params[] = $_GET['filter_member'];
}

// Filtro: Música específica
if (!empty($_GET['filter_song'])) {
    $joins[] = "JOIN schedule_songs ss_song ON s.id = ss_song.schedule_id";
    $wheres[] = "ss_song.song_id = ?";
    $params[] = $_GET['filter_song'];
}

// Filtro: Tipo de Evento (Equipe)
if (!empty($_GET['filter_team'])) {
    $wheres[] = "s.event_type LIKE ?";
    $params[] = "%" . $_GET['filter_team'] . "%";
}

// Filtro da Aba (Próximas ou Histórico)
if ($tab === 'history') {
    $wheres[] = "s.event_date < CURDATE()";
    $orderBy = "s.event_date DESC";
} else {
    $wheres[] = "s.event_date >= CURDATE()";
    $orderBy = "s.event_date ASC";
}

// Montar e executar Query Final
$sql .= " " . implode(" ", array_unique($joins));
$sql .= " WHERE " . implode(" AND ", $wheres);
$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Carregar Dependências Reais em Lote (Batch) usando o Repository ---
$scheduleIds = array_column($schedules, 'id');
$participantsMap = [];
$songCountsMap = [];

if (!empty($scheduleIds)) {
    require_once '../src/classes/ScheduleRepository.php';
    $scheduleRepo = new \App\Repositories\ScheduleRepository($pdo);
    try {
        $participantsMap = $scheduleRepo->getParticipantsByScheduleIds($scheduleIds);
        $songCountsMap   = $scheduleRepo->getSongCountsByScheduleIds($scheduleIds);
    } catch (Exception $e) {
        // Fallback silencioso em caso de ausência estrutural
    }
}

// Helpers Visuais de Tema e Cores
function getEventThemeColor($type) {
    $type = mb_strtolower($type);
    if (strpos($type, 'ensaio') !== false) return '#D97706'; // Âmbar tátil
    if (strpos($type, 'jovem') !== false) return '#0D9488'; // Ciano espiritual
    if (strpos($type, 'especial') !== false) return '#E11D48'; // Coral solene
    return '#2E7EED'; // Azul Worship Safira
}

function getEventMonthShort($n) {
    $months = [
        1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR',
        5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
        9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ'
    ];
    return $months[(int)$n] ?? '';
}

function getEventWeekdayLabel($w) {
    $days = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    return $days[(int)$w] ?? '';
}

// Contar Filtros Ativos
$activeFiltersCount = 0;
if (isset($_GET['filter_my']) && $_GET['filter_my'] == '1') $activeFiltersCount++;
if (!empty($_GET['filter_member'])) $activeFiltersCount++;
if (!empty($_GET['filter_song'])) $activeFiltersCount++;
if (!empty($_GET['filter_team'])) $activeFiltersCount++;

renderAppHeader('Painel de Escalas', 'index.php');
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-24 space-y-8 animate-fade-in">
    
    <!-- Hero Banner Premium -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1A1B1F] to-[#2C2E35] text-white p-8 md:p-10 shadow-xl border border-white/10">
        <!-- Elementos Decorativos de Fundo -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#2E7EED]/20 border border-[#2E7EED]/30 text-[#2E7EED] text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7EED] animate-pulse"></span>
                    Administração e Filtros
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-2">
                    Painel de <span class="bg-gradient-to-r from-[#2E7EED] to-[#60A5FA] bg-clip-text text-transparent">Escalas</span>
                </h1>
                <p class="text-gray-400 font-body text-sm md:text-base max-w-xl">
                    Busque escalas por músicos ou repertórios específicos, gerencie múltiplos registros em lote e alterne os modos de exibição.
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 self-start md:self-auto">
                <!-- Toggle Mode Selection -->
                <button type="button" id="btnSelectMode" onclick="toggleSelectionMode()" class="bg-white/5 hover:bg-white/10 active:scale-95 text-white h-[50px] px-5 rounded-2xl font-bold text-xs tracking-wide transition-all border border-white/10 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">checklist</span>
                    <span>Selecionar em Lote</span>
                </button>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="escala_adicionar.php" class="bg-[#2E7EED] hover:bg-[#1872e0] active:scale-95 text-white h-[50px] px-5 rounded-2xl font-bold text-xs tracking-wide transition-all shadow-lg shadow-[#2E7EED]/25 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span> Nova Escala
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Layout Bento de 12 colunas -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Coluna de Filtros (Bento Box Lateral) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Box de Filtros principal (Desktop) -->
            <div class="hidden lg:block bg-white border border-[#EDEDED] rounded-3xl p-6 shadow-sm sticky top-24 space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-[#1A1B1F] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#2E7EED]">filter_alt</span>
                        Filtros Avançados
                        <?php if ($activeFiltersCount > 0): ?>
                            <span class="bg-[#2E7EED]/10 text-[#2E7EED] text-[10px] px-2 py-0.5 rounded-full font-black"><?= $activeFiltersCount ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Refine a listagem das escalas dinamicamente</p>
                </div>
                
                <form method="GET" action="escala.php" class="space-y-5">
                    <input type="hidden" name="view" value="<?= $viewMode ?>">
                    <input type="hidden" name="tab" value="<?= $tab ?>">

                    <!-- Toggle: Apenas que participo (Switch Tátil Premium) -->
                    <div class="p-4 bg-[#F4F4F5] border border-[#EDEDED] rounded-2xl flex items-center justify-between transition-all duration-200">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-[#1A1B1F]">Onde eu participo</span>
                            <span class="text-[9px] text-gray-400">Ver cultos em que fui escalado</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="filter_my" value="1" <?= (isset($_GET['filter_my']) && $_GET['filter_my'] == '1') ? 'checked' : '' ?> class="sr-only peer" onchange="this.form.submit()">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E7EED]"></div>
                        </label>
                    </div>

                    <!-- Input Busca Rápida de Culto -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Equipe ou Tipo de Culto</label>
                        <div class="relative">
                            <input type="text" name="filter_team" value="<?= htmlspecialchars($_GET['filter_team'] ?? '') ?>" placeholder="Ex: Domingo, Celebração..." class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs font-semibold text-[#1A1B1F] placeholder-gray-400 outline-none transition-all">
                            <span class="material-symbols-outlined text-[18px] text-gray-400 absolute left-3.5 top-3">search</span>
                        </div>
                    </div>

                    <!-- Filtro por Membro -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Membro Escalado</label>
                        <select name="filter_member" onchange="this.form.submit()" class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3 px-3.5 text-xs font-semibold text-[#1A1B1F] outline-none transition-all cursor-pointer">
                            <option value="">Todos os voluntários</option>
                            <?php
                            $users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $u) {
                                $selected = (isset($_GET['filter_member']) && $_GET['filter_member'] == $u['id']) ? 'selected' : '';
                                echo "<option value='{$u['id']}' $selected>" . htmlspecialchars($u['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filtro por Música -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Música no Repertório</label>
                        <select name="filter_song" onchange="this.form.submit()" class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3 px-3.5 text-xs font-semibold text-[#1A1B1F] outline-none transition-all cursor-pointer">
                            <option value="">Todas as músicas</option>
                            <?php
                            $allSongs = $pdo->query("SELECT id, title FROM songs ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($allSongs as $s) {
                                $selected = (isset($_GET['filter_song']) && $_GET['filter_song'] == $s['id']) ? 'selected' : '';
                                echo "<option value='{$s['id']}' $selected>" . htmlspecialchars($s['title']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Botão Submit/Limpar -->
                    <div class="flex gap-2.5 pt-2">
                        <button type="submit" class="flex-grow bg-gray-900 hover:bg-black active:scale-95 text-white py-3 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">done</span> Aplicar Filtros
                        </button>
                        <?php if ($activeFiltersCount > 0): ?>
                            <a href="escala.php?view=<?= $viewMode ?>&tab=<?= $tab ?>" class="bg-gray-100 hover:bg-gray-200 active:scale-95 text-gray-600 px-3.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center" title="Limpar Filtros">
                                <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Botão de Filtro Rápido (Mobile) que abre Bottom Sheet -->
            <div class="lg:hidden flex gap-3">
                <button type="button" onclick="openMobileFilters()" class="flex-grow bg-white border border-[#EDEDED] rounded-2xl py-3.5 px-4 shadow-sm font-bold text-xs text-[#1A1B1F] flex items-center justify-center gap-2 active:scale-98 transition-all">
                    <span class="material-symbols-outlined text-[#2E7EED]">filter_alt</span>
                    <span>Filtros e Parâmetros</span>
                    <?php if ($activeFiltersCount > 0): ?>
                        <span class="bg-[#2E7EED] text-white text-[9px] w-4.5 h-4.5 rounded-full flex items-center justify-center font-bold"><?= $activeFiltersCount ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Coluna de Listagem das Escalas -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Barra Superior de Controle e Abas -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white border border-[#EDEDED] rounded-3xl p-3 shadow-sm">
                <!-- Abas Deslizantes -->
                <div class="flex bg-gray-50 border border-gray-100 rounded-2xl p-1.5 relative w-full sm:w-auto">
                    <a href="?tab=next&view=<?= $viewMode ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['filter_my', 'filter_member', 'filter_song', 'filter_team']))) ?>" class="flex-1 text-center py-2.5 px-6 rounded-xl font-bold text-xs tracking-wide transition-all duration-200 whitespace-nowrap <?= $tab === 'next' ? 'bg-white text-[#2E7EED] shadow-sm' : 'text-gray-500 hover:text-gray-800' ?>">
                        Próximas Escalas
                    </a>
                    <a href="?tab=history&view=<?= $viewMode ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['filter_my', 'filter_member', 'filter_song', 'filter_team']))) ?>" class="flex-1 text-center py-2.5 px-6 rounded-xl font-bold text-xs tracking-wide transition-all duration-200 whitespace-nowrap <?= $tab === 'history' ? 'bg-white text-[#2E7EED] shadow-sm' : 'text-gray-500 hover:text-gray-800' ?>">
                        Histórico
                    </a>
                </div>

                <!-- Modos de Exibição -->
                <div class="flex items-center gap-1.5 self-end sm:self-auto">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mr-2 hidden sm:inline">Exibição</span>
                    <a href="?view=timeline&tab=<?= $tab ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['filter_my', 'filter_member', 'filter_song', 'filter_team']))) ?>" class="p-2.5 rounded-xl border transition-all <?= $viewMode === 'timeline' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/20 text-[#2E7EED]' : 'bg-white border-[#EDEDED] text-gray-400 hover:text-gray-700' ?>" title="Linha do Tempo">
                        <span class="material-symbols-outlined text-[20px] block">format_list_bulleted</span>
                    </a>
                    <a href="?view=list&tab=<?= $tab ?>&<?= http_build_query(array_intersect_key($_GET, array_flip(['filter_my', 'filter_member', 'filter_song', 'filter_team']))) ?>" class="p-2.5 rounded-xl border transition-all <?= $viewMode === 'list' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/20 text-[#2E7EED]' : 'bg-white border-[#EDEDED] text-gray-400 hover:text-gray-700' ?>" title="Lista Compacta">
                        <span class="material-symbols-outlined text-[20px] block">view_headline</span>
                    </a>
                </div>
            </div>

            <!-- Envelope do Form de Exclusão de Lote -->
            <form id="deleteForm" method="POST" action="" class="space-y-6 relative">
                <input type="hidden" name="delete_schedules" value="1">
                <input type="hidden" name="current_tab" value="<?= htmlspecialchars($tab) ?>">
                <input type="hidden" name="current_view" value="<?= htmlspecialchars($viewMode) ?>">

                <!-- Caso não existam escalas -->
                <?php if (empty($schedules)): ?>
                    <div class="bg-white border border-[#EDEDED] rounded-3xl p-12 text-center shadow-sm flex flex-col items-center max-w-md mx-auto">
                        <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 mb-4">
                            <span class="material-symbols-outlined text-3xl">calendar_today</span>
                        </div>
                        <h4 class="text-lg font-bold text-[#1A1B1F] mb-1">Nenhuma escala encontrada</h4>
                        <p class="text-xs text-gray-400 mb-6">A busca dinâmica não retornou escalas cadastradas com os filtros atuais.</p>
                        <?php if ($activeFiltersCount > 0): ?>
                            <a href="escala.php?view=<?= $viewMode ?>&tab=<?= $tab ?>" class="bg-[#2E7EED] text-white hover:bg-[#1872e0] px-5 py-2.5 rounded-xl font-bold text-xs tracking-wide transition-all shadow-md shadow-[#2E7EED]/20">
                                Limpar Todos os Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>

                    <!-- MODO: TIMELINE -->
                    <?php if ($viewMode === 'timeline'): ?>
                        <div class="space-y-6 relative pl-0 md:pl-8">
                            <!-- Linha de Timeline centralizada -->
                            <div class="hidden md:block absolute left-3.5 top-8 bottom-8 w-0.5 bg-gray-100"></div>

                            <?php 
                            $delay = 0;
                            foreach ($schedules as $schedule):
                                $date = new DateTime($schedule['event_date']);
                                $isToday = $date->format('Y-m-d') === date('Y-m-d');
                                $themeColor = getEventThemeColor($schedule['event_type']);
                                $participants = $participantsMap[$schedule['id']] ?? [];
                                $songCount = $songCountsMap[$schedule['id']] ?? 0;
                                $delay += 30;
                            ?>
                                <div class="group bg-white border border-[#EDEDED] rounded-3xl p-6 relative flex flex-col md:flex-row gap-6 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 relative z-10" style="animation-delay: <?= $delay ?>ms;">
                                    
                                    <!-- Efeito de hover na linha do tempo (Bolinha) -->
                                    <div class="hidden md:flex absolute -left-[23px] top-9 w-3 h-3 rounded-full border-2 border-white ring-2 ring-gray-100 group-hover:ring-[#2E7EED]/30 transition-all duration-300 z-20" style="background-color: <?= $themeColor ?>;"></div>
                                    
                                    <!-- Checkbox lateral invisível por padrão (Exclusão em Lote) -->
                                    <div class="schedule-checkbox-container hidden shrink-0 self-center transition-all duration-300 mr-2">
                                        <label class="flex items-center justify-center w-8 h-8 rounded-xl border-2 border-gray-200 hover:border-[#2E7EED] bg-gray-50 peer-checked:bg-[#2E7EED] cursor-pointer transition-all relative">
                                            <input type="checkbox" name="schedule_ids[]" value="<?= $schedule['id'] ?>" class="schedule-checkbox sr-only" onchange="updateDeleteBar()">
                                            <span class="material-symbols-outlined text-[18px] text-white absolute hidden select-icon">done</span>
                                        </label>
                                    </div>

                                    <!-- Coluna de Data Assimétrica Bento -->
                                    <div class="md:w-32 flex-shrink-0 flex flex-row md:flex-col items-center md:items-start justify-between md:justify-center p-4 rounded-2xl bg-gray-50 border border-gray-100 group-hover:bg-[#2E7EED]/5 group-hover:border-[#2E7EED]/20 transition-all duration-300 select-none">
                                        <div>
                                            <div class="text-[9px] font-extrabold uppercase tracking-wider mb-0.5" style="color: <?= $themeColor ?>;">
                                                <?= $isToday ? 'HOJE' : getEventMonthShort($date->format('n')) ?>
                                            </div>
                                            <div class="text-2xl font-black text-[#1A1B1F] leading-none">
                                                <?= $date->format('d') ?>
                                                <span class="text-xs font-bold text-gray-400"><?= substr(getEventMonthShort($date->format('n')), 0, 3) ?></span>
                                            </div>
                                        </div>
                                        <div class="text-xs font-bold text-gray-500 mt-2 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                                            <?= $date->format('H:i') ?>
                                        </div>
                                    </div>

                                    <!-- Informações Principais da Escala -->
                                    <div class="flex-grow flex flex-col justify-between schedule-card-link transition-all duration-300">
                                        <div class="flex justify-between items-start gap-4 mb-4">
                                            <div>
                                                <h3 class="text-lg font-bold text-[#1A1B1F] group-hover:text-[#2E7EED] transition-colors">
                                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                                </h3>
                                                <div class="flex flex-wrap items-center gap-2 mt-2 select-none">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-gray-500 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg">
                                                        <span class="material-symbols-outlined text-[14px] text-[#2E7EED]">music_note</span>
                                                        <?= $songCount ?> Músicas
                                                    </span>
                                                    
                                                    <?php if ($isToday): ?>
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                            Hoje
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Ação Detalhada -->
                                            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="text-[#2E7EED] font-bold text-xs hover:text-[#1872e0] transition-colors shrink-0 flex items-center gap-1 bg-[#2E7EED]/5 hover:bg-[#2E7EED]/10 px-3.5 py-2 rounded-xl">
                                                Gerenciar
                                                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                                            </a>
                                        </div>

                                        <!-- Render da Equipe Escalada Dinâmica -->
                                        <?php if (!empty($participants)): ?>
                                            <div class="mt-2 border-t border-gray-50 pt-3">
                                                <h4 class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 select-none">Voluntários</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($participants as $p): 
                                                        $avatarUrl = !empty($p['photo']) ? $p['photo'] : '';
                                                        if (!empty($avatarUrl)) {
                                                            if (strpos($avatarUrl, 'http') === false && strpos($avatarUrl, 'uploads') === false) {
                                                                $avatarUrl = '../uploads/' . $avatarUrl;
                                                            }
                                                        }
                                                        
                                                        // Gradiente sem roxo (MD5 Hash)
                                                        $hash = md5($p['name']);
                                                        $gradients = [
                                                            ['from-[#2E7EED]', 'to-[#60A5FA]'],
                                                            ['from-[#10B981]', 'to-[#34D399]'],
                                                            ['from-[#F59E0B]', 'to-[#FBBF24]'],
                                                            ['from-[#3B82F6]', 'to-[#10B981]'],
                                                        ];
                                                        $gradIdx = hexdec(substr($hash, 0, 1)) % count($gradients);
                                                        $grad = $gradients[$gradIdx];
                                                        $initial = strtoupper(substr($p['name'], 0, 1));
                                                    ?>
                                                        <div class="flex items-center gap-1.5 bg-[#F4F4F5] border border-[#EDEDED] rounded-xl py-0.5 pr-2.5 pl-0.5 shadow-sm shrink-0">
                                                            <div class="w-5.5 h-5.5 rounded-lg overflow-hidden border border-white flex items-center justify-center text-white text-[9px] font-black relative shrink-0">
                                                                <?php if ($avatarUrl): ?>
                                                                    <img src="<?= htmlspecialchars($avatarUrl) ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                    <div class="hidden absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> items-center justify-center"><?= $initial ?></div>
                                                                <?php else: ?>
                                                                    <div class="absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> flex items-center justify-center"><?= $initial ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span class="text-[10px] font-bold text-gray-700 whitespace-nowrap"><?= htmlspecialchars($p['name']) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-2 text-xs italic text-gray-400 border-t border-gray-50 pt-2">Nenhum voluntário escalado ainda.</div>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <!-- MODO: LISTA COMPACTA -->
                    <?php else: ?>
                        <div class="flex flex-col gap-3">
                            <?php 
                            $delay = 0;
                            foreach ($schedules as $schedule):
                                $date = new DateTime($schedule['event_date']);
                                $themeColor = getEventThemeColor($schedule['event_type']);
                                $songCount = $songCountsMap[$schedule['id']] ?? 0;
                                $dayNumber = $date->format('d');
                                $monthLabel = getEventMonthShort($date->format('n'));
                                $weekdayLabel = getEventWeekdayLabel($date->format('w'));
                                $delay += 20;
                            ?>
                                <div class="group bg-white border border-[#EDEDED] rounded-2xl p-4 flex items-center gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative" style="animation-delay: <?= $delay ?>ms;">
                                    
                                    <!-- Checkbox lateral invisível por padrão -->
                                    <div class="schedule-checkbox-container hidden shrink-0 transition-all duration-300 mr-1">
                                        <label class="flex items-center justify-center w-7 h-7 rounded-lg border-2 border-gray-200 hover:border-[#2E7EED] bg-gray-50 peer-checked:bg-[#2E7EED] cursor-pointer transition-all relative">
                                            <input type="checkbox" name="schedule_ids[]" value="<?= $schedule['id'] ?>" class="schedule-checkbox sr-only" onchange="updateDeleteBar()">
                                            <span class="material-symbols-outlined text-[16px] text-white absolute hidden select-icon">done</span>
                                        </label>
                                    </div>

                                    <!-- Barra Lateral de Tipo de Evento -->
                                    <div class="w-1.5 h-12 rounded-full shrink-0" style="background-color: <?= $themeColor ?>;"></div>

                                    <!-- Coluna de Data -->
                                    <div class="flex flex-col items-center justify-center text-center shrink-0 w-12 py-1 bg-gray-50 border border-gray-100 rounded-xl select-none">
                                        <span class="text-[9px] font-black text-gray-400 leading-none"><?= $monthLabel ?></span>
                                        <span class="text-lg font-black text-[#1A1B1F] leading-tight mt-0.5"><?= $dayNumber ?></span>
                                    </div>

                                    <!-- Informações -->
                                    <div class="flex-grow min-w-0 schedule-card-link">
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider select-none"><?= $weekdayLabel ?> • <?= $date->format('H:i') ?></div>
                                        <h4 class="text-sm font-bold text-[#1A1B1F] truncate group-hover:text-[#2E7EED] transition-colors mt-0.5">
                                            <?= htmlspecialchars($schedule['event_type']) ?>
                                        </h4>
                                        <div class="text-[10px] font-bold text-gray-400 flex items-center gap-1 mt-1 select-none">
                                            <span class="material-symbols-outlined text-[12px] text-[#2E7EED]">music_note</span>
                                            <?= $songCount ?> músicas
                                        </div>
                                    </div>

                                    <!-- Chevron e Link -->
                                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="p-2 bg-gray-50 group-hover:bg-[#2E7EED]/10 text-gray-400 group-hover:text-[#2E7EED] rounded-xl transition-all shrink-0">
                                        <span class="material-symbols-outlined text-[18px] block">chevron_right</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </form>
        </div>

    </div>
</main>

<!-- Bottom Sheet (Modal Tátil) de Filtros Avançados para Mobile -->
<div id="mobileFiltersModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200] hidden items-end transition-all duration-300 opacity-0" onclick="closeMobileFilters()">
    <div class="bg-white rounded-t-[32px] w-full max-h-[85vh] overflow-y-auto p-6 space-y-6 shadow-2xl transition-transform duration-300 translate-y-full" onclick="event.stopPropagation()">
        <!-- Barra de deslize decorativa -->
        <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-2"></div>
        
        <div>
            <h3 class="text-xl font-black text-[#1A1B1F] flex items-center gap-2">
                <span class="material-symbols-outlined text-[#2E7EED] text-2xl">filter_alt</span>
                Filtrar Escalas
            </h3>
            <p class="text-xs text-gray-400 mt-1">Configure os filtros sob demanda abaixo</p>
        </div>

        <form method="GET" action="escala.php" class="space-y-6">
            <input type="hidden" name="view" value="<?= $viewMode ?>">
            <input type="hidden" name="tab" value="<?= $tab ?>">

            <!-- Toggle: Minhas Escalas -->
            <div class="p-4 bg-[#F4F4F5] border border-[#EDEDED] rounded-2xl flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-[#1A1B1F]">Apenas minhas escalas</span>
                    <span class="text-[10px] text-gray-400">Ver eventos onde estou escalado</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="filter_my" value="1" <?= (isset($_GET['filter_my']) && $_GET['filter_my'] == '1') ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E7EED]"></div>
                </label>
            </div>

            <!-- Busca Rápida -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Equipe ou Tipo de Culto</label>
                <div class="relative">
                    <input type="text" name="filter_team" value="<?= htmlspecialchars($_GET['filter_team'] ?? '') ?>" placeholder="Ex: Domingo, Ensaio..." class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3.5 pl-11 pr-4 text-xs font-semibold text-[#1A1B1F] outline-none">
                    <span class="material-symbols-outlined text-[20px] text-gray-400 absolute left-3.5 top-3">search</span>
                </div>
            </div>

            <!-- Membro -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Membro Escalado</label>
                <select name="filter_member" class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3.5 px-4 text-xs font-semibold text-[#1A1B1F] outline-none cursor-pointer">
                    <option value="">Todos os voluntários</option>
                    <?php
                    foreach ($users as $u) {
                        $selected = (isset($_GET['filter_member']) && $_GET['filter_member'] == $u['id']) ? 'selected' : '';
                        echo "<option value='{$u['id']}' $selected>" . htmlspecialchars($u['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Música -->
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Música no Repertório</label>
                <select name="filter_song" class="w-full bg-[#F4F4F5] border border-[#EDEDED] focus:border-[#2E7EED] focus:bg-white rounded-xl py-3.5 px-4 text-xs font-semibold text-[#1A1B1F] outline-none cursor-pointer">
                    <option value="">Todas as músicas</option>
                    <?php
                    foreach ($allSongs as $s) {
                        $selected = (isset($_GET['filter_song']) && $_GET['filter_song'] == $s['id']) ? 'selected' : '';
                        echo "<option value='{$s['id']}' $selected>" . htmlspecialchars($s['title']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Ações -->
            <div class="flex gap-3 pt-4">
                <a href="escala.php?view=<?= $viewMode ?>&tab=<?= $tab ?>" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-600 py-3.5 rounded-xl font-bold text-xs transition-all text-center flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">filter_alt_off</span> Limpar Filtros
                </a>
                <button type="submit" class="flex-1 bg-[#2E7EED] hover:bg-[#1872e0] text-white py-3.5 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-md shadow-[#2E7EED]/10">
                    <span class="material-symbols-outlined text-[16px]">done</span> Aplicar Filtros
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast / Barra de Exclusão Flutuante Tátil (Spring Effect) -->
<div id="deleteBar" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-zinc-900/95 backdrop-blur-md text-white px-6 py-4 rounded-3xl shadow-2xl border border-white/10 flex items-center justify-between gap-8 z-[150] transition-all duration-300 translate-y-32 scale-90 opacity-0 max-w-md w-[90%] pointer-events-none">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-red-500/20 border border-red-500/30 flex items-center justify-center text-red-400">
            <span class="material-symbols-outlined text-[18px]">delete</span>
        </div>
        <div class="flex flex-col">
            <span id="selectedCount" class="text-xs font-black text-white">0 selecionadas</span>
            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Exclusão em lote</span>
        </div>
    </div>
    <div class="flex items-center gap-2.5">
        <button type="button" onclick="cancelSelection()" class="bg-white/5 hover:bg-white/10 active:scale-95 text-gray-300 px-4 py-2 rounded-xl text-xs font-bold transition-all">
            Cancelar
        </button>
        <button type="button" onclick="confirmDelete()" class="bg-red-500 hover:bg-red-600 active:scale-95 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md shadow-red-500/20">
            Confirmar Exclusão
        </button>
    </div>
</div>

<script>
    // --- Controle do Mobile Bottom Sheet ---
    function openMobileFilters() {
        const modal = document.getElementById('mobileFiltersModal');
        const content = modal.querySelector('div');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modal.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        }, 10);
    }

    function closeMobileFilters() {
        const modal = document.getElementById('mobileFiltersModal');
        const content = modal.querySelector('div');
        modal.style.opacity = '0';
        content.style.transform = 'translateY(100%)';
        
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    // --- Controle do Modo de Seleção em Lote (Checkboxes e Animações) ---
    let selectionMode = false;

    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        const containers = document.querySelectorAll('.schedule-checkbox-container');
        const cards = document.querySelectorAll('.schedule-card-link');
        const btn = document.getElementById('btnSelectMode');

        if (selectionMode) {
            // Entrar no modo de seleção
            containers.forEach(el => {
                el.classList.remove('hidden');
                el.classList.add('flex', 'animate-fade-in');
            });
            
            // Alterar o botão superior
            btn.innerHTML = `<span class="material-symbols-outlined text-[18px]">close</span> <span>Cancelar Seleção</span>`;
            btn.classList.add('bg-red-500/10', 'border-red-500/20', 'text-red-500', 'hover:bg-red-500/20');
            btn.classList.remove('bg-white/5', 'border-white/10', 'text-white', 'hover:bg-white/10');
            
            // Modificar o comportamento do link nos cards para apenas alternar o checkbox
            cards.forEach(card => {
                // Prevenir clique e navegação de links filhos
                card.querySelectorAll('a').forEach(link => {
                    link.style.pointerEvents = 'none';
                });
                
                card.style.cursor = 'pointer';
                card.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const container = this.closest('.group, .group\\/list, .bg-white');
                    const cb = container.querySelector('.schedule-checkbox');
                    cb.checked = !cb.checked;
                    
                    // Simular mudança
                    const event = new Event('change');
                    cb.dispatchEvent(event);
                };
            });
        } else {
            // Sair do modo de seleção
            containers.forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('flex', 'animate-fade-in');
            });
            
            // Restaurar botão superior
            btn.innerHTML = `<span class="material-symbols-outlined text-[18px]">checklist</span> <span>Selecionar em Lote</span>`;
            btn.classList.remove('bg-red-500/10', 'border-red-500/20', 'text-red-500', 'hover:bg-red-500/20');
            btn.classList.add('bg-white/5', 'border-white/10', 'text-white', 'hover:bg-white/10');
            
            // Restaurar links e cliques originais
            cards.forEach(card => {
                card.querySelectorAll('a').forEach(link => {
                    link.style.pointerEvents = 'auto';
                });
                card.style.cursor = 'default';
                card.onclick = null;
            });
            
            // Desmarcar todos os checkboxes
            document.querySelectorAll('.schedule-checkbox').forEach(cb => {
                cb.checked = false;
                const label = cb.closest('label');
                label.classList.remove('bg-[#2E7EED]', 'border-[#2E7EED]');
                label.classList.add('bg-gray-50', 'border-gray-200');
                label.querySelector('.select-icon').classList.add('hidden');
            });
            
            updateDeleteBar();
        }
    }

    // Atualizar Barra e Visual do Checkbox
    function updateDeleteBar() {
        const checkboxes = document.querySelectorAll('.schedule-checkbox');
        let selectedCount = 0;
        
        checkboxes.forEach(cb => {
            const label = cb.closest('label');
            const icon = label.querySelector('.select-icon');
            
            if (cb.checked) {
                selectedCount++;
                label.classList.add('bg-[#2E7EED]', 'border-[#2E7EED]');
                label.classList.remove('bg-gray-50', 'border-gray-200');
                icon.classList.remove('hidden');
            } else {
                label.classList.remove('bg-[#2E7EED]', 'border-[#2E7EED]');
                label.classList.add('bg-gray-50', 'border-gray-200');
                icon.classList.add('hidden');
            }
        });

        const deleteBar = document.getElementById('deleteBar');
        const countSpan = document.getElementById('selectedCount');

        if (selectedCount > 0) {
            // Mostrar a barra com efeito elástico
            deleteBar.style.pointerEvents = 'auto';
            deleteBar.classList.remove('opacity-0', 'translate-y-32', 'scale-90');
            deleteBar.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            countSpan.textContent = `${selectedCount} selecionada${selectedCount > 1 ? 's' : ''}`;
        } else {
            // Ocultar a barra
            deleteBar.style.pointerEvents = 'none';
            deleteBar.classList.add('opacity-0', 'translate-y-32', 'scale-90');
            deleteBar.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        }
    }

    // Confirmar Exclusão
    function confirmDelete() {
        const count = document.querySelectorAll('.schedule-checkbox:checked').length;
        if (confirm(`Tem certeza absoluta de que deseja excluir ${count} escala${count > 1 ? 's' : ''} permanentemente? Esta ação não pode ser desfeita.`)) {
            document.getElementById('deleteForm').submit();
        }
    }

    // Cancelar Seleção
    function cancelSelection() {
        toggleSelectionMode();
    }
</script>

<?php renderAppFooter(); ?>
