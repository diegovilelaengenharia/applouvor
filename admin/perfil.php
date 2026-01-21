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
}

// Buscar Dados Atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

renderAppHeader('Meu Perfil');
?>

<div class="container" style="padding-top: 24px; max-width: 600px; margin: 0 auto;">

    <!-- Mensagens de Feedback -->
    <?php if ($success): ?>
        <div style="background: #DCFCE7; color: #166534; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; border: 1px solid #bbf7d0;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #FEF2F2; color: #991B1B; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; border: 1px solid #fecaca;">
            <i data-lucide="alert-circle" style="width: 20px;"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">

        <!-- Card Principal (Avatar + Info Básica) -->
        <div style="background: white; border-radius: 24px; padding: 32px; text-align: center; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">

            <!-- Avatar Upload Wrapper -->
            <div style="position: relative; width: 120px; height: 120px; margin: 0 auto 20px;">
                <div style="
                    width: 100%; height: 100%; 
                    border-radius: 50%; 
                    overflow: hidden; 
                    border: 4px solid white; 
                    box-shadow: 0 0 0 2px #e2e8f0;
                    background: #f1f5f9;
                    display: flex; align-items: center; justify-content: center;
                ">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 3rem; font-weight: 700; color: #94a3b8;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Botão Editar Foto -->
                <label for="avatar_upload" class="ripple" style="
                    position: absolute; bottom: 0; right: 0; 
                    background: #2563eb; color: white; 
                    width: 36px; height: 36px; 
                    border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center; 
                    cursor: pointer; 
                    border: 3px solid white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    transition: transform 0.2s;
                ">
                    <i data-lucide="camera" style="width: 18px;"></i>
                </label>
                <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*" onchange="this.form.submit()">
            </div>

            <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                <?= htmlspecialchars($user['name']) ?>
            </h2>
            <p style="color: #64748b; font-size: 0.95rem;">
                <?= htmlspecialchars($user['email'] ?? '') ?>
            </p>

            <div style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; background: #f1f5f9; border-radius: 20px; font-size: 0.8rem; color: #475569; font-weight: 600;">
                <i data-lucide="user" style="width: 14px;"></i>
                Membro da Equipe
            </div>
        </div>

        <!-- Section: Identidade -->
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="id-card" style="width: 18px; color: #94a3b8;"></i>
                Identidade
            </h3>

            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
                <div style="padding: 16px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                        style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #1e293b; outline: none; transition: border 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding: 16px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                        style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #1e293b; outline: none; transition: border 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding: 16px; background: #f8fafc;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-bottom: 6px; text-transform: uppercase;">
                        Telefone (Fixo)
                        <span style="font-size: 0.7rem; font-weight: normal; text-transform: none; margin-left: 4px;">Não editável</span>
                    </label>
                    <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" disabled
                        style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #94a3b8; background: #f1f5f9; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- Section: Endereço -->
        <div style="margin-bottom: 32px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="map-pin" style="width: 18px; color: #94a3b8;"></i>
                Endereço
            </h3>

            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 16px; display: flex; flex-direction: column; gap: 16px;">

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Rua / Logradouro</label>
                    <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" placeholder="Ex: Av. Maracanã"
                        style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #1e293b; outline: none; transition: border 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="display: flex; gap: 16px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Número</label>
                        <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" placeholder="123"
                            style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #1e293b; outline: none; transition: border 0.2s;"
                            onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>

                    <div style="flex: 2;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Bairro</label>
                        <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" placeholder="Centro"
                            style="width: 100%; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 1rem; color: #1e293b; outline: none; transition: border 0.2s;"
                            onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

            </div>
        </div>

        <!-- Botão Salvar Fixo (ou no final) -->
        <button type="submit" class="ripple" style="
            width: 100%; 
            background: #166534; 
            color: white; 
            border: none; 
            padding: 16px; 
            border-radius: 12px; 
            font-size: 1rem; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(22, 101, 52, 0.2);
            transition: background 0.2s;
        ">
            <i data-lucide="save" style="width: 20px;"></i>
            Salvar Alterações
        </button>

    </form>

    <div style="height: 48px;"></div> <!-- Spacer Footer -->

</div>

<?php renderAppFooter(); ?>