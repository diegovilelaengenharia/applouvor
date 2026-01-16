<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// Ação de Confirmar/Recusar
if (isset($_GET['action']) && isset($_GET['scale_id'])) {
    $action = $_GET['action']; // confirm/decline
    $s_id = $_GET['scale_id'];

    $status = ($action === 'confirm') ? 1 : 2;

    $stmt = $pdo->prepare("UPDATE scale_members SET confirmed = ? WHERE scale_id = ? AND user_id = ?");
    $stmt->execute([$status, $s_id, $user_id]);

    header("Location: escala.php");
    exit;
}

// Buscar Minhas Escalas Futuras
$stmt = $pdo->prepare("
    SELECT s.*, sm.instrument, sm.confirmed, sm.id as member_id
    FROM scales s
    JOIN scale_members sm ON s.id = sm.scale_id
    WHERE sm.user_id = ? AND s.event_date >= CURDATE()
    ORDER BY s.event_date ASC
");
$stmt->execute([$user_id]);
$myScales = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Escalas - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar</a>
        <h1 class="page-title">Minhas Escalas</h1>

        <?php if (empty($myScales)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p>Uau! Você está de folga por enquanto.</p>
                <p style="font-size: 0.9rem; margin-top: 10px;">Aguarde o líder liberar a nova escala.</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($myScales as $scale):
                    $date = new DateTime($scale['event_date']);

                    // Status Badge
                    $statusBadge = '<span class="status-badge status-pending">Pendente</span>';
                    if ($scale['confirmed'] == 1) $statusBadge = '<span class="status-badge status-confirmed">Confirmado</span>';
                    if ($scale['confirmed'] == 2) $statusBadge = '<span class="status-badge status-refused">Recusado</span>';
                ?>
                    <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 20px;">
                        <div style="background: #252525; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="flex items-center gap-3">
                                <div style="text-align: center; background: #333; padding: 5px 10px; border-radius: 6px;">
                                    <span style="display: block; font-size: 0.7rem; text-transform: uppercase;"><?= $date->format('M') ?></span>
                                    <span style="display: block; font-size: 1.2rem; font-weight: bold;"><?= $date->format('d') ?></span>
                                </div>
                                <div>
                                    <h3 style="font-size: 1rem; margin: 0;"><?= htmlspecialchars($scale['event_type']) ?></h3>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Função: <?= htmlspecialchars($scale['instrument']) ?></span>
                                </div>
                            </div>
                            <?= $statusBadge ?>
                        </div>

                        <div style="padding: 15px;">
                            <p style="font-size: 0.9rem; margin-bottom: 15px;"><?= htmlspecialchars($scale['description']) ?: 'Sem observações.' ?></p>

                            <!-- Botões de Ação -->
                            <?php if ($scale['confirmed'] == 0): ?>
                                <div class="flex gap-2">
                                    <a href="?action=confirm&scale_id=<?= $scale['id'] ?>" class="btn w-full" style="background-color: var(--success-color); color: white;">Confirmar</a>
                                    <a href="?action=decline&scale_id=<?= $scale['id'] ?>" class="btn btn-outline w-full" style="border-color: var(--error-color); color: var(--error-color);">Recusar</a>
                                </div>
                            <?php elseif ($scale['confirmed'] == 1): ?>
                                <div class="text-center">
                                    <p style="color: var(--success-color); font-weight: bold;">Obrigado por confirmar! 🙌</p>
                                    <a href="?action=decline&scale_id=<?= $scale['id'] ?>" style="font-size: 0.8rem; color: #777; margin-top: 5px; display: block;">Mudei de ideia, não poderei ir.</a>
                                </div>
                            <?php else: ?>
                                <div class="text-center">
                                    <p style="color: var(--error-color);">Você sinalizou que não poderá ir.</p>
                                    <a href="?action=confirm&scale_id=<?= $scale['id'] ?>" style="font-size: 0.8rem; color: #777; margin-top: 5px; display: block;">Reconsiderar e confirmar.</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>