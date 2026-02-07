<?php
// admin/membros.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// --- LÓGICA DE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Backend Validation (RBAC)
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header("HTTP/1.1 403 Forbidden");
            exit("Acesso negado. Apenas administradores podem realizar esta ação.");
        }

        $userIdRole = null;

        // Adicionar novo membro
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO users (name, role, instrument, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['role'],
                $_POST['instrument'],
                $_POST['phone'],
                $_POST['password']
            ]);
            $userIdRole = $pdo->lastInsertId();
        }
        // Atualizar membro
        elseif ($_POST['action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, role = ?, instrument = ?, phone = ?, password = ? WHERE id = ?");
            $params = [
                $_POST['name'],
                $_POST['role'],
                $_POST['instrument'],
                $_POST['phone'],
                $_POST['password'],
                $_POST['id']
            ];
            $stmt->execute($params);
            $userIdRole = $_POST['id'];
        }
        // Excluir membro
        elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: membros.php");
            exit;
        }

        // --- PROCESSAR FUNÇÕES (ROLES) ---
        if ($userIdRole) {
            $stmtDel = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmtDel->execute([$userIdRole]);

            if (isset($_POST['roles']) && is_array($_POST['roles'])) {
                $stmtIns = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                foreach ($_POST['roles'] as $roleId) {
                    $stmtIns->execute([$userIdRole, $roleId]);
                }
            }
        }

        header("Location: membros.php");
        exit;
    }
}

// Buscar TODAS as funções disponíveis
$stmtAllRoles = $pdo->query("SELECT * FROM roles ORDER BY category, name");
$allRoles = $stmtAllRoles->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os membros com suas funções
$stmt = $pdo->query("
    SELECT u.*, 
           GROUP_CONCAT(
               CONCAT(r.id, ':', r.name, ':', r.icon, ':', r.color, ':', IFNULL(ur.is_primary, 0))
               ORDER BY ur.is_primary DESC, r.name
               SEPARATOR '||'
           ) as roles_data
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    GROUP BY u.id
    ORDER BY u.name ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar funções para cada usuário
foreach ($users as &$user) {
    $user['roles'] = [];
    if (!empty($user['roles_data'])) {
        $rolesArray = explode('||', $user['roles_data']);
        foreach ($rolesArray as $roleStr) {
            list($id, $name, $icon, $color, $isPrimary) = explode(':', $roleStr);
            $user['roles'][] = [
                'id' => $id,
                'name' => $name,
                'icon' => $icon,
                'color' => $color,
                'is_primary' => (bool)$isPrimary
            ];
        }
    }
}
unset($user);

renderAppHeader('Membros');
renderPageHeader('Equipe', count($users) . ' membros cadastrados');
?>

<style>
    /* Custom Actions Layout for Members Card */
    .member-actions-wrapper {
        display: flex;
        gap: 6px;
        margin-left: auto; /* Push to right */
        padding-left: 12px;
        border-left: 1px solid var(--border-subtle);
    }

    .btn-action-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        transition: all 0.2s;
        cursor: pointer;
        color: var(--text-secondary);
        background: transparent;
    }

    .btn-action-icon:hover {
        background: var(--bg-surface-active);
        color: var(--text-primary);
        border-color: var(--border-subtle);
    }

    .btn-action-whatsapp {
        color: var(--green-600);
        background: var(--green-50);
        border-color: var(--green-100);
    }
    .btn-action-whatsapp:hover {
        background: var(--green-100);
        color: var(--green-700);
        border-color: var(--green-200);
    }
    
    .btn-action-delete {
        color: var(--red-500);
    }
    .btn-action-delete:hover {
        background: var(--red-50);
        color: var(--red-600);
    }

    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    
    .search-box input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
    }
    
    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-tertiary);
        pointer-events: none;
    }

    /* Modal Styles (kept from original but cleaned up) */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
    .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 450px; background: var(--bg-surface); border-radius: 20px; padding: 24px; box-shadow: var(--shadow-xl); max-height: 90vh; overflow-y: auto; }
    .modal-header { text-align: center; margin-bottom: 24px; }
    .modal-header h2 { font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin: 0 0 4px 0; }
    .modal-header p { color: var(--text-secondary); font-size: 0.9rem; margin: 0; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
    .form-input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-primary); font-size: 0.9rem; }
    .form-input:focus { outline: none; border-color: var(--primary); }
    .roles-container { max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; background: var(--bg-body); }
    .roles-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .role-option { display: flex; align-items: center; gap: 8px; padding: 6px; border-radius: 6px; background: var(--bg-surface); border: 1px solid var(--border-color); cursor: pointer; }
    .role-option:hover { border-color: var(--primary); background: var(--primary-light); }
    .modal-actions { display: flex; gap: 12px; margin-top: 24px; }
    .btn-cancel { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-secondary); font-weight: 600; cursor: pointer; }
    .btn-submit { flex: 2; padding: 10px; border-radius: 8px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer; }

    /* Role Badge in List */
    .list-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        background: var(--bg-body);
        color: var(--text-secondary);
        border: 1px solid var(--border-subtle);
    }
