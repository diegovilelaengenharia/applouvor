<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: membros.php");
    exit;
}

$success = '';
$error = '';

// Buscar Usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    die("Usuário não encontrado.");
}

// Processar Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $category = $_POST['category'];
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    $address_street = trim($_POST['address_street']);
    $address_number = trim($_POST['address_number']);
    $address_neighborhood = trim($_POST['address_neighborhood']);

    // Atualizar Avatar (Opcional)
    // Logica simplificada se quiser alterar avatar dele também, mas foco é dados cadastrais

    // SQL Update
    $sql = "UPDATE users SET name = ?, role = ?, category = ?, phone = ?, address_street = ?, address_number = ?, address_neighborhood = ? WHERE id = ?";
    $params = [$name, $role, $category, $phone, $address_street, $address_number, $address_neighborhood, $id];

    // Se senha foi preenchida, atualiza
    if (!empty($password)) {
        $sql = "UPDATE users SET name = ?, role = ?, category = ?, phone = ?, address_street = ?, address_number = ?, address_neighborhood = ?, password = ? WHERE id = ?";
        $params = [$name, $role, $category, $phone, $address_street, $address_number, $address_neighborhood, $password, $id];
    }

    $stmtUpdate = $pdo->prepare($sql);
    if ($stmtUpdate->execute($params)) {
        $success = "Membro atualizado com sucesso!";
        // Recarregar dados
        $stmt->execute([$id]);
        $member = $stmt->fetch();
    } else {
        $error = "Erro ao atualizar membro.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Membro - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="membros.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar para Lista</a>
        <h1 class="page-title">Editar Membro: <?= htmlspecialchars($member['name']) ?></h1>

        <?php if ($success): ?>
            <div class="card" style="background-color: rgba(46, 125, 50, 0.1); border-color: var(--success-color); color: var(--success-color); margin-bottom: 20px;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">

                <h3 style="margin-bottom: 20px;">Dados de Acesso e Função</h3>

                <div class="flex gap-4" style="flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($member['name']) ?>" required>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label class="form-label">Senha (Deixe em branco p/ manter)</label>
                        <input type="text" name="password" class="form-input" placeholder="Nova Senha">
                    </div>
                </div>

                <div class="flex gap-4" style="flex-wrap: wrap; margin-top: 15px;">
                    <div style="flex: 1; min-width: 150px;">
                        <label class="form-label">Nível de Acesso</label>
                        <select name="role" class="form-input">
                            <option value="user" <?= $member['role'] === 'user' ? 'selected' : '' ?>>Membro (User)</option>
                            <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Líder (Admin)</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label class="form-label">Categoria Principal</label>
                        <select name="category" class="form-input">
                            <option value="voz_feminina" <?= $member['category'] === 'voz_feminina' ? 'selected' : '' ?>>Voz Feminina</option>
                            <option value="voz_masculina" <?= $member['category'] === 'voz_masculina' ? 'selected' : '' ?>>Voz Masculina</option>
                            <option value="violao" <?= $member['category'] === 'violao' ? 'selected' : '' ?>>Violão</option>
                            <option value="teclado" <?= $member['category'] === 'teclado' ? 'selected' : '' ?>>Teclado</option>
                            <option value="guitarra" <?= $member['category'] === 'guitarra' ? 'selected' : '' ?>>Guitarra</option>
                            <option value="baixo" <?= $member['category'] === 'baixo' ? 'selected' : '' ?>>Baixo</option>
                            <option value="bateria" <?= $member['category'] === 'bateria' ? 'selected' : '' ?>>Bateria</option>
                            <option value="outros" <?= $member['category'] === 'outros' ? 'selected' : '' ?>>Outros</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin: 30px 0 15px; font-size: 1.1rem; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Dados Pessoais</h3>

                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($member['phone']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Rua / Logradouro</label>
                    <input type="text" name="address_street" class="form-input" value="<?= htmlspecialchars($member['address_street'] ?? '') ?>">
                </div>

                <div class="flex gap-4">
                    <div style="flex: 1;">
                        <label class="form-label">Número</label>
                        <input type="text" name="address_number" class="form-input" value="<?= htmlspecialchars($member['address_number'] ?? '') ?>">
                    </div>
                    <div style="flex: 2;">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="address_neighborhood" class="form-input" value="<?= htmlspecialchars($member['address_neighborhood'] ?? '') ?>">
                    </div>
                </div>

                <div class="flex justify-between items-center" style="margin-top: 30px;">
                    <!-- Botão Excluir (Futuro) -->
                    <div style="font-size: 0.8rem; color: #666;">ID: <?= $member['id'] ?></div>

                    <button type="submit" class="btn btn-primary">Salvar Membero</button>
                </div>

            </form>
        </div>
    </div>

</body>

</html>