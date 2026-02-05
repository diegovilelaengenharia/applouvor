<?php
// admin/escalas_relatorio_print.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

checkAdmin();

// --- Filtros (Recuperar mesmos filtros da gestão) ---
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$semester = $_GET['semester'] ?? (date('m') <= 6 ? 1 : 2);

if ($period === 'month') {
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $titlePeriod = "Mensal: " . date('m/Y', strtotime($startDate));
} elseif ($period === 'semester') {
    if ($semester == 1) {
        $startDate = "$year-01-01";
        $endDate = "$year-06-30";
        $titlePeriod = "1º Semestre de $year";
    } else {
        $startDate = "$year-07-01";
        $endDate = "$year-12-31";
        $titlePeriod = "2º Semestre de $year";
    }
} elseif ($period === 'year') {
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $titlePeriod = "Ano de $year";
}

// --- QUERIES (Mesmas da gestão) ---

// 1. KPIs
$stmtTaxa = $pdo->prepare("SELECT ROUND((COUNT(CASE WHEN su.status = 'confirmed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) FROM schedule_users su JOIN schedules s ON su.schedule_id = s.id WHERE s.event_date BETWEEN ? AND ?");
$stmtTaxa->execute([$startDate, $endDate]);
$taxaConfirmacao = $stmtTaxa->fetchColumn() ?: 0;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE event_date BETWEEN ? AND ?");
$stmtCount->execute([$startDate, $endDate]);
$totalEscalas = $stmtCount->fetchColumn();

// 2. Ranking
$stmtMembers = $pdo->prepare("SELECT u.name, u.instrument, COUNT(su.schedule_id) as participacoes FROM users u JOIN schedule_users su ON u.id = su.user_id JOIN schedules s ON su.schedule_id = s.id WHERE s.event_date BETWEEN ? AND ? GROUP BY u.id ORDER BY participacoes DESC LIMIT 20");
$stmtMembers->execute([$startDate, $endDate]);
$rankingParticipacao = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// 3. Ausências
$stmtAbsences = $pdo->prepare("SELECT u.name, COUNT(*) as count FROM user_unavailability ua JOIN users u ON ua.user_id = u.id WHERE (ua.start_date BETWEEN ? AND ?) GROUP BY u.id ORDER BY count DESC LIMIT 10");
$stmtAbsences->execute([$startDate, $endDate]);
$topAbsences = $stmtAbsences->fetchAll(PDO::FETCH_ASSOC);

