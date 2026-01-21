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
        // Adicionar
        if ($_POST['action'] === 'add') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date; // Se não preencher fim, assume 1 dia
            $reason = $_POST['reason'];
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            if ($start_date) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, start_date, end_date, reason, replacement_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $start_date, $end_date, $reason, $replacement_id]);
                    $success = "Indisponibilidade registrada com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao registrar: " . $e->getMessage();
                }
            } else {
                $error = "Data de início é obrigatória.";
            }
        }
        // Excluir
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$id, $user_id])) {
                $success = "Item removido com sucesso.";
            } else {
                $error = "Erro ao remover item.";
            }
        }
    }
}

// --- BUSCAR DADOS ---

// 1. Minhas Indisponibilidades (Futuras e Recentes)
$stmt = $pdo->prepare("
    SELECT u.*, r.name as replacement_name 
    FROM user_unavailability u
    LEFT JOIN users r ON u.replacement_id = r.id
    WHERE u.user_id = ? AND u.end_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
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
        <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px; color: #1e293b;">
                <i data-lucide="calendar-plus" style="color: #667eea;"></i>
                Nova Indisponibilidade
            </h3>

            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="add">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Data Início</label>
                        <input type="date" name="start_date" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Data Fim (Opcional)</label>
                        <input type="date" name="end_date" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Motivo</label>
                    <input type="text" name="reason" placeholder="Ex: Viagem, Trabalho, Saúde..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Substituto Sugerido (Opcional)</label>
                    <div style="position: relative;">
                        <!-- Input visível para busca -->
                        <input type="text" list="users_datalist" placeholder="Digite para buscar..."
                            onchange="updateReplacementId(this)"
                            style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">

                        <!-- Input hidden que vai enviar o ID -->
                        <input type="hidden" name="replacement_id" id="replacement_id_input">

                        <!-- Lista de opções -->
                        <datalist id="users_datalist">
                            <?php foreach ($users_list as $u): ?>
                                <option data-id="<?= $u['id'] ?>" value="<?= htmlspecialchars($u['name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">
                            <i data-lucide="search" style="width: 16px;"></i>
                        </div>
                    </div>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;">Combine com alguém para te cobrir se possível.</p>
                </div>

                <button type="submit" class="ripple" style="background: #1e293b; color: white; border: none; padding: 14px; border-radius: 12px; width: 100%; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <i data-lucide="check" style="width: 18px;"></i>
                    Registrar Ausência
                </button>
            </form>
        </div>

        <!-- LISTA -->
        <div>
            <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">Suas Ausências Programadas</h3>

            <?php if (empty($my_items)): ?>
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                    <i data-lucide="calendar-check" style="width: 40px; height: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
                    <p style="color: #64748b; margin: 0; font-weight: 500;">Nenhuma indisponibilidade futura registrada.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($my_items as $item):
                        $start = date('d/m', strtotime($item['start_date']));
                        $end = date('d/m', strtotime($item['end_date']));
                        $periodo = ($start === $end) ? $start : "$start até $end";
                    ?>
                        <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <div>
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

                            <form method="POST" onsubmit="return confirm('Cancelar esta indisponibilidade?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="ripple" style="background: #fef2f2; border: none; color: #ef4444; cursor: pointer; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
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
</script>

<?php renderAppFooter(); ?>