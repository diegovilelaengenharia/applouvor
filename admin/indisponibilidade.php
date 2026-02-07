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
        
        // UPLOAD DE ÁUDIO
        $audio_path = null;
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/audio/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $filename = uniqid('audio_' . $user_id . '_') . '.webm';
            $targetFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $targetFile)) {
                $audio_path = 'uploads/audio/' . $filename;
            }
        }

        // ADICIONAR
        if ($_POST['action'] === 'add') {
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $observation = $_POST['observation'] ?? '';
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            if ($start_date) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, start_date, end_date, reason, observation, replacement_id, audio_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $start_date, $end_date, $reason, $observation, $replacement_id, $audio_path]);

                    // NOTIFICAR LÍDERES
                    $user_name = $_SESSION['user_name'];
                    $periodo = date('d/m', strtotime($start_date));
                    if ($end_date != $start_date) {
                        $periodo .= " a " . date('d/m', strtotime($end_date));
                    }

                    $notif_msg = "$user_name: Ausência em $periodo ($reason)";
                    if ($audio_path) $notif_msg .= " ♫";

                    $stmt_admin = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                    $admins = $stmt_admin->fetchAll(PDO::FETCH_COLUMN);
                    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'warning', 'indisponibilidades_equipe.php')");

                    foreach ($admins as $admin_id) {
                        if ($admin_id != $user_id) {
                            $stmt_notif->execute([$admin_id, 'Nova Ausência', $notif_msg]);
                        }
                    }

                    $_SESSION['success'] = "Ausência registrada!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Data de início obrigatória.";
            }
        }
        // EDITAR
        elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?: $start_date;
            $reason = $_POST['reason'];
            $observation = $_POST['observation'] ?? '';
            $replacement_id = !empty($_POST['replacement_id']) ? $_POST['replacement_id'] : null;

            try {
                if ($audio_path) {
                    $stmt = $pdo->prepare("UPDATE user_unavailability SET start_date=?, end_date=?, reason=?, observation=?, replacement_id=?, audio_path=? WHERE id=? AND user_id=?");
                    $stmt->execute([$start_date, $end_date, $reason, $observation, $replacement_id, $audio_path, $id, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE user_unavailability SET start_date=?, end_date=?, reason=?, observation=?, replacement_id=? WHERE id=? AND user_id=?");
                    $stmt->execute([$start_date, $end_date, $reason, $observation, $replacement_id, $id, $user_id]);
                }
                $_SESSION['success'] = "Atualizado com sucesso!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
            }
        }
        // EXCLUIR
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$id, $user_id])) {
                $_SESSION['success'] = "Removido com sucesso.";
            }
        }
        header("Location: indisponibilidade.php");
        exit;
    }
}

if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

