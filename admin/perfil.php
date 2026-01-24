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
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']); // Adicionado telefone (se houver campo no DB, verifcar schema depois, mas por hora manteremos o post)
        // Nota: O campo 'phone' estava 'disabled' no form original e pegando do $user['phone']. 
        // Se quisermos permitir edição, precisamos atualizar a query. 
        // Vou manter a lógica original de address mas permitir phone se for editavel.
        // Pelo código anterior, phone era disabled. Vou verificar se user quer editar.
        // O código anterior NÃO atualizava phone no SQL.

        $address_street = trim($_POST['address_street']);
        $address_number = trim($_POST['address_number']);
        $address_neighborhood = trim($_POST['address_neighborhood']);
        $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

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
                    $_SESSION['user_avatar'] = $filename;
                } else {
                    $error = "Erro ao salvar imagem.";
                }
            } else {
                $error = "Formato de imagem inválido.";
            }
        }

        if (!$error) {
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address_street = ?, address_number = ?, address_neighborhood = ?, birth_date = ?";
            $params = [$name, $email, $phone, $address_street, $address_number, $address_neighborhood, $birth_date];

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
}

// Buscar Dados Atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

renderAppHeader('Meu Perfil');
?>

<div class="container" style="padding-top: 16px; max-width: 600px; margin: 0 auto;">

    <!-- Mensagens de Feedback -->
    <?php if ($success): ?>
        <div style="background: var(--primary-subtle); color: var(--primary); padding: 12px; border-radius: 12px; margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; border: 1px solid var(--primary-light); font-size: 0.9rem;">
            <i data-lucide="check-circle" style="width: 18px;"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; border: 1px solid #FECACA; font-size: 0.9rem;">
            <i data-lucide="alert-circle" style="width: 18px;"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">

        <!-- Card Principal (Avatar + Info Básica) -->
        <div style="background: var(--bg-surface); border-radius: var(--radius-lg); padding: 24px; text-align: center; border: 1px solid var(--border-color); margin-bottom: 16px; box-shadow: var(--shadow-sm);">

            <!-- Avatar Upload Wrapper -->
            <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 16px;">
                <div style="
                    width: 100%; height: 100%; 
                    border-radius: 50%; 
                    overflow: hidden; 
                    border: 4px solid var(--bg-surface); 
                    box-shadow: 0 0 0 2px var(--border-color);
                    background: var(--bg-body);
                    display: flex; align-items: center; justify-content: center;
                ">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 2.5rem; font-weight: 700; color: var(--text-muted);">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Botão Editar Foto -->
                <label for="avatar_upload" class="ripple" style="
                    position: absolute; bottom: 0; right: 0; 
                    background: var(--primary); color: white; 
                    width: 32px; height: 32px; 
                    border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center; 
                    cursor: pointer; 
                    border: 3px solid var(--bg-surface);
                    box-shadow: var(--shadow-sm);
                    transition: transform 0.2s;
                ">
                    <i data-lucide="camera" style="width: 16px;"></i>
                </label>
                <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*" onchange="this.form.submit()">
            </div>

            <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                <?= htmlspecialchars($user['name']) ?>
            </h2>
            <p style="color: #64748b; font-size: 0.9rem;">
                <?= htmlspecialchars($user['email'] ?? '') ?>
            </p>

            <div style="margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: #f1f5f9; border-radius: 20px; font-size: 0.75rem; color: #475569; font-weight: 600;">
                <i data-lucide="user" style="width: 12px;"></i>
                Membro da Equipe
            </div>
        </div>

        <!-- Section: Identidade -->
        <div style="margin-bottom: 16px;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="id-card" style="width: 16px; color: var(--text-muted);"></i>
                Identidade
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); overflow: hidden;">
                <div style="padding: 12px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 12px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 12px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                        Telefone / WhatsApp
                    </label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 12px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                        Data de Nascimento
                    </label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>
            </div>
        </div>

        <!-- Section: Endereço -->
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="map-pin" style="width: 16px; color: var(--text-muted);"></i>
                Endereço
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 12px; display: flex; flex-direction: column; gap: 12px;">

                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Rua / Logradouro</label>
                    <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" placeholder="Ex: Av. Maracanã"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Número</label>
                        <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" placeholder="123"
                            style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                            onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                    </div>

                    <div style="flex: 2;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Bairro</label>
                        <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" placeholder="Centro"
                            style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: 0.95rem; color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                            onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                    </div>
                </div>

            </div>
        </div>

        <!-- Botão Salvar Fixo (ou no final) -->
        <button type="submit" class="ripple" style="
            width: 100%; 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 14px; 
            border-radius: var(--radius-lg); 
            font-size: 0.95rem; 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: background 0.2s;
        ">
            <i data-lucide="save" style="width: 18px;"></i>
            Salvar Alterações
        </button>

    </form>

    <div style="height: 48px;"></div> <!-- Spacer Footer -->

</div>

<?php renderAppFooter(); ?>