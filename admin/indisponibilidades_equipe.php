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
            $user_id = $_POST['user_id'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $observation = $_POST['observation'] ?? '';
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            if ($user_id && $start_date) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, start_date, end_date, reason, observation, replacement_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $start_date, $end_date, $reason, $observation, $replacement_id]);
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

if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

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

$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Gerenciar Ausências');
renderPageHeader('Ausências da Equipe', 'Gestão de Indisponibilidades');
?>

<div class="container fade-in-up">

    <?php if ($success): ?>
        <div style="background:var(--sage-100); color:var(--sage-800); padding:12px; border-radius:12px; margin-bottom:20px; border:1px solid var(--sage-200); display:flex; gap:10px; align-items:center; font-weight:700;">
            <i data-lucide="check-circle" width="18"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- Controles Superiores -->
    <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); margin-bottom: 24px; box-shadow: var(--shadow-sm);">
        <form method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: end;">
            <div style="flex: 1; min-width: 180px;">
                <label style="display: block; font-size: 0.8rem; color: var(--slate-500); margin-bottom: 6px; font-weight: 600;">Mês de Referência</label>
                <input type="month" name="month" value="<?= $monthFilter ?>" onchange="this.form.submit()" 
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
            </div>
            
            <div style="flex: 1; min-width: 180px;">
                <label style="display: block; font-size: 0.8rem; color: var(--slate-500); margin-bottom: 6px; font-weight: 600;">Filtrar Membro</label>
                <select name="user_id" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
                    <option value="">Mostrar Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="button" onclick="openModal()" class="ripple" 
                style="padding: 12px 20px; background: #e11d48; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.2);">
                <i data-lucide="plus-circle" width="18"></i> Nova Ausência
            </button>
        </form>
    </div>

    <!-- Lista -->
    <?php if (empty($absences)): ?>
        <div style="text-align: center; padding: 48px; background: white; border-radius: 16px; border: 1px dashed var(--slate-300);">
            <div style="background: var(--slate-100); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                <i data-lucide="calendar-check" style="color:var(--slate-400)" width="32"></i>
            </div>
            <p style="color: var(--slate-500); margin: 0; font-weight: 500;">Nenhuma ausência encontrada para este filtro.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
            <?php foreach ($absences as $item): 
                $startObj = new DateTime($item['start_date']);
                $endObj = new DateTime($item['end_date']);
                
                // Formatar Mês e Dia manualmente (sem depender do IntlDateFormatter)
                $months_pt = [
                    '01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
                    '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
                    '09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ'
                ];
                $monthStr = $months_pt[$startObj->format('m')];
                $dayStr = $startObj->format('d');
                
                $periodo = $startObj->format('d/m');
                if ($item['start_date'] != $item['end_date']) {
                    $periodo .= ' a ' . $endObj->format('d/m');
                }
            ?>
                <!-- CARD TEAM -->
                <div style="background: white; border-radius: 16px; border: 1px solid var(--border-color); display: flex; overflow: hidden; box-shadow: var(--shadow-sm); position: relative;">
                    
                    <!-- ID Color Bar (based on avatar color or default) -->
                    <div style="width: 6px; background: <?= $item['avatar_color'] ?: 'var(--slate-500)' ?>;"></div>

                    <div style="flex: 1; padding: 16px; display: flex; gap: 16px; align-items: flex-start;">
                        
                        <!-- Date Block -->
                         <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 60px; height: 60px; background: var(--slate-50); border-radius: 12px; color: var(--slate-500); border: 1px solid var(--slate-200);">
                            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?= $monthStr ?></span>
                            <span style="font-size: 1.4rem; font-weight: 800; line-height: 1; margin-top: 2px;"><?= $dayStr ?></span>
                        </div>

                        <!-- Content -->
                        <div style="flex: 1;">
                            
                            <!-- Header: Name & Role -->
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $item['avatar_color'] ?: 'var(--slate-300)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                    <?= strtoupper(substr($item['user_name'], 0, 1)) ?>
                                </div>
                                <span style="font-weight: 700; color: var(--slate-800); font-size: 0.95rem;"><?= htmlspecialchars($item['user_name']) ?></span>
                            </div>

                            <!-- Reason & Sub -->
                            <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 600; color: var(--slate-600);">
                                <?= htmlspecialchars($item['reason']) ?>
                            </h4>

                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                <span style="font-size: 0.8rem; background: var(--slate-100); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--slate-200);">
                                    <?= $periodo ?>
                                </span>
                                <?php if ($item['replacement_name']): ?>
                                    <span style="font-size: 0.8rem; color: #059669; background: #ecfdf5; padding: 2px 8px; border-radius: 4px; border: 1px solid #d1fae5; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                                        <i data-lucide="user-check" width="12"></i> <?= htmlspecialchars($item['replacement_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--rose-600); background: var(--rose-50); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--rose-100);">
                                        Sem substituto
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Obs/Audio -->
                            <?php if (!empty($item['observation'])): ?>
                                <div style="font-size: 0.85rem; color: var(--slate-500); background: var(--slate-50); padding: 8px; border-radius: 8px; margin-bottom: 8px; border: 1px solid var(--slate-100);">
                                    <?= nl2br(htmlspecialchars($item['observation'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($item['audio_path'])): ?>
                                <div style="background: #f0f9ff; padding: 6px; border-radius: 8px; border: 1px dashed #bae6fd; display: inline-flex; align-items: center; gap: 6px;">
                                    <div style="background: #0ea5e9; color: white; padding: 4px; border-radius: 50%;">
                                        <i data-lucide="mic" width="12"></i>
                                    </div>
                                    <audio controls style="height: 28px; width: 200px;">
                                        <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/webm">
                                        <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/mp4">
                                    </audio>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Action -->
                        <div>
                            <form method="POST" onsubmit="return confirm('Remover esta ausência?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" title="Excluir" style="background: #fff1f2; color: #e11d48; border: 1px solid var(--rose-200); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                    <i data-lucide="trash-2" width="14"></i>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL ADD (Mantido mas com estilo atualizado) -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="fade-in-up" style="background: white; width: 90%; max-width: 500px; border-radius: 16px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative;">
        
        <button onclick="closeModal()" style="position: absolute; right: 16px; top: 16px; border: none; background: none; color: var(--slate-400); cursor: pointer;">
            <i data-lucide="x" width="24"></i>
        </button>

        <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main);">Registrar Ausência (Admin)</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Membro</label>
                <select name="user_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
                    <option value="">Selecione...</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">De</label>
                    <input type="date" name="start_date" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Até</label>
                    <input type="date" name="end_date" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Motivo</label>
                <input type="text" name="reason" placeholder="Ex: Viagem" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Observação</label>
                <textarea name="observation" rows="2" placeholder="Detalhes..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); resize: vertical; outline:none;"></textarea>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Substituto (Opcional)</label>
                <select name="replacement_id" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--slate-300); outline:none;">
                    <option value="">Sem substituto definido</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 12px; background: var(--slate-200); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 12px; background: #e11d48; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('addModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('addModal').style.display = 'none'; }
</script>

<?php renderAppFooter(); ?>