</style>

<div class="members-container" style="max-width: 800px; margin: 0 auto;">
    <!-- Search Bar -->
    <div class="search-box">
        <i data-lucide="search" class="search-icon" width="20"></i>
        <input type="text" id="memberSearch" placeholder="Buscar por nome ou instrumento..." onkeyup="filterMembers()">
    </div>

    <!-- Members List (Timeline/Compact Style) -->
    <div class="members-list">
        <?php foreach ($users as $user): 
            $userColor = generateAvatarColor($user['name']);
            // Convert simple hex to rgba for backgrounds
            // Simple hack: use the color as border, and a generic light bg or opacity if handled by js/css
            // Here we use inline styles for the specific color logic
        ?>
            <!-- Compact Card Structure -->
            <div class="compact-card" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>"
                 style="border-left-color: <?= $userColor ?>;">
                
                <!-- Avatar/Icon -->
                <div class="compact-card-icon rounded" style="background: <?= $userColor ?>15; color: <?= $userColor ?>; width: 40px; height: 40px;">
                    <?php 
                    $initial = strtoupper(substr($user['name'], 0, 1));
                    if (!empty($user['avatar'])) {
                        $avatarPath = $user['avatar'];
                        if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                            $avatarPath = '../assets/uploads/' . $avatarPath;
                        } elseif (strpos($avatarPath, 'assets/') === 0) {
                             $avatarPath = '../' . $avatarPath;
                        }
                        
                        echo "<img src=\"" . htmlspecialchars($avatarPath) . "\" alt=\"" . htmlspecialchars($user['name']) . "\" style=\"width:100%; height:100%; object-fit:cover; border-radius:50%;\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">";
                        echo "<span style='display:none;' class='fallback-initial font-bold text-lg'>" . $initial . "</span>";
                    } else {
                        echo "<span class='font-bold text-lg'>" . $initial . "</span>";
                    }
                    ?>
                </div>

                <!-- Content -->
                <div class="compact-card-content">
                    <div class="compact-card-title" style="display: flex; align-items: center; gap: 6px;">
                        <?= htmlspecialchars($user['name']) ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span style="background: var(--yellow-500); color: white; padding: 1px 4px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase;">ADM</span>
                        <?php endif; ?>
                    </div>
                    <div class="compact-card-subtitle" style="margin-top: 2px;">
                        <?php 
                        if (!empty($user['roles'])): 
                            $uniqueRoles = [];
                            $seen = [];
                            foreach ($user['roles'] as $r) {
                                if (!in_array($r['name'], $seen)) {
                                    $seen[] = $r['name'];
                                    $uniqueRoles[] = $r;
                                }
                            }
                            // Show max 4 roles to keep it compact
                            $displayRoles = array_slice($uniqueRoles, 0, 4);
                            foreach ($displayRoles as $role): 
                        ?>
                            <span class="list-role-badge">
                                <span><?= $role['icon'] ?></span>
                                <span><?= htmlspecialchars($role['name']) ?></span>
                            </span>
                        <?php 
                            endforeach;
                            if(count($uniqueRoles) > 4): 
                        ?>
                            <span style='font-size:0.7rem; opacity:0.7;'>+<?= (count($uniqueRoles) - 4) ?></span>
                        <?php 
                            endif;
                        else: 
                        ?>
                            <span style="font-size: 0.8rem; color: var(--text-tertiary); font-style: italic;">
                                <?= htmlspecialchars($user['instrument'] ?: 'Sem função definida') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Users Stats / Meta (Optional - e.g. Phone) -->
                <!-- <div style="font-size: 0.8rem; color: var(--text-tertiary); display:none; @media(min-width:768px){display:block;}">
                   <?= htmlspecialchars($user['instrument']) ?>
                </div> -->

                <!-- Actions -->
                <div class="member-actions-wrapper">
                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" class="btn-action-icon btn-action-whatsapp" title="WhatsApp">
                        <i data-lucide="message-circle" width="18"></i>
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <button onclick='openEditModal(<?= json_encode($user) ?>)' class="btn-action-icon" title="Editar">
                            <i data-lucide="edit-3" width="18"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Excluir este membro?');" style="margin: 0; display:flex;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <button type="submit" class="btn-action-icon btn-action-delete" title="Excluir">
                                <i data-lucide="trash-2" width="18"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FAB (Admin Only) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <button onclick="openAddModal()" class="fab" style="position: fixed; bottom: 84px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: var(--primary); color: white; border: none; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 90; transition: transform 0.2s;">
        <i data-lucide="plus" width="28"></i>
    </button>
