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
        ORDER BY created_at DESC
        LIMIT 20
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
        gap: 8px;
        margin-bottom: 16px;
    }

    .kpi-card {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        padding: 10px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
        border-color: var(--primary-light);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 3px;
        height: 100%;
        background: var(--accent-color, var(--primary));
    }

    .kpi-header {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }

    .kpi-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-body);
    }

    .kpi-icon i {
        width: 14px;
        height: 14px;
    }

    .kpi-value {
        font-size: var(--font-h2);
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
        margin-bottom: 2px;
    }

    .kpi-label {
        font-size: var(--font-caption);
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-subtitle {
        font-size: var(--font-caption);
        color: var(--text-muted);
        margin-top: 2px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 16px 0 8px 0;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--border-color);
    }

    .section-header h2 {
        font-size: var(--font-body);
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .section-header i {
        width: 16px;
        height: 16px;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .chart-card {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        padding: 10px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
    }

    .chart-title {
        font-size: var(--font-body-sm);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        margin-bottom: 16px;
    }

    .create-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
        margin-bottom: 16px;
    }

    .create-btn {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 6px 4px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .create-btn span {
        font-size: var(--font-caption);
        font-weight: 600;
        color: var(--text-main);
    }

    .create-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
        border-color: var(--primary);
    }

    .stats-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 8px;
        margin-bottom: 16px;
    }

    .stats-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 10px;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    .stats-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stats-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .stats-icon i {
        width: 16px;
        height: 16px;
    }

    .stats-badge {
        background: var(--gradient-rose-primary);
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stats-card-body h3 {
        font-size: var(--font-body);
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .stats-card-body p {
        font-size: var(--font-caption);
        color: var(--text-muted);
        margin: 2px 0 0 0;
    }

    .stats-card-footer {
        display: flex;
        gap: 10px;
        padding-top: 6px;
        border-top: 1px solid var(--border-color);
    }

    .stats-metric {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .metric-value {
        font-size: var(--font-h3);
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
    }

    .metric-label {
        font-size: var(--font-caption);
        color: var(--text-muted);
        font-weight: 600;
    }

    .alerts-container {
        background: var(--slate-50);
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-lg);
        padding: 8px;
        margin-bottom: 12px;
    }

    .alert-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        background: white;
        border-radius: 6px;
        margin-bottom: 4px;
        font-size: var(--font-body-sm);
    }

    .alert-item:last-child {
        margin-bottom: 0;
    }

    .activity-list {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        padding: 8px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        border-bottom: 1px solid var(--border-color);
        font-size: var(--font-body-sm);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
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

        .section-header h2 {
            font-size: var(--font-body-sm);
        }
    }
</style>

<div class="dashboard-container">

    <!-- 1. Gestão (Acesso Rápido) -->
    <div class="section-header">
        <div style="background: var(--gradient-slate-primary); padding: 8px; border-radius: 8px; color: white;">
            <i data-lucide="layout-grid" style="width: 18px; height: 18px;"></i>
        </div>
        <h2>Gestão</h2>
    </div>

    <!-- Grid de Botões (Gestão) -->
    <div class="create-grid" style="
        display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 32px;
    ">


        <!-- Equipe -->
        <a href="membros.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--slate-100); color: var(--slate-600); padding: 10px; border-radius: 10px;">
                <i data-lucide="users" style="width: 20px;"></i>
            </div>
            <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Equipe</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Gerenciar</div>
            </div>
        </a>



        <!-- Notificações (NOVO) -->
        <a href="notificacoes.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--lavender-50); color: var(--lavender-600); padding: 10px; border-radius: 10px;">
                <i data-lucide="bell" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Notificações</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Visualizar</div>
            </div>
        </a>

        <!-- Indisponibilidades (NOVO) -->
        <a href="indisponibilidades_equipe.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--rose-100); color: var(--rose-500); padding: 10px; border-radius: 10px;">
                <i data-lucide="calendar-x" style="width: 20px;"></i>
            </div>
            <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Ausências</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Equipe</div>
            </div>
        </a>

        <!-- Aniversariantes (MOVIDO) -->
        <a href="aniversarios.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--lavender-50); color: var(--lavender-600); padding: 10px; border-radius: 10px;">
                <i data-lucide="cake" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Aniversários</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Equipe</div>
            </div>
        </a>

        <!-- Sugestões de Músicas (NOVO) -->
        <a href="sugestoes_musicas.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--slate-100); color: var(--slate-600); padding: 10px; border-radius: 10px;">
                <i data-lucide="inbox" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Sugestões</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Pendentes</div>
            </div>
        </a>

        <!-- Gerenciar Tags (NOVO) -->
        <a href="classificacoes.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--lavender-50); color: var(--lavender-700); padding: 10px; border-radius: 10px;">
                <i data-lucide="tags" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Tags</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Gerenciar</div>
            </div>
        </a>
    </div>

    <!-- 2. Estatísticas (Separado) -->
    <div class="section-header">
        <div style="background: var(--gradient-slate-primary); padding: 8px; border-radius: 8px; color: white;">
            <i data-lucide="bar-chart-2" style="width: 18px; height: 18px;"></i>
        </div>
        <h2>Estatísticas</h2>
    </div>

    <div class="create-grid" style="
        display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 32px;
    ">
        <!-- Estatísticas Engajamento -->
        <a href="stats_equipe.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--slate-50); color: var(--slate-500); padding: 10px; border-radius: 10px;">
                 <i data-lucide="activity" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Engajamento</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Equipe</div>
            </div>
        </a>

        <!-- Relatório Completo (Indicadores) -->
        <a href="relatorios_gerais.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--lavender-50); color: var(--lavender-600); padding: 10px; border-radius: 10px;">
                 <i data-lucide="pie-chart" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Indicadores</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Relatório Geral</div>
            </div>
        </a>
    </div>

    <!-- 3. Criar Novo -->
    <div class="section-header">
        <div style="background: var(--gradient-lavender-primary); padding: 8px; border-radius: 8px; color: white;">
            <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i>
        </div>
        <h2>Criar Novo</h2>
    </div>
    
    <div class="create-grid" style="
        display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 32px;
    ">
         <!-- Nova Escala -->
         <a href="escala_adicionar.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--lavender-50); color: var(--lavender-600); padding: 10px; border-radius: 10px;">
                <i data-lucide="calendar-plus" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Escala</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Agendar Nova</div>
            </div>
        </a>

        <!-- Nova Música -->
        <a href="musica_adicionar.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
             <div style="background: var(--sage-50); color: var(--sage-500); padding: 10px; border-radius: 10px;">
                <i data-lucide="music" style="width: 20px;"></i>
            </div>
             <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Música</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Cadastrar</div>
            </div>
        </a>

        <!-- Novo Aviso -->
        <a href="avisos_admin.php" class="create-btn" style="
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 16px 12px; display: flex; flex-direction: column; align-items: center; gap: 8px;
            text-decoration: none; transition: all 0.2s; box-shadow: var(--shadow-sm);
        ">
            <div style="background: var(--slate-50); color: var(--slate-400); padding: 10px; border-radius: 10px;">
                <i data-lucide="megaphone" style="width: 20px;"></i>
            </div>
            <div style="text-align: center;">
                <div style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main);">Aviso</div>
                <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">Criar Novo</div>
            </div>
        </a>

    </div>

    <!-- 4. Atividade Recente -->
    <div class="section-header">
        <i data-lucide="activity" style="color: var(--text-muted);"></i>
        <h2>Atividade Recente</h2>
    </div>
    <div class="activity-list">
        <?php if (empty($recent_activities)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                Nenhuma atividade recente
            </div>
        <?php else: ?>
            <?php 
            $i = 0;
            foreach ($recent_activities as $activity):
                $i++;
                $isHidden = $i > 5;
                
                $icon = 'circle';
                $color = 'var(--slate-500)';
                $bg = 'var(--slate-100)';

                if ($activity['tipo'] === 'escala') {
                    $icon = 'calendar';
                    $color = 'var(--lavender-600)';
                    $bg = 'var(--lavender-50)';
                } elseif ($activity['tipo'] === 'musica') {
                    $icon = 'music';
                    $color = 'var(--sage-500)';
                    $bg = 'var(--sage-50)';
                } elseif ($activity['tipo'] === 'aviso') {
                    $icon = 'bell';
                    $color = 'var(--slate-400)';
                    $bg = 'var(--slate-50)';
                }
            ?>
                <div class="activity-item <?= $isHidden ? 'hidden-activity' : '' ?>" style="<?= $isHidden ? 'display: none;' : '' ?>">
                    <div class="activity-icon" style="background: <?= $bg ?>; color: <?= $color ?>;">
                        <i data-lucide="<?= $icon ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($activity['titulo']) ?></div>
                        <div style="font-size: var(--font-body-sm); color: var(--text-muted);">
                            <?= ucfirst($activity['tipo']) ?> • <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($recent_activities) > 5): ?>
                <button onclick="showAllActivities(this)" style="
                    width: 100%; padding: 12px; background: transparent; border: none; border-top: 1px solid var(--border-color);
                    color: var(--primary); font-weight: 600; cursor: pointer; font-size: var(--font-body);
                    display: flex; align-items: center; justify-content: center; gap: 6px;
                ">
                    Ver mais atividades <i data-lucide="chevron-down" style="width: 16px;"></i>
                </button>
            <?php endif; ?>

            <script>
                function showAllActivities(btn) {
                    const hiddenItems = document.querySelectorAll('.hidden-activity');
                    hiddenItems.forEach(item => {
                        item.style.display = 'flex';
                        // Adicionar animação simples
                        item.animate([
                            { opacity: 0, transform: 'translateY(-10px)' },
                            { opacity: 1, transform: 'translateY(0)' }
                        ], {
                            duration: 300,
                            easing: 'ease-out'
                        });
                    });
                    btn.style.display = 'none';
                }
            </script>
        <?php endif; ?>
    </div>

    <?php renderAppFooter(); ?>