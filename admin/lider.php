<?php
// admin/lider.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Painel do Líder');
renderPageHeader('Painel do Líder', 'Dashboard Executivo');

// --- QUERIES PARA MÉTRICAS ---
$today = date('Y-m-d');

// 1. Próxima Escala
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE event_date >= ? ORDER BY event_date ASC, event_time ASC LIMIT 1");
    $stmt->execute([$today]);
    $next_scale = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $next_scale = null;
}

// 2. Total de Músicas Ativas
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM songs");
    $total_songs = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_songs = 0;
}

// 3. Total de Membros Ativos
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_members = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_members = 0;
}

// 4. Avisos Ativos (não arquivados)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NULL");
    $total_avisos = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_avisos = 0;
}

// 5. Indisponibilidades Futuras
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_unavailability WHERE start_date >= ?");
    $stmt->execute([$today]);
    $absences_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $absences_count = 0;
}

// 6. Músicas sem cifra ou letra
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM songs WHERE (chords IS NULL OR chords = '') OR (lyrics IS NULL OR lyrics = '')");
    $songs_incomplete = $stmt->fetchColumn();
} catch (Exception $e) {
    $songs_incomplete = 0;
}

// 7. Aniversariantes do Mês
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birthdate) = MONTH(CURDATE())");
    $birthdays_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $birthdays_count = 0;
}

// 8. Escalas Próximas (próximos 30 dias)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE event_date BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)");
    $stmt->execute([$today, $today]);
    $upcoming_schedules = $stmt->fetchColumn();
} catch (Exception $e) {
    $upcoming_schedules = 0;
}

