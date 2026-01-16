<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkLogin();
checkAdmin(); // Garante que só admin acessa

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address_street = trim($_POST['address_street']);
    $address_number = trim($_POST['address_number']);
    $address_neighborhood = trim($_POST['address_neighborhood']);

    // Upload de Avatar
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $filename = uniqid('avatar_') . '.' . $ext;
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
                $avatar_path = $filename;
            } else {
                $error = "Erro ao salvar imagem.";
            }
        } else {
            $error = "Formato de imagem inválido (use jpg, png).";
        }
    }

    if (!$error) {
        // Atualizar DB
        $sql = "UPDATE users SET name = ?, address_street = ?, address_number = ?, address_neighborhood = ?";
        $params = [$name, $address_street, $address_number, $address_neighborhood];

        if ($avatar_path) {
            $sql .= ", avatar = ?";
            $params[] = $avatar_path;
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $success = "Dados atualizados com sucesso!";
            $_SESSION['user_name'] = $name; // Atualizar sessão se mudou nome
        } else {
            $error = "Erro ao atualizar perfil.";
        }
    }
}

// Buscar Dados Atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Admin - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar para Dashboard</a>
        <h1 class="page-title">Meu Perfil (Admin)</h1>

        <?php if ($success): ?>
            <div class="card" style="background-color: rgba(46, 125, 50, 0.1); border-color: var(--success-color); color: var(--success-color); margin-bottom: 20px;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="card" style="background-color: rgba(198, 40, 40, 0.1); border-color: var(--error-color); color: var(--error-color); margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">

                <!-- Avatar -->
                <div class="flex flex-col items-center justify-center" style="margin-bottom: 30px;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: #333; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; border: 3px solid var(--accent-color);">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 2.5rem; color: #fff;"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <label for="avatar" class="btn btn-outline" style="font-size: 0.8rem; cursor: pointer;">Alterar Foto</label>
                    <input type="file" id="avatar" name="avatar" style="display: none;" accept="image/*" onchange="document.querySelector('form').submit()">
                </div>

                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefone (Somente leitura)</label>
                    <input type="text" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>" disabled style="opacity: 0.7; background-color: #eee; border-color: transparent;">
                </div>

                <h3 style="margin: 30px 0 15px; font-size: 1.1rem; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Endereço</h3>

                <div class="form-group">
                    <label class="form-label">Rua / Logradouro</label>
                    <input type="text" name="address_street" class="form-input" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>">
                </div>

                <div class="flex gap-4">
                    <div style="flex: 1;">
                        <label class="form-label">Número</label>
                        <input type="text" name="address_number" class="form-input" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>">
                    </div>
                    <div style="flex: 2;">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="address_neighborhood" class="form-input" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="margin-top: 30px;">Salvar Alterações</button>

            </form>
        </div>
    </div>

</body>

</html>