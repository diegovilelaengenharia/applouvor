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

<style>
.profile-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 16px;
}

.profile-header {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 12px;
    border: 1px solid #bae6fd;
}


.change-photo-text {
    font-size: 0.6875rem;
    color: #6b7280;
    font-weight: 600;
    cursor: pointer;
    transition: color 0.2s;
}

.change-photo-text:hover {
    color: var(--primary);
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.avatar-wrapper {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.avatar-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initial {
    font-size: 3rem;
    font-weight: 700;
    color: #9ca3af;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    border: 3px solid white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}

.avatar-edit-btn:hover {
    transform: scale(1.1);
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-info-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 4px;
}

.profile-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.profile-email {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #dbeafe;
    border-radius: 12px;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #1e40af;
    white-space: nowrap;
}

.form-section {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 12px;
    text-align: left;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f3f4f6;
    text-align: left;
}

.section-icon {
    width: 18px;
    height: 18px;
    color: var(--primary);
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    text-align: left;
}

.form-field {
    margin-bottom: 12px;
}

.form-field:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #111827;
    background: #f9fafb;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(55, 106, 200, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 12px;
}

.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.role-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.2s;
}

.role-checkbox-label:hover {
    border-color: var(--primary);
    background: rgba(55, 106, 200, 0.03);
}

.role-checkbox-label input:checked ~ .role-info {
    color: var(--primary);
    font-weight: 600;
}

.role-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: #374151;
    transition: all 0.2s;
}

.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    flex: 1;
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: var(--primary);
    color: var(--primary);
}

.btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.alert {
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}


@media (max-width: 768px) {
    .profile-container {
        padding: 12px;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 16px;
    }
    
    .avatar-section {
        width: 100%;
    }
    
    .profile-info {
        width: 100%;
    }
    
    .profile-info-header {
        flex-direction: column;
        gap: 8px;
    }
    
    .avatar-wrapper {
        width: 100px;
        height: 100px;
    }
    
    .avatar-edit-btn {
        width: 32px;
        height: 32px;
    }
    
    .profile-name {
        font-size: 1.25rem;
    }
    
    .form-section {
        padding: 16px;
    }
    
    .section-header {
        margin-bottom: 16px;
        padding-bottom: 10px;
    }
    
    .section-title {
        font-size: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .roles-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .btn-primary,
    .btn-secondary {
        padding: 12px 20px;
    }
}

@media (max-width: 480px) {
    .profile-container {
        padding: 8px;
    }
    
    .avatar-wrapper {
        width: 80px;
        height: 80px;
    }
    
    .profile-name {
        font-size: 1.125rem;
    }
    
    .form-section {
        padding: 12px;
        border-radius: 12px;
    }
    
    .form-input {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 12px;
    }
    
    .roles-grid {
        grid-template-columns: 1fr;
    }
    
    .role-checkbox-label {
        padding: 12px;
    }
}

</style>

<div class="profile-container">
    <!-- Mensagens de Feedback -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-circle" style="width: 20px;"></i>
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

        <!-- Profile Header -->
        <div class="profile-header">
            <!-- Avatar Section -->
            <div class="avatar-section">
                <div class="avatar-wrapper">
                    <div class="avatar-circle">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>" id="avatar_preview" alt="Avatar">
                        <?php else: ?>
                            <span class="avatar-initial" id="avatar_initial">
                                <?= !empty($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : 'U' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <label for="avatar_upload" class="avatar-edit-btn">
                        <i data-lucide="camera" style="width: 14px;"></i>
                    </label>
                    <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*" onchange="previewImage(this)">
                </div>
                <label for="avatar_upload" class="change-photo-text">
                    Mudar Foto
                </label>
            </div>

            <!-- Profile Info -->
            <div class="profile-info">
                <div class="profile-info-header">
                    <h2 class="profile-name">
                        <?= !empty($user['name']) ? htmlspecialchars($user['name']) : 'Novo Usuário' ?>
                    </h2>
                    <div class="profile-badge">
                        <i data-lucide="user" style="width: 10px;"></i>
                        <?= $is_creating_new ? 'Novo Membro' : 'Membro da Equipe' ?>
                    </div>
                </div>
                <p class="profile-email"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            </div>
        </div>

        <!-- Form Sections -->
                <!-- Dados Pessoais -->
                <div class="form-section">
                    <div class="section-header">
                        <i data-lucide="user" class="section-icon"></i>
                        <h3 class="section-title">Dados Pessoais</h3>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input" placeholder="Digite o nome completo">
                    </div>

                    <div class="form-field">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="form-input" placeholder="exemplo@email.com">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Telefone / WhatsApp</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-input" placeholder="(37) 99999-9999">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Data de Nascimento</label>
                        <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" class="form-input">
                    </div>
                </div>

                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <!-- Permissões -->
                <div class="form-section">
                    <div class="section-header">
                        <i data-lucide="shield" class="section-icon"></i>
                        <h3 class="section-title">Permissões e Acesso</h3>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Nível de Acesso</label>
                        <select name="role" class="form-input">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Membro</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Senha de Acesso</label>
                        <input type="text" name="password" value="<?= htmlspecialchars($user['password'] ?? '') ?>" <?= $is_creating_new ? 'required' : '' ?> class="form-input" placeholder="<?= $is_creating_new ? '4 dígitos' : 'Deixe em branco para não alterar' ?>">
                        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 4px;">Recomendado: Últimos 4 dígitos do celular</p>
                    </div>
                </div>

                <!-- Funções/Instrumentos -->
                <div class="form-section">
                    <div class="section-header">
                        <i data-lucide="music" class="section-icon"></i>
                        <h3 class="section-title">Funções / Instrumentos</h3>
                    </div>

                    <div class="roles-grid">
                        <?php foreach ($allRoles as $role): ?>
                            <label class="role-checkbox-label">
                                <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" <?= in_array($role['id'], $user_roles) ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                                <span class="role-info">
                                    <span><?= $role['icon'] ?></span>
                                    <span><?= $role['name'] ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Endereço -->
                <div class="form-section">
                    <div class="section-header">
                        <i data-lucide="map-pin" class="section-icon"></i>
                        <h3 class="section-title">Endereço</h3>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Rua / Logradouro</label>
                        <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" class="form-input" placeholder="Ex: Av. Maracanã">
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label">Número</label>
                            <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" class="form-input" placeholder="123">
                        </div>

                        <div class="form-field">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" class="form-input" placeholder="Centro">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin' && !$is_creating_new && $editing_user_id != $_SESSION['user_id']): ?>
                    <a href="membros.php" class="btn btn-secondary">
                        <i data-lucide="arrow-left" style="width: 18px;"></i>
                        Voltar
                    </a>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary" style="flex: <?= (($_SESSION['user_role'] ?? '') === 'admin' && !$is_creating_new && $editing_user_id != $_SESSION['user_id']) ? '2' : '1' ?>;">
                        <i data-lucide="<?= $is_creating_new ? 'user-plus' : 'save' ?>" style="width: 20px;"></i>
                        <?= $is_creating_new ? 'Criar Membro' : 'Salvar Alterações' ?>
                    </button>
                </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('avatar_preview');
            const initial = document.getElementById('avatar_initial');
            
            if (preview) {
                preview.src = e.target.result;
            } else if (initial && initial.parentElement) {
                initial.parentElement.innerHTML = '<img src="' + e.target.result + '" id="avatar_preview" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php renderAppFooter(); ?>