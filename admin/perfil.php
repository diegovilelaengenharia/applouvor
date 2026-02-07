<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Garantir CSS
echo '<link rel="stylesheet" href="../assets/css/design-system.css">';

checkLogin();

// Determine which user to edit
$is_creating_new = isset($_GET['new']) && $_GET['new'] == '1';
$editing_user_id = null;

if ($is_creating_new) {
    // Admin creating a new user
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: perfil.php");
        exit;
    }
    $editing_user_id = null;
} elseif (isset($_GET['id'])) {
    // Admin editing another user
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: perfil.php");
        exit;
    }
    $editing_user_id = (int)$_GET['id'];
} else {
    // User editing their own profile
    $editing_user_id = $_SESSION['user_id'];
}

$success = '';
$error = '';

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Criar novo usuário (apenas admin)
    if (isset($_POST['create_user'])) {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $error = "Acesso negado.";
        } else {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'];
            $role = $_POST['role'] ?? 'user';
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
                    }
                }
            }
            
            // Insert new user
            $sql = "INSERT INTO users (name, email, phone, password, role, birth_date, avatar, instrument) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$name, $email, $phone, $password, $role, $birth_date, $avatar_path, ''];
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $new_user_id = $pdo->lastInsertId();
                
                // Process roles/functions
                if (isset($_POST['roles']) && is_array($_POST['roles'])) {
                    $stmtIns = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    foreach ($_POST['roles'] as $roleId) {
                        $stmtIns->execute([$new_user_id, $roleId]);
                    }
                }
                
                $success = "Usuário criado com sucesso!";
                header("Location: membros.php");
                exit;
            } else {
                $error = "Erro ao criar usuário.";
            }
        }
    }
    // 2. Atualização de Dados Pessoais
    elseif (isset($_POST['update_profile'])) {
        $target_user_id = $_POST['user_id'] ?? $editing_user_id;
        
        // Security check
        if ($target_user_id != $_SESSION['user_id'] && ($_SESSION['user_role'] ?? '') !== 'admin') {
            $error = "Acesso negado.";
        } else {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
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
                        if ($target_user_id == $_SESSION['user_id']) {
                            $_SESSION['user_avatar'] = $filename;
                        }
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
                
                // Admin can also update role and password
                if (($_SESSION['user_role'] ?? '') === 'admin') {
                    if (isset($_POST['role'])) {
                        $sql .= ", role = ?";
                        $params[] = $_POST['role'];
                    }
                    if (isset($_POST['password']) && !empty($_POST['password'])) {
                        $sql .= ", password = ?";
                        $params[] = $_POST['password'];
                    }
                    
                    // Process roles/functions
                    $stmtDel = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                    $stmtDel->execute([$target_user_id]);

                    if (isset($_POST['roles']) && is_array($_POST['roles'])) {
                        $stmtIns = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                        foreach ($_POST['roles'] as $roleId) {
                            $stmtIns->execute([$target_user_id, $roleId]);
                        }
                    }
                }

                $sql .= " WHERE id = ?";
                $params[] = $target_user_id;

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $success = "Perfil atualizado com sucesso!";
                    if ($target_user_id == $_SESSION['user_id']) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                    }
                    
                    // If admin edited another user, redirect back to members page
                    if (($_SESSION['user_role'] ?? '') === 'admin' && $target_user_id != $_SESSION['user_id']) {
                        header("Location: membros.php");
                        exit;
                    }
                } else {
                    $error = "Erro ao atualizar dados.";
                }
            }
        }
    }
}

// Buscar TODAS as funções disponíveis (for admin use)
$stmtAllRoles = $pdo->query("SELECT * FROM roles ORDER BY category, name");
$allRoles = $stmtAllRoles->fetchAll(PDO::FETCH_ASSOC);

// Buscar Dados do Usuário
if ($is_creating_new) {
    $user = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
        'address_street' => '',
        'address_number' => '',
        'address_neighborhood' => '',
        'birth_date' => '',
        'avatar' => null,
        'role' => 'user',
        'password' => ''
    ];
    $user_roles = [];
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editing_user_id]);
    $user = $stmt->fetch();
    
    // Fetch user roles
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        $stmtRoles = $pdo->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
        $stmtRoles->execute([$editing_user_id]);
        $user_roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $user_roles = [];
    }
}

$page_title = $is_creating_new ? 'Novo Membro' : ($editing_user_id == $_SESSION['user_id'] ? 'Meu Perfil' : 'Editar Perfil');
$page_subtitle = $is_creating_new ? 'Cadastre um novo membro da equipe' : 'Gerencie as informações pessoais';

renderAppHeader($page_title);
renderPageHeader($page_title, $page_subtitle);
?>

