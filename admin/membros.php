<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

// Buscar todos os usuários
$stmt = $pdo->query("SELECT * FROM users ORDER BY name ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Membros - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" style="display: block; margin-top: 20px; color: var(--text-secondary); text-decoration: none;">&larr; Voltar para Dashboard</a>

        <div class="flex justify-between items-center" style="margin-top: 20px;">
            <h1 class="page-title" style="margin: 0;">Membros Cadastrados</h1>
            <!-- Futuro: Botão Cadastrar Novo -->
        </div>

        <div class="card" style="margin-top: 20px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; text-align: left;">
                        <th style="padding: 10px;">Nome</th>
                        <th style="padding: 10px;">Função Principal</th>
                        <th style="padding: 10px;">Telefone</th>
                        <th style="padding: 10px;">Nível</th>
                        <th style="padding: 10px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #222;">
                            <td style="padding: 15px 10px;">
                                <div class="flex items-center gap-2">
                                    <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                        <?php if (!empty($u['avatar'])): ?>
                                            <img src="../assets/uploads/<?= htmlspecialchars($u['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                                </div>
                            </td>
                            <td style="padding: 10px; color: var(--text-secondary);"><?= ucfirst(str_replace('_', ' ', $u['category'])) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($u['phone']) ?></td>
                            <td style="padding: 10px;">
                                <span class="status-badge <?= $u['role'] === 'admin' ? 'status-confirmed' : 'status-pending' ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: right;">
                                <a href="editar_membro.php?id=<?= $u['id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>