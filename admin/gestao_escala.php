<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();

if (!isset($_GET['id'])) {
    header("Location: escala.php");
    exit;
}

$scale_id = $_GET['id'];

// Buscar Dados da Escala
$stmt = $pdo->prepare("SELECT * FROM scales WHERE id = ?");
$stmt->execute([$scale_id]);
$scale = $stmt->fetch();

if (!$scale) {
    die("Escala não encontrada.");
}

// Update Scale Event Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE scales SET event_date = ?, event_type = ?, description = ? WHERE id = ?");
    $stmt->execute([$date, $type, $description, $scale_id]);
    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Delete Scale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scale'])) {
    // Delete members first
    $pdo->prepare("DELETE FROM scale_members WHERE scale_id = ?")->execute([$scale_id]);
    // Delete scale
    $pdo->prepare("DELETE FROM scales WHERE id = ?")->execute([$scale_id]);
    header("Location: escala.php");
    exit;
}

// Adicionar Membro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $user_id = $_POST['user_id'];
    $instrument = $_POST['instrument']; // Se não selecionado, usa a categoria padrão do user? Vamos forçar selection ou pegar do banco.

    // Verificar se já está na escala
    $check = $pdo->prepare("SELECT id FROM scale_members WHERE scale_id = ? AND user_id = ?");
    $check->execute([$scale_id, $user_id]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO scale_members (scale_id, user_id, instrument, confirmed) VALUES (?, ?, ?, 0)");
        $stmt->execute([$scale_id, $user_id, $instrument]);
    }

    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Remover Membro
if (isset($_GET['remove'])) {
    $member_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM scale_members WHERE id = ? AND scale_id = ?");
    $stmt->execute([$member_id, $scale_id]);
    header("Location: gestao_escala.php?id=$scale_id");
    exit;
}

