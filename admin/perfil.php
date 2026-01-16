<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Atualização de Dados Pessoais
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']); // Adicionado email
        $address_street = trim($_POST['address_street']);
        $address_number = trim($_POST['address_number']);
        $address_neighborhood = trim($_POST['address_neighborhood']);

        // Upload de Avatar
        $avatar_path = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $filename = uniqid('avatar_') . '.' . $ext;
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
                    $avatar_path = $filename;
                    $_SESSION['user_avatar'] = $filename; // Atualiza sessão na hora
                } else {
                    $error = "Erro ao salvar imagem.";
                }
            } else {
                $error = "Formato de imagem inválido.";
            }
        }

        if (!$error) {
            $sql = "UPDATE users SET name = ?, email = ?, address_street = ?, address_number = ?, address_neighborhood = ?";
            $params = [$name, $email, $address_street, $address_number, $address_neighborhood];

            if ($avatar_path) {
                $sql .= ", avatar = ?";
                $params[] = $avatar_path;
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "Perfil atualizado com sucesso!";
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
            } else {
                $error = "Erro ao atualizar dados.";
            }
        }
    }

    // 2. Alteração de Senha (Somente Admin)
    if (isset($_POST['change_password']) && $_SESSION['user_role'] === 'admin') {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            $error = "As novas senhas não coincidem.";
        } else {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $stored_pass = $stmt->fetchColumn();

            if (password_verify($current_pass, $stored_pass)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$new_hash, $user_id])) {
                    $success = "Senha alterada com segurança!";
                } else {
                    $error = "Erro ao alterar senha.";
                }
            } else {
                $error = "Senha atual incorreta.";
            }
        }
    }
}

// Buscar Dados Atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Inicia Layout
renderAppHeader('Meu Perfil');
?>

<div class="container" style="padding-top: 20px;">

    <!-- Card de Avatar -->
    <div class="card-clean" style="text-align: center; margin-bottom: 24px;">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_profile" value="1">

            <div class="profile-avatar-wrapper" style="position: relative; width: 100px; height: 100px; margin: 0 auto 16px;">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>"
                        style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-tertiary);">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--text-secondary); border: 4px solid var(--border-subtle);">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <label for="avatar_upload" style="position: absolute; bottom: 0; right: 0; background: var(--accent-interactive); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <i data-lucide="camera" style="width: 16px; height: 16px;"></i>
                </label>
                <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*" onchange="this.form.submit()">
            </div>

            <h2 style="font-size: 1.25rem; margin-bottom: 4px;"><?= htmlspecialchars($user['name']) ?></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= htmlspecialchars($user['email']) ?></p>
        </form>
    </div>

    <?php if ($success): ?>
        <div style="background: #DCFCE7; color: #166534; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; text-align: center;">
            <i data-lucide="check-circle" style="vertical-align: middle; width: 18px;"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #FEF2F2; color: #991B1B; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; text-align: center;">
            <i data-lucide="alert-circle" style="vertical-align: middle; width: 18px;"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Tabs (Alternativa Clean) -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
        <button onclick="showTab('info')" id="btn-info" class="btn-outline active" style="flex: 1; justify-content: center; background: var(--bg-secondary);">Dados Pessoais</button>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button onclick="showTab('security')" id="btn-security" class="btn-outline" style="flex: 1; justify-content: center;">Segurança</button>
        <?php endif; ?>
    </div>

    <!-- Tab Info -->
    <div id="tab-info">
        <form method="POST" class="card-clean">
            <input type="hidden" name="update_profile" value="1">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Telefone / WhatsApp</label>
                    <input type="text" value="<?= htmlspecialchars($user['phone']) ?>" disabled
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-tertiary); color: var(--text-muted); cursor: not-allowed;">
                </div>

                <div style="border-top: 1px solid var(--border-subtle); margin: 8px 0;"></div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Endereço</label>
                    <input type="text" name="address_street" placeholder="Rua..." value="<?= htmlspecialchars($user['address_street'] ?? '') ?>"
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none; margin-bottom: 10px;">

                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="address_number" placeholder="Nº" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>"
                            style="width: 80px; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">

                        <input type="text" name="address_neighborhood" placeholder="Bairro" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>"
                            style="flex: 1; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                    Salvar Dados
                </button>
            </div>
        </form>
    </div>

    <!-- Tab Security -->
    <div id="tab-security" style="display: none;">
        <form method="POST" class="card-clean">
            <input type="hidden" name="change_password" value="1">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="padding: 12px; background: var(--bg-tertiary); border-radius: 12px; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px; vertical-align: text-bottom;"></i> Recomenda-se usar uma senha forte.
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Senha Atual</label>
                    <input type="password" name="current_password" required
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Nova Senha</label>
                    <input type="password" name="new_password" required minlength="6"
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" required minlength="6"
                        style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-primary); color: var(--text-primary); outline: none;">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; background: var(--status-warning);">
                    Alterar Senha
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function showTab(tabName) {
        document.getElementById('tab-info').style.display = 'none';
        document.getElementById('tab-security').style.display = 'none';

        document.getElementById('btn-info').style.background = 'transparent';
        document.getElementById('btn-security').style.background = 'transparent';

        document.getElementById('tab-' + tabName).style.display = 'block';
        document.getElementById('btn-' + tabName).style.background = 'var(--bg-secondary)';
    }
</script>

<?php
renderAppFooter();
?>