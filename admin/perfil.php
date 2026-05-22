<?php
// admin/perfil.php
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
                
                $success = "Membro criado com sucesso!";
                header("Location: membros.php");
                exit;
            } else {
                $error = "Erro ao criar voluntário.";
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
                        $error = "Erro ao salvar imagem no servidor.";
                    }
                } else {
                    $error = "Formato de imagem inválido. Use JPG, PNG, GIF ou WEBP.";
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
                    $error = "Erro ao atualizar dados no banco de dados.";
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
$page_subtitle = $is_creating_new ? 'Cadastre um novo membro na equipe' : 'Gerencie as informações e configurações';

renderAppHeader($page_title);
?>

<div class="min-h-screen bg-[#121316] text-[#E2E8F0] px-4 py-8 md:px-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <!-- Top Navigation -->
        <div class="flex items-center justify-between border-b border-neutral-800/80 pb-4">
            <a href="membros.php" class="inline-flex items-center gap-2 text-neutral-400 hover:text-white transition-colors text-sm font-medium group active:scale-[0.97]">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Voltar para a Equipe
            </a>
            
            <div class="flex items-center gap-2">
                <a href="index.php" title="Painel Admin" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </a>
                <a href="../app/index.php" title="Painel do Músico" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="p-4 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-sm font-semibold flex items-center gap-2 shadow-lg">
                <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="p-4 rounded-xl bg-[#F43F5E]/10 border border-[#F43F5E]/20 text-[#F43F5E] text-sm font-semibold flex items-center gap-2 shadow-lg">
                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-8">
            <?= App\AuthMiddleware::csrfField() ?>
            <?php if ($is_creating_new): ?>
                <input type="hidden" name="create_user" value="1">
            <?php else: ?>
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            <?php endif; ?>

            <!-- Bento Layout Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                
                <!-- Coluna da Esquerda (Avatar e Endereço) -->
                <div class="space-y-6 md:col-span-1">
                    
                    <!-- Avatar Bento Card -->
                    <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 flex flex-col items-center text-center shadow-xl relative overflow-hidden">
                        <!-- Background Glow Decorator -->
                        <div class="absolute -right-16 -top-16 w-32 h-32 rounded-full bg-[#2E7EED]/5 blur-2xl pointer-events-none"></div>
                        
                        <div class="relative group">
                            <!-- Drag & Drop Container Decorator -->
                            <div class="w-28 h-28 rounded-full bg-[#121316] border-2 border-neutral-800 flex items-center justify-center overflow-hidden shadow-2xl relative group-hover:border-[#2E7EED]/65 transition-all duration-300">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($user['avatar']) ?>" id="avatar_preview" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php 
                                    $initial = !empty($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : 'U';
                                    $colors = [
                                        'A' => '#2E7EED', 'B' => '#10B981', 'C' => '#FFC107', 'D' => '#F43F5E',
                                        'E' => '#2E7EED', 'F' => '#10B981', 'G' => '#FFC107', 'H' => '#F43F5E',
                                        'I' => '#2E7EED', 'J' => '#10B981', 'K' => '#FFC107', 'L' => '#F43F5E',
                                        'M' => '#2E7EED', 'N' => '#10B981', 'O' => '#FFC107', 'P' => '#F43F5E',
                                        'Q' => '#2E7EED', 'R' => '#10B981', 'S' => '#FFC107', 'T' => '#F43F5E',
                                        'U' => '#2E7EED', 'V' => '#10B981', 'W' => '#FFC107', 'X' => '#F43F5E',
                                        'Y' => '#2E7EED', 'Z' => '#10B981'
                                    ];
                                    $avatarBg = $colors[$initial] ?? '#2E7EED';
                                    ?>
                                    <span class="font-extrabold text-4xl text-white select-none w-full h-full flex items-center justify-center" id="avatar_initial" style="background: <?= $avatarBg ?>; box-shadow: inset 0 0 20px rgba(0,0,0,0.15);">
                                        <?= $initial ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <label for="avatar_upload" class="absolute bottom-1 right-1 w-8 h-8 bg-[#2E7EED] hover:bg-[#1C66CE] text-white rounded-lg flex items-center justify-center cursor-pointer shadow-lg active:scale-[0.9] transition-all">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                            </label>
                            <input type="file" id="avatar_upload" name="avatar" class="hidden" accept="image/*" onchange="previewImage(this)">
                        </div>

                        <h2 class="text-xl font-bold text-white mt-4 font-sans leading-snug">
                            <?= !empty($user['name']) ? htmlspecialchars($user['name']) : 'Novo Voluntário' ?>
                        </h2>
                        
                        <div class="inline-flex items-center gap-1.5 bg-neutral-800/40 border border-neutral-700/50 px-3 py-1 rounded-full text-neutral-400 text-[10px] uppercase font-bold tracking-wider mt-3">
                            <i data-lucide="shield" class="w-3 h-3 text-[#FFC107]"></i>
                            <?= $is_creating_new ? 'Cadastro pendente' : ($user['role'] === 'admin' ? 'Administrador' : 'Voluntário') ?>
                        </div>
                        
                        <p class="text-xs text-neutral-400 mt-2 font-mono truncate max-w-full">
                            <?= htmlspecialchars($user['email'] ?: 'sem-email@louvor.com') ?>
                        </p>
                    </div>

                    <!-- Endereço Bento Card -->
                    <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl space-y-4">
                        <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2 border-b border-neutral-800/80 pb-3">
                            <i data-lucide="map" class="w-4 h-4 text-[#2E7EED]"></i>
                            Endereço
                        </h3>

                        <div class="space-y-4">
                            <!-- Rua -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Logradouro / Rua</label>
                                <div class="relative">
                                    <input type="text" name="address_street" value="<?= htmlspecialchars($user['address_street'] ?? '') ?>" placeholder="Ex: Rua das Flores" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                </div>
                            </div>

                            <!-- Número e Bairro -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Número</label>
                                    <input type="text" name="address_number" value="<?= htmlspecialchars($user['address_number'] ?? '') ?>" placeholder="Ex: 120" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Bairro</label>
                                    <input type="text" name="address_neighborhood" value="<?= htmlspecialchars($user['address_neighborhood'] ?? '') ?>" placeholder="Ex: Centro" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Coluna da Direita (Dados Pessoais, Acesso e Permissões, Funções Litúrgicas) -->
                <div class="space-y-6 md:col-span-2">
                    
                    <!-- Dados Pessoais Bento Card -->
                    <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl space-y-5">
                        <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2 border-b border-neutral-800/80 pb-3">
                            <i data-lucide="user" class="w-4 h-4 text-[#2E7EED]"></i>
                            Dados Pessoais
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            
                            <!-- Nome Completo -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Nome Completo</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Digite o nome do voluntário" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold text-sm">
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">E-mail</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required placeholder="exemplo@email.com" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all text-sm">
                            </div>

                            <!-- WhatsApp -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Telefone / WhatsApp</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Ex: (21) 99999-9999" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all text-sm">
                            </div>

                            <!-- Data de Nascimento -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Data de Nascimento</label>
                                <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all text-sm">
                            </div>

                        </div>
                    </div>

                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    
                    <!-- Acesso e Permissões Bento Card -->
                    <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl space-y-5">
                        <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2 border-b border-neutral-800/80 pb-3">
                            <i data-lucide="lock" class="w-4 h-4 text-[#2E7EED]"></i>
                            Acesso e Segurança
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            
                            <!-- Nível de Acesso -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Nível de Acesso (Cargo)</label>
                                <select name="role" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all text-sm font-semibold appearance-none cursor-pointer">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>👤 Voluntário Padrão</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>👑 Líder / Administrador</option>
                                </select>
                            </div>

                            <!-- Senha de Acesso -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Senha de Acesso</label>
                                <div class="relative">
                                    <input type="password" id="passwordInput" name="password" <?= $is_creating_new ? 'required' : '' ?> placeholder="<?= $is_creating_new ? 'Crie uma senha de acesso' : '•••••••• (Deixe vazio para manter)' ?>" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 pl-4 pr-12 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all text-sm tracking-[0.15em]">
                                    <button type="button" class="absolute right-3.5 top-3.5 text-neutral-400 hover:text-white transition-colors active:scale-[0.9]" onclick="togglePassword()" title="Ver/Ocultar Senha">
                                        <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <p class="text-[10px] text-neutral-500 mt-1 flex items-center gap-1">
                                    <i data-lucide="info" class="w-3.5 h-3.5 text-[#FFC107]"></i>
                                    Recomendado: Usar os 4 últimos dígitos do celular do membro.
                                </p>
                            </div>

                        </div>
                    </div>

                    <!-- Ministerial Functions Bento Card -->
                    <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl space-y-5">
                        <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2 border-b border-neutral-800/80 pb-3">
                            <i data-lucide="music" class="w-4 h-4 text-[#2E7EED]"></i>
                            Funções Litúrgicas (Atuação Ministerial)
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <?php foreach ($allRoles as $role): ?>
                                <label class="flex items-center justify-between p-4 border border-neutral-800 rounded-xl cursor-pointer hover:bg-neutral-800/20 hover:border-neutral-700/80 transition-all duration-200 group active:scale-[0.99] select-none">
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg w-8 h-8 rounded-lg bg-neutral-800/50 border border-neutral-700/40 flex items-center justify-center group-hover:scale-105 transition-transform duration-200"><?= $role['icon'] ?></span>
                                        <span class="text-xs font-bold text-neutral-300 group-hover:text-white transition-colors"><?= $role['name'] ?></span>
                                    </div>
                                    
                                    <!-- Sacred Minimalist GPU Switch -->
                                    <div class="relative inline-block w-[40px] h-[22px] align-middle select-none">
                                        <input type="checkbox" id="role_<?= $role['id'] ?>" name="roles[]" value="<?= $role['id'] ?>" <?= in_array($role['id'], $user_roles) ? 'checked' : '' ?> class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white appearance-none cursor-pointer z-10 opacity-0">
                                        <label class="toggle-label block overflow-hidden h-[22px] rounded-full cursor-pointer bg-neutral-800 border border-neutral-700/40" for="role_<?= $role['id'] ?>"></label>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>

            </div>

            <!-- Botões de Ação Inferiores -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-6 border-t border-neutral-800/80">
                <?php if (($_SESSION['user_role'] ?? '') === 'admin' && !$is_creating_new && $editing_user_id != $_SESSION['user_id']): ?>
                    <a href="membro_detalhe.php?id=<?= $user['id'] ?>" class="w-full sm:w-auto px-6 py-3 rounded-lg border border-neutral-800 text-neutral-400 hover:text-white hover:bg-neutral-800/40 text-xs font-bold transition-all text-center active:scale-[0.97]">
                        Cancelar
                    </a>
                <?php endif; ?>

                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                    <i data-lucide="<?= $is_creating_new ? 'user-plus' : 'save' ?>" class="w-4 h-4"></i>
                    <?= $is_creating_new ? 'Criar Novo Voluntário' : 'Salvar Alterações' ?>
                </button>
            </div>

        </form>

    </div>
</div>

<style>
/* Sacred Minimalist Toggle Switch - GPU Accelerated */
.toggle-checkbox:checked + .toggle-label {
    background-color: #2E7EED;
    border-color: #2E7EED;
}
.toggle-checkbox:checked + .toggle-label:after {
    transform: translateX(18px) translateZ(0);
}
.toggle-label {
    width: 40px;
    height: 22px;
    border-radius: 9999px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: background-color, border-color;
}
.toggle-label:after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    transform: translateZ(0); /* Forçar GPU */
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
            } else if (initial) {
                // Se era uma inicial, remove e insere imagem
                const parent = initial.parentElement;
                parent.innerHTML = '<img src="' + e.target.result + '" id="avatar_preview" class="w-full h-full object-cover">';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePassword() {
    const pwd = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        pwd.classList.remove('tracking-[0.15em]');
        if (typeof lucide !== 'undefined') {
            eyeIcon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
        }
    } else {
        pwd.type = 'password';
        pwd.classList.add('tracking-[0.15em]');
        if (typeof lucide !== 'undefined') {
            eyeIcon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php renderAppFooter(); ?>