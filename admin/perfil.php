<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';


checkLogin();

// Determine which user to edit
$is_creating_new = isset($_GET['new']) && $_GET['new'] == '1';
$editing_user_id = null;

if ($is_creating_new) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: perfil.php");
        exit;
    }
    $editing_user_id = null;
} elseif (isset($_GET['id'])) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: perfil.php");
        exit;
    }
    $editing_user_id = (int)$_GET['id'];
} else {
    $editing_user_id = $_SESSION['user_id'];
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

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
            
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $filename = uniqid('avatar_') . '.' . $ext;
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
                        $avatar_path = $filename;
                    }
                }
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, phone, password, role, birth_date, avatar, instrument) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$name, $email, $phone, $hashed_password, $role, $birth_date, $avatar_path, ''];
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $new_user_id = $pdo->lastInsertId();
                
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
    elseif (isset($_POST['update_profile'])) {
        $target_user_id = $_POST['user_id'] ?? $editing_user_id;
        
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

            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $filename = uniqid('avatar_') . '.' . $ext;
                    $upload_dir = '../uploads/';
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
                
                if (($_SESSION['user_role'] ?? '') === 'admin') {
                    if (isset($_POST['role'])) {
                        $sql .= ", role = ?";
                        $params[] = $_POST['role'];
                    }
                    if (isset($_POST['password']) && !empty($_POST['password'])) {
                        $sql .= ", password = ?";
                        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    }
                    
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

$stmtAllRoles = $pdo->query("SELECT * FROM roles ORDER BY category, name");
$allRoles = $stmtAllRoles->fetchAll(PDO::FETCH_ASSOC);

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
?>

<main class="max-w-[800px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24">
    <div class="mb-8">
        <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface font-bold"><?= $page_title ?></h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant mt-2"><?= $page_subtitle ?></p>
    </div>

    <?php if ($success): ?>
        <div class="bg-primary-fixed/30 border border-primary-fixed text-primary-fixed-variant px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-body-md font-bold"><?= $success ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-error-container/20 border border-error-container/30 text-error px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-body-md font-bold"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= App\AuthMiddleware::csrfField() ?>
        <?php if ($is_creating_new): ?>
            <input type="hidden" name="create_user" value="1">
        <?php else: ?>
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <?php endif; ?>

        <!-- Avatar Section -->
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 flex flex-col md:flex-row items-center gap-6 shadow-sm">
            <div class="relative group">
                <div class="w-24 h-24 rounded-full bg-primary-fixed/20 border-4 border-surface shadow-md flex items-center justify-center overflow-hidden">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($user['avatar']) ?>" id="avatar_preview" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="font-display-lg text-3xl text-primary font-bold" id="avatar_initial">
                            <?= !empty($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : 'U' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <label for="avatar_upload" class="absolute bottom-0 right-0 w-8 h-8 bg-primary text-on-primary rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-primary-container hover:text-on-primary-container transition-colors transform active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                </label>
                <input type="file" id="avatar_upload" name="avatar" class="hidden" accept="image/*" onchange="previewImage(this)">
            </div>
            <div class="text-center md:text-left">
                <h2 class="font-headline-md text-xl font-bold text-on-surface">
                    <?= !empty($user['name']) ? htmlspecialchars($user['name']) : 'Novo Usuário' ?>
                </h2>
                <div class="inline-flex items-center gap-1 bg-surface-container-high px-2 py-1 rounded text-on-surface-variant font-label-sm text-[10px] uppercase tracking-wider font-bold mt-2">
                    <span class="material-symbols-outlined text-[12px]">badge</span>
                    <?= $is_creating_new ? 'Novo Membro' : 'Membro da Equipe' ?>
                </div>
                <p class="font-body-md text-on-surface-variant mt-2"><?= htmlspecialchars($user['email'] ?? 'Sem e-mail') ?></p>
            </div>
        </div>

        <!-- Personal Data -->
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-6 border-b border-surface-container-highest pb-4">
                <span class="material-symbols-outlined text-primary">person</span>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Dados Pessoais</h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="Digite o nome completo">
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="exemplo@email.com">
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Telefone / WhatsApp</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="(37) 99999-9999">
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Data de Nascimento</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                </div>
            </div>
        </div>

        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <!-- Access & Permissions -->
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-6 border-b border-surface-container-highest pb-4">
                <span class="material-symbols-outlined text-primary">shield</span>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Permissões e Acesso</h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Nível de Acesso (Cargo no Sistema)</label>
                    <select name="role" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors appearance-none font-bold">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>👤 Membro Padrão</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>👑 Administrador</option>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Senha de Acesso</label>
                    <div class="relative">
                        <input type="password" id="passwordInput" name="password" <?= $is_creating_new ? 'required' : '' ?> class="w-full bg-surface-container border border-surface-container-highest rounded-xl pl-4 pr-12 py-3 font-body-md text-on-surface tracking-[0.2em] focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="<?= $is_creating_new ? 'Crie uma senha' : '•••••••• (Deixe em branco para manter)' ?>">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center" onclick="togglePassword()" title="Ver/Ocultar Senha">
                            <span class="material-symbols-outlined text-[20px]" id="eyeIcon">visibility</span>
                        </button>
                    </div>
                    <p class="font-label-sm text-[10px] text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">info</span>
                        Recomendado: Usar os 4 últimos dígitos do celular para facilitar.
                    </p>
                </div>
            </div>
        </div>

        <!-- Ministerial Functions -->
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-6 border-b border-surface-container-highest pb-4">
                <span class="material-symbols-outlined text-primary">music_note</span>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Atuação Ministerial (Funções)</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($allRoles as $role): ?>
                    <label class="flex items-center justify-between p-4 border border-surface-container-highest rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="text-xl"><?= $role['icon'] ?></span>
                            <span class="font-body-md font-bold text-on-surface group-hover:text-primary transition-colors"><?= $role['name'] ?></span>
                        </div>
                        <!-- Custom Toggle -->
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" <?= in_array($role['id'], $user_roles) ? 'checked' : '' ?> class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer transition-transform duration-200 ease-in-out">
                            <label class="toggle-label block overflow-hidden h-5 rounded-full bg-surface-container-highest cursor-pointer transition-colors duration-200 ease-in-out"></label>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Address -->
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-6 border-b border-surface-container-highest pb-4">
                <span class="material-symbols-outlined text-primary">map</span>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Endereço</h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Rua / Logradouro</label>
                    <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="Ex: Av. Maracanã">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Número</label>
                        <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="123">
                    </div>

                    <div>
                        <label class="block font-label-sm text-on-surface-variant mb-1 font-bold">Bairro</label>
                        <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" class="w-full bg-surface-container border border-surface-container-highest rounded-xl px-4 py-3 font-body-md text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors" placeholder="Centro">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="flex gap-4 pt-4">
            <?php if (($_SESSION['user_role'] ?? '') === 'admin' && !$is_creating_new && $editing_user_id != $_SESSION['user_id']): ?>
            <a href="membros.php" class="flex-1 md:flex-none py-3 px-6 bg-surface-container border border-surface-container-highest text-on-surface rounded-full font-label-sm font-bold text-center hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
            </a>
            <?php endif; ?>
            
            <button type="submit" class="flex-[2] py-3 px-6 bg-primary text-on-primary rounded-full font-label-sm font-bold shadow-md hover:bg-primary-container hover:text-on-primary-container transition-colors transform active:scale-95 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]"><?= $is_creating_new ? 'person_add' : 'save' ?></span>
                <?= $is_creating_new ? 'Criar Membro' : 'Salvar Alterações' ?>
            </button>
        </div>

    </form>
</main>

<style>
/* Custom Toggle Checkbox */
.toggle-checkbox:checked {
    right: 0;
    border-color: var(--primary);
    background-color: var(--primary);
}
.toggle-checkbox:checked + .toggle-label {
    background-color: var(--primary-fixed);
}
.toggle-checkbox {
    right: 50%;
    z-index: 1;
    border-color: #e5e7eb;
}
</style>

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
                initial.parentElement.innerHTML = '<img src="' + e.target.result + '" id="avatar_preview" class="w-full h-full object-cover">';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePassword() {
    const pwd = document.getElementById('passwordInput');
    const eye = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        pwd.classList.remove('tracking-[0.2em]');
        eye.textContent = 'visibility_off';
    } else {
        pwd.type = 'password';
        pwd.classList.add('tracking-[0.2em]');
        eye.textContent = 'visibility';
    }
}
</script>

<?php renderAppFooter(); ?>