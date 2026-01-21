<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Verificar se está logado e se é líder/admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Verificar se é líder ou admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// ==========================================
// FILTRO DE PERÍODO
// ==========================================

$periodo = $_GET['periodo'] ?? 'semestral';
$periodo_sql = '';
$periodo_label = '';
$periodo_meses = 6;

switch ($periodo) {
    case 'mensal':
        $periodo_sql = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        $periodo_label = "Último Mês";
        $periodo_meses = 1;
        break;
    case 'semestral':
        $periodo_sql = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $periodo_label = "Últimos 6 Meses";
        $periodo_meses = 6;
        break;
    case 'anual':
        $periodo_sql = "AND s.event_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        $periodo_label = "Último Ano";
        $periodo_meses = 12;
        break;
    default:
        $periodo_sql = "";
        $periodo_label = "Todo o Período";
        $periodo_meses = 999;
}

// ==========================================
// MÉTRICAS GERAIS DO MINISTÉRIO
// ==========================================

// Total de escalas no período
$stmt = $pdo->query("SELECT COUNT(*) as total FROM schedules s WHERE 1=1 $periodo_sql");
$total_escalas = $stmt->fetch()['total'];

// Total de membros ativos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$total_membros = $stmt->fetch()['total'];

// Total de músicas no repertório
$stmt = $pdo->query("SELECT COUNT(*) as total FROM songs");
$total_musicas = $stmt->fetch()['total'];

// Média de participações por escala
$stmt = $pdo->query("
    SELECT AVG(participantes) as media 
    FROM (
        SELECT COUNT(*) as participantes 
        FROM schedule_users su
        JOIN schedules s ON su.schedule_id = s.id
        WHERE 1=1 $periodo_sql
        GROUP BY su.schedule_id
    ) as subquery
");
$media_participacoes = round($stmt->fetch()['media'] ?? 0, 1);

// Taxa de presença geral no período
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT su.user_id, su.schedule_id) as participacoes_reais,
        COUNT(DISTINCT s.id) * COUNT(DISTINCT u.id) as participacoes_possiveis
    FROM schedules s
    CROSS JOIN users u
    LEFT JOIN schedule_users su ON s.id = su.schedule_id AND u.id = su.user_id
    WHERE u.role != 'admin' $periodo_sql
");
$presenca_data = $stmt->fetch();
$taxa_presenca = $presenca_data['participacoes_possiveis'] > 0
    ? round(($presenca_data['participacoes_reais'] / $presenca_data['participacoes_possiveis']) * 100, 1)
    : 0;

// ==========================================
// ESTATÍSTICAS POR MEMBRO
// ==========================================

$periodo_sql_subquery = str_replace('s.', 'schedules.', $periodo_sql);
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        u.instrument,
        COUNT(DISTINCT su.schedule_id) as total_participacoes,
        ROUND(COUNT(DISTINCT su.schedule_id) * 100.0 / NULLIF((SELECT COUNT(*) FROM schedules WHERE 1=1 " . $periodo_sql_subquery . "), 0), 1) as taxa_presenca,
        MAX(s.event_date) as ultima_participacao,
        MIN(s.event_date) as primeira_participacao,
        DATEDIFF(CURDATE(), MAX(s.event_date)) as dias_sem_tocar
    FROM users u
    LEFT JOIN schedule_users su ON u.id = su.user_id
    LEFT JOIN schedules s ON su.schedule_id = s.id " . $periodo_sql . "
    WHERE u.role != 'admin'
    GROUP BY u.id, u.name, u.instrument
    ORDER BY total_participacoes DESC
");
$stmt->execute();
$membros_stats = $stmt->fetchAll();

// ==========================================
// TOP 10 MÚSICAS MAIS TOCADAS
// ==========================================

