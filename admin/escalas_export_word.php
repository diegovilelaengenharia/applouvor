<?php
// admin/escalas_export_word.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

checkAdmin();

// --- Filtros (Mesma lógica de gestao) ---
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

// --- QUERIES ---

// 1. Estatísticas Gerais
$stmtTaxa = $pdo->prepare("SELECT ROUND((COUNT(CASE WHEN su.status = 'confirmed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) FROM schedule_users su JOIN schedules s ON su.schedule_id = s.id WHERE s.event_date BETWEEN ? AND ?");
$stmtTaxa->execute([$startDate, $endDate]);
$taxaConfirmacao = $stmtTaxa->fetchColumn() ?: 0;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE event_date BETWEEN ? AND ?");
$stmtCount->execute([$startDate, $endDate]);
$totalEscalas = $stmtCount->fetchColumn();

// 2. Ranking Participação
$stmtMembers = $pdo->prepare("SELECT u.name, u.instrument, COUNT(su.schedule_id) as participacoes FROM users u JOIN schedule_users su ON u.id = su.user_id JOIN schedules s ON su.schedule_id = s.id WHERE s.event_date BETWEEN ? AND ? GROUP BY u.id ORDER BY participacoes DESC LIMIT 20");
$stmtMembers->execute([$startDate, $endDate]);
$rankingParticipacao = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// 3. Ausências (Top)
$stmtAbsences = $pdo->prepare("SELECT u.name, COUNT(*) as count FROM user_unavailability ua JOIN users u ON ua.user_id = u.id WHERE (ua.start_date BETWEEN ? AND ?) GROUP BY u.id ORDER BY count DESC LIMIT 10");
$stmtAbsences->execute([$startDate, $endDate]);
$topAbsences = $stmtAbsences->fetchAll(PDO::FETCH_ASSOC);

// 4. Listagem
$stmtSchedules = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM schedule_users WHERE schedule_id = s.id) as total_users, (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs FROM schedules s WHERE s.event_date BETWEEN ? AND ? ORDER BY s.event_date ASC");
$stmtSchedules->execute([$startDate, $endDate]);
$schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);


// --- HEADERS WORD ---
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename=Relatorio_Escalas_{$period}_{$year}.doc");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

</head>
<body>

    <h1 style="text-align: center;">Relatório de Gestão de Escalas</h1>
    <p style="text-align: center;"><strong>Período:</strong> <?= $titlePeriod ?></p>
    <p style="text-align: center;">Gerado em: <?= date('d/m/Y H:i') ?></p>

    <!-- KPIs -->
    <h2>Resumo Geral</h2>
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; text-align: center;">
                <div style="font-size: 24pt; font-weight: bold; color: var(--slate-500);"><?= $totalEscalas ?></div>
                <div>Total de Escalas</div>
            </td>
            <td style="border: none; text-align: center;">
                <div style="font-size: 24pt; font-weight: bold; color: #10b981;"><?= $taxaConfirmacao ?>%</div>
                <div>Taxa de Confirmação</div>
            </td>
        </tr>
    </table>

    <!-- RANKING -->
    <h2>Ranking de Participação (Top 20)</h2>
    <table>
        <thead>
            <tr>
                <th width="10%">Pos</th>
                <th>Nome</th>
                <th>Instrumento</th>
                <th width="15%">Escalas</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rankingParticipacao as $i => $m): ?>
            <tr>
                <td><?= $i+1 ?>º</td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= htmlspecialchars($m['instrument']) ?></td>
                <td><?= $m['participacoes'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- AUSÊNCIAS -->
    <h2>Membros Mais Indisponíveis (Top 10)</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Registros de Ausência</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($topAbsences as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= $a['count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- LISTAGEM DETALHADA -->
    <h2>Listagem de Escalas do Período</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Evento</th>
                <th>Horário</th>
                <th>Equipe</th>
                <th>Músicas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($schedules as $s): 
                 $d = new DateTime($s['event_date']);
                 $status = ($d < new DateTime('today')) ? 'Realizada' : 'Agendada';
            ?>
            <tr>
                <td><?= $d->format('d/m/Y') ?></td>
                <td><?= htmlspecialchars($s['event_type']) ?></td>
                <td><?= substr($s['event_time'],0,5) ?></td>
                <td><?= $s['total_users'] ?></td>
                <td><?= $s['total_songs'] ?></td>
                <td><?= $status ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Departamento de Louvor - PIB Oliveira</p>
    </div>

</body>
</html>
