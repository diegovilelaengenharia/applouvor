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
            header("Location: membros.php");
            exit;
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

            header("Location: membros.php");
            exit;
        }
        // Excluir membro
        elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: membros.php");
            exit;
        }
    }
}

// Buscar todos os membros
$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Membros');
?>

    <?php
    renderPageHeader('Equipe', count($users) . ' membros cadastrados');
    ?>

<div class="container" style="padding-top: 16px; max-width: 900px; margin: 0 auto;">

    <div style="margin-bottom: 16px;">
        <div style="display: none;"> <!-- Ocultando header manual antigo -->
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">Equipe</h1>
                <p style="color: var(--text-muted); margin-top: 2px; font-size: 0.9rem;">
                    <?= count($users) ?> membros cadastrados
                </p>
            </div>
        </div>


        <!-- Barra de Busca -->
        <div style="position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 20px;"></i>
            <input type="text" id="memberSearch" placeholder="Buscar por nome ou instrumento..." onkeyup="filterMembers()"
                style="
                       width: 100%; padding: 12px 14px 12px 48px; border-radius: var(--radius-md); 
                       border: 1px solid var(--border-color); font-size: 1rem; outline: none; 
                       transition: border 0.2s; background: var(--bg-surface); box-shadow: var(--shadow-sm);
                       color: var(--text-main);
                   "
                onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-color)'">
        </div>
    </div>

    <!-- Grid de Cards -->
    <div class="member-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">

        <?php foreach ($users as $user): ?>
            <div class="member-card" data-name="<?= strtolower($user['name']) ?>" data-role="<?= strtolower($user['instrument'] ?? '') ?>">
                <div style="display: flex; align-items: center; gap: 12px;">

                    <!-- Avatar Compacto -->
                    <div style="
                        width: 42px; height: 42px; border-radius: 50%; 
                        background: <?= generateAvatarColor($user['name']) ?>; 
                        color: white; display: flex; align-items: center; justify-content: center;
                        font-weight: 700; font-size: 1rem; flex-shrink: 0;
                        box-shadow: var(--shadow-sm);
                    ">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($user['avatar']) ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <!-- Info (Nome e Função) -->
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <h3 class="member-name" style="margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($user['name']) ?>
                            </h3>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span style="background: var(--primary-subtle); color: var(--primary); padding: 1px 4px; border-radius: 4px; font-size: 0.6rem; font-weight: 800;">ADM</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($user['instrument'] ?: 'Membro') ?>
                        </p>
                    </div>

                    <!-- Ações Rápidas (Compactas) -->
                    <div style="display: flex; align-items: center; gap: 4px;">
                    <!-- Ações Rápidas (Compactas) -->
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" class="ripple icon-action" title="WhatsApp" style="color: #25D366; background: #ecfdf5;">
                            <i data-lucide="message-circle" style="width: 16px;"></i>
                        </a>
                        <a href="tel:<?= $user['phone'] ?>" class="ripple icon-action" title="Ligar" style="color: var(--text-muted); background: var(--bg-body);">
                            <i data-lucide="phone" style="width: 16px;"></i>
                        </a>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <!-- Menu Trigger (Admin Only) -->
                            <div style="position: relative;">
                                <button onclick="toggleMenu(event, 'menu-<?= $user['id'] ?>')" class="ripple icon-action" style="color: var(--text-muted); background: transparent;">
                                    <i data-lucide="more-vertical" style="width: 16px;"></i>
                                </button>

                                <!-- Dropdown -->
                                <div id="menu-<?= $user['id'] ?>" class="dropdown-menu" style="
                                    display: none; position: absolute; right: 0; top: 32px; 
                                    background: var(--bg-surface); border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                                    border: 1px solid var(--border-color); width: 140px; z-index: 50; overflow: hidden;
                                ">
                                    <button onclick='openEditModal(<?= json_encode($user) ?>)' style="width: 100%; text-align: left; padding: 10px; background: transparent; border: none; color: var(--text-main); font-size: 0.85rem; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <i data-lucide="edit-3" style="width: 14px;"></i> Editar
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Excluir este membro?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" style="width: 100%; text-align: left; padding: 10px; background: transparent; border: none; color: #ef4444; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; cursor: pointer; border-top: 1px solid var(--border-color);">
                                            <i data-lucide="trash-2" style="width: 14px;"></i> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <div style="height: 60px;"></div>

    <!-- Floating Action Button (Admin Only) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <button onclick="openAddModal()" class="ripple" style="
            position: fixed; bottom: 32px; right: 24px;
            background: #166534; color: white; padding: 16px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(22, 101, 52, 0.4);
            border: none; cursor: pointer; z-index: 50; transition: transform 0.2s;
        " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14" />
                <path d="M12 5v14" />
            </svg>
        </button>
    <?php endif; ?>
</div>

