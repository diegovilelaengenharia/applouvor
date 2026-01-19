<?php
// admin/membros.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Verificar se é admin (você pode adicionar verificação de sessão aqui)

// --- LÓGICA DE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
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
            $stmt->execute([
                $_POST['name'],
                $_POST['role'],
                $_POST['instrument'],
                $_POST['phone'],
                $_POST['password'],
                $_POST['id']
            ]);
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
$users = $pdo->query("SELECT * FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Membros');
?>

<style>
    .member-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .member-info {
        flex: 1;
    }

    .member-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .member-details {
        font-size: 0.85rem;
        color: var(--text-secondary);
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .member-actions {
        display: flex;
        gap: 8px;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: var(--bg-secondary);
        border-radius: 20px;
        padding: 24px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
</style>

<!-- Hero Header -->
<div style="
    background: var(--gradient-yellow); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Membros</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
        <div style="width: 44px;"></div> <!-- Spacer -->
    </div>

    <!-- Floating Toolbar -->
    <div style="position: absolute; bottom: -28px; left: 20px; right: 20px; z-index: 10;">
        <div style="
            background: var(--bg-secondary); 
            border-radius: 16px; 
            padding: 6px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            display: flex; 
            align-items: center;
            border: 1px solid rgba(0,0,0,0.05);
            gap: 8px;
        ">
            <div style="
                width: 44px; 
                height: 44px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                color: var(--primary-green);
                flex-shrink: 0;
            ">
                <i data-lucide="search" style="width: 22px;"></i>
            </div>

            <input
                type="text"
                id="memberSearch"
                placeholder="Buscar membros..."
                onkeyup="filterMembers()"
                style="
                    border: none; 
                    background: transparent; 
                    padding: 12px 0; 
                    flex: 1; 
                    font-size: 1rem; 
                    color: var(--text-primary);
                    outline: none;
                    font-weight: 500;
                    min-width: 0;
                ">

            <button onclick="openAddModal()" class="ripple" style="
                background: var(--accent-interactive); 
                color: white; 
                border: none; 
                padding: 10px 16px; 
                border-radius: 12px; 
                font-weight: 700; 
                font-size: 0.9rem; 
                display: flex; 
                align-items: center; 
                gap: 6px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(45, 122, 79, 0.2);
            ">
                <i data-lucide="plus" style="width: 18px;"></i> <span style="display: none; @media(min-width: 360px) { display: inline; }">Novo</span>
            </button>
        </div>
    </div>
</div>

<script>
    function filterMembers() {
        const input = document.getElementById('memberSearch');
        const filter = input.value.toLowerCase();
        const cards = document.getElementsByClassName('member-card');

        for (let i = 0; i < cards.length; i++) {
            const name = cards[i].querySelector('.member-name').textContent || cards[i].querySelector('.member-name').innerText;
            if (name.toLowerCase().indexOf(filter) > -1) {
                cards[i].style.display = "";
            } else {
                cards[i].style.display = "none";
            }
        }
    }
</script>

<!-- Lista de Membros -->
<?php foreach ($users as $user): ?>
    <div class="member-card">
        <div class="member-info">
            <div class="member-name">
                <?= htmlspecialchars($user['name']) ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <span style="background: #DCFCE7; color: #166534; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; margin-left: 8px;">ADMIN</span>
                <?php endif; ?>
            </div>
            <div class="member-details">
                <span><i data-lucide="music" style="width: 14px; display: inline;"></i> <?= htmlspecialchars($user['instrument'] ?: 'Não definido') ?></span>
                <span><i data-lucide="phone" style="width: 14px; display: inline;"></i> <?= htmlspecialchars($user['phone']) ?></span>
            </div>
        </div>
        <div class="member-actions">
            <button onclick='openEditModal(<?= json_encode($user) ?>)' class="btn-icon" style="color: var(--accent-interactive);">
                <i data-lucide="edit-2" style="width: 18px;"></i>
            </button>
            <form method="POST" onsubmit="return confirm('Excluir este membro?');" style="margin: 0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <button type="submit" class="btn-icon" style="color: var(--status-error);">
                    <i data-lucide="trash-2" style="width: 18px;"></i>
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal Adicionar -->
<div id="modalAdd" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin-bottom: 20px;">Novo Membro</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-input" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Instrumento/Função</label>
                <input type="text" name="instrument" class="form-input" placeholder="Ex: Voz, Violão, Bateria">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Telefone</label>
                <input type="text" name="phone" class="form-input" placeholder="37 98888-8888">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Senha (4 dígitos)</label>
                <input type="text" name="password" class="form-input" required placeholder="Últimos 4 dígitos do telefone">
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Tipo de Acesso</label>
                <select name="role" class="form-input">
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('modalAdd')" class="btn-outline ripple" style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn-primary ripple" style="flex: 1;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEdit" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin-bottom: 20px;">Editar Membro</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Nome</label>
                <input type="text" name="name" id="edit_name" class="form-input" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Instrumento/Função</label>
                <input type="text" name="instrument" id="edit_instrument" class="form-input">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Telefone</label>
                <input type="text" name="phone" id="edit_phone" class="form-input">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Senha</label>
                <input type="text" name="password" id="edit_password" class="form-input" required>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Tipo de Acesso</label>
                <select name="role" id="edit_role" class="form-input">
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('modalEdit')" class="btn-outline ripple" style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn-primary ripple" style="flex: 1;">Atualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalAdd').classList.add('active');
    }

    function openEditModal(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_instrument').value = user.instrument || '';
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_password').value = user.password;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('modalEdit').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Fechar modal ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
</script>

<?php renderAppFooter(); ?>