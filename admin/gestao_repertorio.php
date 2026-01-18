<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: repertorio.php");
    exit;
}

$scale_id = $_GET['id'];

// 1. Garantir que exita um registro na tabela 'repertories' ligado a essa scale
$stmt = $pdo->prepare("SELECT * FROM repertories WHERE scale_id = ?");
$stmt->execute([$scale_id]);
$repertory = $stmt->fetch();

if (!$repertory) {
    // Cria automaticamente
    $stmt = $pdo->prepare("INSERT INTO repertories (scale_id, planner_id) VALUES (?, ?)");
    $stmt->execute([$scale_id, $_SESSION['user_id']]);
    $repertory_id = $pdo->lastInsertId();
} else {
    $repertory_id = $repertory['id'];
}

// 2. Buscar Info da Escala (Para o Título)
$stmt = $pdo->prepare("SELECT * FROM scales WHERE id = ?");
$stmt->execute([$scale_id]);
$scale = $stmt->fetch();


// 3. Adicionar Música
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_song'])) {
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $key_note = $_POST['key_note'];
    $link_cifra = $_POST['link_cifra'];
    $link_youtube = $_POST['link_youtube'];

    // Calcular Ordem (Simples, pega o ultimo + 1)
    $stmt = $pdo->prepare("SELECT MAX(order_index) FROM songs WHERE repertory_id = ?");
    $stmt->execute([$repertory_id]);
    $max_order = $stmt->fetchColumn();
    $order = $max_order ? $max_order + 1 : 1;

    $stmt = $pdo->prepare("INSERT INTO songs (repertory_id, title, artist, key_note, link_cifra, link_youtube, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$repertory_id, $title, $artist, $key_note, $link_cifra, $link_youtube, $order]);

    header("Location: gestao_repertorio.php?id=$scale_id");
    exit;
}

// 4. Remover Música
if (isset($_GET['remove_song'])) {
    $song_id = $_GET['remove_song'];
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ? AND repertory_id = ?");
    $stmt->execute([$song_id, $repertory_id]);
    header("Location: gestao_repertorio.php?id=$scale_id");
    exit;
}

// 5. Buscar Músicas
$stmt = $pdo->prepare("SELECT * FROM songs WHERE repertory_id = ? ORDER BY order_index ASC");
$stmt->execute([$repertory_id]);
$songs = $stmt->fetchAll();

renderAppHeader('Repertório: ' . date('d/m', strtotime($scale['event_date'])));
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">
    <a href="repertorio.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar</a>

    <h1 class="page-title" style="margin-top: 10px;">
        Repertório: <?= date('d/m', strtotime($scale['event_date'])) ?>
    </h1>

    <!-- Lista -->
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 20px;">Músicas Selecionadas</h3>

        <?php if (empty($songs)): ?>
            <p style="opacity: 0.6; text-align: center;">Nenhuma música adicionada.</p>
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
                            <div class="flex gap-2">
                                <span class="status-badge status-pending" style="color: #fff; border: 1px solid #555;"><?= htmlspecialchars($song['key_note'] ?: '-') ?></span>
                                <a href="?id=<?= $scale_id ?>&remove_song=<?= $song['id'] ?>" style="color: #ef5350; text-decoration: none; margin-left: 10px;">&times;</a>
                            </div>
                        </div>

                        <!-- Links -->
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

    <!-- Adicionar Música -->
    <div class="card">
        <h3>Adicionar Música</h3>
        <form method="POST" style="margin-top: 15px;">
            <input type="hidden" name="add_song" value="1">

            <div class="form-group">
                <label class="form-label">Nome da Música</label>
                <input type="text" name="title" class="form-input" required placeholder="Ex: Bondade de Deus">
            </div>

            <div class="flex gap-4">
                <div style="flex: 2;">
                    <label class="form-label">Artista / Versão</label>
                    <input type="text" name="artist" class="form-input" placeholder="Ex: Isaías Saad">
                </div>
                <div style="flex: 1;">
                    <label class="form-label">Tom</label>
                    <input type="text" name="key_note" class="form-input" placeholder="G">
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Link da Cifra (Opcional)</label>
                <input type="url" name="link_cifra" class="form-input" placeholder="https://cifraclub.com...">
            </div>

            <div class="form-group">
                <label class="form-label">Link do YouTube (Opcional)</label>
                <input type="url" name="link_youtube" class="form-input" placeholder="https://youtube.com...">
            </div>

            <button type="submit" class="btn btn-primary w-full">Adicionar ao Repertório</button>
        </form>
    </div>

</div>

<?php
renderAppFooter();
?>