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
                        
                        echo "<img src=\"" . htmlspecialchars($avatarPath) . "\" alt=\"" . htmlspecialchars($user['name']) . "\" style=\"width:100%; height:100%; object-fit:cover; border-radius:50%;\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">";
                        echo "<span style='display:none;' class='fallback-initial font-bold text-lg'>" . $initial . "</span>";
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
                            <span style="background: var(--yellow-500); color: white; padding: 1px 4px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; margin-left: 6px;">ADM</span>
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
    <a href="perfil.php?new=1" class="fab">
        <i data-lucide="plus" width="28"></i>
    </a>
<?php endif; ?>


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