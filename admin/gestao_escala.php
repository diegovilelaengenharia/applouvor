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

// Update Scale Event Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE scales SET event_date = ?, event_type = ?, description = ? WHERE id = ?");
    $stmt->execute([$date, $type, $description, $scale_id]);
    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Delete Scale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scale'])) {
    // Delete members first
    $pdo->prepare("DELETE FROM scale_members WHERE scale_id = ?")->execute([$scale_id]);
    // Delete scale
    $pdo->prepare("DELETE FROM scales WHERE id = ?")->execute([$scale_id]);
    header("Location: escala.php");
    exit;
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
        <div style="display: flex; align-items: center; gap: 10px; margin-top: 20px; margin-bottom: 20px;">
            <a href="escala.php" class="btn-icon" style="background: var(--bg-secondary); color: var(--text-primary); width: 36px; height: 36px;">
                <i data-lucide="arrow-left" style="width: 18px;"></i>
            </a>
            <h1 class="page-title" style="margin: 0; font-size: 1.3rem;">
                Gerenciar Escala
            </h1>
        </div>

        <!-- TABS -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-subtle);">
            <button onclick="showTab('evento')" id="btn-evento" class="tab-btn active">Evento</button>
            <button onclick="showTab('equipe')" id="btn-equipe" class="tab-btn">Equipe</button>
        </div>

        <!-- TAB EVENTO -->
        <div id="tab-evento">
            <div class="card">
                <h3>Detalhes do Evento</h3>
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="update_event" value="1">

                    <div class="form-group">
                        <label class="form-label">Data</label>
                        <input type="date" name="date" class="form-input" value="<?= $scale['event_date'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select">
                            <option value="Culto de Domingo" <?= $scale['event_type'] == 'Culto de Domingo' ? 'selected' : '' ?>>Culto de Domingo</option>
                            <option value="Ensaio" <?= $scale['event_type'] == 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                            <option value="Evento Jovens" <?= $scale['event_type'] == 'Evento Jovens' ? 'selected' : '' ?>>Evento Jovens</option>
                            <option value="Evento Especial" <?= $scale['event_type'] == 'Evento Especial' ? 'selected' : '' ?>>Evento Especial</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="description" class="form-input" value="<?= htmlspecialchars($scale['description'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-full" style="margin-top: 10px;">Salvar Alterações</button>
                </form>

                <!-- Área de Perigo -->
                <div style="margin-top: 40px; border-top: 1px solid var(--border-subtle); padding-top: 20px;">
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');">
                        <input type="hidden" name="delete_scale" value="1">
                        <button type="submit" class="btn w-full" style="background: var(--bg-secondary); color: var(--status-error); border: 1px solid var(--status-error);">Excluir Escala</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB EQUIPE -->
        <div id="tab-equipe" style="display: none;">

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

    </div>

    <style>
        .tab-btn {
            padding: 10px 20px;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .tab-btn.active {
            color: var(--accent-interactive);
            border-bottom-color: var(--accent-interactive);
        }
    </style>

    <script>
        function showTab(tab) {
            document.getElementById('tab-evento').style.display = 'none';
            document.getElementById('tab-equipe').style.display = 'none';

            document.getElementById('btn-evento').classList.remove('active');
            document.getElementById('btn-equipe').classList.remove('active');

            document.getElementById('tab-' + tab).style.display = 'block';
            document.getElementById('btn-' + tab).classList.add('active');
        }


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