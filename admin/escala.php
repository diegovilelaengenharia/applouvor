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
    background: var(--gradient-green); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Escalas</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <!-- Add Button -->
            <a href="escala_adicionar.php" class="ripple" style="
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
            <button onclick="toggleViewMenu()" class="ripple" style="
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
                position: relative;
            ">
                <i data-lucide="<?= $viewMode == 'calendar' ? 'calendar' : ($viewMode == 'list' ? 'list' : 'align-left') ?>" style="width: 20px;"></i>
                <!-- Dropdown Menu -->
                <div id="viewMenu" style="display: none; position: absolute; top: 100%; right: 0; background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; box-shadow: var(--shadow-lg); width: 200px; z-index: 100; margin-top: 8px; overflow: hidden;">
                    <a href="?view=timeline&tab=<?= $tab ?>" class="dropdown-item ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-primary); text-decoration: none;">
                        <i data-lucide="align-left" style="width: 18px; color: var(--text-secondary);"></i> Linha do Tempo
                    </a>
                    <a href="?view=list&tab=<?= $tab ?>" class="dropdown-item ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-primary); text-decoration: none;">
                        <i data-lucide="list" style="width: 18px; color: var(--text-secondary);"></i> Lista Compacta
                    </a>
                    <a href="?view=calendar" class="dropdown-item ripple" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-primary); text-decoration: none;">
                        <i data-lucide="calendar" style="width: 18px; color: var(--text-secondary);"></i> Calendário
                    </a>
                </div>
            </button>
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

    <!-- VIEW: CALENDAR -->
    <?php if ($viewMode === 'calendar'):
        $currentMonth = $_GET['month'] ?? date('n');
        $currentYear = $_GET['year'] ?? date('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
        $firstDayOfWeek = date('w', strtotime("$currentYear-$currentMonth-01"));

        // Map schedules by date
        $eventsByDate = [];
        foreach ($schedules as $s) {
            $eventsByDate[$s['event_date']][] = $s;
        }
    ?>
        <div class="calendar-header">
            <div>DOM</div>
            <div>SEG</div>
            <div>TER</div>
            <div>QUA</div>
            <div>QUI</div>
            <div>SEX</div>
            <div>SÁB</div>
        </div>
        <div class="calendar-grid">
            <!-- Empty slots before 1st day -->
            <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?><div class="calendar-day empty"></div><?php endfor; ?>

            <!-- Days -->
            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $isToday = $dateStr === date('Y-m-d');
                $hasEvents = isset($eventsByDate[$dateStr]);
            ?>
                <div class="calendar-day <?= $isToday ? 'today' : '' ?>" onclick="window.location.href='?view=list&tab=next'">
                    <div class="calendar-number"><?= $day ?></div>
                    <?php if ($hasEvents): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 2px;">
                            <?php foreach ($eventsByDate[$dateStr] as $evt): ?>
                                <div class="event-dot" title="<?= htmlspecialchars($evt['event_type']) ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <!-- VIEW: LIST -->
    <?php elseif ($viewMode === 'list'): ?>
        <div style="display: flex; flex-direction: column; background: var(--bg-tertiary); border-radius: 12px; overflow: hidden;">
            <?php foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
                // Status based on date
                $isPast = $date < new DateTime('today');
            ?>
                <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="list-row ripple" style="text-decoration: none; color: inherit;">
                    <div style="font-weight: 700; width: 40px; text-align: center; color: var(--text-secondary);">
                        <?= $date->format('d') ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($schedule['event_type']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= $date->format('d/m/Y') ?> • 19:00</div>
                    </div>
                    <div>
                        <i data-lucide="chevron-right" style="color: var(--text-muted); width: 16px;"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- VIEW: TIMELINE (DEFAULT) -->
    <?php else: ?>
        <div class="schedules-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php
            $count = 0;
            foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
                $dayNumber = $date->format('d');
                $monthShort = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
                $weekDay = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];
                $colorClass = stripos($schedule['event_type'], 'Domingo') !== false ? '#F59E0B' : (stripos($schedule['event_type'], 'Ensaio') !== false ? '#3B82F6' : 'var(--text-primary)');

                // Logic for hiding items > 5 (Applied to ALL tabs now)
                $isHidden = ($count >= 5);
                $displayStyle = $isHidden ? 'display: none;' : 'display: flex;';
                $extraClass = $isHidden ? 'hidden-item' : '';
            ?>
                <div class="schedule-card-wrapper <?= $extraClass ?>" style="position: relative; <?= $displayStyle ?>">
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="card-clean ripple schedule-card-link" style="padding: 0; display: flex; text-decoration: none; overflow: hidden; border: 1px solid var(--border-subtle); transition: all 0.2s; width: 100%;">
                        <!-- Coluna Data -->
                        <?php
                        $today = new DateTime('today');
                        $isUpcoming = $date >= $today;
                        // Se for passado, usa amarelo. Se for futuro/hoje, verde.
                        $borderColor = $isUpcoming ? '#10B981' : '#F59E0B';
                        $borderStyle = "border-left: 4px solid $borderColor;";
                        ?>
                        <div style="background: var(--bg-tertiary); min-width: 70px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; border-right: 1px solid var(--border-subtle); <?= $borderStyle ?>">
                            <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); line-height: 1;"><?= $dayNumber ?></span>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); margin-top: 4px;"><?= $monthShort ?></span>
                        </div>

                        <!-- Conteúdo -->
                        <div style="flex: 1; padding: 16px; display: flex; flex-direction: column; justify-content: center;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                <span style="font-size: 0.8rem; font-weight: 600; color: <?= $colorClass ?>; text-transform: uppercase;">
                                    <?= $weekDay ?> • 19:00
                                </span>
                            </div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                                <?= htmlspecialchars($schedule['event_type']) ?>
                            </h3>
                            <div style="display: flex; gap: 16px; margin-top: auto;">
                                <div style="display: flex; align-items: center; gap: 4px; color: var(--text-secondary); font-size: 0.8rem;">
                                    <i data-lucide="users" style="width: 14px; height: 14px;"></i>
                                    <span>Escala</span>
                                </div>
                            </div>
                        </div>

                        <!-- Chevron Icon -->
                        <div style="padding-right: 16px; display: flex; align-items: center; color: var(--text-muted);">
                            <i data-lucide="chevron-right"></i>
                        </div>
                    </a>
                </div>
            <?php
                $count++;
            endforeach;

            if ($count > 5):
                $btnText = ($tab === 'history') ? 'escalas anteriores' : 'escalas futuras';
            ?>
                <button onclick="showHiddenItems()" id="btnShowMore" class="ripple" style="
                    width: 100%; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    gap: 10px; 
                    padding: 16px; 
                    margin-top: 16px; 
                    background: var(--bg-secondary); 
                    border: 1px solid var(--border-subtle); 
                    border-radius: 16px; 
                    color: var(--text-primary); 
                    font-weight: 700; 
                    font-size: 0.95rem;
                    cursor: pointer; 
                    transition: all 0.2s;
                    box-shadow: var(--shadow-sm);
                ">
                    <span style="color: var(--primary-green);">Ver mais <?= $count - 5 ?> <?= $btnText ?></span>
                    <i data-lucide="chevron-down" style="width: 18px; color: var(--primary-green);"></i>
                </button>
                <script>
                    function showHiddenItems() {
                        document.querySelectorAll('.hidden-item').forEach(el => {
                            el.style.display = 'flex';
                        });
                        document.getElementById('btnShowMore').style.display = 'none';
                    }
                </script>
            <?php endif; ?>
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

            <button type="submit" class="btn-primary ripple" style="width: 100%; justify-content: center;">Aplicar Filtros</button>
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