// 4. Listagem
$stmtSchedules = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM schedule_users WHERE schedule_id = s.id) as total_users, (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs FROM schedules s WHERE s.event_date BETWEEN ? AND ? ORDER BY s.event_date ASC");
$stmtSchedules->execute([$startDate, $endDate]);
$schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Escalas - <?= $titlePeriod ?></title>
    <style>
        /* Reset e Configuração A4 */
        @page { size: A4; margin: 1.5cm; }
        body {
            font-family: "Segoe UI", "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: var(--slate-700); /* Slate 700 */
            line-height: 1.4;
            font-size: 10pt;
            background: white;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* Layout */
        .header {
            background: var(--slate-50); /* Slate 50 */
            border-bottom: 2px solid var(--slate-200); /* Slate 200 */
            color: var(--slate-900); /* Slate 900 */
            padding: 25px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 22pt;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            font-weight: 800;
            color: var(--slate-600); /* Primary Blue */
        }
        .header .subtitle {
            font-size: 11pt;
            color: var(--slate-500); /* Slate 500 */
            font-weight: 500;
            margin-top: 4px;
        }
        .header .meta {
            text-align: right;
            font-size: 9pt;
            color: var(--slate-500);
        }

        .section { margin-bottom: 35px; }
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: var(--slate-700);
            border-bottom: 2px solid var(--slate-200);
            padding-bottom: 10px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background: var(--slate-600);
            border-radius: 50%;
        }

        /* KPIs */
        .kpi-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 35px;
        }
        .kpi-item { 
            flex: 1;
            text-align: center; 
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            padding: 20px 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .kpi-value { font-size: 24pt; font-weight: 800; color: var(--slate-900); line-height: 1; margin-bottom: 6px; }
        .kpi-label { font-size: 8pt; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px; }
        
        /* Cores Específicas para KPIs */
        .kpi-item.blue .kpi-value { color: var(--slate-600); }
        .kpi-item.green .kpi-value { color: #10b981; }
        .kpi-item.amber .kpi-value { color: var(--yellow-500); }

        .kpi-item.blue { background: var(--slate-50); border-color: var(--slate-100); } /* Blue 50 */
        .kpi-item.green { background: var(--sage-50); border-color: var(--sage-100); } /* Emerald 50 */
        .kpi-item.amber { background: var(--yellow-50); border-color: var(--yellow-100); } /* Amber 50 */

        /* Tabelas */
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 9.5pt; }
        th { 
            text-align: left; 
            background: var(--slate-50); 
            color: var(--slate-500); 
            padding: 12px 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 8pt; 
            border-bottom: 2px solid var(--slate-200);
            border-top: 1px solid var(--slate-200);
        }
        th:first-child { border-left: 1px solid var(--slate-200); border-top-left-radius: 8px; }
        th:last-child { border-right: 1px solid var(--slate-200); border-top-right-radius: 8px; }

        td { 
            border-bottom: 1px solid var(--slate-100); 
            padding: 10px 10px; 
            vertical-align: middle; 
            color: var(--slate-700);
        }
        tr:last-child td { border-bottom: none; }
        
        /* Linhas pares mais sutis */
        tr:nth-child(even) td { background: #fcfcfc; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-future { background: var(--slate-50); color: var(--slate-600); border: 1px solid var(--slate-100); }
        .status-past { background: var(--slate-50); color: var(--slate-500); border: 1px solid var(--slate-200); }

        /* Footer */
        .footer {
            margin-top: 50px;
            padding: 20px;
            border-top: 1px solid var(--slate-200);
            text-align: center;
            font-size: 8pt;
            color: var(--slate-400);
        }

        /* Para Tela (Botão Imprimir) */
        .no-print {
            position: fixed; top: 20px; right: 20px;
            background: var(--slate-600); color: white;
            padding: 12px 24px; border-radius: 8px;
            text-decoration: none; font-weight: bold;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.2s;
        }
        .no-print:hover { background: var(--slate-700); }

        @media print { 
            @page { margin: 1cm; }
            body { margin: 0; padding: 0; }
            .no-print { display: none; } 
            .header { background: var(--slate-50) !important; -webkit-print-color-adjust: exact; }
            .kpi-item { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <a href="#" onclick="window.print()" class="no-print">🖨️ IMPRIMIR</a>

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>Relatório de Gestão</h1>
            <div class="subtitle">Departamento de Louvor</div>
        </div>
        <div class="meta">
            <div><strong>Período:</strong> <?= $titlePeriod ?></div>
            <div>Gerado em: <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- KPI SUMMARY -->
    <div class="kpi-row">
        <div class="kpi-item blue">
            <div class="kpi-value"><?= $totalEscalas ?></div>
            <div class="kpi-label">Total de Escalas</div>
        </div>
        <div class="kpi-item green">
            <div class="kpi-value"><?= $taxaConfirmacao ?>%</div>
            <div class="kpi-label">Taxa de Confirmação</div>
        </div>
        <div class="kpi-item amber">
            <div class="kpi-value"><?= count($schedules) ?></div>
            <div class="kpi-label">Eventos Listados</div>
        </div>
    </div>

    <div style="display: flex; gap: 40px;">
        
        <!-- RANKING -->
        <div class="section" style="flex: 1;">
            <div class="section-title">Participação (Top 10)</div>
            <table>
                <thead>
                    <tr>
                        <th width="30">Pos</th>
                        <th>Membro</th>
                        <th style="text-align: right;">Escalas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $limit = 0;
                    foreach($rankingParticipacao as $i => $m): 
                        if($limit++ >= 10) break;
                    ?>
                    <tr>
                        <td><b><?= $i+1 ?>º</b></td>
                        <td>
                            <?= htmlspecialchars($m['name']) ?>
                            <div style="font-size: 8pt; color: #666;"><?= htmlspecialchars($m['instrument']) ?></div>
                        </td>
                        <td style="text-align: right;"><b><?= $m['participacoes'] ?></b></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- AUSÊNCIAS -->
        <div class="section" style="flex: 1;">
            <div class="section-title">Indisponibilidades</div>
            <table>
                <thead>
                    <tr>
                        <th>Membro</th>
                        <th style="text-align: right;">Registros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $limit = 0;
                    foreach($topAbsences as $a): 
                        if($limit++ >= 10) break;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($a['name']) ?></td>
                        <td style="text-align: right;"><?= $a['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- LISTAGEM COMPLETA -->
    <div class="section">
        <div class="section-title">Detalhamento de Escalas</div>
        <table>
            <thead>
                <tr>
                    <th width="80">Data</th>
                    <th width="50">Hora</th>
                    <th>Evento</th>
                    <th>Equipe</th>
                    <th>Músicas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($schedules as $s): 
                     $d = new DateTime($s['event_date']);
                     $isFuture = $d >= new DateTime('today');
                ?>
                <tr>
                    <td><?= $d->format('d/m/Y') ?></td>
                    <td><?= substr($s['event_time'],0,5) ?></td>
                    <td><b><?= htmlspecialchars($s['event_type']) ?></b></td>
                    <td><?= $s['total_users'] ?> membros</td>
                    <td><?= $s['total_songs'] ?> canções</td>
                    <td>
                        <span class="status-badge <?= $isFuture ? 'status-future' : 'status-past' ?>">
                            <?= $isFuture ? 'Agendada' : 'Realizada' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Relatório gerado automaticamente pelo App Louvor PIB Oliveira. Uso interno.
    </div>

</body>
</html>
