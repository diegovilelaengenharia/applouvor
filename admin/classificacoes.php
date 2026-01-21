<?php
// admin/classificacoes.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// --- LOGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Criar
        if ($_POST['action'] === 'create') {
            $stmt = $pdo->prepare("INSERT INTO tags (name, description, color) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['color']]);
        }
        // Editar
        elseif ($_POST['action'] === 'update') {
            $stmt = $pdo->prepare("UPDATE tags SET name = ?, description = ?, color = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['color'], $_POST['id']]);
        }
        // Excluir
        elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        header("Location: classificacoes.php");
        exit;
    }
}

// Buscar Tags
$tags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Classificações');
?>

<style>
    .tag-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .tag-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-subtle);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        transition: transform 0.2s;
    }

    .tag-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .tag-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .tag-content {
        flex: 1;
    }

    .tag-title {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 1.1rem;
        margin-bottom: 4px;
        display: block;
        text-decoration: none;
    }

    .tag-desc {
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .btn-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #047857;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.4);
        border: none;
        cursor: pointer;
        z-index: 100;
        transition: transform 0.2s;
    }

    .btn-fab:hover {
        transform: scale(1.1);
    }
</style>

<!-- Hero Simplificado -->
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.8rem; font-weight: 800; color: white;">Gestão de Tags</h1>
    <p style="color: rgba(255,255,255,0.8);">Crie pastas para organizar o repertório.</p>
</div>

<div class="tag-list">
    <?php foreach ($tags as $tag): ?>
        <div class="tag-card">
            <div class="tag-icon" style="background: <?= $tag['color'] ?: '#047857' ?>;">
                <i data-lucide="folder" style="width: 24px;"></i>
            </div>
            <div class="tag-content">
                <div class="tag-title"><?= htmlspecialchars($tag['name']) ?></div>
                <div class="tag-desc"><?= htmlspecialchars($tag['description']) ?></div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button onclick='editTag(<?= json_encode($tag) ?>)' class="btn-icon ripple" style="color: #64748b;">
                    <i data-lucide="edit-2" style="width: 18px;"></i>
                </button>
                <form method="POST" onsubmit="return confirm('Excluir esta tag?');" style="margin: 0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                    <button type="submit" class="btn-icon ripple" style="color: #ef4444;">
                        <i data-lucide="trash-2" style="width: 18px;"></i>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($tags)): ?>
        <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.7);">
            <i data-lucide="folder-open" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Nenhuma classificação criada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- FAB Add -->
<button class="btn-fab ripple" onclick="openModal()">
    <i data-lucide="plus" style="width: 24px;"></i>
</button>

<!-- Modal CRUD -->
<div id="modalTag" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Nova Classificação</div>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="tagId">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Nome da Pasta</label>
                <input type="text" name="name" id="tagName" class="form-input" placeholder="Ex: Adoração" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Descrição</label>
                <textarea name="description" id="tagDesc" class="form-input" rows="3" placeholder="Para que serve..."></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Cor</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $colors = ['#047857', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899', '#6366F1'];
                    foreach ($colors as $c): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="color" value="<?= $c ?>" style="display: none;" onchange="selectColor(this)">
                            <div class="color-circle" style="
                                width: 32px; height: 32px; 
                                background: <?= $c ?>; 
                                border-radius: 50%; 
                                border: 2px solid transparent;
                                transition: transform 0.2s;
                            "></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-action-save ripple" style="width: 100%; justify-content: center;">Salvar</button>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('formAction').value = 'create';
        document.getElementById('tagId').value = '';
        document.getElementById('tagName').value = '';
        document.getElementById('tagDesc').value = '';
        document.querySelector('.sheet-header').textContent = 'Nova Classificação';
        document.getElementById('modalTag').classList.add('active');
    }

    function editTag(tag) {
        document.getElementById('formAction').value = 'update';
        document.getElementById('tagId').value = tag.id;
        document.getElementById('tagName').value = tag.name;
        document.getElementById('tagDesc').value = tag.description;
        // Selecionar cor (simplificado)

        document.querySelector('.sheet-header').textContent = 'Editar Classificação';
        document.getElementById('modalTag').classList.add('active');
    }

    // Fechar modal ao clicar fora (já implementado no layout.php se usar overlay)
    document.getElementById('modalTag').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });

    function selectColor(input) {
        document.querySelectorAll('.color-circle').forEach(c => {
            c.style.transform = 'scale(1)';
            c.style.borderColor = 'transparent';
        });
        if (input.checked) {
            input.nextElementSibling.style.transform = 'scale(1.2)';
            input.nextElementSibling.style.borderColor = 'white';
            input.nextElementSibling.style.boxShadow = '0 0 0 2px ' + input.value;
        }
    }
</script>

<?php renderAppFooter(); ?>