<div class="container" style="padding-top: 16px; max-width: 600px; margin: 0 auto; padding-bottom: 24px;">


    <!-- Mensagens de Feedback -->
    <?php if ($success): ?>
        <div style="background: var(--green-50); color: var(--green-700); padding: 12px; border-radius: 12px; margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; border: 1px solid var(--green-200); font-size: var(--font-body);">
            <i data-lucide="check-circle" style="width: 18px;"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: var(--rose-50); color: var(--rose-600); padding: 12px; border-radius: 12px; margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; border: 1px solid var(--rose-200); font-size: var(--font-body);">
            <i data-lucide="alert-circle" style="width: 18px;"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>


    <form method="POST" enctype="multipart/form-data">
        <?php if ($is_creating_new): ?>
            <input type="hidden" name="create_user" value="1">
        <?php else: ?>
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <?php endif; ?>

        <!-- Card Principal (Avatar + Info Básica) -->
        <div style="background: var(--bg-surface); border-radius: var(--radius-lg); padding: 16px; text-align: center; border: 1px solid var(--border-color); margin-bottom: 12px; box-shadow: var(--shadow-sm);">

            <!-- Avatar Upload Wrapper -->
            <div style="position: relative; width: 80px; height: 80px; margin: 0 auto 12px;">
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
                            <?= !empty($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : 'U' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Botão Editar Foto -->
                <label for="avatar_upload" class="ripple" style="
                    position: absolute; bottom: 0; right: 0; 
                    background: var(--primary); color: white; 
                    width: 28px; height: 28px; 
                    border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center; 
                    cursor: pointer; 
                    border: 3px solid var(--bg-surface);
                    box-shadow: var(--shadow-sm);
                    transition: transform 0.2s;
                ">
                    <i data-lucide="camera" style="width: 16px;"></i>
                </label>
                <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*">
            </div>

            <h2 style="font-size: var(--font-h3); font-weight: 700; color: var(--slate-900); margin-bottom: 2px;">
                <?= !empty($user['name']) ? htmlspecialchars($user['name']) : 'Novo Usuário' ?>
            </h2>
            <p style="color: var(--slate-500); font-size: var(--font-body-sm);">
                <?= htmlspecialchars($user['email'] ?? '') ?>
            </p>

            <div style="margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; background: var(--slate-100); border-radius: 20px; font-size: var(--font-caption); color: var(--slate-600); font-weight: 600;">
                <i data-lucide="user" style="width: 12px;"></i>
                <?= $is_creating_new ? 'Novo Membro' : 'Membro da Equipe' ?>
            </div>
        </div>

        <!-- Section: Identidade -->
        <div style="margin-bottom: 12px;">
            <h3 style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="id-card" style="width: 16px; color: var(--text-muted);"></i>
                Identidade
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); overflow: hidden;">
                <div style="padding: 10px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 10px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 12px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                        Telefone / WhatsApp
                    </label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 12px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                        Data de Nascimento
                    </label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>
            </div>
        </div>

        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <!-- Section: Permissões (Admin Only) -->
        <div style="margin-bottom: 12px;">
            <h3 style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="shield" style="width: 16px; color: var(--text-muted);"></i>
                Permissões
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); overflow: hidden;">
                <div style="padding: 10px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Nível de Acesso</label>
                    <select name="role" style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Membro</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding: 10px;">
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Senha de Acesso</label>
                    <input type="text" name="password" value="<?= htmlspecialchars($user['password'] ?? '') ?>" <?= $is_creating_new ? 'required' : '' ?> placeholder="<?= $is_creating_new ? '4 dígitos' : 'Deixe em branco para n��o alterar' ?>"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                    <p style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px;">Recomendado: Últimos 4 dígitos do celular</p>
                </div>
            </div>
        </div>

        <!-- Section: Funções/Instrumentos (Admin Only) -->
        <div style="margin-bottom: 12px;">
            <h3 style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="music" style="width: 16px; color: var(--text-muted);"></i>
                Funções / Instrumentos
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 12px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px;">
                    <?php foreach ($allRoles as $role): ?>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.2s; background: var(--bg-body);"
                            onmouseover="this.style.borderColor='var(--primary)'; this.style.background='rgba(55, 106, 200, 0.05)'" 
                            onmouseout="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                            <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" 
                                <?= in_array($role['id'], $user_roles) ? 'checked' : '' ?>
                                style="width: 16px; height: 16px; cursor: pointer;">
                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                                <span><?= $role['icon'] ?></span>
                                <span style="font-weight: 500; color: var(--text-secondary);"><?= $role['name'] ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section: Endereço -->
        <div style="margin-bottom: 16px;">
            <h3 style="font-size: var(--font-body-sm); font-weight: 700; color: var(--text-main); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="map-pin" style="width: 16px; color: var(--text-muted);"></i>
                Endereço
            </h3>

            <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 10px; display: flex; flex-direction: column; gap: 10px;">

                <div>
                    <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Rua / Logradouro</label>
                    <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" placeholder="Ex: Av. Maracanã"
                        style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                        onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                </div>

                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Número</label>
                        <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" placeholder="123"
                            style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
                            onfocus="this.style.borderColor='var(--primary)'; this.style.background='var(--bg-surface)'" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-body)'">
                    </div>

                    <div style="flex: 2;">
                        <label style="display: block; font-size: var(--font-caption); font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Bairro</label>
                        <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" placeholder="Centro"
                            style="width: 100%; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-size: var(--font-body); color: var(--text-main); outline: none; transition: border 0.2s; background: var(--bg-body);"
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
            padding: 12px; 
            border-radius: var(--radius-lg); 
            font-size: var(--font-body); 
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