// --- DADOS ---
$stmt = $pdo->prepare("
    SELECT u.*, r.name as replacement_name 
    FROM user_unavailability u
    LEFT JOIN users r ON u.replacement_id = r.id
    WHERE u.user_id = ? AND u.end_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    ORDER BY u.start_date ASC
");
$stmt->execute([$user_id]);
$my_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM users WHERE id != $user_id ORDER BY name ASC");
$users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

renderAppHeader('Ausências de Escala');
renderPageHeader('Ausências de Escala', 'Informe suas ausências');
?>

<div class="container fade-in-up">

    <?php if ($success): ?>
        <div style="background:var(--primary-subtle); color:var(--primary); padding:12px; border-radius:12px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:10px; border:1px solid var(--primary-light);">
            <i data-lucide="check-circle" width="18"></i> <?= $success ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:var(--rose-50); color:var(--rose-600); padding:12px; border-radius:12px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:10px; border:1px solid var(--rose-200);">
            <i data-lucide="alert-circle" width="18"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- INFO E BOTÃO ADD -->
    <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        
        <div style="display: flex; gap: 16px; margin-bottom: 20px;">
            <div style="background: var(--primary-subtle); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
                <i data-lucide="info" width="24"></i>
            </div>
            <div>
                <h4 style="margin: 0 0 12px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Sobre suas Ausências</h4>
                <div style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.6;">
                    <p style="margin-top: 0;">
                        Entendemos que imprevistos acontecem. No entanto, lembre-se que <strong>cada ausência gera impacto na equipe</strong> e pode sobrecarregar outros irmãos que precisarão cobrir sua função.
                    </p>
                    
                    <strong style="display:block; color:var(--text-main); margin-top:12px; margin-bottom:4px;">Como funciona:</strong>
                    <ul style="margin: 0; padding-left: 20px; color: var(--text-muted);">
                        <li>Utilize este recurso apenas quando realmente necessário.</li>
                        <li>Clique no botão abaixo para registrar.</li>
                        <li>Indique a data, o motivo e, obrigatoriamente, <strong>quem irá te substituir</strong>.</li>
                        <li>Você pode gravar um áudio explicando melhor a situação para a liderança.</li>
                    </ul>

                    <p style="margin-bottom: 0; margin-top: 12px; font-style: italic; color: var(--yellow-600);">
                        "Servi uns aos outros, cada um conforme o dom que recebeu, como bons despenseiros da multiforme graça de Deus." (1 Pedro 4:10)
                    </p>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
            <button onclick="openModal()" class="ripple" style="
                width: 100%;
                background: var(--slate-600); 
                border: none; 
                padding: 14px 20px; 
                border-radius: 12px; 
                color: white; 
                font-weight: 700; 
                font-size: 1rem; 
                cursor: pointer; 
                box-shadow: 0 4px 6px -1px rgba(55, 106, 200, 0.2); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 10px;
                transition: transform 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                <i data-lucide="plus-circle" width="22"></i>
                Registrar Nova Ausência
            </button>
        </div>
    </div>

    <!-- LISTAGEM (Redesenhada) -->
    <div>
        <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: var(--text-main); font-weight: 700;">Suas Ausências</h3>

        <?php if (empty($my_items)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 16px; border: 1px dashed var(--slate-300);">
                <div style="background: var(--slate-100); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                    <i data-lucide="calendar" style="color: var(--slate-400);" width="28"></i>
                </div>
                <p style="color: var(--slate-500); margin: 0; font-weight: 500;">Nenhuma ausência futura.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($my_items as $item):
                    // Datas
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
                    
                    $dateRange = $startObj->format('d/m');
                    if ($item['start_date'] != $item['end_date']) {
                        $dateRange .= ' a ' . $endObj->format('d/m');
                    }

                    $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                ?>
                    <!-- CARD -->
                    <div style="background: white; border-radius: 16px; padding: 0; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; overflow: hidden; position: relative;">
                        
                        <!-- Barra Lateral Colorida (Opcional, decorativa) -->
                        <div style="width: 6px; background: var(--slate-600);"></div>

                        <div style="flex: 1; padding: 16px; display: flex; gap: 16px; align-items: flex-start;">
                            
                            <!-- Bloco de Data -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 60px; height: 60px; background: var(--sage-50); border-radius: 12px; color: var(--sage-700); border: 1px solid var(--sage-100);">
                                <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?= $monthStr ?></span>
                                <span style="font-size: 1.4rem; font-weight: 800; line-height: 1; margin-top: 2px;"><?= $dayStr ?></span>
                            </div>

                            <!-- Conteúdo -->
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                                            <?= htmlspecialchars($item['reason'] ?: 'Ausência') ?>
                                        </h4>
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 0.8rem; color: var(--slate-500); background: var(--slate-50); padding: 2px 8px; border-radius: 6px; border: 1px solid var(--slate-200); font-weight: 500;">
                                                <?= $dateRange ?>
                                            </span>
                                            
                                            <?php if ($item['replacement_name']): ?>
                                                <span style="font-size: 0.8rem; color: var(--primary); background: var(--primary-50); padding: 2px 8px; border-radius: 6px; border: 1px solid var(--primary-100); font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                                    <i data-lucide="user-check" width="12"></i> <?= htmlspecialchars($item['replacement_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size: 0.8rem; color: var(--rose-600); background: var(--rose-50); padding: 2px 8px; border-radius: 6px; border: 1px solid var(--rose-100); font-weight: 600;">
                                                    Sem substituto!
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Ações (Desktop) -->
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="editItem(<?= $itemJson ?>)" title="Editar" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #bfdbfe; background: var(--slate-50); color: var(--slate-600); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                            <i data-lucide="edit-2" width="14"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Cancelar esta ausência?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" title="Excluir" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--rose-200); background: var(--rose-50); color: var(--rose-600); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                                <i data-lucide="trash-2" width="14"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Detalhes Extras (Obs/Audio) -->
                                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                                    <?php if (!empty($item['observation'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--slate-600); background: var(--slate-50); padding: 8px 12px; border-radius: 8px; border-left: 3px solid var(--slate-300); line-height: 1.4;">
                                            <?= nl2br(htmlspecialchars($item['observation'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($item['audio_path'])): ?>
                                        <div style="background: #f0f9ff; padding: 8px; border-radius: 10px; border: 1px dashed #bae6fd; display: flex; align-items: center; gap: 8px;">
                                            <div style="background: #0ea5e9; color: white; padding: 6px; border-radius: 50%;">
                                                <i data-lucide="mic" width="14"></i>
                                            </div>
                                            <audio controls style="height: 32px; flex: 1; max-width: 300px;">
                                                <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/webm">
                                                <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/mp4">
                                            </audio>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="height: 48px;"></div>
</div>

<!-- MODAL E SCRIPTS (Mantidos iguais, apenas ajustando visual se necessário) -->
<!-- ... código do modal e JS ... -->
<style>
/* FORÇA a cor do botão de gravação */
button#btnStartRecord,
button#btnStopRecord {
    background: var(--slate-600) !important;
    color: white !important;
}
button#btnStartRecord:hover {
    background: #dc2626 !important; /* Vermelho */
}
button#btnStartRecord:active,
button#btnStartRecord:focus {
    background: #b91c1c !important; /* Vermelho escuro */
}
button#btnStopRecord {
    background: var(--rose-500) !important;
}
button#btnStopRecord:hover {
    background: var(--rose-600) !important;
}
button#btnStopRecord:active,
button#btnStopRecord:focus {
    background: var(--rose-700) !important;
}
</style>
<div id="absenceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div id="formCard" class="fade-in-up" style="background: var(--bg-surface); width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; border-radius: var(--radius-lg); padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); position: relative;">
        
        <button type="button" onclick="closeModal()" style="position: absolute; right: 16px; top: 16px; background: none; border: none; color: var(--slate-400); cursor: pointer;">
            <i data-lucide="x" width="24"></i>
        </button>

        <h3 id="formTitle" style="margin-top: 0; display: flex; align-items: center; gap: 8px; color: var(--text-main); font-size: 1.25rem;">
            <i data-lucide="calendar-plus" style="color: var(--primary);" width="24"></i>
            Nova Indisponibilidade
        </h3>

        <form method="POST" id="absenceForm" style="margin-top: 20px;" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">Início</label>
                    <input type="date" name="start_date" id="startDate" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">Fim (Opcional)</label>
                    <input type="date" name="end_date" id="endDate" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">Motivo Resumido</label>
                <input type="text" name="reason" id="reason" placeholder="Ex: Viagem, Trabalho..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">Observação / Áudio</label>
                <textarea name="observation" id="observation" rows="2" placeholder="Explique..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); margin-bottom: 10px;"></textarea>
                
                <input type="file" name="audio_file" id="audioInput" accept="audio/*" style="display: none;">

                <div style="background: var(--slate-50); border: 1px dashed var(--slate-300); border-radius: 12px; padding: 12px; text-align: center;">
                    <div id="recordingControls">
                        <button type="button" id="btnStartRecord" onclick="startRecording()" style="background: var(--slate-600); color: white; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='var(--slate-700)'" onmouseout="this.style.background='var(--slate-600)'" onmousedown="this.style.background='var(--slate-800)'" onmouseup="this.style.background='var(--slate-700)'">
                            <i data-lucide="mic" width="16"></i> Gravar Explicação
                        </button>
                        <button type="button" id="btnStopRecord" onclick="stopRecording()" style="display: none; background: var(--rose-500); color: white; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            <i data-lucide="square" width="16"></i> Parar
                        </button>
                    </div>

                    <div id="audioPreviewContainer" style="display: none; margin-top: 10px;">
                        <audio id="audioPlayer" controls style="width: 100%; height: 36px;"></audio>
                        <button type="button" onclick="clearAudio()" style="margin-top: 6px; background: none; border: none; color: var(--rose-500); font-size: 0.75rem; cursor: pointer; text-decoration: underline;">Remover Áudio</button>
                    </div>

                    <div id="existingAudioContainer" style="display: none; margin-top: 10px; border-top: 1px solid var(--slate-200); paddingTop: 10px;">
                        <p style="font-size: 0.75rem; color: var(--slate-500); margin-bottom: 4px;">Áudio Salvo</p>
                        <audio id="existingAudioPlayer" controls style="width: 100%; height: 36px;"></audio>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                    Substituto <span style="color: var(--rose-500);">*</span>
                </label>
                <div style="position: relative;">
                    <input type="text" list="users_datalist" id="replacementSearch" placeholder="Busque o nome..." required
                        onchange="updateReplacementId(this)"
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                    <input type="hidden" name="replacement_id" id="replacement_id_input">
                    <datalist id="users_datalist">
                        <?php foreach ($users_list as $u): ?>
                            <option data-id="<?= $u['id'] ?>" value="<?= htmlspecialchars($u['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <i data-lucide="search" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--slate-400); width: 16px;"></i>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; background: var(--slate-200); color: var(--slate-600); border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" id="submitBtn" class="ripple" style="flex: 2; background: var(--slate-600); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <span id="submitText">Registrar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mesmas funções JS de antes
    function updateReplacementId(input) {
        const list = document.getElementById('users_datalist');
        const hiddenInput = document.getElementById('replacement_id_input');
        const options = list.options;
        hiddenInput.value = '';
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === input.value) {
                hiddenInput.value = options[i].getAttribute('data-id');
                break;
            }
        }
    }

    let mediaRecorder;
    let audioChunks = [];

    async function startRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Erro: Microfone não disponível (HTTPS necessário).');
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.start();
            audioChunks = [];
            document.getElementById('btnStartRecord').style.display = 'none';
            document.getElementById('btnStopRecord').style.display = 'inline-flex';

            mediaRecorder.ondataavailable = event => audioChunks.push(event.data);
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const audioUrl = URL.createObjectURL(audioBlob);
                const audioPlayer = document.getElementById('audioPlayer');
                audioPlayer.src = audioUrl;
                document.getElementById('audioPreviewContainer').style.display = 'block';

                const file = new File([audioBlob], "recording.webm", { type: "audio/webm" });
                const container = new DataTransfer();
                container.items.add(file);
                document.getElementById('audioInput').files = container.files;
            };
        } catch (err) {
            alert("Erro ao gravar: " + err.message);
        }
    }

    function stopRecording() {
        if (mediaRecorder) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
        document.getElementById('btnStartRecord').style.display = 'inline-flex';
        document.getElementById('btnStopRecord').style.display = 'none';
    }

    function clearAudio() {
        document.getElementById('audioInput').value = '';
        document.getElementById('audioPreviewContainer').style.display = 'none';
    }

    function openModal() {
        document.getElementById('absenceModal').style.display = 'flex';
        if(document.getElementById('formAction').value !== 'edit') cancelEdit();
    }

    function closeModal() {
        document.getElementById('absenceModal').style.display = 'none';
        cancelEdit();
    }

    function editItem(item) {
        cancelEdit();
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = item.id;
        document.getElementById('startDate').value = item.start_date;
        document.getElementById('endDate').value = item.end_date;
        document.getElementById('reason').value = item.reason;
        document.getElementById('observation').value = item.observation || '';

        if (item.replacement_name) {
            document.getElementById('replacementSearch').value = item.replacement_name;
            document.getElementById('replacement_id_input').value = item.replacement_id;
        }

        if (item.audio_path) {
            document.getElementById('existingAudioPlayer').src = '../' + item.audio_path;
            document.getElementById('existingAudioContainer').style.display = 'block';
        }

        document.getElementById('formTitle').innerText = 'Editar Ausência';
        document.getElementById('submitText').innerText = 'Salvar';
        document.getElementById('absenceModal').style.display = 'flex';
    }

    function cancelEdit() {
        document.getElementById('absenceForm').reset();
        document.getElementById('formAction').value = 'add';
        ['formId', 'replacement_id_input'].forEach(id => document.getElementById(id).value = '');
        clearAudio();
        document.getElementById('existingAudioContainer').style.display = 'none';
        document.getElementById('existingAudioPlayer').src = '';
        document.getElementById('formTitle').innerHTML = '<i data-lucide="calendar-plus" style="color:var(--primary)"></i> Nova Indisponibilidade';
        document.getElementById('submitText').innerText = 'Registrar';
        lucide.createIcons();
    }
</script>

<?php renderAppFooter(); ?>