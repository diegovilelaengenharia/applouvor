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
    .members-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 16px;
    }

    .search-box {
        position: relative;
        margin-bottom: 24px;
    }

    .search-box input {
        width: 100%;
        padding: 14px 14px 14px 48px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        font-size: 1rem;
        outline: none;
        transition: all 0.2s;
        background: var(--bg-surface);
        color: var(--text-main);
        box-shadow: var(--shadow-sm);
    }

    .search-box input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-subtle);
    }

    .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        width: 20px;
    }

    .members-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 80px;
    }

    .member-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 12px;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
        cursor: pointer;
        position: relative;
    }

    .member-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }

    .member-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        color: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        flex-shrink: 0;
    }

    .member-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .member-info {
        flex: 1;
        min-width: 0;
    }

    .member-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 4px 0;
        line-height: 1.2;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .member-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 2px;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 5px;
        font-size: 0.65rem;
        font-weight: 600;
        color: var(--text-main);
        background: var(--bg-body);
        border: 1px solid var(--border-color);
    }

    .role-icon {
        font-size: 0.75rem;
    }

    .badge-admin {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 1px 5px;
        border-radius: 6px;
        font-size: 0.55rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .member-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-whatsapp {
        background: #10b981;
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-phone {
        background: var(--bg-body);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
    }

    .btn-edit {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(22, 101, 52, 0.3);
    }

    .btn-delete {
        background: #ef4444;
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        background: #166534;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(22, 101, 52, 0.4);
        border: none;
        cursor: pointer;
        z-index: 100;
        transition: transform 0.2s;
    }

    .fab:hover {
        transform: scale(1.1);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(2px);
    }

    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 450px;
        background: var(--bg-surface);
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .modal-header h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
        margin: 0 0 4px 0;
    }

    .modal-header p {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin: 0;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: 1rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-subtle);
        background: var(--bg-surface);
    }

    .roles-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 12px;
        background: var(--bg-body);
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .role-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 8px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: all 0.2s;
    }

    .role-option:hover {
        border-color: var(--primary);
        background: var(--primary-subtle);
    }

    .role-checkbox {
        accent-color: var(--primary);
        width: 16px;
        height: 16px;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .btn-cancel {
        flex: 1;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-surface);
        color: var(--text-muted);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-submit {
        flex: 2;
        padding: 12px;
        border-radius: 10px;
        border: none;
        background: var(--text-main);
        color: white;
        font-weight: 700;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
    }

    .btn-submit:hover, .btn-cancel:hover {
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .members-grid {
            gap: 10px;
        }
    }
</style>

<div class="members-container">
    <!-- Search Bar -->
    <div class="search-box">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" id="memberSearch" placeholder="Buscar por nome ou instrumento..." onkeyup="filterMembers()">
    </div>

    <!-- Members Grid -->
    <div class="members-grid">
        <?php foreach ($users as $user): ?>
            <div class="member-card" data-name="<?= strtolower($user['name']) ?>" data-role="<?= strtolower($user['instrument'] ?? '') ?>">
                <!-- Avatar -->
                <div class="member-avatar" style="background: <?= generateAvatarColor($user['name']) ?>">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['name']) ?>">
                    <?php else: ?>
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="member-info">
                    <div class="member-name">
                        <?= htmlspecialchars($user['name']) ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge-admin">ADM</span>
                        <?php endif; ?>
                    </div>

                    <!-- Roles -->
                    <div class="member-roles">
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
                            foreach ($uniqueRoles as $role): 
                        ?>
                            <span class="role-badge" style="border-left: 3px solid <?= $role['color'] ?>;">
                                <span class="role-icon"><?= $role['icon'] ?></span>
                                <span><?= htmlspecialchars($role['name']) ?></span>
                            </span>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                                <?= htmlspecialchars($user['instrument'] ?: 'Sem função') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="member-actions">
                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" class="action-btn btn-whatsapp" title="WhatsApp">
                        <i data-lucide="message-circle" style="width: 16px;"></i>
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <button onclick='openEditModal(<?= json_encode($user) ?>)' class="action-btn btn-edit" title="Editar">
                            <i data-lucide="edit-3" style="width: 16px;"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Excluir este membro?');" style="margin: 0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <button type="submit" class="action-btn btn-delete" title="Excluir">
                                <i data-lucide="trash-2" style="width: 16px;"></i>
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
    <button onclick="openAddModal()" class="fab">
        <i data-lucide="plus" style="width: 28px;"></i>
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
                                    <span style="font-weight: 500; font-size: 0.85rem;"><?= $role['name'] ?></span>
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
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Recomendado: Últimos 4 dígitos do celular</p>
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
        const cards = document.querySelectorAll('.member-card');

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
    $colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#d946ef', '#f43f5e'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

renderAppFooter();
?>