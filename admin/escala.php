<?php
// admin/escala.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Processar exclusão em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedules'])) {
    if (!empty($_POST['schedule_ids'])) {
        $placeholders = str_repeat('?,', count($_POST['schedule_ids']) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id IN ($placeholders)");
        $stmt->execute($_POST['schedule_ids']);
    }
    header("Location: escala.php?tab=" . ($_POST['current_tab'] ?? 'next'));
    exit;
}

// Filtros e Visualização
$viewMode = $_GET['view'] ?? 'timeline'; // timeline, list, calendar
$infoMsg = '';

// Construir Query com Filtros
$sql = "SELECT DISTINCT s.* FROM schedules s";
$joins = [];
$wheres = ["1=1"];
$params = [];

// Filtro: Apenas que eu participo
if (isset($_GET['filter_my']) && $_GET['filter_my'] == '1') {
    $joins[] = "JOIN schedule_users su_my ON s.id = su_my.schedule_id";
    $wheres[] = "su_my.user_id = ?";
    $params[] = $_SESSION['user_id'];
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

// Abas (apenas se não estiver em modo calendário)
$tab = $_GET['tab'] ?? 'next';
if ($viewMode !== 'calendar') {
    if ($tab === 'history') {
        $wheres[] = "s.event_date < CURDATE()";
        $orderBy = "s.event_date DESC";
    } else {
        $wheres[] = "s.event_date >= CURDATE()";
        $orderBy = "s.event_date ASC";
    }
} else {
    // No calendário, pegamos o mês selecionado
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    $wheres[] = "MONTH(s.event_date) = ? AND YEAR(s.event_date) = ?";
    $params[] = $month;
    $params[] = $year;
    $orderBy = "s.event_date ASC";
}

// Montar SQL Final
$sql .= " " . implode(" ", array_unique($joins));
$sql .= " WHERE " . implode(" AND ", $wheres);
$sql .= " ORDER BY $orderBy";

if ($viewMode !== 'calendar') {
    // $sql .= " LIMIT 20"; // Opcional: paginar
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Escalas');
?>

<style>
    /* Estilos do Calendário */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-top: 10px;
    }

    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        text-align: center;
        font-weight: bold;
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-bottom: 8px;
    }

    .calendar-day {
        background: var(--bg-tertiary);
        border-radius: 8px;
        min-height: 80px;
        padding: 6px;
        position: relative;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .calendar-day.today {
        border-color: var(--primary-color);
        background: rgba(45, 122, 79, 0.05);
    }

    .calendar-day.empty {
        background: transparent;
        cursor: default;
    }

    .calendar-number {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .event-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--primary-color);
        display: inline-block;
        margin-right: 2px;
    }

    /* Estilos da Lista */
    .list-row {
        display: flex;
        align-items: center;
        padding: 12px;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-subtle);
        gap: 12px;
    }

    .list-row:last-child {
        border-bottom: none;
    }

    /* Checkbox original style */
    .schedule-checkbox {
        transform: scale(1.4);
        accent-color: var(--status-error);
        cursor: pointer;
    }

    .delete-bar {
        position: fixed;
        bottom: calc(var(--bottom-nav-height) + 20px);
        left: 50%;
        transform: translateX(-50%);
        background: var(--status-error);
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
        display: none;
        align-items: center;
        gap: 12px;
        z-index: 600;
        animation: slideUp 0.3s ease;
    }

    .delete-bar.active {
        display: flex;
    }

    @keyframes slideUp {
        from {
            transform: translateX(-50%) translateY(100px);
            opacity: 0;
        }

        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }
