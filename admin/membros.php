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

        // Excluir membro
        if ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: membros.php");
            exit;
        }
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

<!-- Import CSS -->
<link rel="stylesheet" href="../assets/css/pages/membros.css?v=<?= time() ?>">

<div class="members-container">
    <!-- Search Bar -->
    <div class="search-box">
        <i data-lucide="search" class="search-icon" width="20"></i>
        <input type="text" id="memberSearch" placeholder="Buscar por nome ou instrumento..." onkeyup="filterMembers()">
    </div>

    <!-- Members List (Timeline/Compact Style) -->
    <div class="members-list">
        <?php foreach ($users as $user): ?>
            <!-- Compact Card Structure -->
            <div class="compact-card" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>"
                 style="border-left-color: var(--primary);">
                
                <!-- Avatar/Icon -->
                <div class="compact-card-icon rounded">
                    <?php 
                    $initial = strtoupper(substr($user['name'], 0, 1));
                    if (!empty($user['avatar'])) {
                        $avatarPath = $user['avatar'];
                        if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                            $avatarPath = '../assets/uploads/' . $avatarPath;
                        } elseif (strpos($avatarPath, 'assets/') === 0) {
                             $avatarPath = '../' . $avatarPath;
                        }
                        
                        echo "<img src=\"" . htmlspecialchars($avatarPath) . "\" alt=\"" . htmlspecialchars($user['name']) . "\" class=\"avatar-img\" loading=\"lazy\" onerror=\"this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');\">";
                        echo "<span class='fallback-initial hidden font-bold text-lg'>" . $initial . "</span>";
                    } else {
                        echo "<span class='font-bold text-lg'>" . $initial . "</span>";
                    }
                    ?>
                </div>

                <!-- Content -->
                <div class="compact-card-content">
                    <div class="compact-card-title">
                        <?= htmlspecialchars($user['name']) ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge-admin">ADM</span>
                        <?php endif; ?>
                    </div>
                    <div class="compact-card-subtitle">
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
                            <span class="more-roles">+<?= (count($uniqueRoles) - 4) ?></span>
                        <?php 
                            endif;
                        else: 
                        ?>
                            <span class="no-role">
                                <?= htmlspecialchars($user['instrument'] ?: 'Sem função definida') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="member-actions-wrapper">
                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" class="btn-action-icon btn-action-whatsapp" title="WhatsApp">
                        <i data-lucide="message-circle" width="18"></i>
                    </a>
                    
                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                        <a href="perfil.php" class="btn-action-icon btn-action-profile" title="Meu Perfil">
                            <i data-lucide="user" width="18"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="perfil.php?id=<?= $user['id'] ?>" class="btn-action-icon" title="Editar Perfil">
                            <i data-lucide="edit-3" width="18"></i>
                        </a>
                        <button type="button" class="btn-action-icon btn-action-delete" title="Excluir" onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')">
                            <i data-lucide="trash-2" width="18"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FAB (Admin Only) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a href="perfil.php?new=1" class="fab">
        <i data-lucide="plus" width="28"></i>
    </a>
<?php endif; ?>


<!-- DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay" onclick="if(event.target === this) closeDeleteModal()">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: var(--danger);">
                <i data-lucide="trash-2" width="20"></i> Excluir Membro
            </h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">
                <i data-lucide="x" width="20"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 10px;">Tem certeza que deseja excluir este membro?</p>
            <p id="deleteMemberName" style="font-weight: 600; font-size: 1.1em; color: var(--text-primary);"></p>
            <p style="font-size: 0.9em; color: var(--text-tertiary); margin-top: 10px;">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancelar</button>
            <form method="POST" id="deleteForm" style="margin: 0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteMemberId">
                <button type="submit" class="btn btn-danger">Excluir Permanentemente</button>
            </form>
        </div>
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

    function confirmDelete(id, name) {
        document.getElementById('deleteMemberId').value = id;
        document.getElementById('deleteMemberName').textContent = name;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }
</script>

<?php
function generateAvatarColor($name) {
    // Usando apenas variáveis do Design System
    $colors = [
        'var(--rose-500)', 
        'var(--orange-500)', 
        'var(--yellow-500)', 
        'var(--lime-500)', 
        'var(--sage-500)', 
        'var(--cyan-500)', 
        'var(--slate-500)', 
        'var(--indigo-500)', 
        'var(--lavender-500)', 
        'var(--fuchsia-500)', 
        'var(--pink-500)'
    ];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

renderAppFooter();
?>