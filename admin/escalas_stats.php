<?php
// admin/escalas_stats.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Estatísticas de Escalas');
renderPageHeader('Estatísticas de Escalas', 'Análise de Participação');

// Filtros de período
$period = $_GET['period'] ?? 'all';
$customStart = $_GET['start_date'] ?? '';
$customEnd = $_GET['end_date'] ?? '';

// Definir datas baseado no período
$dateFilter = '';
$params = [];

if ($period === 'month') {
    $dateFilter = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
} elseif ($period === '3months') {
    $dateFilter = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
} elseif ($period === '6months') {
    $dateFilter = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
} elseif ($period === 'year') {
    $dateFilter = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
} elseif ($period === 'custom' && $customStart && $customEnd) {
    $dateFilter = "AND s.event_date BETWEEN ? AND ?";
    $params = [$customStart, $customEnd];
}

// Buscar dados estatísticos
try {
    // Total de escalas
    $sql = "SELECT COUNT(*) FROM schedules s WHERE 1=1 $dateFilter";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $totalEscalas = $stmt->fetchColumn();

    // Taxa de confirmação
    $sql = "
        SELECT 
            ROUND((COUNT(CASE WHEN su.confirmed = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) as taxa
        FROM schedule_users su
        JOIN schedules s ON su.schedule_id = s.id
        WHERE 1=1 $dateFilter
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $taxaConfirmacao = $stmt->fetchColumn() ?: 0;

    // Escalas por tipo
    $sql = "
        SELECT event_type, COUNT(*) as total
        FROM schedules s
        WHERE 1=1 $dateFilter
        GROUP BY event_type
        ORDER BY total DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $escalasPorTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ranking de participação por membro
    $sql = "
        SELECT u.name, u.instrument, COUNT(su.user_id) as participacoes
        FROM users u
        LEFT JOIN schedule_users su ON u.id = su.user_id
        LEFT JOIN schedules s ON su.schedule_id = s.id
        WHERE 1=1 $dateFilter
        GROUP BY u.id
        HAVING participacoes > 0
        ORDER BY participacoes DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rankingParticipacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Músicas mais usadas em escalas
    $sql = "
        SELECT so.title, so.artist, COUNT(ss.song_id) as vezes
        FROM songs so
        JOIN schedule_songs ss ON so.id = ss.song_id
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE 1=1 $dateFilter
        GROUP BY so.id
        ORDER BY vezes DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $musicasMaisUsadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Escalas pendentes de confirmação
    $sql = "
        SELECT COUNT(DISTINCT su.schedule_id) as pendentes
        FROM schedule_users su
        JOIN schedules s ON su.schedule_id = s.id
        WHERE su.confirmed = 0 AND s.event_date >= CURDATE()
    ";
    $stmt = $pdo->query($sql);
    $escalasPendentes = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalEscalas = 0;
    $taxaConfirmacao = 0;
    $escalasPorTipo = [];
    $rankingParticipacao = [];
    $musicasMaisUsadas = [];
    $escalasPendentes = 0;
}
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .stats-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 12px;
    }

    .filter-bar {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }

    .filter-tabs {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .filter-tab {
        padding: 6px 10px;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.75rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .filter-tab.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .custom-date-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px;
        margin-top: 8px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }

    .kpi-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px;
        box-shadow: var(--shadow-sm);
        text-align: center;
    }

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
        margin-bottom: 4px;
    }

    .kpi-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .stat-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px;
        box-shadow: var(--shadow-sm);
    }

    .stat-title {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .rank-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        background: var(--bg-body);
        border-radius: 8px;
        margin-bottom: 4px;
    }

    .rank-number {
        width: 24px;
        height: 24px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .rank-number.gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
    }

    .rank-number.silver {
        background: linear-gradient(135deg, #d1d5db, #9ca3af);
    }

    .rank-number.bronze {
        background: linear-gradient(135deg, #f97316, #ea580c);
    }

    @media (max-width: 768px) {
        .custom-date-inputs {
            grid-template-columns: 1fr;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="stats-container">

    <!-- Filtros de Período -->
    <div class="filter-bar">
        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="calendar" style="width: 18px;"></i>
            Período de Análise
        </div>

        <div class="filter-tabs">
            <a href="?period=all" class="filter-tab <?= $period === 'all' ? 'active' : '' ?>">Todo Período</a>
            <a href="?period=month" class="filter-tab <?= $period === 'month' ? 'active' : '' ?>">Último Mês</a>
            <a href="?period=3months" class="filter-tab <?= $period === '3months' ? 'active' : '' ?>">3 Meses</a>
            <a href="?period=6months" class="filter-tab <?= $period === '6months' ? 'active' : '' ?>">6 Meses</a>
            <a href="?period=year" class="filter-tab <?= $period === 'year' ? 'active' : '' ?>">1 Ano</a>
            <a href="#" onclick="document.getElementById('customDates').style.display='grid'; return false;" class="filter-tab <?= $period === 'custom' ? 'active' : '' ?>">Personalizado</a>
        </div>

        <form method="GET" id="customDates" class="custom-date-inputs" style="display: <?= $period === 'custom' ? 'grid' : 'none' ?>;">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="start_date" value="<?= htmlspecialchars($customStart) ?>" required
                style="padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
            <input type="date" name="end_date" value="<?= htmlspecialchars($customEnd) ?>" required
                style="padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
            <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                Aplicar
            </button>
        </form>
    </div>

    <!-- KPIs Grid -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid #8b5cf6;">
            <div class="kpi-value" style="color: #8b5cf6;"><?= $totalEscalas ?></div>
            <div class="kpi-label">Total de Escalas</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #10b981;">
            <div class="kpi-value" style="color: #10b981;"><?= $taxaConfirmacao ?>%</div>
            <div class="kpi-label">Taxa Confirmação</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
            <div class="kpi-value" style="color: #f59e0b;"><?= $escalasPendentes ?></div>
            <div class="kpi-label">Pendentes</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
            <div class="kpi-value" style="color: #3b82f6;"><?= count($escalasPorTipo) ?></div>
            <div class="kpi-label">Tipos de Culto</div>
        </div>
    </div>

    <!-- Ranking de Participação -->
    <div class="stat-card" style="margin-bottom: 20px;">
        <div class="stat-title">🏆 Top 10 Participação <?= $period !== 'all' ? '(Período Selecionado)' : '' ?></div>

        <?php if (empty($rankingParticipacao)): ?>
            <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                <i data-lucide="users" style="width: 32px; height: 32px; opacity: 0.3; margin-bottom: 8px;"></i>
                <p style="margin: 0;">Nenhum dado disponível para o período selecionado</p>
            </div>
        <?php else: ?>
            <?php foreach ($rankingParticipacao as $index => $membro): ?>
                <div class="rank-item">
                    <div class="rank-number <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">
                        <?= $index + 1 ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                            <?= htmlspecialchars($membro['name']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            <?= htmlspecialchars($membro['instrument'] ?: 'Membro') ?>
                        </div>
                    </div>
                    <div style="background: #ecfdf5; color: #047857; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                        <?= $membro['participacoes'] ?> escalas
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Grid de Estatísticas -->
    <div class="stat-grid">

        <!-- Músicas Mais Usadas -->
        <div class="stat-card">
            <div class="stat-title">🎵 Top Músicas em Escalas</div>
            <?php if (empty($musicasMaisUsadas)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach (array_slice($musicasMaisUsadas, 0, 5) as $musica): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($musica['title']) ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= htmlspecialchars($musica['artist']) ?>
                            </div>
                        </div>
                        <span style="background: #ecfdf5; color: #047857; padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; margin-left: 8px;">
                            <?= $musica['vezes'] ?>x
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Escalas por Tipo -->
        <div class="stat-card">
            <div class="stat-title">📊 Por Tipo de Culto</div>
            <?php if (empty($escalasPorTipo)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach ($escalasPorTipo as $tipo): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($tipo['event_type']) ?></span>
                        <span style="background: #eff6ff; color: #2563eb; padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
                            <?= $tipo['total'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php renderAppFooter(); ?>