<?php endif; ?>

<!-- Modal Add/Edit -->
<div id="memberModal" class="modal" onclick="if(event.target === this) closeModal()">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Novo Membro</h2>
            <p>Gerencie as informações de acesso</p>
        </div>

        <form method="POST" id="memberForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId">

            <div class="form-group">
                <label class="form-label">Nome Completo</label>
                <input type="text" name="name" id="userName" required class="form-input" placeholder="Ex: João da Silva">
            </div>

            <div class="form-group">
                <label class="form-label">Permissão</label>
                <select name="role" id="userRole" class="form-input">
                    <option value="user">Membro</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Funções / Instrumentos</label>
                <div class="roles-container">
                    <div class="roles-grid">
                        <?php foreach ($allRoles as $role): ?>
                            <label class="role-option">
                                <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" class="role-checkbox">
                                <span style="display: flex; align-items: center; gap: 6px;">
                                    <span><?= $role['icon'] ?></span>
                                    <span style="font-weight: 500; font-size: 0.85rem; color: var(--text-secondary);"><?= $role['name'] ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <input type="hidden" name="instrument" id="userInst" value="">

            <div class="form-group">
                <label class="form-label">WhatsApp</label>
                <input type="text" name="phone" id="userPhone" class="form-input" placeholder="(37) 99999-9999">
            </div>

            <div class="form-group">
                <label class="form-label">Senha de Acesso</label>
                <input type="text" name="password" id="userPass" required class="form-input" placeholder="4 dígitos para login">
                <p style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px;">Recomendado: Últimos 4 dígitos do celular</p>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function filterMembers() {
        const term = document.getElementById('memberSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.compact-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const role = card.getAttribute('data-role');
            if (name.includes(term) || role.includes(term)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Novo Membro';
        document.getElementById('formAction').value = 'add';
        document.getElementById('memberForm').reset();
        document.getElementById('userId').value = '';
        document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('memberModal').style.display = 'block';
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').innerText = 'Editar Membro';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userInst').value = user.instrument;
        document.getElementById('userPhone').value = user.phone;
        document.getElementById('userPass').value = user.password;
        document.getElementById('userRole').value = user.role;

        document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
        if (user.roles && user.roles.length > 0) {
            user.roles.forEach(role => {
                const checkbox = document.querySelector(`.role-checkbox[value="${role.id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        document.getElementById('memberModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('memberModal').style.display = 'none';
    }
</script>

<?php
function generateAvatarColor($name) {
    $colors = ['var(--rose-500)', '#f97316', 'var(--yellow-500)', '#84cc16', 'var(--sage-500)', '#06b6d4', 'var(--slate-500)', '#6366f1', 'var(--lavender-500)', '#d946ef', '#f43f5e'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

renderAppFooter();
?>