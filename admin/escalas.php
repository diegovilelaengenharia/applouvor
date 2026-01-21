<?php
// admin/escalas.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// --- Lógica de Filtros ---
$filterMine = isset($_GET['mine']) && $_GET['mine'] == '1';
$filterType = $_GET['type'] ?? '';

// ID do usuário logado (Assumindo sessão ou hardcoded 1 para dev se não tiver sessão ainda)
// Na prática deve vir de $_SESSION['user_id']
$loggedUserId = $_SESSION['user_id'] ?? 1;

// Construção da Query FUTURA
$sqlFuture = "SELECT DISTINCT s.* FROM schedules s ";
if ($filterMine) {
    $sqlFuture .= " JOIN schedule_users su ON su.schedule_id = s.id ";
}
$sqlFuture .= " WHERE s.event_date >= CURDATE() ";

if ($filterMine) {
    $sqlFuture .= " AND su.user_id = :userId ";
}
if (!empty($filterType)) {
    $sqlFuture .= " AND s.event_type = :eventType ";
}
$sqlFuture .= " ORDER BY s.event_date ASC";

// Construção da Query PASSADA
$sqlPast = "SELECT DISTINCT s.* FROM schedules s ";
if ($filterMine) {
    $sqlPast .= " JOIN schedule_users su ON su.schedule_id = s.id ";
}
$sqlPast .= " WHERE s.event_date < CURDATE() ";

if ($filterMine) {
    $sqlPast .= " AND su.user_id = :userId ";
}
if (!empty($filterType)) {
    $sqlPast .= " AND s.event_type = :eventType ";
}
$sqlPast .= " ORDER BY s.event_date DESC LIMIT 20";


