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
renderPageHeader('Gestão de Tags', 'Crie pastas para organizar o repertório');
?>

<style>
    /* Tag Cards */
    .tag-list {
        display: grid;
        gap: 8px;
        margin-bottom: 80px;
    }

    .tag-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .tag-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .tag-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
    }

    .tag-icon i {
        width: 16px;
    }

    .tag-content {
        flex: 1;
        min-width: 0;
    }

    .tag-title {
        font-weight: 700;
        font-size: var(--font-body);
        color: var(--text-main);
        margin-bottom: 2px;
    }

    .tag-desc {
        font-size: var(--font-caption);
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-icon i {
        width: 16px;
    }

    .btn-icon:hover {
        background: var(--bg-body);
    }

    /* FAB Button */
    .btn-fab {
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        border: none;
        color: white;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
        z-index: 100;
    }

    .btn-fab i {
        width: 20px;
    }

    .btn-fab:hover {
        transform: scale(1.1);
    }

    .btn-fab:active {
        transform: scale(0.95);
    }

    /* Bottom Sheet Modal */
    .bottom-sheet-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        align-items: flex-end;
    }

    .bottom-sheet-overlay.active {
        display: flex;
    }

    .bottom-sheet-content {
        background: var(--bg-surface);
        border-radius: 24px 24px 0 0;
        padding: 20px;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }

    .sheet-header {
        font-size: var(--font-h2);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 20px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 12px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        font-size: var(--font-body-sm);
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--bg-body);
        color: var(--text-main);
        font-size: var(--font-body);
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }

    .btn-action-save {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: var(--font-body);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
    }

    .btn-action-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(4, 120, 87, 0.3);
    }

    .btn-action-save:active {
        transform: translateY(0);
    }

    .color-circle {
        cursor: pointer;
    }

    @media (max-width: 1024px) {
        .btn-fab {
            bottom: 80px;
        }
    }
</style>

<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">



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