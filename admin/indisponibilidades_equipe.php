<?php
// admin/indisponibilidades_equipe.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

$success = '';
$error = '';

// --- PROCESSAR FORMULÁRIO (ADD/EDIT/DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ADICIONAR (Admin adicionando para outro usuário)
        if ($_POST['action'] === 'add') {
            $user_id = $_POST['user_id']; // ID do membro selecionado
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            if ($user_id && $start_date) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, start_date, end_date, reason, replacement_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $start_date, $end_date, $reason, $replacement_id]);
                    $_SESSION['success'] = "Ausência registrada com sucesso!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro ao registrar: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Preencha os campos obrigatórios.";
            }
        }
        // EXCLUIR
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Ausência removida.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro ao remover: " . $e->getMessage();
            }
        }
        
        header("Location: indisponibilidades_equipe.php");
        exit;
    }
}

// Recuperar Feedback
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// --- FILTROS ---
$monthFilter = $_GET['month'] ?? date('Y-m');
$userFilter = $_GET['user_id'] ?? '';

// Construir Query
$sql = "
    SELECT u.*, us.name as user_name, us.avatar_color, r.name as replacement_name 
    FROM user_unavailability u
    JOIN users us ON u.user_id = us.id
    LEFT JOIN users r ON u.replacement_id = r.id
    WHERE DATE_FORMAT(u.start_date, '%Y-%m') = :month
";

if ($userFilter) {
    $sql .= " AND u.user_id = :userId";
}

$sql .= " ORDER BY u.start_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':month', $monthFilter);
if ($userFilter) $stmt->bindValue(':userId', $userFilter);
$stmt->execute();
$absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de Usuários para Select
$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Gerenciar Ausências');
renderPageHeader('Ausências da Equipe', 'Gestão de Indisponibilidades');
?>

<div class="container pb-5">

    <!-- Feedback -->
    <?php if ($success): ?>
        <div class="alert-success mb-3 p-3 rounded" style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0;">
            <i data-lucide="check-circle" style="width:18px; display:inline; vertical-align:middle;"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- Controles Superiores -->
    <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-size: 0.75rem; color: #64748b; margin-bottom: 4px; font-weight: 600;">Mês</label>
                <input type="month" name="month" value="<?= $monthFilter ?>" onchange="this.form.submit()" 
                    style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>
            
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; font-size: 0.75rem; color: #64748b; margin-bottom: 4px; font-weight: 600;">Filtrar Membro</label>
                <select name="user_id" onchange="this.form.submit()" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="button" onclick="openModal()" class="btn-primary ripple" 
                style="padding: 10px 16px; border: none; background: #e11d48; color: white; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="plus-circle" style="width: 18px;"></i> Nova Ausência
            </button>
        </form>
    </div>

    <!-- Lista de Ausências -->
    <?php if (empty($absences)): ?>
        <div style="text-align: center; padding: 40px; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
            <i data-lucide="calendar-check" style="width: 32px; height: 32px; margin-bottom: 8px;"></i>
            <p>Nenhuma ausência registrada para este período.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($absences as $item): 
                $start = date('d/m', strtotime($item['start_date']));
                $end = date('d/m', strtotime($item['end_date']));
                $periodo = ($start === $end) ? $start : "$start até $end";
            ?>
                <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <!-- Avatar -->
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $item['avatar_color'] ?: '#cbd5e1' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                            <?= strtoupper(substr($item['user_name'], 0, 1)) ?>
                        </div>
                        
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 1rem;"><?= htmlspecialchars($item['user_name']) ?></div>
                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 2px;">
                                <div style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                    <?= $periodo ?>
                                </div>
                                <div style="color: #64748b; font-size: 0.85rem;">
                                    <?= htmlspecialchars($item['reason']) ?>
                                </div>
                            </div>
                            <?php if ($item['replacement_name']): ?>
                                <div style="font-size: 0.75rem; color: #059669; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="user-check" style="width: 12px;"></i> Sub: <?= htmlspecialchars($item['replacement_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" onsubmit="return confirm('Remover esta ausência?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" style="background: #fff1f2; color: #e11d48; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                            <i data-lucide="trash-2" style="width: 16px;"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL ADICIONAR -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 16px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; margin-bottom: 20px;">Registrar Ausência</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Membro</label>
                <select name="user_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="">Selecione...</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">De</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Até</label>
                    <input type="date" name="end_date" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Motivo</label>
                <input type="text" name="reason" placeholder="Ex: Viagem" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Substituto (Opcional)</label>
                <select name="replacement_id" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="">Sem substituto definido</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 12px; background: #e2e8f0; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 12px; background: #e11d48; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('addModal').style.display = 'none';
    }
</script>

<?php renderAppFooter(); ?>