// Buscar Membros Escalados
$stmt = $pdo->prepare("
    SELECT sm.*, u.name, u.category 
    FROM scale_members sm 
    JOIN users u ON sm.user_id = u.id 
    WHERE sm.scale_id = ?
    ORDER BY u.category, u.name
");
$stmt->execute([$scale_id]);
$members = $stmt->fetchAll();

// Buscar Todos Usuários para o Select (Agrupados ou Lista Simples)
// Buscar Todos Usuários para o Select (Agrupados ou Lista Simples)
$users = $pdo->query("SELECT * FROM users ORDER BY name")->fetchAll();

require_once '../includes/layout.php';

// Inicia Layout Padrão
renderAppHeader('Gerenciar Escala');
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
        <a href="escala.php" class="btn-icon" style="background: var(--bg-secondary); color: var(--text-primary); width: 36px; height: 36px;">
            <i data-lucide="arrow-left" style="width: 18px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0; font-size: 1.3rem;">
            <?= date('d/m', strtotime($scale['event_date'])) ?> - <?= htmlspecialchars($scale['event_type']) ?>
        </h1>
    </div>

    <!-- TABS -->
    <div style="display: flex; gap: 0; margin-bottom: 20px; background: var(--bg-tertiary); padding: 4px; border-radius: 12px;">
        <button onclick="showTab('evento')" id="btn-evento" class="tab-btn active">
            Configurar Evento
        </button>
        <button onclick="showTab('equipe')" id="btn-equipe" class="tab-btn">
            Gerenciar Equipe
        </button>
    </div>

    <!-- TAB EVENTO -->
    <div id="tab-evento">
        <div class="card" style="padding: 20px; border: none; box-shadow: none; background: transparent;">

            <form method="POST">
                <input type="hidden" name="update_event" value="1">

                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.75rem;">Data</label>
                        <input type="date" name="date" class="form-input" value="<?= $scale['event_date'] ?>" required style="background: var(--bg-secondary);">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.75rem;">Tipo</label>
                        <select name="type" class="form-select" style="background: var(--bg-secondary);">
                            <option value="Culto de Domingo" <?= $scale['event_type'] == 'Culto de Domingo' ? 'selected' : '' ?>>Culto de Domingo</option>
                            <option value="Ensaio" <?= $scale['event_type'] == 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                            <option value="Evento Jovens" <?= $scale['event_type'] == 'Evento Jovens' ? 'selected' : '' ?>>Evento Jovens</option>
                            <option value="Evento Especial" <?= $scale['event_type'] == 'Evento Especial' ? 'selected' : '' ?>>Evento Especial</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 0.75rem;">Detalhes / Liturgia</label>
                    <textarea name="description" class="form-input" rows="4" placeholder="Adicione observações..." style="resize: none; background: var(--bg-secondary);"><?= htmlspecialchars($scale['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="height: 48px; margin-top: 10px; margin-bottom: 30px;">
                    Salvar Alterações
                </button>
            </form>

            <!-- Delete Section Compact -->
            <div style="margin-top: 15px;">
                <form method="POST" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir esta escala?');">
                    <input type="hidden" name="delete_scale" value="1">
                    <button type="submit" class="btn w-full" style="background: var(--status-error); border: none; color: white; font-weight: 600; height: 48px;">
                        <i data-lucide="trash-2" style="width: 18px; margin-right: 6px;"></i> Excluir Escala
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB EQUIPE -->
    <div id="tab-equipe" style="display: none;">

        <div class="card" style="padding: 20px; border: none; box-shadow: none; background: transparent;">

            <!-- Header Resumo -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-primary);">Quadro Técnico</h3>
                <span style="font-size: 0.8rem; background: var(--bg-tertiary); padding: 4px 10px; border-radius: 20px; color: var(--text-secondary);"><?= count($members) ?> integrantes</span>
            </div>

            <?php if (empty($members)): ?>
                <div style="text-align: center; padding: 30px; background: var(--bg-primary); border-radius: 12px; border: 1px dashed var(--border-subtle); margin-bottom: 25px;">
                    <i data-lucide="user-x" style="width: 32px; height: 32px; color: var(--text-muted); opacity: 0.5; margin-bottom: 10px;"></i>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Nenhum técnico ou músico escalado.</p>
                </div>
            <?php else: ?>
                <?php
                // Agrupamento
                $groups = ['Voz' => [], 'Instrumental' => [], 'Apoio/Outros' => []];
                foreach ($members as $m) {
                    $cat = strtolower($m['category']);
                    $inst = strtolower($m['instrument']);

                    if (strpos($cat, 'voz') !== false || strpos($inst, 'voz') !== false || strpos($inst, 'bck') !== false || strpos($inst, 'soprano') !== false) {
                        $groups['Voz'][] = $m;
                    } elseif (in_array($cat, ['violao', 'teclado', 'bateria', 'baixo', 'guitarra']) || in_array($inst, ['violão', 'teclado', 'bateria', 'baixo', 'guitarra'])) {
                        $groups['Instrumental'][] = $m;
                    } else {
                        $groups['Apoio/Outros'][] = $m;
                    }
                }
                ?>

                <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px;">
                    <?php foreach ($groups as $groupName => $groupMembers): ?>
                        <?php if (!empty($groupMembers)): ?>
                            <div>
                                <h4 style="margin: 0 0 10px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                    <?= $groupName ?>
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($groupMembers as $member): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-subtle); border-radius: 12px;">

                                            <!-- User -->
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem; background: white; border: 1px solid var(--border-subtle);">
                                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;"><?= htmlspecialchars($member['name']) ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($member['instrument']) ?></div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <?php if ($member['confirmed'] == 1): ?>
                                                    <i data-lucide="check-circle-2" style="width: 18px; color: var(--status-success);"></i>
                                                <?php else: ?>
                                                    <i data-lucide="clock" style="width: 18px; color: var(--status-warning);"></i>
                                                <?php endif; ?>

                                                <a href="?id=<?= $scale_id ?>&remove=<?= $member['id'] ?>" onclick="return confirm('Remover integrante?')" style="color: var(--text-muted); opacity: 0.6; transition: opacity 0.2s;">
                                                    <i data-lucide="trash-2" style="width: 16px;"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Adicionar Novo Membro - Clean -->
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 20px;">
                <h3 style="margin-bottom: 15px; font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Adicionar Recurso</h3>
                <form method="POST">
                    <input type="hidden" name="add_member" value="1">

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px; margin-bottom: 15px;">
                        <!-- 1. Select Function -->
                        <div style="position: relative;">
                            <select name="instrument" id="roleSelect" class="form-select" onchange="filterUsersByRole()" required style="background: var(--bg-secondary);">
                                <option value="">Função...</option>
                                <option value="Voz">Voz</option>
                                <option value="Violão">Violão</option>
                                <option value="Teclado">Teclado</option>
                                <option value="Bateria">Bateria</option>
                                <option value="Baixo">Baixo</option>
                                <option value="Guitarra">Guitarra</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>

                        <!-- 2. Select User (Filtered) -->
                        <div style="position: relative;">
                            <select name="user_id" id="userSelect" class="form-select" required style="background: var(--bg-secondary);">
                                <option value="">Selecione primeiro a função...</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" data-role="<?= ucfirst(str_replace('_', ' ', $u['category'])) ?>" style="display:none;">
                                        <?= htmlspecialchars($u['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline w-full" style="height: 48px; border-color: var(--accent-interactive); color: var(--accent-interactive); border-style: dashed;">
                        <i data-lucide="plus" style="width: 18px;"></i> Adicionar à Escala
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<style>
    .tab-btn {
        flex: 1;
        padding: 8px;
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .tab-btn:hover {
        color: var(--text-primary);
    }

    .tab-btn.active {
        background: var(--bg-secondary);
        color: var(--text-primary);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
</style>

<script>
    function showTab(tab) {
        document.getElementById('tab-evento').style.display = 'none';
        document.getElementById('tab-equipe').style.display = 'none';

        document.getElementById('btn-evento').classList.remove('active');
        document.getElementById('btn-equipe').classList.remove('active');

        document.getElementById('tab-' + tab).style.display = 'block';
        document.getElementById('btn-' + tab).classList.add('active');
    }

    function filterUsersByRole() {
        const roleSelect = document.getElementById('roleSelect');
        const userSelect = document.getElementById('userSelect');
        const selectedRole = roleSelect.value.toLowerCase();

        // Reset user selection
        userSelect.value = "";

        const options = userSelect.getElementsByTagName('option');
        let count = 0;

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];

            // Skip placeholder
            if (opt.value === "") {
                opt.innerText = selectedRole ? "Selecione o integrante..." : "Selecione primeiro a função...";
                continue;
            }

            const optRole = (opt.getAttribute('data-role') || '').toLowerCase();

            // Logic: Show if role matches OR if role is empty (show all)
            // But user specifically asked for "Select role THEN show users"
            // So if no role selected, we might hide all or show all? Let's hide all to force workflow.

            if (selectedRole && (optRole.includes(selectedRole) || selectedRole === 'outros')) {
                opt.style.display = 'block';
                count++;
            } else {
                opt.style.display = 'none';
            }
        }
    }
</script>

<?php
renderAppFooter();
?>