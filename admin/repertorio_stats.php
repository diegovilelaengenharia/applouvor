<?php
// admin/repertorio_stats.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Estatísticas do Repertório');
renderPageHeader('Estatísticas do Repertório', 'Análise de Músicas');

// Filtros de período
$period = $_GET['period'] ?? 'all';
$customStart = $_GET['start_date'] ?? '';
$customEnd = $_GET['end_date'] ?? '';

// Definir datas baseado no período
$dateFilter = '';
$params = [];

if ($period === 'month') {
    $dateFilter = "AND ss.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($period === '3months') {
    $dateFilter = "AND ss.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($period === '6months') {
    $dateFilter = "AND ss.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($period === 'year') {
    $dateFilter = "AND ss.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
} elseif ($period === 'custom' && $customStart && $customEnd) {
    $dateFilter = "AND ss.created_at BETWEEN ? AND ?";
    $params = [$customStart, $customEnd];
}

// Buscar dados estatísticos
try {
    // Total de músicas
    $totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();

    // Músicas sem material completo
    $incompleteSongs = $pdo->query("
        SELECT COUNT(*) FROM songs 
        WHERE (chords IS NULL OR chords = '') OR (lyrics IS NULL OR lyrics = '')
    ")->fetchColumn();

    // Músicas adicionadas recentemente (últimos 30 dias)
    $recentSongs = $pdo->query("
        SELECT COUNT(*) FROM songs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();

    // Músicas mais tocadas (com filtro de período)
    $sql = "
        SELECT s.title, s.artist, COUNT(ss.song_id) as play_count
        FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        WHERE 1=1 $dateFilter
        GROUP BY s.id
        HAVING play_count > 0
        ORDER BY play_count DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mostPlayed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Músicas nunca tocadas
    $neverPlayed = $pdo->query("
        SELECT COUNT(*) FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        WHERE ss.song_id IS NULL
    ")->fetchColumn();

    // Músicas por tom
    $byTone = $pdo->query("
        SELECT tone, COUNT(*) as count
        FROM songs
        WHERE tone IS NOT NULL AND tone != ''
        GROUP BY tone
        ORDER BY count DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Músicas por categoria/tag
    $byCategory = $pdo->query("
        SELECT category, COUNT(*) as count
        FROM songs
        WHERE category IS NOT NULL AND category != ''
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Artistas mais tocados
    $topArtists = $pdo->query("
        SELECT s.artist, COUNT(ss.song_id) as play_count
        FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        WHERE s.artist IS NOT NULL AND s.artist != ''
        GROUP BY s.artist
        ORDER BY play_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // BPM médio
    $avgBpm = $pdo->query("
        SELECT ROUND(AVG(bpm)) as avg_bpm
        FROM songs
        WHERE bpm IS NOT NULL AND bpm > 0
    ")->fetchColumn();
} catch (Exception $e) {
    $totalSongs = 0;
    $incompleteSongs = 0;
    $recentSongs = 0;
    $mostPlayed = [];
    $neverPlayed = 0;
    $byTone = [];
    $byCategory = [];
    $topArtists = [];
    $avgBpm = 0;
}
?>

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

    .stat-hero {
        background: linear-gradient(135deg, var(--slate-500) 0%, var(--slate-700) 100%);
        color: white;
        padding: 24px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        text-align: center;
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

    .song-rank {
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
        background: linear-gradient(135deg, #fbbf24, var(--yellow-500));
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

    <!-- Hero Stats -->
    <div class="stat-hero">
        <div style="font-size: 3rem; font-weight: 800; margin-bottom: 8px;"><?= $totalSongs ?></div>
        <div style="font-size: 1.1rem; opacity: 0.9;">Músicas no Repertório</div>
    </div>

    <!-- KPIs Grid -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid var(--sage-500);">
            <div class="kpi-value" style="color: var(--sage-500);"><?= $totalSongs - $neverPlayed ?></div>
            <div class="kpi-label">Músicas Tocadas</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--yellow-500);">
            <div class="kpi-value" style="color: var(--yellow-500);"><?= $neverPlayed ?></div>
            <div class="kpi-label">Nunca Tocadas</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--rose-500);">
            <div class="kpi-value" style="color: var(--rose-500);"><?= $incompleteSongs ?></div>
            <div class="kpi-label">Sem Material</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--slate-500);">
            <div class="kpi-value" style="color: var(--slate-500);"><?= $recentSongs ?></div>
            <div class="kpi-label">Adicionadas (30d)</div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--lavender-600);">
            <div class="kpi-value" style="color: var(--lavender-600);"><?= $avgBpm ?: '--' ?></div>
            <div class="kpi-label">BPM Médio</div>
        </div>
    </div>

    <!-- Top 10 Mais Tocadas -->
    <div class="stat-card" style="margin-bottom: 24px;">
        <div class="stat-title">🔥 Top 10 Mais Tocadas <?= $period !== 'all' ? '(Período Selecionado)' : '' ?></div>

        <?php if (empty($mostPlayed)): ?>
            <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                <i data-lucide="music" style="width: 32px; height: 32px; opacity: 0.3; margin-bottom: 8px;"></i>
                <p style="margin: 0;">Nenhum dado disponível para o período selecionado</p>
            </div>
        <?php else: ?>
            <?php foreach ($mostPlayed as $index => $song): ?>
                <div class="song-rank">
                    <div class="rank-number <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">
                        <?= $index + 1 ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                            <?= htmlspecialchars($song['title']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            <?= htmlspecialchars($song['artist']) ?>
                        </div>
                    </div>
                    <div style="background: var(--sage-50); color: var(--sage-500); padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                        <?= $song['play_count'] ?>x
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Grid de Estatísticas -->
    <div class="stat-grid">

        <!-- Top Artistas -->
        <div class="stat-card">
            <div class="stat-title">🎤 Top 5 Artistas</div>
            <?php if (empty($topArtists)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach ($topArtists as $artist): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($artist['artist']) ?></span>
                        <span style="background: var(--sage-50); color: var(--sage-500); padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
                            <?= $artist['play_count'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Por Tom -->
        <div class="stat-card">
            <div class="stat-title">🎵 Distribuição por Tom</div>
            <?php if (empty($byTone)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach ($byTone as $tone): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($tone['tone']) ?></span>
                        <span style="background: var(--slate-50); color: var(--slate-600); padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
                            <?= $tone['count'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Por Categoria -->
        <div class="stat-card">
            <div class="stat-title">📂 Por Categoria</div>
            <?php if (empty($byCategory)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach ($byCategory as $cat): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($cat['category']) ?></span>
                        <span style="background: #fdf2f8; color: #db2777; padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
                            <?= $cat['count'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php renderAppFooter(); ?>