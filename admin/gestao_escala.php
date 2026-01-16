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
    header("Location: gestao_escala.php?id=$scale_id&tab=evento");
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
    $instrument = $_POST['instrument'];

    // Verificar se já está na escala
    $check = $pdo->prepare("SELECT id FROM scale_members WHERE scale_id = ? AND user_id = ?");
    $check->execute([$scale_id, $user_id]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO scale_members (scale_id, user_id, instrument, confirmed) VALUES (?, ?, ?, 0)");
        $stmt->execute([$scale_id, $user_id, $instrument]);
    }

    header("Location: gestao_escala.php?id=$scale_id&tab=equipe");
    exit;
}

// Remover Membro
if (isset($_GET['remove'])) {
    $member_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM scale_members WHERE id = ? AND scale_id = ?");
    $stmt->execute([$member_id, $scale_id]);
    header("Location: gestao_escala.php?id=$scale_id&tab=equipe");
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

// Buscar Todos Usuários para o Select
$users = $pdo->query("SELECT * FROM users ORDER BY name")->fetchAll();

require_once '../includes/layout.php';

renderAppHeader('Gerenciar Escala');
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
        <a href="escala.php" class="btn-icon" style="background: var(--bg-secondary); color: var(--text-primary); width: 40px; height: 40px; border-radius: 12px; transition: all 0.2s;">
            <i data-lucide="arrow-left" style="width: 20px;"></i>
        </a>
        <h1 class="page-title" style="margin: 0; font-size: 1.2rem; font-weight: 700;">
            <?= date('d/m', strtotime($scale['event_date'])) ?> - <?= htmlspecialchars($scale['event_type']) ?>
        </h1>
    </div>

    <!-- TABS MODERNAS -->
    <div style="display: flex; gap: 5px; margin-bottom: 25px; background: var(--bg-tertiary); padding: 5px; border-radius: 14px;">
        <button onclick="showTab('evento')" id="btn-evento" class="tab-btn active">
            Configurar Evento
        </button>
        <button onclick="showTab('equipe')" id="btn-equipe" class="tab-btn">
            Gerenciar Equipe
        </button>
    </div>

    <!-- TAB EVENTO -->
    <div id="tab-evento">
        <div class="card" style="padding: 24px; border: none; box-shadow: var(--shadow-sm); border-radius: 16px;">

            <form method="POST">
                <input type="hidden" name="update_event" value="1">

                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">DATA</label>
                        <input type="date" name="date" class="form-input modern-input" value="<?= $scale['event_date'] ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">TIPO</label>
                        <select name="type" class="form-select modern-input">
                            <option value="Culto de Domingo" <?= $scale['event_type'] == 'Culto de Domingo' ? 'selected' : '' ?>>Culto de Domingo</option>
                            <option value="Ensaio" <?= $scale['event_type'] == 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                            <option value="Evento Jovens" <?= $scale['event_type'] == 'Evento Jovens' ? 'selected' : '' ?>>Evento Jovens</option>
                            <option value="Evento Especial" <?= $scale['event_type'] == 'Evento Especial' ? 'selected' : '' ?>>Evento Especial</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">DESCRIÇÃO / LITURGIA</label>
                    <textarea name="description" class="form-input modern-input" rows="4" placeholder="Insira informações relevantes..." style="resize: none;"><?= htmlspecialchars($scale['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full modern-btn" style="margin-top: 10px;">
                    Salvar Alterações
                </button>
            </form>

            <!-- Delete Section -->
            <div style="margin-top: 20px; border-top: 1px dashed var(--border-subtle); padding-top: 20px;">
                <form method="POST" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir esta escala?');">
                    <input type="hidden" name="delete_scale" value="1">
                    <button type="submit" class="btn w-full btn-danger-soft">
                        <i data-lucide="trash-2" style="width: 18px; margin-right: 6px;"></i> Excluir Escala
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB EQUIPE -->
    <div id="tab-equipe" style="display: none;">

        <div class="card" style="padding: 24px; border: none; box-shadow: var(--shadow-sm); border-radius: 16px;">

            <!-- Quick Stats -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Equipe Técnica</h3>
                    <p style="margin: 2px 0 0; font-size: 0.85rem; color: var(--text-secondary);">Músicos e Apoio</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">
                    <?= count($members) ?>
                </div>
            </div>

            <?php if (empty($members)): ?>
                <div style="text-align: center; padding: 40px 20px; margin-bottom: 30px; background: var(--bg-tertiary); border-radius: 12px; border: 1px dashed var(--border-subtle);">
                    <div style="width: 48px; height: 48px; background: var(--bg-secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i data-lucide="users" style="width: 24px; color: var(--text-muted);"></i>
                    </div>
                    <p style="color: var(--text-secondary); font-weight: 500;">Ninguém escalado ainda</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Adicione membros usando o formulário abaixo</p>
                </div>
            <?php else: ?>
                <?php
                // Agrupamento
                $groups = ['Voz' => [], 'Instrumental' => [], 'Outros' => []];
                foreach ($members as $m) {
                    $cat = strtolower($m['category']);
                    $inst = strtolower($m['instrument']);

                    if (strpos($cat, 'voz') !== false || strpos($inst, 'voz') !== false || strpos($inst, 'bck') !== false || strpos($inst, 'soprano') !== false) {
                        $groups['Voz'][] = $m;
                    } elseif (in_array($cat, ['violao', 'teclado', 'bateria', 'baixo', 'guitarra']) || in_array($inst, ['violão', 'teclado', 'bateria', 'baixo', 'guitarra'])) {
                        $groups['Instrumental'][] = $m;
                    } else {
                        $groups['Outros'][] = $m;
                    }
                }
                ?>

                <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 30px;">
                    <?php foreach ($groups as $groupName => $groupMembers): ?>
                        <?php if (!empty($groupMembers)): ?>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; margin-left: 4px;">
                                    <span style="width: 6px; height: 6px; background: var(--accent-interactive); border-radius: 50%;"></span>
                                    <h4 style="margin: 0; color: var(--text-secondary); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;"><?= $groupName ?></h4>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ($groupMembers as $member): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--bg-primary); border: 1px solid var(--border-subtle); border-radius: 12px; box-shadow: var(--shadow-sm);">

                                            <!-- User -->
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; font-size: 0.9rem; background: var(--gradient-primary); color: white; border: none; font-weight: 600;">
                                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($member['name']) ?></div>
                                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($member['instrument']) ?></div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if ($member['confirmed'] == 1): ?>
                                                    <div title="Confirmado" style="background: #ECFDF5; padding: 6px; border-radius: 8px;">
                                                        <i data-lucide="check" style="width: 16px; color: #059669;"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div title="Pendente" style="background: #FFFBEB; padding: 6px; border-radius: 8px;">
                                                        <i data-lucide="clock" style="width: 16px; color: #D97706;"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <a href="?id=<?= $scale_id ?>&remove=<?= $member['id'] ?>" onclick="return confirm('Remover integrante?')" style="color: var(--status-error); padding: 6px; opacity: 0.8; transition: opacity 0.2s;">
                                                    <i data-lucide="trash-2" style="width: 18px;"></i>
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

            <!-- Add Section Styled -->
            <div style="background: var(--bg-tertiary); padding: 20px; border-radius: 14px;">
                <h3 style="margin: 0 0 15px; font-size: 0.95rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="user-plus" style="width: 18px;"></i> Adicionar à Escala
                </h3>

                <form method="POST">
                    <input type="hidden" name="add_member" value="1">

                    <div style="margin-bottom: 12px;">
                        <select name="instrument" id="roleSelect" class="form-select modern-input" onchange="filterUsersByRole()" required>
                            <option value="">1º Selecione a Função...</option>
                            <option value="Voz">Voz / Backing</option>
                            <option value="Violão">Violão</option>
                            <option value="Teclado">Teclado</option>
                            <option value="Bateria">Bateria</option>
                            <option value="Baixo">Baixo</option>
                            <option value="Guitarra">Guitarra</option>
                            <option value="Mídia">Mídia / Datashow</option>
                            <option value="Som">Técnico de Som</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <select name="user_id" id="userSelect" class="form-select modern-input" required disabled style="opacity: 0.7;">
                            <option value="">Aguardando função...</option>
                            <?php foreach ($users as $u): ?>
                                <?php
                                // Normalize category for data-role
                                $rawCat = mb_strtolower($u['category'], 'UTF-8');
                                $normalizedCat = $rawCat;
                                // Map specific DB values to filter keys
                                if (strpos($rawCat, 'voz') !== false) $normalizedCat = 'voz';
                                if (strpos($rawCat, 'violao') !== false) $normalizedCat = 'violão';
                                if (strpos($rawCat, 'teclado') !== false) $normalizedCat = 'teclado';
                                if (strpos($rawCat, 'bateria') !== false) $normalizedCat = 'bateria';
                                if (strpos($rawCat, 'baixo') !== false) $normalizedCat = 'baixo';
                                if (strpos($rawCat, 'guitarra') !== false) $normalizedCat = 'guitarra';
                                if (strpos($rawCat, 'midia') !== false) $normalizedCat = 'mídia';
                                if (strpos($rawCat, 'som') !== false) $normalizedCat = 'som';
                                ?>
                                <option value="<?= $u['id'] ?>" data-role="<?= $normalizedCat ?>" style="display:none;">
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" id="btnAddMember" disabled class="btn btn-primary w-full modern-btn" style="opacity: 0.5;">
                        Inserir na Escala <i data-lucide="arrow-right" style="width: 16px; margin-left: 6px;"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<style>
    /* Modern Inputs */
    .modern-input {
        background: var(--bg-primary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: all 0.2s;
        width: 100%;
    }

    .modern-input:focus {
        border-color: var(--accent-interactive);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    /* Modern Buttons */
    .modern-btn {
        height: 50px;
        border-radius: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: transform 0.1s, box-shadow 0.1s;
    }

    .modern-btn:active {
        transform: translateY(1px);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .btn-danger-soft {
        background: #FEF2F2;
        color: #DC2626;
        border: 1px solid #FECACA;
        height: 50px;
        border-radius: 12px;
        font-weight: 600;
    }

    .btn-danger-soft:hover {
        background: #DC2626;
        color: white;
        border-color: #DC2626;
    }

    /* Tabs */
    .tab-btn {
        flex: 1;
        padding: 10px;
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .tab-btn:hover {
        color: var(--text-primary);
        background: rgba(255, 255, 255, 0.05);
    }

    .tab-btn.active {
        background: var(--bg-primary);
        color: var(--accent-interactive);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'equipe') {
            showTab('equipe');
        } else {
            showTab('evento');
        }
    });

    function showTab(tab) {
        document.getElementById('tab-evento').style.display = 'none';
        document.getElementById('tab-equipe').style.display = 'none';

        document.getElementById('btn-evento').classList.remove('active');
        document.getElementById('btn-equipe').classList.remove('active');

        document.getElementById('tab-' + tab).style.display = 'block';
        document.getElementById('btn-' + tab).classList.add('active');

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.pushState({}, '', url);
    }

    function filterUsersByRole() {
        const roleSelect = document.getElementById('roleSelect');
        const userSelect = document.getElementById('userSelect');
        const btnAdd = document.getElementById('btnAddMember');

        const selectedRole = roleSelect.value.toLowerCase().trim();

        // Reset user selection
        userSelect.value = "";

        if (!selectedRole) {
            userSelect.disabled = true;
            userSelect.style.opacity = '0.7';
            btnAdd.disabled = true;
            btnAdd.style.opacity = '0.5';

            // Show placeholder only
            Array.from(userSelect.options).forEach(opt => {
                if (opt.value === "") opt.innerText = "Aguardando função...";
                else opt.style.display = 'none';
            });
            return;
        }

        userSelect.disabled = false;
        userSelect.style.opacity = '1';
        btnAdd.disabled = false;
        btnAdd.style.opacity = '1';

        let count = 0;
        const options = userSelect.getElementsByTagName('option');

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];

            if (opt.value === "") {
                opt.innerText = "Selecione o integrante...";
                continue;
            }

            const optRole = (opt.getAttribute('data-role') || '').toLowerCase().trim();

            if (selectedRole === 'outros' || optRole.includes(selectedRole)) {
                opt.style.display = 'block';
                count++;
            } else {
                opt.style.display = 'none';
            }
        }

        if (count === 0) {
            userSelect.options[0].innerText = "Ninguém encontrado nesta função";
        }
    }
</script>

<?php
renderAppFooter();
?>