</style>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="index.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; align-items: center;">
            <?php renderGlobalNavButtons(); ?>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Escalas</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <!-- Add Button -->
            <a href="escala_adicionar.php" class="ripple" style="
                background: #D97706; 
                border: none; 
                width: 44px; 
                height: 44px; 
                border-radius: 12px; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                color: white;
                backdrop-filter: blur(4px);
                cursor: pointer;
                box-shadow: 0 1px 3px rgba(217, 119, 6, 0.15);
            ">
                <i data-lucide="plus" style="width: 20px;"></i>
            </a>
            <!-- Filter Button -->
            <button onclick="openFilters()" class="ripple" style="
                background: rgba(255,255,255,0.2); 
                border: none; 
                width: 44px; 
                height: 44px; 
                border-radius: 12px; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                color: white;
                backdrop-filter: blur(4px);
                cursor: pointer;
            ">
                <i data-lucide="filter" style="width: 20px;"></i>
            </button>
            <!-- View Button -->
            <!-- View Button Wrapper -->
            <div style="position: relative;">
                <button id="btnViewToggle" onclick="toggleViewMenu()" class="ripple" style="
                    background: rgba(255,255,255,0.2); 
                    border: none; 
                    width: 44px; 
                    height: 44px; 
                    border-radius: 12px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    color: white;
                    backdrop-filter: blur(4px);
                    cursor: pointer;
                ">
                    <i data-lucide="<?= $viewMode == 'calendar' ? 'calendar' : ($viewMode == 'list' ? 'list' : 'align-left') ?>" style="width: 20px;"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="viewMenu" style="display: none; position: absolute; top: 100%; right: 0; background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; box-shadow: var(--shadow-lg); width: 200px; z-index: 600; margin-top: 8px; overflow: hidden;">
                    <a href="?view=timeline&tab=<?= $tab ?>" class="dropdown-item ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-primary); text-decoration: none;">
                        <i data-lucide="align-left" style="width: 18px; color: var(--text-secondary);"></i> Linha do Tempo
                    </a>
                    <a href="?view=list&tab=<?= $tab ?>" class="dropdown-item ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-primary); text-decoration: none;">
                        <i data-lucide="list" style="width: 18px; color: var(--text-secondary);"></i> Lista Compacta
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Tabs -->
    <div style="position: absolute; bottom: -28px; left: 20px; right: 20px; z-index: 10;">
        <div style="
            background: var(--bg-secondary); 
            border-radius: 16px; 
            padding: 6px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            display: flex; 
            align-items: center;
            border: 1px solid rgba(0,0,0,0.05);
        ">
            <a href="?tab=next" class="ripple" style="flex: 1; text-align: center; padding: 12px; border-radius: 12px; font-size: 0.9rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?= $tab === 'next' ? 'background: var(--primary-green); color: white; box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
                Próximas
            </a>
            <a href="?tab=history" class="ripple" style="flex: 1; text-align: center; padding: 12px; border-radius: 12px; font-size: 0.9rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?= $tab === 'history' ? 'background: var(--primary-green); color: white; box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
                Anteriores
            </a>
        </div>
    </div>
</div>

<?php if (empty($schedules)): ?>
    <!-- Empty State -->
    <div style="text-align: center; padding: 60px 20px; display: flex; flex-direction: column; align-items: center;">
        <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <i data-lucide="calendar-off" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">
            Nenhuma escala encontrada
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 250px; margin-bottom: 24px;">
            Tente ajustar os filtros ou adicione uma nova escala.
        </p>
    </div>
