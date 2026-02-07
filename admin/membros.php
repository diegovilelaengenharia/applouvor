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
        border: 1px solid var(--border-subtle);
        transition: all 0.2s;
        cursor: pointer;
        color: var(--text-secondary);
        background: var(--bg-surface);
        flex-shrink: 0; /* Prevent button from shrinking */
    }

    .btn-action-icon:hover {
        background: var(--bg-surface-active);
        color: var(--text-primary);
        border-color: var(--border-color);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-action-whatsapp {
        color: var(--green-600);
        background: var(--green-50);
        border-color: var(--green-200);
    }
    .btn-action-whatsapp:hover {
        background: var(--green-100);
        color: var(--green-700);
        border-color: var(--green-300);
    }
    
    .btn-action-delete {
        color: var(--red-500);
        border-color: var(--red-100);
    }
    .btn-action-delete:hover {
        background: var(--red-50);
        color: var(--red-600);
        border-color: var(--red-200);
    }
    
    .btn-action-profile {
        color: var(--primary);
        background: rgba(55, 106, 200, 0.1);
        border-color: rgba(55, 106, 200, 0.2);
    }
    .btn-action-profile:hover {
        background: rgba(55, 106, 200, 0.2);
        color: var(--primary);
        border-color: rgba(55, 106, 200, 0.3);
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
        background: white !important; 
        border-radius: 20px; 
        padding: 24px; 
        box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        max-height: 90vh; 
        overflow-y: auto; 
    }
    
    .modal-header { 
        text-align: center; 
        margin-bottom: 24px; 
    }
    
    .modal-header h2 { 
        font-size: 1.25rem; 
        font-weight: 800; 
        color: #1a1a1a !important; 
        margin: 0 0 4px 0; 
    }
    
    .modal-header p { 
        color: #666 !important; 
        font-size: 0.9rem; 
        margin: 0; 
    }
    
    .form-group { 
        margin-bottom: 16px; 
    }
    
    .form-label { 
        display: block; 
        font-size: 0.9rem; 
        font-weight: 600; 
        color: #1a1a1a !important; 
        margin-bottom: 6px; 
    }
    
    .form-input { 
        width: 100%; 
        padding: 10px; 
        border-radius: 8px; 
        border: 1px solid #ddd; 
        background: #f9f9f9 !important; 
        color: #1a1a1a !important; 
        font-size: 0.9rem; 
    }
    
    .form-input:focus { 
        outline: none; 
        border-color: var(--primary); 
        background: white !important;
    }
    
    .roles-container { 
        max-height: 200px; 
        overflow-y: auto; 
        border: 1px solid #ddd; 
        border-radius: 8px; 
        padding: 8px; 
        background: #f9f9f9 !important; 
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
        padding: 6px; 
        border-radius: 6px; 
        background: white !important; 
        border: 1px solid #ddd; 
        cursor: pointer; 
    }
    
    .role-option:hover { 
        border-color: var(--primary); 
        background: #e8f5e9 !important; 
    }
    
    .role-option span {
        color: #1a1a1a !important;
    }
    
    .role-checkbox {
        accent-color: var(--primary);
    }
    
    .modal-actions { 
        display: flex; 
        gap: 12px; 
        margin-top: 24px; 
    }
    
    .btn-cancel { 
        flex: 1; 
        padding: 12px; 
        border-radius: 8px; 
        border: 1px solid #ddd; 
        background: white !important; 
        color: #666 !important; 
        font-weight: 600; 
        cursor: pointer; 
        transition: all 0.2s;
    }
    
    .btn-cancel:hover {
        background: #f5f5f5 !important;
        border-color: #999;
    }
    
    .btn-submit { 
        flex: 2; 
        padding: 12px; 
        border-radius: 8px; 
        border: none; 
        background: var(--primary) !important; 
        color: white !important; 
        font-weight: 700; 
        cursor: pointer; 
        transition: all 0.2s;
    }
    
    .btn-submit:hover {
        background: var(--green-700) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }


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
        <?php foreach ($users as $user): ?>
            <!-- Compact Card Structure -->
            <div class="compact-card" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>"
                 style="border-left-color: var(--primary);">
                
                <!-- Avatar/Icon -->
                <div class="compact-card-icon rounded" style="background: rgba(55, 106, 200, 0.1); color: var(--primary); width: 40px; height: 40px;">
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
    <a href="perfil.php?new=1" class="fab" style="position: fixed; bottom: 84px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: var(--primary); color: white; border: none; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 90; transition: transform 0.2s; text-decoration: none;">
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
    $colors = ['var(--rose-500)', '#f97316', 'var(--yellow-500)', '#84cc16', 'var(--sage-500)', '#06b6d4', 'var(--slate-500)', '#6366f1', 'var(--lavender-500)', '#d946ef', '#f43f5e'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

renderAppFooter();
?>