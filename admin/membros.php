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
    <div class="members-list" style="display: flex; flex-direction: column; gap: var(--space-md); padding-bottom: 100px;">
        <?php 
        $delay = 0.1;
        foreach ($users as $user): 
            $initial = strtoupper(substr($user['name'], 0, 1));
            $avatarPath = !empty($user['avatar']) ? $user['avatar'] : '';
            if (!empty($avatarPath)) {
                if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                    $avatarPath = '../assets/uploads/' . $avatarPath;
                } elseif (strpos($avatarPath, 'assets/') === 0) {
                     $avatarPath = '../' . $avatarPath;
                }
            }
        ?>
            <!-- PIB MEMBER CARD -->
            <div class="animate-card" style="animation-delay: <?= $delay ?>s;" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>">
                
                <div class="pib-card" style="flex-direction: row; align-items: center; gap: var(--space-md);">
                    <!-- Avatar -->
                    <div style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; background: var(--color-surface-alt); border: 2px solid var(--color-primary); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <?php if ($avatarPath): ?>
                            <img src="<?= htmlspecialchars($avatarPath) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span style="display: none; font-weight: 800; font-size: 1.2rem; color: var(--color-primary);"><?= $initial ?></span>
                        <?php else: ?>
                            <span style="font-weight: 800; font-size: 1.2rem; color: var(--color-primary);"><?= $initial ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 800;"><?= htmlspecialchars($user['name']) ?></h3>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="pib-badge pib-badge-danger" style="font-size: 0.5rem; padding: 1px 6px;">ADM</span>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                            <?php 
                            if (!empty($user['roles'])): 
                                $displayRoles = array_slice($user['roles'], 0, 3);
                                foreach ($displayRoles as $role): 
                            ?>
                                <span style="background: var(--color-surface-alt); color: var(--color-text-muted); padding: 2px 8px; border-radius: var(--radius-sm); font-size: 0.65rem; font-weight: 700; border: 1px solid var(--color-border); display: flex; align-items: center; gap: 4px;">
                                    <span><?= $role['icon'] ?></span>
                                    <span><?= htmlspecialchars($role['name']) ?></span>
                                </span>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" style="width: 36px; height: 36px; background: #25d366; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);" title="WhatsApp">
                            <i data-lucide="message-circle" style="width: 18px;"></i>
                        </a>
                        
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="perfil.php?id=<?= $user['id'] ?>" style="width: 36px; height: 36px; background: var(--color-surface-alt); color: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--color-border);" title="Editar">
                                <i data-lucide="edit-3" style="width: 18px;"></i>
                            </a>
                            <button type="button" onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" style="width: 36px; height: 36px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #fecaca; cursor: pointer;" title="Excluir">
                                <i data-lucide="trash-2" style="width: 18px;"></i>
                            </button>
                        <?php else: ?>
                            <a href="perfil.php?id=<?= $user['id'] ?>" style="width: 36px; height: 36px; background: var(--color-surface-alt); color: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--color-border);">
                                <i data-lucide="user" style="width: 18px;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php 
            $delay += 0.05;
        endforeach; ?>
    </div>
</div>

<!-- FAB (Admin Only) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a href="perfil.php?new=1" class="fab">
        <i data-lucide="plus" width="28"></i>
    </a>
<?php endif; ?>


<!-- DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="urgent-modal-overlay" onclick="if(event.target === this) closeDeleteModal()">
    <div class="urgent-modal-card">
        
        <div class="urgent-icon-wrapper">
             <i data-lucide="alert-triangle" width="32"></i>
        </div>
        
        <h3 class="urgent-title">Excluir Membro</h3>
        <p class="urgent-subtitle" id="deleteMemberName"></p>
        
        <div class="urgent-body">
            Tem certeza que deseja excluir este membro?<br>
            Esta ação não pode ser desfeita e todo o histórico associado (como escalas) pode ser afetado ou perdido.
        </div>
        
        <form method="POST" id="deleteForm" style="margin: 0; width: 100%;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteMemberId">
            <button type="submit" class="btn-urgent-action">Sim, Excluir Membro</button>
            <button type="button" class="btn btn-ghost" style="width: 100%; margin-top: 12px;" onclick="closeDeleteModal()">Cancelar</button>
        </form>
    </div>
</div>

<script>
    function filterMembers() {
        const term = document.getElementById('memberSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.animate-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const role = card.getAttribute('data-role');
            if (name.includes(term) || role.includes(term)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteMemberId').value = id;
        document.getElementById('deleteMemberName').textContent = name;
        // O urgent-modal usa style.display = 'flex' no CSS dele
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
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