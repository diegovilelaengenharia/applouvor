<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

// Processar Nova Escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_scale'])) {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO scales (event_date, event_type, description) VALUES (?, ?, ?)");
    $stmt->execute([$date, $type, $description]);

    header("Location: escala.php?success=created");
    exit;
}

// Buscar Escalas
$stmt = $pdo->query("SELECT * FROM scales ORDER BY event_date ASC");
$scales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Escalas - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="flex justify-between items-center" style="margin-top: 30px;">
            <h1 class="page-title" style="margin: 0;">Escalas</h1>
            <button onclick="document.getElementById('newScaleModal').style.display='block'" class="btn btn-primary">
                + Nova Escala
            </button>
        </div>

        <p style="margin-top: 10px; margin-bottom: 30px;">Gerencie as datas e quem vai ministrar.</p>

        <!-- Lista de Escalas -->
        <div class="list-group">
            <?php foreach ($scales as $scale):
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
                    <a href="gestao_escala.php?id=<?= $scale['id'] ?>" class="btn btn-outline" style="font-size: 0.9rem;">
                        Gerenciar Equipe
                    </a>
                </div>
            <?php endforeach; ?>

            <?php if (empty($scales)): ?>
                <div class="card text-center" style="padding: 40px;">
                    <p>Nenhuma escala cadastrada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Nova Escala -->
    <div id="newScaleModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 90%; max-width: 500px; margin: 100px auto; position: relative;">
            <h2 style="margin-bottom: 20px;">Nova Escala</h2>
            <form method="POST">
                <input type="hidden" name="create_scale" value="1">
                <div class="form-group">
                    <label class="form-label">Data</label>
                    <input type="date" name="date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Evento</label>
                    <select name="type" class="form-input">
                        <option value="Culto de Domingo Manhã">Culto de Domingo (Manhã)</option>
                        <option value="Culto de Domingo Noite">Culto de Domingo (Noite)</option>
                        <option value="Ensaio Geral">Ensaio Geral</option>
                        <option value="Evento Especial">Evento Especial</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Observação (Opcional)</label>
                    <input type="text" name="description" class="form-input" placeholder="Ex: Ceia do Senhor">
                </div>
                <div class="flex gap-4" style="margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('newScaleModal').style.display='none'" class="btn btn-outline w-full">Cancelar</button>
                    <button type="submit" class="btn btn-primary w-full">Criar</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>