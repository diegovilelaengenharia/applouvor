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
    <div style="display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
        <button onclick="showTab('evento')" id="btn-evento" class="tab-btn active">
            <i data-lucide="calendar-days" style="width: 18px;"></i> Dados do Evento
        </button>
        <button onclick="showTab('equipe')" id="btn-equipe" class="tab-btn">
            <i data-lucide="users" style="width: 18px;"></i> Escala Técnica
        </button>
    </div>

    <!-- TAB EVENTO -->
    <div id="tab-evento">
        <div class="card" style="padding: 25px; border-top: 4px solid var(--accent-interactive);">
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--accent-interactive);">
                <i data-lucide="settings-2" style="width: 20px;"></i>
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Configurações Operacionais</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="update_event" value="1">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="form-label" style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;">Data Programada</label>
                        <div style="position: relative;">
                            <i data-lucide="calendar" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-secondary);"></i>
                            <input type="date" name="date" class="form-input" value="<?= $scale['event_date'] ?>" required style="padding-left: 36px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;">Tipologia</label>
                        <div style="position: relative;">
                            <i data-lucide="tag" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: var(--text-secondary);"></i>
                            <select name="type" class="form-select" style="padding-left: 36px;">
                                <option value="Culto de Domingo" <?= $scale['event_type'] == 'Culto de Domingo' ? 'selected' : '' ?>>Culto de Domingo</option>
                                <option value="Ensaio" <?= $scale['event_type'] == 'Ensaio' ? 'selected' : '' ?>>Ensaio Técnico</option>
                                <option value="Evento Jovens" <?= $scale['event_type'] == 'Evento Jovens' ? 'selected' : '' ?>>Evento Jovens</option>
                                <option value="Evento Especial" <?= $scale['event_type'] == 'Evento Especial' ? 'selected' : '' ?>>Evento Especial</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;">Observações / Liturgia</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Insira detalhes técnicos, roteiro ou observações..." style="resize: vertical;"><?= htmlspecialchars($scale['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="justify-content: center; height: 48px; margin-top: 10px;">
                    <i data-lucide="save"></i> Atualizar Registro
                </button>
            </form>

            <!-- Área de Perigo -->
            <div style="margin-top: 40px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 20px;">
                <h4 style="color: #991B1B; font-size: 0.9rem; margin-top: 0; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="alert-triangle" style="width: 16px;"></i> Zona de Perigo
                </h4>
                <p style="font-size: 0.8rem; color: #B91C1C; margin-bottom: 15px;">A exclusão removerá permanentemente este registro e desvinculará todos os membros.</p>
                <form method="POST" onsubmit="return confirm('ATENÇÃO: Confirma a exclusão definitiva deste evento?');">
                    <input type="hidden" name="delete_scale" value="1">
                    <button type="submit" class="btn w-full" style="background: white; color: #EF4444; border: 1px solid #EF4444; font-weight: 600;">
                        Excluir Evento Definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB EQUIPE -->
    <div id="tab-equipe" style="display: none;">

        <!-- Lista de Escalados (Agrupada) -->
        <div class="card" style="margin-bottom: 25px; border-top: 4px solid var(--accent-interactive);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Quadro Técnico</h3>
                <span style="font-size: 0.8rem; background: var(--bg-tertiary); padding: 4px 10px; border-radius: 20px; color: var(--text-secondary);"><?= count($members) ?> integrantes</span>
            </div>

            <?php if (empty($members)): ?>
                <div style="text-align: center; padding: 30px; background: var(--bg-primary); border-radius: 12px; border: 1px dashed var(--border-subtle);">
                    <i data-lucide="user-x" style="width: 32px; height: 32px; color: var(--text-muted); opacity: 0.5; margin-bottom: 10px;"></i>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">Nenhum técnico ou músico escalado.</p>
                </div>
            <?php else: ?>
                <?php
                // Agrupamento (Mantido lógica original mas melhorando visual)
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

                <?php foreach ($groups as $groupName => $groupMembers): ?>
                    <?php if (!empty($groupMembers)): ?>
                        <div style="margin-bottom: 24px;">
                            <h4 style="margin: 0 0 12px; color: var(--accent-blue); background: var(--bg-tertiary); padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px;">
                                <?= $groupName ?>
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($groupMembers as $member): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--bg-primary); border: 1px solid var(--border-subtle); border-radius: 12px; transition: all 0.2s;">

                                        <!-- User Info -->
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="user-avatar" style="width: 40px; height: 40px; font-size: 0.95rem; background: white; border: 2px solid var(--border-subtle); color: var(--accent-interactive); box-shadow: var(--shadow-sm);">
                                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem; line-height: 1.2;"><?= htmlspecialchars($member['name']) ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                                                    <span style="width: 6px; height: 6px; background: var(--accent-interactive); border-radius: 50%;"></span>
                                                    <?= htmlspecialchars($member['instrument']) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php if ($member['confirmed'] == 1): ?>
                                                <span title="Confirmado" style="display: flex; padding: 6px; background: #DCFCE7; color: #166534; border-radius: 50%;">
                                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                                </span>
                                            <?php else: ?>
                                                <span title="Pendente" style="display: flex; padding: 6px; background: #FEF9C3; color: #854D0E; border-radius: 50%;">
                                                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                                                </span>
                                            <?php endif; ?>

                                            <a href="?id=<?= $scale_id ?>&remove=<?= $member['id'] ?>" onclick="return confirm('Remover integrante?')" style="display: flex; padding: 8px; color: var(--text-muted); transition: color 0.2s;">
                                                <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <!-- Adicionar Novo Membro -->
        <div class="card" style="border: 2px dashed var(--border-subtle); background: transparent;">
            <h3 style="margin-bottom: 15px; font-size: 1rem; color: var(--text-secondary);">+ Adicionar Recurso Humano</h3>
            <form method="POST">
                <input type="hidden" name="add_member" value="1">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <select name="user_id" class="form-select" required id="userSelect" onchange="updateInstrument()" style="height: 48px;">
                            <option value="">Selecione o Integrante...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" data-category="<?= $u['category'] ?>">
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <input type="text" name="instrument" id="instrumentInput" class="form-input" placeholder="Função" required style="height: 48px;">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline w-full" style="border-color: var(--accent-interactive); color: var(--accent-interactive);">
                    <i data-lucide="plus-circle" style="width: 18px;"></i> Inserir na Escala
                </button>
            </form>
        </div>
    </div>

</div>

<style>
    .tab-btn {
        flex: 1;
        padding: 12px;
        background: transparent;
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        border-radius: 8px;
    }

    .tab-btn:hover {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
    }

    .tab-btn.active {
        background: var(--bg-secondary);
        color: var(--accent-interactive);
        box-shadow: var(--shadow-sm);
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

    function updateInstrument() {
        const select = document.getElementById('userSelect');
        const input = document.getElementById('instrumentInput');
        const selectedOption = select.options[select.selectedIndex];
        const nameText = selectedOption.text;

        if (selectedOption.value) {
            let category = selectedOption.getAttribute('data-category') || '';
            let finalInst = '';

            // Smart Overrides
            if (nameText.includes('Diego')) finalInst = 'Violão';
            else if (nameText.includes('Thalyta')) finalInst = 'Voz';
            else {
                if (category.includes('voz')) finalInst = 'Voz';
                else if (category.includes('violao')) finalInst = 'Violão';
                else if (category.includes('teclado')) finalInst = 'Teclado';
                else if (category.includes('bateria')) finalInst = 'Bateria';
                else if (category) finalInst = category.charAt(0).toUpperCase() + category.slice(1);
            }
            input.value = finalInst;
        }
    }
</script>

<?php
renderAppFooter();
?>