// 9. Top 5 Músicas Mais Tocadas (último mês)
try {
    $stmt = $pdo->query("
        SELECT s.title, COUNT(*) as vezes 
        FROM schedule_songs ss
        JOIN songs s ON ss.song_id = s.id
        WHERE ss.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        GROUP BY ss.song_id 
        ORDER BY vezes DESC 
        LIMIT 5
    ");
    $top_songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_songs = [];
}

// 10. Atividades Recentes
try {
    $stmt = $pdo->query("
        (SELECT 'escala' as tipo, event_type as titulo, created_at FROM schedules ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'musica' as tipo, title as titulo, created_at FROM songs ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'aviso' as tipo, title as titulo, created_at FROM avisos ORDER BY created_at DESC LIMIT 3)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_activities = [];
}

// 11. Métricas para Estatísticas de Escalas
try {
    // Total de escalas este mês
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM schedules 
        WHERE MONTH(event_date) = MONTH(CURDATE()) 
        AND YEAR(event_date) = YEAR(CURDATE())
    ");
    $escalas_mes = $stmt->fetchColumn();

    // Taxa de confirmação média
    $stmt = $pdo->query("
        SELECT 
            ROUND((COUNT(CASE WHEN confirmed = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) as taxa
        FROM schedule_users
    ");
    $taxa_confirmacao = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $escalas_mes = 0;
    $taxa_confirmacao = 0;
}

?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 12px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }

    .kpi-card {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        padding: 12px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--accent-color, var(--primary));
    }

    .kpi-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
        margin-bottom: 4px;
    }

    .kpi-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-subtitle {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 20px 0 10px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--border-color);
    }

    .section-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .chart-card {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        padding: 16px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
    }

    .chart-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .create-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 16px;
    }

    .create-btn {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 10px 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .create-btn span {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .create-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .stats-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stats-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 16px;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        border-color: var(--primary);
    }

    .stats-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stats-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .stats-icon i {
        width: 22px;
        height: 22px;
    }

    .stats-badge {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        padding: 3px 10px;
        border-radius: 16px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stats-card-body h3 {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .stats-card-body p {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin: 2px 0 0 0;
    }

    .stats-card-footer {
        display: flex;
        gap: 16px;
        padding-top: 10px;
        border-top: 1px solid var(--border-color);
    }

    .stats-metric {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .metric-value {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
    }

    .metric-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .alerts-container {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: var(--radius-lg);
        padding: 12px;
        margin-bottom: 16px;
    }

    .alert-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px;
        background: white;
        border-radius: 8px;
        margin-bottom: 6px;
    }

    .alert-item:last-child {
        margin-bottom: 0;
    }

    .activity-list {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        padding: 14px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .create-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .stats-buttons {
            grid-template-columns: 1fr;
        }

        .charts-grid {
            grid-template-columns: 1fr;
        }

        .actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="dashboard-container">

    <!-- KPIs Grid -->
    <div class="kpi-grid">

        <!-- Próxima Escala -->
        <a href="escalas.php" class="kpi-card" style="--accent-color: #8b5cf6;">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #f5f3ff; color: #8b5cf6;">
                    <i data-lucide="calendar"></i>
                </div>
                <div class="kpi-label">Próxima Escala</div>
            </div>
            <?php if ($next_scale): ?>
                <div class="kpi-value"><?= date('d/m', strtotime($next_scale['event_date'])) ?></div>
                <div class="kpi-subtitle"><?= htmlspecialchars($next_scale['event_type']) ?> • <?= date('H:i', strtotime($next_scale['event_time'])) ?></div>
            <?php else: ?>
                <div class="kpi-value">--</div>
                <div class="kpi-subtitle">Nenhuma agendada</div>
            <?php endif; ?>
        </a>

        <!-- Total de Músicas -->
        <a href="repertorio.php" class="kpi-card" style="--accent-color: #10b981;">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
                    <i data-lucide="music"></i>
                </div>
                <div class="kpi-label">Músicas Ativas</div>
            </div>
            <div class="kpi-value"><?= $total_songs ?></div>
            <div class="kpi-subtitle"><?= $songs_incomplete ?> sem material completo</div>
        </a>

        <!-- Total de Membros -->
        <a href="membros.php" class="kpi-card" style="--accent-color: #3b82f6;">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #eff6ff; color: #3b82f6;">
                    <i data-lucide="users"></i>
                </div>
                <div class="kpi-label">Membros Ativos</div>
            </div>
            <div class="kpi-value"><?= $total_members ?></div>
            <div class="kpi-subtitle"><?= $birthdays_count ?> aniversariantes este mês</div>
        </a>

        <!-- Avisos Ativos -->
        <a href="avisos.php" class="kpi-card" style="--accent-color: #f59e0b;">
            <div class="kpi-header">
                <div class="kpi-icon" style="background: #fffbeb; color: #f59e0b;">
                    <i data-lucide="bell"></i>
                </div>
                <div class="kpi-label">Avisos Ativos</div>
            </div>
            <div class="kpi-value"><?= $total_avisos ?></div>
            <div class="kpi-subtitle">Comunicação ativa</div>
        </a>

    </div>

    <!-- Alertas -->
    <?php if ($absences_count > 0 || $songs_incomplete > 0 || $birthdays_count > 0): ?>
        <div class="section-header">
            <i data-lucide="alert-triangle" style="color: #f59e0b;"></i>
            <h2>Alertas e Notificações</h2>
        </div>
        <div class="alerts-container">
            <?php if ($absences_count > 0): ?>
                <div class="alert-item">
                    <i data-lucide="user-x" style="color: #ef4444;"></i>
                    <div>
                        <strong><?= $absences_count ?> indisponibilidade(s)</strong> registrada(s) para os próximos dias
                        <a href="indisponibilidade.php" style="color: #ef4444; margin-left: 8px;">Ver detalhes →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($songs_incomplete > 0): ?>
                <div class="alert-item">
                    <i data-lucide="music" style="color: #f59e0b;"></i>
                    <div>
                        <strong><?= $songs_incomplete ?> música(s)</strong> sem cifra ou letra completa
                        <a href="repertorio.php" style="color: #f59e0b; margin-left: 8px;">Revisar →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($birthdays_count > 0): ?>
                <div class="alert-item">
                    <i data-lucide="cake" style="color: #8b5cf6;"></i>
                    <div>
                        <strong><?= $birthdays_count ?> aniversariante(s)</strong> este mês
                        <a href="aniversarios.php" style="color: #8b5cf6; margin-left: 8px;">Ver lista →</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Criar Novo -->
    <div class="section-header">
        <i data-lucide="plus-circle"></i>
        <h2>Criar Novo</h2>
    </div>
    <div class="create-grid">
        <a href="escala_adicionar.php" class="create-btn">
            <i data-lucide="calendar-plus" style="width: 18px; color: #8b5cf6;"></i>
            <span>Escala</span>
        </a>
        <a href="musica_adicionar.php" class="create-btn">
            <i data-lucide="music" style="width: 18px; color: #10b981;"></i>
            <span>Música</span>
        </a>
        <a href="membros.php" class="create-btn">
            <i data-lucide="user-plus" style="width: 18px; color: #3b82f6;"></i>
            <span>Membro</span>
        </a>
        <a href="avisos.php" class="create-btn">
            <i data-lucide="megaphone" style="width: 18px; color: #f59e0b;"></i>
            <span>Aviso</span>
        </a>
    </div>

    <!-- Estatísticas -->
    <div class="section-header" style="margin-top: 32px;">
        <i data-lucide="bar-chart-2"></i>
        <h2>Estatísticas</h2>
    </div>
    <div class="stats-buttons">
        <!-- Estatísticas de Escalas -->
        <a href="escalas_stats.php" class="stats-card">
            <div class="stats-card-header">
                <div class="stats-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                    <i data-lucide="calendar-check"></i>
                </div>
                <div class="stats-badge">Novo</div>
            </div>
            <div class="stats-card-body">
                <h3>Estatísticas de Escalas</h3>
                <p>Análise completa de participação e frequência</p>
            </div>
            <div class="stats-card-footer">
                <div class="stats-metric">
                    <span class="metric-value"><?= $escalas_mes ?></span>
                    <span class="metric-label">escalas este mês</span>
                </div>
                <div class="stats-metric">
                    <span class="metric-value"><?= $taxa_confirmacao ?>%</span>
                    <span class="metric-label">confirmação</span>
                </div>
            </div>
        </a>

        <!-- Estatísticas do Repertório -->
        <a href="repertorio_stats.php" class="stats-card">
            <div class="stats-card-header">
                <div class="stats-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i data-lucide="music-2"></i>
                </div>
            </div>
            <div class="stats-card-body">
                <h3>Estatísticas do Repertório</h3>
                <p>Músicas mais tocadas e análises detalhadas</p>
            </div>
            <div class="stats-card-footer">
                <div class="stats-metric">
                    <span class="metric-value"><?= $total_songs ?></span>
                    <span class="metric-label">músicas ativas</span>
                </div>
                <div class="stats-metric">
                    <span class="metric-value"><?= count($top_songs) ?></span>
                    <span class="metric-label">top músicas</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Atividade Recente -->
    <div class="section-header">
        <i data-lucide="activity"></i>
        <h2>Atividade Recente</h2>
    </div>
    <div class="activity-list">
        <?php if (empty($recent_activities)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                Nenhuma atividade recente
            </div>
        <?php else: ?>
            <?php foreach ($recent_activities as $activity):
                $icon = 'circle';
                $color = '#64748b';
                $bg = '#f1f5f9';

                if ($activity['tipo'] === 'escala') {
                    $icon = 'calendar';
                    $color = '#8b5cf6';
                    $bg = '#f5f3ff';
                } elseif ($activity['tipo'] === 'musica') {
                    $icon = 'music';
                    $color = '#10b981';
                    $bg = '#ecfdf5';
                } elseif ($activity['tipo'] === 'aviso') {
                    $icon = 'bell';
                    $color = '#f59e0b';
                    $bg = '#fffbeb';
                }
            ?>
                <div class="activity-item">
                    <div class="activity-icon" style="background: <?= $bg ?>; color: <?= $color ?>;">
                        <i data-lucide="<?= $icon ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($activity['titulo']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            <?= ucfirst($activity['tipo']) ?> • <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php renderAppFooter(); ?>