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

<link rel="stylesheet" href="../assets/css/pages/escala.css?v=<?= time() ?>">



<!-- Hero Header -->
<div class="hero-header">
    <!-- Navigation Row -->
    <div class="hero-nav-row">
        <a href="index.php" class="btn-back ripple">
            <i data-lucide="arrow-left"></i> Voltar
        </a>
        
        <div class="hero-nav-actions">
            <?php renderGlobalNavButtons(); ?>
        </div>
    </div>

    <div class="hero-title-row">
        <div>
            <h1 class="hero-title">Escalas</h1>
            <p class="hero-subtitle">Louvor PIB Oliveira</p>
        </div>
        <div class="hero-actions">
            <!-- Add Button -->
            <a href="escala_adicionar.php" class="btn-hero-action btn-add ripple">
                <i data-lucide="plus"></i>
            </a>
            <!-- Filter Button -->
            <button onclick="openFilters()" class="btn-hero-action btn-filter ripple">
                <i data-lucide="filter"></i>
            </button>
            <!-- View Button Wrapper -->
            <div class="view-menu-wrapper">
                <button id="btnViewToggle" onclick="toggleViewMenu()" class="btn-hero-action btn-view ripple">
                    <i data-lucide="<?= $viewMode == 'calendar' ? 'calendar' : ($viewMode == 'list' ? 'list' : 'align-left') ?>"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="viewMenu" class="view-dropdown">
                    <a href="?view=timeline&tab=<?= $tab ?>" class="dropdown-item ripple">
                        <i data-lucide="align-left"></i> Linha do Tempo
                    </a>
                    <a href="?view=list&tab=<?= $tab ?>" class="dropdown-item ripple">
                        <i data-lucide="list"></i> Lista Compacta
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Tabs -->
    <div class="floating-tabs-wrapper">
        <div class="floating-tabs">
            <a href="?tab=next" class="tab-link <?= $tab == 'next' ? 'active' : '' ?>">
                Próximas
            </a>
            <a href="?tab=history" class="tab-link <?= $tab == 'history' ? 'active' : '' ?>">
                Anteriores
            </a>
        </div>
    </div>
</div>

<?php if (empty($schedules)): ?>
    <!-- Empty State -->
    <div>
        <div>
            <i data-lucide="calendar-off"></i>
        </div>
        <h3>
            Nenhuma escala encontrada
        </h3>
        <p>
            Tente ajustar os filtros ou adicione uma nova escala.
        </p>
    </div>
<?php else: ?>

    <!-- VIEW: LIST (COMPACT) -->
    <?php if ($viewMode === 'list'): ?>
        <div>
            <?php foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
                $monthShort = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
                $dayNumber = $date->format('d');
                $weekDay = ['DOMINGO', 'SEGUNDA', 'TERÇA', 'QUARTA', 'QUINTA', 'SEXTA', 'SÁBADO'][$date->format('w')];
            ?>
                <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="compact-card ripple">
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
                            <i data-lucide="users"></i> Escala
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
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="event-card ripple">

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
                                <div class="avatar-small"></div>
                                <div class="avatar-small"></div>
                                <div class="avatar-small"></div>
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

            <div>
                <!-- Toggle: Apenas que eu participo -->
                <?php
                $isMyFilterActive = isset($_GET['filter_my']) && $_GET['filter_my'] == '1';
                $rowStyle = $isMyFilterActive ? 'background: var(--yellow-100); border: 1px solid var(--yellow-500); color: #92400E;' : 'background: var(--bg-tertiary); border: 1px solid transparent;';
                ?>
                <div id="filterMyRow">
                    <span>Apenas que eu participo</span>
                    <label class="switch">
                        <input type="checkbox" name="filter_my" value="1" <?= $isMyFilterActive ? 'checked' : '' ?> onchange="toggleMyRowStyle(this)">
                        <span class="slider round"></span>
                    </label>
                </div>

                <!-- Select Membro -->
                <div>
                    <label>Membro</label>
                    <select name="filter_member" class="form-input">
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
                    <label>Música</label>
                    <select name="filter_song" class="form-input">
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

            <button type="submit" class="btn-action-save ripple">Aplicar Filtros</button>
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
            row.style.background = 'var(--yellow-100)';
            row.style.border = '1px solid var(--yellow-500)';
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
    <button type="button" onclick="confirmDelete()">
        Excluir
    </button>
    <button type="button" onclick="cancelSelection()">
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