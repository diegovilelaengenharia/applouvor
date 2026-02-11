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