try {
    // Executar Futuras
    $stmtFuture = $pdo->prepare($sqlFuture);
    if ($filterMine) $stmtFuture->bindValue(':userId', $loggedUserId);
    if (!empty($filterType)) $stmtFuture->bindValue(':eventType', $filterType);
    $stmtFuture->execute();
    $futureSchedules = $stmtFuture->fetchAll(PDO::FETCH_ASSOC);

    // Executar Passadas
    $stmtPast = $pdo->prepare($sqlPast);
    if ($filterMine) $stmtPast->bindValue(':userId', $loggedUserId);
    if (!empty($filterType)) $stmtPast->bindValue(':eventType', $filterType);
    $stmtPast->execute();
    $pastSchedules = $stmtPast->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

// Contar filtros ativos para Badge
$activeFilters = 0;
if ($filterMine) $activeFilters++;
if (!empty($filterType)) $activeFilters++;

renderAppHeader('Escalas');
?>

<!-- Header Clean com Botão Filtro -->
<header style="
    background: white; 
    padding: 20px 24px; 
    border-bottom: 1px solid #e2e8f0; 
    margin: -16px -16px 24px -16px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 20;
">
    <!-- Placeholder esquerda para balancear -->
    <div style="width: 40px;"></div>

    <div style="text-align: center;">
        <h1 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Escalas</h1>
        <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Louvor PIB Oliveira</p>
    </div>

    <!-- Botão Filtro -->
    <button onclick="openSheet('filterSheet')" class="ripple" style="
        width: 40px; height: 40px; 
        background: <?= $activeFilters > 0 ? '#dcfce7' : 'transparent' ?>; 
        border: <?= $activeFilters > 0 ? '1px solid #166534' : 'none' ?>; 
        color: <?= $activeFilters > 0 ? '#166534' : '#64748b' ?>; 
        border-radius: 50%; 
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        position: relative;
    ">
        <i data-lucide="filter" style="width: 20px;"></i>
        <?php if ($activeFilters > 0): ?>
            <span style="
                position: absolute; top: -2px; right: -2px; 
                background: #166534; color: white; 
                font-size: 0.7rem; font-weight: 700; 
                width: 18px; height: 18px; border-radius: 50%; 
                display: flex; align-items: center; justify-content: center;
                border: 2px solid white;
            "><?= $activeFilters ?></span>
        <?php endif; ?>
    </button>
</header>

<!-- Controles Principais: Tabs + View Toggles -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px;">

    <!-- Central Tabs -->
    <div style="display: flex; justify-content: center; gap: 8px;">
        <button id="btn-future" onclick="switchTab('future')" class="ripple" style="
            background: #dcfce7; 
            color: #166534; 
            border: none; 
            padding: 8px 32px; 
            border-radius: 20px; 
            font-weight: 700; 
            font-size: 0.9rem;
            box-shadow: 0 2px 6px rgba(22, 101, 52, 0.1);
            cursor: pointer;
            transition: all 0.2s;
        ">Próximas</button>
        <button id="btn-past" onclick="switchTab('past')" class="ripple" style="
            background: transparent; 
            color: #64748b; 
            border: none; 
            padding: 8px 32px; 
            border-radius: 20px; 
            font-weight: 600; 
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        ">Anteriores</button>
    </div>

    <!-- View Toggles (Alinhado à direita, visível apenas na Tab Future) -->
    <div id="view-controls" style="display: flex; justify-content: flex-end; width: 100%;">
        <div style="background: #e2e8f0; padding: 4px; border-radius: 10px; display: flex; gap: 4px;">
            <button onclick="switchView('cards')" id="btn-view-cards" title="Visualização Cards" style="
                border: none; 
                background: white; 
                color: #0f172a; 
                width: 32px; height: 32px; 
                border-radius: 8px; 
                cursor: pointer; 
                display: flex; align-items: center; justify-content: center;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
                transition: all 0.2s;
            ">
                <i data-lucide="layout-grid" style="width: 18px;"></i>
            </button>
            <button onclick="switchView('compact')" id="btn-view-compact" title="Visualização Lista Compacta" style="
                border: none; 
                background: transparent; 
                color: #64748b; 
                width: 32px; height: 32px; 
                border-radius: 8px; 
                cursor: pointer; 
                display: flex; align-items: center; justify-content: center;
                transition: all 0.2s;
            ">
                <i data-lucide="list" style="width: 18px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Container Principal -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- TAB: FUTURAS -->
    <div id="tab-future">
        <?php if (empty($futureSchedules)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="
                    background: #f1f5f9; 
                    width: 120px; height: 120px; 
                    border-radius: 50%; 
                    margin: 0 auto 24px auto; 
                    display: flex; align-items: center; justify-content: center;
                ">
                    <i data-lucide="filter-x" style="width: 48px; height: 48px; color: #94a3b8;"></i>
                </div>
                <h3 style="color: #334155; margin-bottom: 8px;">Nenhuma escala encontrada.</h3>
                <p style="color: #64748b; font-size: 0.9rem;">Tente remover os filtros ou cadastrar uma nova.</p>
                <?php if ($activeFilters > 0): ?>
                    <a href="escalas.php" style="display: inline-block; margin-top: 16px; color: #166534; font-weight: 600; text-decoration: none;">Limpar Filtros</a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <!-- LISTA CARDS (Padrão) -->
            <div id="list-cards" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Contagens
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $teamCount = $stmtCount->fetchColumn();

                    $stmtSongs = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                    $stmtSongs->execute([$schedule['id']]);
                    $songCount = $stmtSongs->fetchColumn();
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="display: block; text-decoration: none; color: inherit;">
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">

                            <!-- Data Box -->
                            <div style="text-align: center; min-width: 60px;">
                                <div style="font-size: 1.5rem; font-weight: 800; color: #334155; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px;"><?= strtoupper($date->format('M')) ?></div>
                            </div>

                            <!-- Divider -->
                            <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>

                            <!-- Info -->
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                    <?php if ($isToday): ?>
                                        <span style="background: #be123c; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 700;">HOJE</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 12px; color: #64748b; font-size: 0.85rem;">
                                    <span><?= $songCount ?> Músicas</span>
                                    <span>•</span>
                                    <span><?= $teamCount ?> Membros</span>
                                </div>
                            </div>

                            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- LISTA COMPACTA (Oculta por padrão) -->
            <div id="list-compact" style="display: none; flex-direction: column; margin-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Contagens Compactas
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $teamCount = $stmtCount->fetchColumn();
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="
                    display: flex; align-items: center; gap: 16px; 
                    padding: 16px; 
                    background: white; 
                    border-bottom: 1px solid #e2e8f0;
                    text-decoration: none; color: inherit;
                ">
                        <!-- Data Small -->
                        <div style="text-align: center; width: 40px; flex-shrink: 0;">
                            <div style="font-size: 1.1rem; font-weight: 700; color: #475569;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.7rem; font-weight: 600; color: #94a3b8; text-transform: uppercase;"><?= strtoupper($date->format('M')) ?></div>
                        </div>

                        <!-- Linha Vertical -->
                        <div style="width: 2px; height: 32px; background: <?= $isToday ? '#166534' : '#e2e8f0' ?>; border-radius: 2px;"></div>

                        <!-- Conteúdo Minimalista -->
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                                <?= htmlspecialchars($schedule['event_type']) ?>
                                <?php if ($isToday): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">HOJE</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #64748b;">
                                <?= ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')] ?> • <?= $teamCount ?> escalados
                            </div>
                        </div>

                        <i data-lucide="chevron-right" style="color: #cbd5e1; width: 16px;"></i>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- TAB: ANTERIORES -->
    <div id="tab-past" style="display: none;">
        <?php if (empty($pastSchedules)): ?>
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i data-lucide="history" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Nenhum histórico encontrado.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 100px;">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="display: block; text-decoration: none; color: inherit;">
                        <div style="
                        background: #f8fafc; 
                        border-radius: 12px; 
                        padding: 16px; 
                        border: 1px solid #e2e8f0; 
                        display: flex; 
                        align-items: center; 
                        gap: 16px;
                    ">
                            <!-- Data Box Small -->
                            <div style="text-align: center; min-width: 50px;">
                                <div style="font-size: 1.1rem; font-weight: 700; color: #64748b; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.65rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-top: 2px;"><?= strtoupper($date->format('M')) ?></div>
                            </div>

                            <!-- Divider -->
                            <div style="width: 1px; height: 32px; background: #e2e8f0;"></div>

                            <!-- Info -->
                            <div style="flex: 1;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #475569;"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                <div style="font-size: 0.75rem; color: #94a3b8;">Realizado</div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Action Button -->
<a href="escala_adicionar.php" class="ripple" style="
    position: fixed;
    bottom: 80px; 
    right: 24px;
    background: #166534; 
    color: white;
    padding: 12px 24px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 101, 52, 0.3);
    text-decoration: none;
    font-weight: 600;
    z-index: 50;
    transition: transform 0.2s;
" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
    <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
    <span>Adicionar</span>
</a>

<!-- Bottom Sheet Filtros -->
<div id="filterSheet" class="bottom-sheet-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: flex-end; justify-content: center;">
    <div class="bottom-sheet-content" style="background: white; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0; padding: 24px; animation: slideUp 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Filtrar Escalas</h3>
            <button onclick="closeSheet('filterSheet')" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>

        <form method="GET" action="escalas.php">
            <!-- Toggle 'Onde participo' -->
            <label style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 16px; cursor: pointer;">
                <span style="font-weight: 600; color: #334155;">Apenas que eu participo</span>
                <div style="position: relative; width: 44px; height: 24px;">
                    <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;">
                    <div class="toggle-slider" style="
                        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; 
                        background-color: #cbd5e1; transition: .4s; border-radius: 34px;
                    "></div>
                    <div class="toggle-knob" style="
                        position: absolute; content: ''; height: 18px; width: 18px; 
                        left: 3px; bottom: 3px; background-color: white; 
                        transition: .4s; border-radius: 50%;
                    "></div>
                </div>
            </label>

            <!-- Select Tipo -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #64748b; font-size: 0.9rem;">Tipo de Evento</label>
                <select name="type" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; color: #334155; font-size: 1rem; outline: none;">
                    <option value="">Todos</option>
                    <option value="Culto Domingo a Noite" <?= $filterType == 'Culto Domingo a Noite' ? 'selected' : '' ?>>Culto Domingo a Noite</option>
                    <option value="Culto Tema Especial" <?= $filterType == 'Culto Tema Especial' ? 'selected' : '' ?>>Culto Tema Especial</option>
                    <option value="Ensaio" <?= $filterType == 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                    <option value="Outro" <?= $filterType == 'Outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="escalas.php" style="
                    flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; 
                    text-align: center; text-decoration: none; color: #64748b; font-weight: 600;
                ">Limpar</a>
                <button type="submit" style="
                    flex: 2; padding: 12px; border: none; border-radius: 12px; 
                    background: #166534; color: white; font-weight: 700; cursor: pointer;
                ">Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>

<style>
    @media (min-width: 1025px) {
        a[href="escala_adicionar.php"] {
            bottom: 32px;
        }
    }

    /* Toggle CSS */
    input:checked+.toggle-slider {
        background-color: #166534;
    }

    input:checked+.toggle-slider+.toggle-knob {
        transform: translateX(20px);
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }
</style>

<script>
    function switchTab(tab) {
        document.getElementById('tab-future').style.display = tab === 'future' ? 'block' : 'none';
        document.getElementById('tab-past').style.display = tab === 'past' ? 'block' : 'none';

        const viewControls = document.getElementById('view-controls');
        if (viewControls) viewControls.style.visibility = tab === 'future' ? 'visible' : 'hidden';

        // Atualizar Botões de Tab
        const btnFuture = document.getElementById('btn-future');
        const btnPast = document.getElementById('btn-past');
        const activeTabStyle = "background: #dcfce7; color: #166534; box-shadow: 0 2px 6px rgba(22, 101, 52, 0.1);";
        const inactiveTabStyle = "background: transparent; color: #64748b; box-shadow: none;";

        if (tab === 'future') {
            btnFuture.style.cssText += activeTabStyle;
            btnPast.style.cssText += inactiveTabStyle;
        } else {
            btnFuture.style.cssText += inactiveTabStyle;
            btnPast.style.cssText += activeTabStyle;
        }
    }

    function switchView(view) {
        const listCards = document.getElementById('list-cards');
        const listCompact = document.getElementById('list-compact');
        const btnCards = document.getElementById('btn-view-cards');
        const btnCompact = document.getElementById('btn-view-compact');
        const activeViewStyle = "border: none; background: white; color: #0f172a; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.1); transition: all 0.2s;";
        const inactiveViewStyle = "border: none; background: transparent; color: #64748b; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;";

        if (view === 'cards') {
            if (listCards) listCards.style.display = 'flex';
            if (listCompact) listCompact.style.display = 'none';
            btnCards.setAttribute('style', activeViewStyle);
            btnCompact.setAttribute('style', inactiveViewStyle);
        } else {
            if (listCards) listCards.style.display = 'none';
            if (listCompact) listCompact.style.display = 'flex';
            btnCards.setAttribute('style', inactiveViewStyle);
            btnCompact.setAttribute('style', activeViewStyle);
        }
    }

    function openSheet(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeSheet(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php renderAppFooter(); ?>