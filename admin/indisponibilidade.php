<?php
// admin/indisponibilidade.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- PROCESSAR FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ADICIONAR
        if ($_POST['action'] === 'add') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            if ($start_date) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, start_date, end_date, reason, replacement_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $start_date, $end_date, $reason, $replacement_id]);

                    // NOTIFICAR LÍDERES (Admins)
                    $user_name = $_SESSION['user_name'];
                    $periodo = date('d/m', strtotime($start_date));
                    if ($end_date != $start_date) {
                        $periodo .= " a " . date('d/m', strtotime($end_date));
                    }

                    $notif_msg = "$user_name registrou ausência para $periodo. Motivo: $reason";

                    // Buscar admins
                    $stmt_admin = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                    $admins = $stmt_admin->fetchAll(PDO::FETCH_COLUMN);

                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'warning', 'indisponibilidade.php')");

                    foreach ($admins as $admin_id) {
                        // Não notificar a si mesmo se for admin
                        if ($admin_id != $user_id) {
                            $stmt_notif->execute([$admin_id, 'Nova Ausência', $notif_msg]);
                        }
                    }

                    $_SESSION['success'] = "Indisponibilidade registrada e líderes notificados!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro ao registrar: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Data de início é obrigatória.";
            }
        }
        // EDITAR
        elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            try {
                $stmt = $pdo->prepare("UPDATE user_unavailability SET start_date = ?, end_date = ?, reason = ?, replacement_id = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$start_date, $end_date, $reason, $replacement_id, $id, $user_id]);
                $_SESSION['success'] = "Indisponibilidade atualizada com sucesso!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
            }
        }
        // EXCLUIR
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$id, $user_id])) {
                $_SESSION['success'] = "Item removido com sucesso.";
            } else {
                $_SESSION['error'] = "Erro ao remover item.";
            }
        }

        // REDIRECT (PRG Pattern)
        header("Location: indisponibilidade.php");
        exit;
    }
}

// Recuperar Feedback da Sessão
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// --- BUSCAR DADOS ---

// 1. Minhas Indisponibilidades (Futuras e Recentes)
$stmt = $pdo->prepare("
    SELECT u.*, r.name as replacement_name 
    FROM user_unavailability u
    LEFT JOIN users r ON u.replacement_id = r.id
    WHERE u.user_id = ? AND u.end_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY u.start_date ASC
");
$stmt->execute([$user_id]);
$my_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Lista de Membros (Para o Select de Substituto)
$stmt = $pdo->query("SELECT id, name FROM users WHERE id != $user_id ORDER BY name ASC");
$users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);


renderAppHeader('Ausências de Escala');
renderPageHeader('Ausências de Escala', 'Informe suas ausências');
?>

