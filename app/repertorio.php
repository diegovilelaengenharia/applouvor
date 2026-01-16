<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkLogin();

// Se clicou em um repertório específico
$view_repertory = isset($_GET['id']) ? $_GET['id'] : null;

if ($view_repertory) {
    // Buscar Escala
    $stmt = $pdo->prepare("SELECT * FROM scales WHERE id = ?");
    $stmt->execute([$view_repertory]);
    $scale = $stmt->fetch();

    // Buscar ID do Repertório ou Criar se não existir (Participante pode iniciar a lista?)
    // Vamos supor que só admin cria ou se já existir. Se não existir, mostra vazio mas permite adicionar.
    $stmt = $pdo->prepare("SELECT * FROM repertories WHERE scale_id = ?");
    $stmt->execute([$view_repertory]);
    $repertory = $stmt->fetch();

    if (!$repertory) {
        $stmt = $pdo->prepare("INSERT INTO repertories (scale_id, planner_id) VALUES (?, ?)");
        $stmt->execute([$view_repertory, $_SESSION['user_id']]);
        $repertory_id = $pdo->lastInsertId();
    } else {
        $repertory_id = $repertory['id'];
    }

    // Adicionar Música (Sugestão)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggest_song'])) {
        $title = $_POST['title'];
        $artist = $_POST['artist'];

        $stmt = $pdo->prepare("INSERT INTO songs (repertory_id, title, artist, order_index) VALUES (?, ?, ?, 99)"); // 99 fim da fila
        $stmt->execute([$repertory_id, $title, $artist]);

        header("Location: repertorio.php?id=$view_repertory&success=1");
        exit;
    }

    // Buscar Músicas
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE repertory_id = ? ORDER BY order_index ASC");
    $stmt->execute([$repertory_id]);
    $songs = $stmt->fetchAll();
} else {
    // Listar Próximos Eventos
    $stmt = $pdo->query("SELECT * FROM scales WHERE event_date >= CURDATE() ORDER BY event_date ASC");
    $upcoming = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repertórios - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">

        <?php if ($view_repertory): ?>
            <a href="repertorio.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar para Lista</a>
            <h1 class="page-title">
                <?= date('d/m', strtotime($scale['event_date'])) ?> - <?= htmlspecialchars($scale['event_type']) ?>
            </h1>

            <!-- Lista de Músicas -->
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px;">Músicas</h3>

                <?php if (empty($songs)): ?>
                    <p style="opacity: 0.6; text-align: center; padding: 20px;">Nenhuma música definida ainda. Sugira uma!</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($songs as $index => $song): ?>
                            <div class="list-item" style="flex-direction: column; align-items: flex-start;">
                                <div class="flex justify-between w-full">
                                    <div class="flex gap-3 items-center">
                                        <span style="background: #333; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;"><?= $index + 1 ?></span>
                                        <div>
                                            <strong style="font-size: 1.1rem;"><?= htmlspecialchars($song['title']) ?></strong>
                                            <span style="font-size: 0.9rem; color: var(--text-secondary);"> - <?= htmlspecialchars($song['artist']) ?></span>
                                        </div>
                                    </div>
                                    <span class="status-badge status-pending" style="color: #fff; border: 1px solid #555;"><?= htmlspecialchars($song['key_note'] ?: '?') ?></span>
                                </div>
                                <div class="flex gap-2" style="margin-top: 10px; margin-left: 36px;">
                                    <?php if ($song['link_cifra']): ?>
                                        <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">Cifra</a>
                                    <?php endif; ?>
                                    <?php if ($song['link_youtube']): ?>
                                        <a href="<?= htmlspecialchars($song['link_youtube']) ?>" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">YouTube</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sugerir Música -->
            <div class="card">
                <h3>Sugerir / Adicionar Música</h3>
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="suggest_song" value="1">
                    <div class="form-group">
                        <label class="form-label">Nome da Música</label>
                        <input type="text" name="title" class="form-input" required placeholder="Ex: Grande é o Senhor">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Artista</label>
                        <input type="text" name="artist" class="form-input" placeholder="Ex: Adhemar de Campos">
                    </div>
                    <!-- Nota: Links deixei pro admin para simplificar mobile, ou add depois -->
                    <button type="submit" class="btn btn-primary w-full">Adicionar na Lista</button>
                    <p style="font-size: 0.8rem; color: #777; margin-top: 10px; text-align: center;">Músicas sugeridas entram no final da lista.</p>
                </form>
            </div>

        <?php else: ?>
            <a href="index.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar</a>
            <h1 class="page-title">Repertórios da Semana</h1>
            <p style="margin-bottom: 20px;">Escolha uma data para ver ou sugerir músicas.</p>

            <?php if (empty($upcoming)): ?>
                <div class="card text-center">
                    <p>Nenhum evento próximo.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($upcoming as $scale):
                        $date = new DateTime($scale['event_date']);
                    ?>
                        <a href="?id=<?= $scale['id'] ?>" class="list-item" style="text-decoration: none; color: inherit;">
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
                            <span>&rsaquo;</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</body>

</html>