<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAdmin();

// --- 1. CONFIGURAÇÃO DE FILTROS ---
$period = $_GET['period'] ?? 'this_month';
$startDate = '';
$endDate = '';
$periodLabel = '';

// Helper de Datas
switch ($period) {
    case 'this_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $periodLabel = 'Este Mês';
        break;
    case 'last_3_months':
        $startDate = date('Y-m-d', strtotime('-3 months'));
        $endDate = date('Y-m-d');
        $periodLabel = 'Últimos 3 Meses';
        break;
    case 'last_6_months':
        $startDate = date('Y-m-d', strtotime('-6 months'));
        $endDate = date('Y-m-d');
        $periodLabel = 'Últimos 6 Meses';
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        $periodLabel = 'Ano Atual';
        break;
    case 'all_time':
        $startDate = '2020-01-01';
        $endDate = date('Y-12-31');
        $periodLabel = 'Todo o Período';
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $periodLabel = 'Este Mês';
        break;
}

// --- 2. INTELIGÊNCIA DE DADOS (QUERIES) ---
try {
    // 2.1 SCORECARD DE SAÚDE (KPIs)
    
    // Confiabilidade: (Confirmados / (Confirmados + Recusados + Pendentes))
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
            COUNT(*) as total
        FROM schedule_users su
        JOIN schedules s ON su.schedule_id = s.id
        WHERE s.event_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $reliabilityStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $reliabilityScore = $reliabilityStats['total'] > 0 
        ? round(($reliabilityStats['confirmed'] / $reliabilityStats['total']) * 100) 
        : 0;

    $absenteeismRate = $reliabilityStats['total'] > 0 
        ? round(($reliabilityStats['declined'] / $reliabilityStats['total']) * 100)
        : 0;

    // Membros Ativos x Carga (Ocupação)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM schedule_users su JOIN schedules s ON su.schedule_id = s.id WHERE s.event_date BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $activeMembers = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE event_date BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $totalKeyEvents = $stmt->fetchColumn(); // Total de escalas (cultos)

    // Novidade (Músicas tocadas que NÃO foram tocadas nos 3 meses anteriores ao inicio do periodo)
    // Query simplificada para "Unique Songs" no período
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT song_id) 
        FROM schedule_songs ss
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE s.event_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $uniqueSongsPlayed = $stmt->fetchColumn();

    // 2.2 ANÁLISE DE MEMBROS (MATRIZ)
    // Top Comprometidos (Mais Escalas Confirmadas)
    $stmt = $pdo->prepare("
        SELECT u.name, u.instrument, COUNT(*) as count 
        FROM schedule_users su
        JOIN users u ON su.user_id = u.id
        JOIN schedules s ON su.schedule_id = s.id
        WHERE s.event_date BETWEEN ? AND ? AND su.status = 'confirmed'
        GROUP BY su.user_id
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$startDate, $endDate]);
    $topCommitted = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Indisponíveis (Mais Recusas)
    $stmt = $pdo->prepare("
        SELECT u.name, COUNT(*) as count 
        FROM schedule_users su
        JOIN users u ON su.user_id = u.id
        JOIN schedules s ON su.schedule_id = s.id
        WHERE s.event_date BETWEEN ? AND ? AND su.status = 'declined'
        GROUP BY su.user_id
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$startDate, $endDate]);
    $topDecliners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2.3 SAÚDE DO REPERTÓRIO
    // Músicas Saturadas (> 50% das escalas do período ou Top 5 absolutas)
    $stmt = $pdo->prepare("
        SELECT so.title, so.artist, COUNT(*) as count
        FROM schedule_songs ss
        JOIN schedules s ON ss.schedule_id = s.id
        JOIN songs so ON ss.song_id = so.id
        WHERE s.event_date BETWEEN ? AND ?
        GROUP BY ss.song_id
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$startDate, $endDate]);
    $saturatedSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // O Baú do Tesouro (Músicas NÃO tocadas no período, mas existentes)
    // Pegar 10 aleatórias para sugestão
    $stmt = $pdo->prepare("
        SELECT title, artist 
        FROM songs 
        WHERE id NOT IN (
            SELECT DISTINCT song_id 
            FROM schedule_songs ss
            JOIN schedules s ON ss.schedule_id = s.id
            WHERE s.event_date BETWEEN ? AND ?
        )
        ORDER BY RAND()
        LIMIT 12
    ");
    $stmt->execute([$startDate, $endDate]);
    $forgottenSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2.4 DINÂMICA (DUPLAS)
    $stmt = $pdo->prepare("
        SELECT u1.name as p1, u2.name as p2, COUNT(*) as count
        FROM schedule_users su1
        JOIN schedule_users su2 ON su1.schedule_id = su2.schedule_id AND su1.user_id < su2.user_id
        JOIN schedules s ON su1.schedule_id = s.id
        JOIN users u1 ON su1.user_id = u1.id
        JOIN users u2 ON su2.user_id = u2.id
        WHERE s.event_date BETWEEN ? AND ? AND su1.status = 'confirmed' AND su2.status = 'confirmed'
        GROUP BY su1.user_id, su2.user_id
        ORDER BY count DESC
        LIMIT 6
    ");
    $stmt->execute([$startDate, $endDate]);
    $topDuos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro Crítico no Motor de Inteligência: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Inteligência Ministerial</title>
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #0f172a; /* Slate 900 */
            --accent: #2563eb; /* Royal Blue */
            --danger: #dc2626;
            --success: #16a34a;
            --warning: #ca8a04;
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            padding: 40px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* --- Header Premium --- */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
        }
        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin: 0;
            color: var(--primary);
            line-height: 1;
        }
        .brand p {
            margin: 8px 0 0 0;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            font-weight: 600;
        }
        .meta {
            text-align: right;
        }
        .meta-date {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
        }
        .meta-generated {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* --- Scorecard Grid --- */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 48px;
        }
        .score-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .score-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--border);
        }
        .score-card.positive::after { background: var(--success); }
        .score-card.warning::after { background: var(--warning); }
        .score-card.negative::after { background: var(--danger); }
        
        .score-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .score-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }
        .score-sub {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 8px;
        }

        /* --- Report Sections --- */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            margin-top: 40px;
        }
        .section-icon {
            width: 32px; height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            font-family: 'Playfair Display', serif;
        }

        /* --- Layout de Colunas --- */
        .two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }

        /* --- Tabelas Premium --- */
        .premium-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .premium-table th {
            text-align: left;
            padding: 12px 8px;
            border-bottom: 2px solid var(--border);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .premium-table td {
            padding: 14px 8px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
        }
        .premium-table tr:last-child td { border-bottom: none; }
        
        .rank-badge {
            display: inline-flex;
            width: 24px; height: 24px;
            background: var(--bg-page);
            border-radius: 50%;
            align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .rank-1 .rank-badge { background: #fef9c3; color: #b45309; }
        .rank-2 .rank-badge { background: #f1f5f9; color: #475569; }
        .rank-3 .rank-badge { background: #fff7ed; color: #c2410c; }

        /* --- Barras de Progresso --- */
        .progress-track {
            background: #f1f5f9;
            height: 6px;
            border-radius: 3px;
            width: 100%;
            overflow: hidden;
            margin-top: 6px;
        }
        .progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 3px;
        }

        /* --- Tags e Chips --- */
        .chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }
        .chip-instrument { background: #eff6ff; color: #1e40af; }
        .chip-warn { background: #fef2f2; color: #991b1b; }

        /* --- "Treasure Chest" Grid --- */
        .treasure-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .treasure-item {
            background: #fff;
            border: 1px dashed var(--border);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        .treasure-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .treasure-artist { font-size: 0.75rem; color: var(--text-muted); }

        /* --- Controles (No Print) --- */
        .controls {
            position: fixed;
            bottom: 32px;
            right: 32px;
            display: flex;
            gap: 12px;
            background: white;
            padding: 12px;
            border-radius: 50px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            z-index: 100;
        }
        .btn-control {
            background: var(--bg-page);
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--text-main);
            display: flex; align-items: center; gap: 8px;
        }
        .btn-control:hover { background: #e2e8f0; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #334155; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .analytics-grid { gap: 16px; margin-bottom: 32px; }
            .score-card { padding: 16px; border: 1px solid #ddd; box-shadow: none; }
            .section-header { margin-top: 24px; }
            .treasure-grid { grid-template-columns: repeat(4, 1fr); }
            /* Evitar quebra */
            .score-card, .premium-table, .treasure-grid { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Controls -->
    <div class="controls no-print">
        <a href="?period=this_month" class="btn-control">Mês</a>
        <a href="?period=last_3_months" class="btn-control">3 Meses</a>
        <a href="?period=this_year" class="btn-control">Ano</a>
        <button onclick="window.print()" class="btn-control btn-primary">
            <i data-lucide="printer" width="18"></i> Imprimir PDF
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <header class="report-header">
            <div class="brand">
                <h1>Inteligência Ministerial</h1>
                <p>Boletim de Gestão & Performance</p>
            </div>
            <div class="meta">
                <div class="meta-date"><?= mb_strtoupper($periodLabel) ?></div>
                <div class="meta-generated">Gerado em <?= date('d/m/Y \à\s H:i') ?></div>
            </div>
        </header>

        <!-- KPI Scorecard -->
        <div class="analytics-grid">
            <!-- Confiabilidade -->
            <div class="score-card <?= $reliabilityScore >= 80 ? 'positive' : ($reliabilityScore >= 60 ? 'warning' : 'negative') ?>">
                <div class="score-label"><i data-lucide="shield-check" width="16"></i> Confiabilidade</div>
                <div class="score-value"><?= $reliabilityScore ?>%</div>
                <div class="score-sub">Escalas Confirmadas</div>
            </div>

            <!-- Engajamento -->
            <div class="score-card warning">
                <div class="score-label"><i data-lucide="users" width="16"></i> Time Ativo</div>
                <div class="score-value"><?= $activeMembers ?></div>
                <div class="score-sub">Participaram no período</div>
            </div>

            <!-- Saturação/Repetição -->
            <div class="score-card positive">
                <div class="score-label"><i data-lucide="music" width="16"></i> Repertório Único</div>
                <div class="score-value"><?= $uniqueSongsPlayed ?></div>
                <div class="score-sub">Músicas distintas tocadas</div>
            </div>

            <!-- Absenteísmo -->
            <div class="score-card <?= $absenteeismRate <= 10 ? 'positive' : 'negative' ?>">
                <div class="score-label"><i data-lucide="user-x" width="16"></i> Taxa de Recusa</div>
                <div class="score-value"><?= $absenteeismRate ?>%</div>
                <div class="score-sub"><?= $reliabilityStats['declined'] ?? 0 ?> recusas explícitas</div>
            </div>
        </div>

        <!-- 2 Columns: Members & Dynamics -->
        <div class="two-cols">
            
            <!-- Col 1: Membros -->
            <div>
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="award"></i></div>
                    <h2 class="section-title">Pilares da Equipe</h2>
                </div>
                
                <table class="premium-table">
                    <thead><tr><th style="width: 40px">#</th><th>Membro Chave</th><th>Escalas</th></tr></thead>
                    <tbody>
                        <?php foreach ($topCommitted as $i => $m): 
                             $max = $topCommitted[0]['count']; $pct = ($m['count']/$max)*100;
                        ?>
                        <tr class="rank-<?= $i+1 ?>">
                            <td><div class="rank-badge"><?= $i+1 ?></div></td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="chip chip-instrument"><?= htmlspecialchars($m['instrument']) ?></div>
                            </td>
                            <td style="width: 80px; text-align: right;">
                                <div style="font-weight: 700; font-size: 1.1rem; color: var(--primary);"><?= $m['count'] ?></div>
                                <div class="progress-track"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 32px">
                    <h4 style="font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px;">Alertas de Indisponibilidade (Top Recusas)</h4>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php foreach ($topDecliners as $d): ?>
                            <div class="chip chip-warn" style="font-size: 0.85rem; padding: 6px 12px;">
                                <?= htmlspecialchars($d['name']) ?> (<?= $d['count'] ?>x)
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($topDecliners)) echo '<span style="color:var(--text-muted)">Nenhuma recusa no período.</span>'; ?>
                    </div>
                </div>
            </div>

            <!-- Col 2: Repertório & Dinâmica -->
            <div>
                <div class="section-header">
                    <div class="section-icon"><i data-lucide="bar-chart-2"></i></div>
                    <h2 class="section-title">Análise de Repertório</h2>
                </div>

                <table class="premium-table">
                    <thead><tr><th>Músicas Saturadas (Top 5)</th><th style="text-align: right;">Frequência</th></tr></thead>
                    <tbody>
                        <?php foreach ($saturatedSongs as $s):  
                            $freq = $totalKeyEvents > 0 ? round(($s['count'] / $totalKeyEvents) * 100) : 0;
                            $barColor = $freq > 40 ? '#ef4444' : '#f59e0b';
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($s['title']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($s['artist']) ?></div>
                            </td>
                            <td style="width: 100px; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                    <span style="font-weight: 700; color: <?= $barColor ?>"><?= $freq ?>%</span>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">(<?= $s['count'] ?>x)</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 40px;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="link" width="16"></i> Sinergia de Equipe (Top Duplas)
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($topDuos as $duo): ?>
                            <div style="border: 1px solid var(--border); padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; background: white;">
                                <strong><?= explode(' ', $duo['p1'])[0] ?></strong> + 
                                <strong><?= explode(' ', $duo['p2'])[0] ?></strong> 
                                <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 6px; font-weight: 700;"><?= $duo['count'] ?>x</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treasure Chest -->
        <div class="section-header" style="page-break-before: always;">
            <div class="section-icon" style="background: var(--warning);"><i data-lucide="gem"></i></div>
            <h2 class="section-title">O Baú do Tesouro</h2>
        </div>
        <p style="margin-bottom: 24px; color: var(--text-muted);">
            Sugestões de músicas ativas no seu repertório que <strong>não foram tocadas</strong> neste período. 
            Que tal resgatar uma dessas para a próxima escala?
        </p>
        
        <div class="treasure-grid">
            <?php foreach ($forgottenSongs as $fs): ?>
            <div class="treasure-item">
                <div class="treasure-title"><?= htmlspecialchars($fs['title']) ?></div>
                <div class="treasure-artist"><?= htmlspecialchars($fs['artist']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>