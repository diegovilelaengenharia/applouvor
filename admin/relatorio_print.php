<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if admin
checkAdmin();

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t', strtotime('+1 month'));

// Fetch Schedules in Range
$stmt = $pdo->prepare("
    SELECT * FROM schedules 
    WHERE event_date BETWEEN ? AND ? 
    ORDER BY event_date ASC
");
$stmt->execute([$start_date, $end_date]);
$schedules = $stmt->fetchAll();

// Fetch Members for these schedules
$scheduleIds = array_column($schedules, 'id');
$membersMap = [];

if (!empty($scheduleIds)) {
    $inQuery = implode(',', array_fill(0, count($scheduleIds), '?'));
    $stmtMembers = $pdo->prepare("
        SELECT su.*, u.name, u.instrument 
        FROM schedule_users su
        JOIN users u ON su.user_id = u.id
        WHERE su.schedule_id IN ($inQuery)
        ORDER BY FIELD(u.instrument, 'Voz', 'Teclado', 'Violão', 'Guitarra', 'Baixo', 'Bateria', 'Outros'), u.name
    ");
    $stmtMembers->execute($scheduleIds);
    $allMembers = $stmtMembers->fetchAll();

    foreach ($allMembers as $m) {
        $membersMap[$m['schedule_id']][] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Escalas</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.4;
            max-width: 210mm;
            /* A4 width */
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        .scale-item {
            margin-bottom: 30px;
            page-break-inside: avoid;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .scale-header {
            background: #f9f9f9;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .scale-date {
            font-weight: bold;
            font-size: 16px;
            color: #000;
        }

        .scale-type {
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .scale-body {
            padding: 15px;
        }

        .team-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .member-badge {
            background: #fff;
            border: 1px solid #ccc;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            display: inline-block;
        }

        .member-role {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            margin-left: 5px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .scale-item {
                border: 1px solid #ccc;
            }
        }

        .btn-print {
            background: var(--slate-600);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            transition: transform 0.2s, background 0.2s;
        }

        .btn-print:hover {
            background: var(--slate-700);
            transform: translateY(-2px);
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 30px; text-align: right; background: var(--slate-50); padding: 15px; border-radius: 8px; border: 1px dashed var(--slate-300); display:flex; align-items:center; justify-content:space-between;">
        <div style="text-align: left; color: var(--slate-500); font-size: 14px;">
            <strong>Dica:</strong> Ajuste as margens na visualização de impressão se necessário.
        </div>
        <button onclick="window.print()" class="btn-print">
            <span>🖨️ Imprimir agora</span>
        </button>
    </div>

    <div class="header">
        <h1>Relatório de Escalas</h1>
        <p>Período: <?= date('d/m/Y', strtotime($start_date)) ?> a <?= date('d/m/Y', strtotime($end_date)) ?></p>
        <p>Ministério de Louvor PIB Oliveira</p>
    </div>

    <?php if (empty($schedules)): ?>
        <p style="text-align: center; color: #666;">Nenhuma escala encontrada neste período.</p>
    <?php else: ?>
        <?php foreach ($schedules as $schedule):
            $team = $membersMap[$schedule['id']] ?? [];
        ?>
            <div class="scale-item">
                <div class="scale-header">
                    <div class="scale-date">
                        <?= date('d/m/Y', strtotime($schedule['event_date'])) ?>
                        <span style="font-weight: normal; margin-left: 10px; font-size: 14px; color: #555;">(<?= strftime('%A', strtotime($schedule['event_date'])) ?>)</span>
                    </div>
                    <div class="scale-type"><?= htmlspecialchars($schedule['event_type']) ?></div>
                </div>
                <div class="scale-body">
                    <?php if (!empty($schedule['notes'])): ?>
                        <div style="margin-bottom: 10px; font-style: italic; color: #666; font-size: 13px;">
                            <?= htmlspecialchars($schedule['notes']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($team)): ?>
                        <div style="color: #999; font-size: 13px;">Ninguém escalado.</div>
                    <?php else: ?>
                        <div class="team-grid">
                            <?php foreach ($team as $m): ?>
                                <div class="member-badge">
                                    <?= htmlspecialchars($m['name']) ?>
                                    <span class="member-role"><?= htmlspecialchars($m['instrument']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>