$stmt = $pdo->prepare("
    SELECT 
        s.title,
        s.artist,
        s.category,
        COUNT(*) as vezes_tocada
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    JOIN schedules sch ON ss.schedule_id = sch.id
    WHERE 1=1 " . str_replace('s.event_date', 'sch.event_date', $periodo_sql) . "
    GROUP BY s.id, s.title, s.artist, s.category
    ORDER BY vezes_tocada DESC
    LIMIT 10
");
$stmt->execute();
$top_musicas = $stmt->fetchAll();

// ==========================================
// PARTICIPAÇÕES POR MÊS
// ==========================================

$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(s.event_date, '%Y-%m') as mes,
        DATE_FORMAT(s.event_date, '%b/%Y') as mes_formatado,
        COUNT(DISTINCT s.id) as total_escalas,
        COUNT(DISTINCT su.user_id) as total_participantes
    FROM schedules s
    LEFT JOIN schedule_users su ON s.id = su.schedule_id
    WHERE s.event_date >= DATE_SUB(CURDATE(), INTERVAL $periodo_meses MONTH)
    GROUP BY mes, mes_formatado
    ORDER BY mes DESC
");
$escalas_por_mes = $stmt->fetchAll();

// ==========================================
// PARCEIROS MAIS FREQUENTES (DUPLAS)
// ==========================================

$stmt = $pdo->prepare("
    SELECT 
        u1.name as membro1,
        u2.name as membro2,
        COUNT(*) as escalas_juntos
    FROM schedule_users su1
    JOIN schedule_users su2 ON su1.schedule_id = su2.schedule_id AND su1.user_id < su2.user_id
    JOIN users u1 ON su1.user_id = u1.id
    JOIN users u2 ON su2.user_id = u2.id
    JOIN schedules s ON su1.schedule_id = s.id
    WHERE u1.role != 'admin' AND u2.role != 'admin' " . $periodo_sql . "
    GROUP BY u1.id, u2.id, u1.name, u2.name
    ORDER BY escalas_juntos DESC
    LIMIT 10
");
$stmt->execute();
$parceiros_frequentes = $stmt->fetchAll();

// ==========================================
// DISTRIBUIÇÃO POR INSTRUMENTO
// ==========================================

$stmt = $pdo->query("
    SELECT 
        COALESCE(instrument, 'Não definido') as instrumento,
        COUNT(*) as total,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users WHERE role != 'admin'), 1) as percentual
    FROM users
    WHERE role != 'admin'
    GROUP BY instrument
    ORDER BY total DESC
");
$distribuicao_instrumentos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletim Estatístico - App Louvor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .stats-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            border-radius: 16px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .stats-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .stats-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .period-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .period-selector h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .period-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .period-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
            position: relative;
        }

        .metric-card.success {
            border-left-color: #10b981;
        }

        .metric-card.warning {
            border-left-color: #f59e0b;
        }

        .metric-card.info {
            border-left-color: #3b82f6;
        }

        .metric-card.purple {
            border-left-color: #8b5cf6;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin: 10px 0 5px 0;
        }

        .metric-label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .metric-help {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            cursor: help;
            position: relative;
        }

        .metric-help:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 400;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .metric-help:hover::before {
            content: '';
            position: absolute;
            bottom: 110%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #1f2937;
            z-index: 1000;
        }

        .metric-desc {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 8px;
            line-height: 1.4;
            font-weight: 400;
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 40px 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .section-desc {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 20px 34px;
            line-height: 1.5;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        .stats-table tbody tr:hover {
            background: #f9fafb;
        }

        .stats-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.gold {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.silver {
            background: #e5e7eb;
            color: #374151;
        }

        .badge.bronze {
            background: #fed7aa;
            color: #9a3412;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.info {
            background: #dbeafe;
            color: #1e40af;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .rank-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .rank-1 {
            background: #fef3c7;
            color: #92400e;
        }

        .rank-2 {
            background: #e5e7eb;
            color: #374151;
        }

        .rank-3 {
            background: #fed7aa;
            color: #9a3412;
        }

        .rank-other {
            background: #f3f4f6;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .stats-container {
                padding: 15px;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .stats-table {
                font-size: 13px;
            }

            .stats-table th,
            .stats-table td {
                padding: 10px;
            }

            .period-buttons {
                flex-direction: column;
            }

            .period-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <div class="stats-container">
        <!-- Header -->
        <div class="stats-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <a href="lider.php" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; opacity: 0.9;">
                    <i data-lucide="arrow-left" style="width: 20px;"></i>
                    Voltar
                </a>
                <button onclick="gerarPDF()" style="
                    background: rgba(255,255,255,0.2);
                    border: 2px solid white;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.2s;
                    backdrop-filter: blur(10px);
                " onmouseover="this.style.background='white'; this.style.color='#667eea';" onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.color='white';">
                    <i data-lucide="download" style="width: 18px;"></i>
                    Baixar PDF
                </button>
            </div>
            <h1>📊 Boletim Estatístico do Ministério</h1>
            <p>Análise completa de desempenho e participação - <?= $periodo_label ?></p>
        </div>

        <!-- Seletor de Período -->
        <div class="period-selector">
            <h3>
                <i data-lucide="calendar-range" style="width: 18px; color: #667eea;"></i>
                Selecione o Período de Análise
            </h3>
            <div class="period-buttons">
                <a href="?periodo=mensal" class="period-btn <?= $periodo === 'mensal' ? 'active' : '' ?>">
                    📅 Último Mês
                </a>
                <a href="?periodo=semestral" class="period-btn <?= $periodo === 'semestral' ? 'active' : '' ?>">
                    📊 Últimos 6 Meses
                </a>
                <a href="?periodo=anual" class="period-btn <?= $periodo === 'anual' ? 'active' : '' ?>">
                    📈 Último Ano
                </a>
            </div>
        </div>

        <!-- Métricas Principais -->
        <div class="metrics-grid">
            <div class="metric-card info">
                <div class="metric-icon">
                    <i data-lucide="calendar" style="width: 20px;"></i>
                </div>
                <div class="metric-label">
                    Total de Escalas
                    <span class="metric-help" data-tooltip="Número total de escalas/cultos realizados">?</span>
                </div>
                <div class="metric-value"><?= $total_escalas ?></div>
                <div class="metric-desc">Escalas realizadas no período selecionado</div>
            </div>

            <div class="metric-card success">
                <div class="metric-icon">
                    <i data-lucide="users" style="width: 20px;"></i>
                </div>
                <div class="metric-label">
                    Membros Ativos
                    <span class="metric-help" data-tooltip="Total de membros cadastrados na equipe">?</span>
                </div>
                <div class="metric-value"><?= $total_membros ?></div>
                <div class="metric-desc">Membros cadastrados no ministério</div>
            </div>

            <div class="metric-card purple">
                <div class="metric-icon">
                    <i data-lucide="music" style="width: 20px;"></i>
                </div>
                <div class="metric-label">
                    Músicas no Repertório
                    <span class="metric-help" data-tooltip="Total de músicas cadastradas no sistema">?</span>
                </div>
                <div class="metric-value"><?= $total_musicas ?></div>
                <div class="metric-desc">Músicas disponíveis para escalas</div>
            </div>

            <div class="metric-card warning">
                <div class="metric-icon">
                    <i data-lucide="trending-up" style="width: 20px;"></i>
                </div>
                <div class="metric-label">
                    Média por Escala
                    <span class="metric-help" data-tooltip="Média de pessoas que participam de cada escala">?</span>
                </div>
                <div class="metric-value"><?= $media_participacoes ?></div>
                <div class="metric-desc">Participantes médios por culto</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i data-lucide="percent" style="width: 20px;"></i>
                </div>
                <div class="metric-label">
                    Taxa de Presença
                    <span class="metric-help" data-tooltip="Percentual de participação geral da equipe">?</span>
                </div>
                <div class="metric-value"><?= $taxa_presenca ?>%</div>
                <div class="metric-desc">Engajamento geral do ministério</div>
            </div>
        </div>

        <!-- Ranking de Membros -->
        <h2 class="section-title">
            <i data-lucide="trophy" style="width: 24px;"></i>
            Ranking de Participação
        </h2>
        <p class="section-desc">
            Classificação dos membros por número de participações. Status:
            <span class="badge success">Ativo</span> (tocou nos últimos 7 dias),
            <span class="badge warning">Regular</span> (últimos 30 dias),
            <span class="badge danger">Inativo</span> (mais de 30 dias).
        </p>
        <div class="table-container">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Membro</th>
                        <th>Instrumento</th>
                        <th style="text-align: center;">Participações</th>
                        <th style="text-align: center;">Taxa</th>
                        <th>Última Participação</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    foreach ($membros_stats as $membro):
                        $rank_class = $rank <= 3 ? "rank-$rank" : "rank-other";

                        // Status baseado em dias sem tocar
                        if ($membro['dias_sem_tocar'] === null) {
                            $status = '<span class="badge info">Novo</span>';
                        } elseif ($membro['dias_sem_tocar'] <= 7) {
                            $status = '<span class="badge success">Ativo</span>';
                        } elseif ($membro['dias_sem_tocar'] <= 30) {
                            $status = '<span class="badge warning">Regular</span>';
                        } else {
                            $status = '<span class="badge danger">Inativo</span>';
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="rank-number <?= $rank_class ?>">
                                    <?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : $rank)) ?>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($membro['name']) ?></strong></td>
                            <td><?= htmlspecialchars($membro['instrument'] ?? 'Não definido') ?></td>
                            <td style="text-align: center;"><strong><?= $membro['total_participacoes'] ?></strong></td>
                            <td style="text-align: center;">
                                <strong><?= $membro['taxa_presenca'] ?>%</strong>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $membro['taxa_presenca'] ?>%"></div>
                                </div>
                            </td>
                            <td><?= $membro['ultima_participacao'] ? date('d/m/Y', strtotime($membro['ultima_participacao'])) : '-' ?></td>
                            <td style="text-align: center;"><?= $status ?></td>
                        </tr>
                    <?php
                        $rank++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Top Músicas -->
        <h2 class="section-title">
            <i data-lucide="music-2" style="width: 24px;"></i>
            Top 10 Músicas Mais Tocadas
        </h2>
        <p class="section-desc">
            Músicas mais executadas no período. Útil para identificar favoritas e planejar repertórios variados.
        </p>
        <div class="table-container">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Música</th>
                        <th>Artista</th>
                        <th>Categoria</th>
                        <th style="text-align: center;">Vezes Tocada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pos = 1;
                    foreach ($top_musicas as $musica):
                        $badge_class = $pos <= 3 ? 'gold' : 'info';
                    ?>
                        <tr>
                            <td>
                                <div class="rank-number <?= $pos <= 3 ? "rank-$pos" : "rank-other" ?>">
                                    <?= $pos ?>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($musica['title']) ?></strong></td>
                            <td><?= htmlspecialchars($musica['artist'] ?? '-') ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($musica['category'] ?? 'Geral') ?></span></td>
                            <td style="text-align: center;"><strong><?= $musica['vezes_tocada'] ?>x</strong></td>
                        </tr>
                    <?php
                        $pos++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Participações por Mês -->
        <h2 class="section-title">
            <i data-lucide="bar-chart-3" style="width: 24px;"></i>
            Participações por Mês
        </h2>
        <p class="section-desc">
            Evolução temporal das escalas. Mostra quantas escalas foram realizadas e quantos membros únicos participaram em cada mês.
        </p>
        <div class="table-container">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th style="text-align: center;">Total de Escalas</th>
                        <th style="text-align: center;">Participantes Únicos</th>
                        <th style="text-align: center;">Média por Escala</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escalas_por_mes as $mes):
                        $media_mes = $mes['total_escalas'] > 0 ? round($mes['total_participantes'] / $mes['total_escalas'], 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($mes['mes_formatado']) ?></strong></td>
                            <td style="text-align: center;"><?= $mes['total_escalas'] ?></td>
                            <td style="text-align: center;"><?= $mes['total_participantes'] ?></td>
                            <td style="text-align: center;"><span class="badge info"><?= $media_mes ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Parceiros Frequentes -->
        <h2 class="section-title">
            <i data-lucide="users-2" style="width: 24px;"></i>
            Duplas Mais Frequentes
        </h2>
        <p class="section-desc">
            Membros que mais tocam juntos. A "sinergia" indica o percentual de escalas em que a dupla atuou em conjunto.
        </p>
        <div class="table-container">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Dupla</th>
                        <th style="text-align: center;">Escalas Juntos</th>
                        <th style="text-align: center;">Sinergia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pos = 1;
                    foreach ($parceiros_frequentes as $dupla):
                        $sinergia_pct = $total_escalas > 0 ? round(($dupla['escalas_juntos'] / $total_escalas) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?= $pos ?></td>
                            <td>
                                <strong><?= htmlspecialchars($dupla['membro1']) ?></strong>
                                +
                                <strong><?= htmlspecialchars($dupla['membro2']) ?></strong>
                            </td>
                            <td style="text-align: center;"><strong><?= $dupla['escalas_juntos'] ?></strong></td>
                            <td style="text-align: center;">
                                <span class="badge success"><?= $sinergia_pct ?>%</span>
                            </td>
                        </tr>
                    <?php
                        $pos++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Distribuição por Instrumento -->
        <h2 class="section-title">
            <i data-lucide="guitar" style="width: 24px;"></i>
            Distribuição por Instrumento
        </h2>
        <p class="section-desc">
            Composição da equipe por instrumento. Ajuda a identificar áreas que precisam de reforço ou estão bem equilibradas.
        </p>
        <div class="table-container">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Instrumento</th>
                        <th style="text-align: center;">Total de Membros</th>
                        <th style="text-align: center;">Percentual</th>
                        <th>Distribuição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribuicao_instrumentos as $inst): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inst['instrumento']) ?></strong></td>
                            <td style="text-align: center;"><?= $inst['total'] ?></td>
                            <td style="text-align: center;"><span class="badge purple"><?= $inst['percentual'] ?>%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $inst['percentual'] ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        lucide.createIcons();

        function gerarPDF() {
            // Salvar título original
            const tituloOriginal = document.title;

            // Definir título do PDF
            const periodo = '<?= $periodo_label ?>';
            const dataAtual = new Date().toLocaleDateString('pt-BR');
            document.title = `Boletim Estatístico - ${periodo} - ${dataAtual}`;

            // Configurar impressão
            const css = `
                @media print {
                    body { 
                        margin: 0; 
                        padding: 20px;
                        background: white;
                    }
                    .stats-header a,
                    .stats-header button {
                        display: none !important;
                    }
                    .period-selector {
                        display: none !important;
                    }
                    .stats-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .metric-card,
                    .table-container {
                        page-break-inside: avoid;
                    }
                    .section-title {
                        page-break-after: avoid;
                    }
                    .badge,
                    .progress-fill {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            `;

            const style = document.createElement('style');
            style.textContent = css;
            document.head.appendChild(style);

            // Abrir diálogo de impressão
            window.print();

            // Restaurar título e remover estilo
            setTimeout(() => {
                document.title = tituloOriginal;
                style.remove();
            }, 100);
        }
    </script>
</body>

</html>