<div class="container fade-in-up">

    <!-- Feedback -->
    <?php if ($success): ?>
        <div style="background: #DCFCE7; color: #166534; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; border: 1px solid #bbf7d0;">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #FEF2F2; color: #991B1B; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; display: flex; align-items: center; gap: 12px; border: 1px solid #fecaca;">
            <i data-lucide="alert-circle" style="width: 20px;"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- INSTRUÇÕES / COMO FUNCIONA -->
    <div style="background: linear-gradient(to right, #eff6ff, #f8fafc); border-left: 4px solid #3b82f6; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.05);">
        <h4 style="margin: 0 0 10px 0; color: #1e40af; display: flex; align-items: center; gap: 8px; font-size: 1rem;">
            <div style="background: #dbeafe; padding: 6px; border-radius: 8px; display: flex;">
                <i data-lucide="info" style="width: 18px; color: #2563eb;"></i>
            </div>
            Como funciona?
        </h4>
        <p style="margin: 0; font-size: 0.9rem; color: #334155; line-height: 1.6;">
            Vai viajar ou tem um compromisso? Avise aqui.
            <br>
            <span style="display: inline-block; margin-top: 6px;">
                <strong style="color: #2563eb;">1.</strong> Escolha o período. &nbsp;
                <strong style="color: #2563eb;">2.</strong> <span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">Obrigatório:</span> Indique quem vai te substituir.
            </span>
        </p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">

        <!-- FORMULÁRIO -->
        <div id="formCard" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <h3 id="formTitle" style="margin-top: 0; display: flex; align-items: center; gap: 10px; color: #1e293b;">
                <i data-lucide="calendar-plus" style="color: #166534;"></i>
                Nova Indisponibilidade
            </h3>

            <form method="POST" id="absenceForm" style="margin-top: 20px;">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Data Início</label>
                        <input type="date" name="start_date" id="startDate" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#166534'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Data Fim (Opcional)</label>
                        <input type="date" name="end_date" id="endDate" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#166534'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Motivo</label>
                    <input type="text" name="reason" id="reason" placeholder="Ex: Viagem, Trabalho, Saúde..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#166534'" onblur="this.style.borderColor='#cbd5e1'">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">
                        Substituto Sugerido <span style="color: #ef4444; margin-left: 2px;">*</span>
                    </label>
                    <div style="position: relative;">
                        <input type="text" list="users_datalist" id="replacementSearch" placeholder="Digite para buscar..." required
                            onchange="updateReplacementId(this)"
                            style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;"
                            onfocus="this.style.borderColor='#166534'" onblur="this.style.borderColor='#cbd5e1'">

                        <input type="hidden" name="replacement_id" id="replacement_id_input">

                        <datalist id="users_datalist">
                            <?php foreach ($users_list as $u): ?>
                                <option data-id="<?= $u['id'] ?>" value="<?= htmlspecialchars($u['name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">
                            <i data-lucide="search" style="width: 16px;"></i>
                        </div>
                    </div>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;">É obrigatório indicar quem irá te cobrir.</p>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" id="cancelEditBtn" onclick="cancelEdit()" style="display: none; flex: 1; background: #f1f5f9; color: #64748b; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="submitBtn" class="ripple" style="flex: 2; background: #166534; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(22, 101, 52, 0.2);">
                        <i data-lucide="check" style="width: 18px;"></i>
                        <span id="submitText">Registrar Ausência</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTA -->
        <div>
            <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">Suas Ausências Programadas</h3>

            <?php if (empty($my_items)): ?>
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                    <i data-lucide="calendar-check" style="width: 40px; height: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
                    <p style="color: #64748b; margin: 0; font-weight: 500;">Nenhuma ausência futura registrada.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($my_items as $item):
                        $start = date('d/m', strtotime($item['start_date']));
                        $end = date('d/m', strtotime($item['end_date']));
                        $periodo = ($start === $end) ? $start : "$start até $end";

                        // JSON para passar para o JS
                        $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                    ?>
                        <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <div style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                                        <?= $periodo ?>
                                    </div>
                                    <?php if ($item['replacement_name']): ?>
                                        <div style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                            <i data-lucide="user-check" style="width: 12px;"></i>
                                            Sub: <strong><?= htmlspecialchars($item['replacement_name']) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="color: #1e293b; font-weight: 600; font-size: 0.95rem;">
                                    <?= htmlspecialchars($item['reason'] ?: 'Sem motivo especificado') ?>
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <!-- Botão Editar -->
                                <button onclick="editItem(<?= $itemJson ?>)" style="background: #eff6ff; border: none; color: #3b82f6; cursor: pointer; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="edit-2" style="width: 16px;"></i>
                                </button>

                                <!-- Botão Excluir -->
                                <form method="POST" onsubmit="return confirm('Cancelar esta ausência?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" style="background: #fef2f2; border: none; color: #ef4444; cursor: pointer; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i data-lucide="trash-2" style="width: 16px;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div style="height: 48px;"></div>
</div>

<script>
    function updateReplacementId(input) {
        const list = document.getElementById('users_datalist');
        const hiddenInput = document.getElementById('replacement_id_input');
        const options = list.options;

        hiddenInput.value = ''; // Reset se não encontrar match exato

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === input.value) {
                hiddenInput.value = options[i].getAttribute('data-id');
                break;
            }
        }
    }

    function editItem(item) {
        // Preencher formulário
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = item.id;
        document.getElementById('startDate').value = item.start_date;
        document.getElementById('endDate').value = item.end_date;
        document.getElementById('reason').value = item.reason;

        // Preencher substituto
        if (item.replacement_name) {
            document.getElementById('replacementSearch').value = item.replacement_name;
            document.getElementById('replacement_id_input').value = item.replacement_id;
        } else {
            document.getElementById('replacementSearch').value = '';
            document.getElementById('replacement_id_input').value = '';
        }

        // Alterar UI
        document.getElementById('formTitle').innerText = 'Editar Ausência';
        document.getElementById('submitText').innerText = 'Salvar Alterações';
        document.getElementById('cancelEditBtn').style.display = 'block';

        // Scroll para o form
        document.getElementById('formCard').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function cancelEdit() {
        // Resetar formulário
        document.getElementById('absenceForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('replacement_id_input').value = '';

        // Resetar UI
        document.getElementById('formTitle').innerHTML = '<i data-lucide="calendar-plus" style="color: #166534;"></i> Nova Indisponibilidade';
        document.getElementById('submitText').innerText = 'Registrar Ausência';
        document.getElementById('cancelEditBtn').style.display = 'none';

        // Re-inicializar ícones se necessário (Lucide auto-replaces, mas innerHTML text is safe)
        lucide.createIcons();
    }
</script>

<?php renderAppFooter(); ?>