<?php else: ?>

    <!-- VIEW: LIST (COMPACT) -->
    <?php if ($viewMode === 'list'): ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
                $monthShort = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
                $dayNumber = $date->format('d');
                $weekDay = ['DOMINGO', 'SEGUNDA', 'TERÇA', 'QUARTA', 'QUINTA', 'SEXTA', 'SÁBADO'][$date->format('w')];
            ?>
                <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="compact-card ripple" style="text-decoration: none;">
                    <!-- Left Strip handled by CSS or border -->

                    <!-- Date Column -->
                    <div class="compact-date-col">
                        <span class="compact-day"><?= $dayNumber ?></span>
                        <span class="compact-month"><?= $monthShort ?></span>
                    </div>

                    <!-- Divider -->
                    <div class="compact-divider"></div>

                    <!-- Content -->
                    <div class="compact-info-col">
                        <div class="compact-meta"><?= $weekDay ?> • 19:00</div>
                        <div class="compact-title"><?= htmlspecialchars($schedule['event_type']) ?></div>
                        <div class="compact-sub">
                            <i data-lucide="users" style="width: 14px; height: 14px;"></i> Escala
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="compact-arrow">
                        <i data-lucide="chevron-right"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- VIEW: TIMELINE (DEFAULT) -->
    <?php else: ?>
        <div class="timeline-wrapper">
            <?php foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
                $dayNumber = $date->format('d');
                $monthShort = ['OUT', 'NOV', 'DEZ', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET'][$date->format('n') - 1]; // Simplified month array for demo logic check index if needed
                // PHP DateTime 'n' is 1-12. Using standard approach:
                $ptMonths = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ'];
                $monthLabel = $ptMonths[$date->format('n')];

                $weekDay = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

                // Calculate time relative today
                $today = new DateTime('today');
                $interval = $today->diff($date);
                $timeAgo = $interval->format('%a') . ' dias';
                if ($date < $today) {
                    $timeLabel = $interval->days . ' dias atrás';
                } elseif ($date == $today) {
                    $timeLabel = 'Hoje';
                } else {
                    $timeLabel = 'Em ' . $interval->days . ' dias';
                }
            ?>
                <div class="timeline-item">
                    <!-- Date Column -->
                    <div class="timeline-date">
                        <div class="timeline-day"><?= $dayNumber ?></div>
                        <div class="timeline-month"><?= $monthLabel ?></div>
                    </div>

                    <!-- Card -->
                    <!-- Card -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="event-card ripple" style="text-decoration: none;">

                        <!-- 1. Header: Title -->
                        <div class="event-header">
                            <div class="event-title"><?= htmlspecialchars($schedule['event_type']) ?></div>
                        </div>

                        <!-- 2. Info: Date & Time -->
                        <div class="event-info-row">
                            <div class="info-item">
                                <i data-lucide="clock" class="icon-sm text-orange"></i>
                                <span class="<?= $date < $today ? 'text-muted' : 'text-highlight' ?>"><?= $timeLabel ?></span>
                            </div>
                            <div class="info-item">
                                <i data-lucide="calendar" class="icon-sm text-blue"></i>
                                <span><?= $weekDay ?> • 19:00</span>
                            </div>
                        </div>

                        <!-- 3. Tags -->
                        <div class="event-tags">
                            <span class="tag tag-green">Culto</span>
                            <span class="tag tag-purple">Louvor</span>
                        </div>

                        <!-- 4. Footer: Avatars & Stats -->
                        <div class="event-footer">
                            <div class="avatar-stack">
                                <!-- Placeholder Avatars for "Team Preview" -->
                                <div class="avatar-small" style="background-image: url('https://ui-avatars.com/api/?name=A&background=2563EB&color=fff');"></div>
                                <div class="avatar-small" style="background-image: url('https://ui-avatars.com/api/?name=B&background=059669&color=fff');"></div>
                                <div class="avatar-small" style="background-image: url('https://ui-avatars.com/api/?name=C&background=db2777&color=fff');"></div>
                            </div>

                            <div class="footer-right">
                                <div class="stat-pill" title="Músicas">
                                    <i data-lucide="music" class="icon-xs"></i> 5
                                </div>
                                <div class="stat-pill" title="Confirmados">
                                    <i data-lucide="thumbs-up" class="icon-xs"></i> 3/5
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<!-- MODAL FILTROS -->
<div id="filtersModal" class="bottom-sheet-overlay" onclick="closeAllSheets()">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()">
        <div class="sheet-header">Filtrar Escalas</div>

        <form action="" method="GET">
            <input type="hidden" name="view" value="<?= $viewMode ?>">
            <input type="hidden" name="tab" value="<?= $tab ?>">

            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                <!-- Toggle: Apenas que eu participo -->
                <?php
                $isMyFilterActive = isset($_GET['filter_my']) && $_GET['filter_my'] == '1';
                $rowStyle = $isMyFilterActive ? 'background: #FEF3C7; border: 1px solid #F59E0B; color: #92400E;' : 'background: var(--bg-tertiary); border: 1px solid transparent;';
                ?>
                <div id="filterMyRow" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 12px; transition: all 0.2s; <?= $rowStyle ?>">
                    <span style="font-weight: 600;">Apenas que eu participo</span>
                    <label class="switch">
                        <input type="checkbox" name="filter_my" value="1" <?= $isMyFilterActive ? 'checked' : '' ?> onchange="toggleMyRowStyle(this)">
                        <span class="slider round"></span>
                    </label>
                </div>

                <!-- Select Membro -->
                <div>
                    <label style="font-size: 0.9rem; margin-bottom: 4px; display: block; color: var(--text-secondary);">Membro</label>
                    <select name="filter_member" class="form-input" style="width: 100%;">
                        <option value="">Todos</option>
                        <?php
                        $users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users as $u) {
                            $sel = (isset($_GET['filter_member']) && $_GET['filter_member'] == $u['id']) ? 'selected' : '';
                            echo "<option value='{$u['id']}' $sel>{$u['name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Select Música -->
                <div>
                    <label style="font-size: 0.9rem; margin-bottom: 4px; display: block; color: var(--text-secondary);">Música</label>
                    <select name="filter_song" class="form-input" style="width: 100%;">
                        <option value="">Todas</option>
                        <?php
                        $allSongs = $pdo->query("SELECT id, title FROM songs ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($allSongs as $s) {
                            $sel = (isset($_GET['filter_song']) && $_GET['filter_song'] == $s['id']) ? 'selected' : '';
                            echo "<option value='{$s['id']}' $sel>{$s['title']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-action-save ripple" style="width: 100%; justify-content: center;">Aplicar Filtros</button>
        </form>
    </div>
</div>

<script>
    function toggleViewMenu() {
        const menu = document.getElementById('viewMenu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('viewMenu');
        const btn = document.querySelector('button[onclick="toggleViewMenu()"]');
        if (menu.style.display === 'block' && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    function openFilters() {
        closeAllSheets();
        document.getElementById('filtersModal').classList.add('active');
    }

    function toggleMyRowStyle(checkbox) {
        const row = document.getElementById('filterMyRow');
        if (checkbox.checked) {
            row.style.background = '#FEF3C7';
            row.style.border = '1px solid #F59E0B';
            row.style.color = '#92400E';
        } else {
            row.style.background = 'var(--bg-tertiary)';
            row.style.border = '1px solid transparent';
            row.style.color = 'inherit';
        }
    }

    function closeAllSheets() {
        document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
    }
</script>

<!-- Barra de Exclusão Flutuante -->
<div id="deleteBar" class="delete-bar">
    <span id="selectedCount">0 selecionadas</span>
    <button type="button" onclick="confirmDelete()" style="background: white; color: var(--status-error); border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; cursor: pointer;">
        Excluir
    </button>
    <button type="button" onclick="cancelSelection()" style="background: transparent; color: white; border: 1px solid white; padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer;">
        Cancelar
    </button>
</div>

<script>
    let selectionMode = false;

    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        const checkboxes = document.querySelectorAll('.schedule-checkbox-container');
        const links = document.querySelectorAll('.schedule-card-link');
        const btn = document.getElementById('btnSelectMode');

        if (selectionMode) {
            checkboxes.forEach(cb => cb.style.display = 'block');
            links.forEach(link => {
                link.style.pointerEvents = 'none';
                link.style.paddingLeft = '60px';
            });
            btn.style.background = 'var(--status-error)';
            btn.style.color = 'white';
        } else {
            checkboxes.forEach(cb => cb.style.display = 'none');
            links.forEach(link => {
                link.style.pointerEvents = 'auto';
                link.style.paddingLeft = '0';
            });
            btn.style.background = '';
            btn.style.color = '';
            document.querySelectorAll('.schedule-checkbox').forEach(cb => cb.checked = false);
            updateDeleteBar();
        }
    }

    function updateDeleteBar() {
        const checked = document.querySelectorAll('.schedule-checkbox:checked');
        const deleteBar = document.getElementById('deleteBar');
        const count = document.getElementById('selectedCount');

        if (checked.length > 0) {
            deleteBar.classList.add('active');
            count.textContent = `${checked.length} selecionada${checked.length > 1 ? 's' : ''}`;
        } else {
            deleteBar.classList.remove('active');
        }
    }

    function confirmDelete() {
        const count = document.querySelectorAll('.schedule-checkbox:checked').length;
        if (confirm(`Tem certeza que deseja excluir ${count} escala${count > 1 ? 's' : ''}?`)) {
            document.getElementById('deleteForm').submit();
        }
    }

    function cancelSelection() {
        toggleSelectionMode();
    }
</script>

<?php renderAppFooter(); ?>