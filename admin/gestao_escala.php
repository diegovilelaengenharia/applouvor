<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: escala.php");
    exit;
}

$scale_id = $_GET['id'];

// Buscar Dados da Escala
$stmt = $pdo->prepare("SELECT * FROM scales WHERE id = ?");
$stmt->execute([$scale_id]);
$scale = $stmt->fetch();

if (!$scale) {
    die("Escala não encontrada.");
}

// Adicionar Membro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $user_id = $_POST['user_id'];
    $instrument = $_POST['instrument']; // Se não selecionado, usa a categoria padrão do user? Vamos forçar selection ou pegar do banco.

    // Verificar se já está na escala
    $check = $pdo->prepare("SELECT id FROM scale_members WHERE scale_id = ? AND user_id = ?");
    $check->execute([$scale_id, $user_id]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO scale_members (scale_id, user_id, instrument, confirmed) VALUES (?, ?, ?, 0)");
        $stmt->execute([$scale_id, $user_id, $instrument]);
    }

    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Remover Membro
if (isset($_GET['remove'])) {
    $member_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM scale_members WHERE id = ? AND scale_id = ?");
    $stmt->execute([$member_id, $scale_id]);
    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Buscar Membros Escalados
$stmt = $pdo->prepare("
    SELECT sm.*, u.name, u.category 
    FROM scale_members sm 
    JOIN users u ON sm.user_id = u.id 
    WHERE sm.scale_id = ?
    ORDER BY u.category, u.name
");
$stmt->execute([$scale_id]);
$members = $stmt->fetchAll();

// Buscar Todos Usuários para o Select (Agrupados ou Lista Simples)
$users = $pdo->query("SELECT * FROM users ORDER BY name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Equipe - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="escala.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar para Escalas</a>

        <h1 class="page-title" style="margin-top: 10px;">
            <?= date('d/m', strtotime($scale['event_date'])) ?> - <?= htmlspecialchars($scale['event_type']) ?>
        </h1>

        <!-- Lista de Escalados -->
        <div class="card" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">Equipe Escalada</h3>

            <?php if (empty($members)): ?>
                <p style="opacity: 0.6;">Ninguém escalado ainda.</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($members as $member): ?>
                        <div class="list-item" style="padding: 10px 15px;">
                            <div class="flex items-center gap-4">
                                <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($member['name']) ?></strong>
                                    <span style="font-size: 0.8rem; opacity: 0.7;"> • <?= htmlspecialchars($member['instrument']) ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <?php
                                $statusClass = 'status-pending';
                                $statusText = 'Pendente';
                                if ($member['confirmed'] == 1) {
                                    $statusClass = 'status-confirmed';
                                    $statusText = 'Confirmado';
                                }
                                if ($member['confirmed'] == 2) {
                                    $statusClass = 'status-refused';
                                    $statusText = 'Recusou';
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                <a href="?id=<?= $scale_id ?>&remove=<?= $member['id'] ?>" style="color: #ef5350; text-decoration: none; font-size: 1.2rem;">&times;</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Adicionar Novo Membro -->
        <div class="card">
            <h3>Adicionar Integrante</h3>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="add_member" value="1">
                <div class="flex gap-4" style="flex-wrap: wrap;">
                    <div style="flex: 2; min-width: 200px;">
                        <label class="form-label">Músico</label>
                        <select name="user_id" class="form-input" required id="userSelect" onchange="updateInstrument()">
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" data-category="<?= $u['category'] ?>">
                                    <?= htmlspecialchars($u['name']) ?> (<?= ucfirst(str_replace('_', ' ', $u['category'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label class="form-label">Instrumento/Função</label>
                        <input type="text" name="instrument" id="instrumentInput" class="form-input" placeholder="Ex: Voz, Violão" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full" style="margin-top: 20px;">Adicionar à Escala</button>
            </form>
        </div>

    </div>

    <script>
        function updateInstrument() {
            const select = document.getElementById('userSelect');
            const input = document.getElementById('instrumentInput');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.value) {
                let category = selectedOption.getAttribute('data-category');
                // Formatar categoria para texto bonito
                if (category === 'voz_feminina' || category === 'voz_masculina') category = 'Voz';
                if (category === 'violao') category = 'Violão';
                if (category === 'teclado') category = 'Teclado';
                if (category === 'bateria') category = 'Bateria';

                input.value = category.charAt(0).toUpperCase() + category.slice(1);
            }
        }
    </script>

</body>

</html>