<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

// Buscar Próximos Eventos com Repertório (Ligado à escala)
$stmt = $pdo->query("SELECT * FROM scales WHERE event_date >= CURDATE() ORDER BY event_date ASC");
$upcoming = $stmt->fetchAll();

// Buscar Histórico
$stmt = $pdo->query("SELECT * FROM scales WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 10");
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Repertórios - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 class="page-title">Repertórios</h1>

        <h3 style="margin-bottom: 15px;">Próximos Cultos</h3>
        <?php if (empty($upcoming)): ?>
            <p>Nenhuma escala futura agendada. Crie uma escala primeiro.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($upcoming as $scale):
                    $date = new DateTime($scale['event_date']);
                ?>
                    <div class="list-item">
                        <div class="flex items-center gap-4">
                            <div style="text-align: center; background: #333; padding: 10px; border-radius: 8px; min-width: 60px;">
                                <span style="display: block; font-size: 0.8rem; text-transform: uppercase;"><?= $date->format('M') ?></span>
                                <span style="display: block; font-size: 1.5rem; font-weight: bold;"><?= $date->format('d') ?></span>
                            </div>
                            <div>
                                <h3 style="font-size: 1.1rem;"><?= htmlspecialchars($scale['event_type']) ?></h3>
                                <p style="font-size: 0.9rem;"><?= htmlspecialchars($scale['description']) ?></p>
                            </div>
                        </div>
                        <a href="gestao_repertorio.php?id=<?= $scale['id'] ?>" class="btn btn-outline">
                            🎵 Montar Lista
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
            <h3 style="margin: 40px 0 15px;">Histórico</h3>
            <div class="list-group" style="opacity: 0.8;">
                <?php foreach ($history as $scale):
                    $date = new DateTime($scale['event_date']);
                ?>
                    <div class="list-item" style="border-color: #222;">
                        <div class="flex items-center gap-4">
                            <span style="font-weight: bold; color: #777;"><?= $date->format('d/m/Y') ?></span>
                            <span><?= htmlspecialchars($scale['event_type']) ?></span>
                        </div>
                        <a href="gestao_repertorio.php?id=<?= $scale['id'] ?>" class="btn btn-outline" style="font-size: 0.8rem; padding: 5px 10px;">
                            Ver
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>