<!-- MODAL ADD/EDIT -->
<div id="memberModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);"></div>

    <div style="
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 90%; max-width: 450px; background: var(--bg-surface); border-radius: 24px; padding: 32px;
        box-shadow: var(--shadow-md);
        max-height: 90vh; overflow-y: auto;
    ">
        <div style="margin-bottom: 24px; text-align: center;">
            <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">Novo Membro</h2>
            <p style="color: var(--text-muted); margin-top: 4px;">Gerencie as informações de acesso</p>
        </div>

        <form method="POST" id="memberForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Nome Completo</label>
                <input type="text" name="name" id="userName" required class="input-modern" placeholder="Ex: João da Silva">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Função</label>
                    <input type="text" name="instrument" id="userInst" class="input-modern" placeholder="Ex: Baixo">
                </div>
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Permissão</label>
                    <div style="position: relative;">
                        <select name="role" id="userRole" class="input-modern" style="appearance: none;">
                            <option value="user">Membro</option>
                            <option value="admin">Admin</option>
                        </select>
                        <i data-lucide="chevron-down" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-muted); pointer-events: none;"></i>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">WhatsApp</label>
                <input type="text" name="phone" id="userPhone" class="input-modern" placeholder="(37) 99999-9999">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Senha de Acesso</label>
                <input type="text" name="password" id="userPass" required class="input-modern" placeholder="4 dígitos para login">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Recomendado: Últimos 4 dígitos do celular</p>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="
                    flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); 
                    color: var(--text-muted); font-weight: 600; cursor: pointer;
                ">Cancelar</button>
                <button type="submit" style="
                    flex: 2; padding: 14px; border-radius: 12px; border: none; background: var(--text-main); 
                    color: white; font-weight: 700; cursor: pointer; box-shadow: var(--shadow-sm);
                ">Salvar</button>
            </div>

        </form>
    </div>
</div>

<style>
    .member-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 12px;
    }

    .member-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        /* Radius otimizado */
        padding: 12px;
        /* Padding reduzido de 16px */
        display: flex;
        align-items: center;
        gap: 12px;
        /* Gap reduzido */
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .member-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .member-avatar {
        width: 42px;
        /* Reduzido de 48px */
        height: 42px;
        border-radius: 10px;
        /* Radius avatar otimizado */
        background-color: var(--bg-body);
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: var(--shadow-sm);
    }

    .member-info h3 {
        font-size: 0.95rem;
        /* Fonte reduzida */
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 2px;
        line-height: 1.2;
        margin: 0;
    }

    .member-role {
        font-size: 0.75rem;
        /* Fonte reduzida */
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icon-action {
        width: 32px;
        /* Ícone de ação compacto */
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
    }

    .icon-action:hover {
        filter: brightness(0.95);
    }

    /* Modal Mobile Otimizado */
    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 400px;
        /* Max width reduzido */
        background: var(--bg-surface);
        border-radius: 20px;
        /* Radius modal */
        padding: 24px;
        /* Padding modal reduzido */
        box-shadow: var(--shadow-md);
        max-height: 90vh;
        overflow-y: auto;
    }

    .input-modern {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        font-size: 1rem;
        color: var(--text-main);
        outline: none;
        transition: all 0.2s;
        background: var(--bg-body);
    }

    .input-modern:focus {
        background: var(--bg-surface);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
    }
</style>

<script>
    // Gerador de cores para avatar
    // (O PHP já cuida disso no server side rendering, mas se precisar no JS, ok)

    // Filtro de Busca
    function filterMembers() {
        const term = document.getElementById('memberSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.member-card');

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

    // Modal Logic
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Novo Membro';
        document.getElementById('formAction').value = 'add';
        document.getElementById('memberForm').reset();
        document.getElementById('userId').value = '';
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

        closeAllMenus();
        document.getElementById('memberModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('memberModal').style.display = 'none';
    }

    // Dropdown Logic
    function toggleMenu(e, id) {
        if(e) e.stopPropagation();
        
        // Se este j├í est├í aberto, fecha todos. Se n├úo, fecha e abre este.
        const menu = document.getElementById(id);
        const isVisible = menu.style.display === 'block';
        
        closeAllMenus();
        
        if (!isVisible) {
            menu.style.display = 'block';
        }
    }

    function closeAllMenus() {
        document.querySelectorAll('.dropdown-menu').forEach(el => el.style.display = 'none');
    }

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        // Se n├úo clicou em um bot├úo de menu ou dentro de um menu, fecha tudo
        if (!e.target.closest('.icon-action') && !e.target.closest('.dropdown-menu')) {
            closeAllMenus();
        }
    });
</script>

<?php
// Helper function para cor
function generateAvatarColor($name)
{
    $colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#d946ef', '#f43f5e'];
    $index = crc32($name) % count($colors);
    return $colors[$index];
}

renderAppFooter();
?>