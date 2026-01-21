<?php
// admin/repertorio_stats.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Estatísticas do Repertório');
renderPageHeader('Estatísticas do Repertório', 'Análise de Músicas');

// Buscar dados estatísticos
try {
    // Total de músicas
    $totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();

    // Músicas mais tocadas (baseado em quantas vezes aparece em escalas)
    $mostPlayed = $pdo->query("
        SELECT s.title, s.artist, COUNT(ss.song_id) as play_count
        FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        GROUP BY s.id
        ORDER BY play_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Músicas por tom
    $byTone = $pdo->query("
        SELECT tone, COUNT(*) as count
        FROM songs
        WHERE tone IS NOT NULL AND tone != ''
        GROUP BY tone
        ORDER BY count DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Músicas por categoria
    $byCategory = $pdo->query("
        SELECT category, COUNT(*) as count
        FROM songs
        WHERE category IS NOT NULL AND category != ''
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $totalSongs = 0;
    $mostPlayed = [];
    $byTone = [];
    $byCategory = [];
}
?>

<style>
    .stats-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 16px;
    }

    .stat-hero {
        background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        color: white;
        padding: 32px 24px;
        border-radius: 20px;
        margin-bottom: 24px;
        text-align: center;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow-sm);
    }

    .stat-title {
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .song-rank {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--bg-body);
        border-radius: 12px;
        margin-bottom: 8px;
    }

    .rank-number {
        width: 32px;
        height: 32px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.9rem;
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
</style>

<div class="stats-container">

    <!-- Hero Stats -->
    <div class="stat-hero">
        <div style="font-size: 3rem; font-weight: 800; margin-bottom: 8px;"><?= $totalSongs ?></div>
        <div style="font-size: 1.1rem; opacity: 0.9;">Músicas no Repertório</div>
    </div>

    <!-- Top 10 Mais Tocadas -->
    <div class="stat-card">
        <div class="stat-title">🔥 Top 10 Mais Tocadas</div>

        <?php if (empty($mostPlayed)): ?>
            <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                <i data-lucide="music" style="width: 32px; height: 32px; opacity: 0.3; margin-bottom: 8px;"></i>
                <p style="margin: 0;">Nenhum dado disponível</p>
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
                    <div style="background: #ecfdf5; color: #047857; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
                        <?= $song['play_count'] ?>x
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Grid de Estatísticas -->
    <div class="stat-grid">

        <!-- Por Tom -->
        <div class="stat-card">
            <div class="stat-title">🎵 Distribuição por Tom</div>
            <?php if (empty($byTone)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhum dado</p>
            <?php else: ?>
                <?php foreach ($byTone as $tone): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($tone['tone']) ?></span>
                        <span style="background: #eff6ff; color: #2563eb; padding: 2px 10px; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
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