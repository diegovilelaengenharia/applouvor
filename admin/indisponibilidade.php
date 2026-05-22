<?php
// admin/indisponibilidade.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

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

                    $_SESSION['success'] = "Ausência registrada com sucesso!";
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
                $_SESSION['success'] = "Ausência atualizada com sucesso!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
            }
        }
        // EXCLUIR
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$id, $user_id])) {
                $_SESSION['success'] = "Ausência cancelada com sucesso.";
            }
        }
        header("Location: indisponibilidade.php");
        exit;
    }
}

if (isset($_SESSION['success'])) { $success = $_SESSION['success']; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

// --- DADOS ---
$my_items = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as replacement_name 
        FROM user_unavailability u
        LEFT JOIN users r ON u.replacement_id = r.id
        WHERE u.user_id = ? AND u.end_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ORDER BY u.start_date ASC
    ");
    $stmt->execute([$user_id]);
    $my_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Graceful degradation
}

$users_list = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM users WHERE id != $user_id ORDER BY name ASC");
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Graceful degradation
}

renderAppHeader('Minhas Ausências');
?>

<div class="min-h-screen bg-[#121316] text-[#E2E8F0] px-4 py-8 md:px-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <!-- Top Navigation Header -->
        <div class="flex items-center justify-between border-b border-neutral-800/80 pb-4">
            <a href="index.php" class="inline-flex items-center gap-2 text-neutral-400 hover:text-white transition-colors text-sm font-medium group active:scale-[0.97]">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Voltar para o Painel
            </a>
            
            <div class="flex items-center gap-2">
                <a href="index.php" title="Painel Admin" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </a>
                <a href="../app/index.php" title="Painel do Músico" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Title Header -->
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white font-sans">
                Ausências de Escala
            </h1>
            <p class="text-sm text-neutral-400 mt-2">
                Informe as datas em que você não poderá servir para que a liderança reorganize as escalas.
            </p>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="p-4 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-sm font-semibold flex items-center gap-2 shadow-lg">
                <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="p-4 rounded-xl bg-[#F43F5E]/10 border border-[#F43F5E]/20 text-[#F43F5E] text-sm font-semibold flex items-center gap-2 shadow-lg">
                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Bento Layout Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            
            <!-- Coluna da Esquerda (Informações e Ação) -->
            <div class="md:col-span-1 space-y-6">
                
                <!-- Bento Info Card -->
                <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl relative overflow-hidden space-y-5">
                    <div class="absolute -right-16 -top-16 w-32 h-32 rounded-full bg-[#2E7EED]/5 blur-2xl pointer-events-none"></div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-[#2E7EED]/10 border border-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] flex-shrink-0">
                            <i data-lucide="info" class="w-5 h-5"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-white leading-snug">Sobre as Ausências</h4>
                            <p class="text-xs text-neutral-400 leading-relaxed">
                                Cada falta gera um impacto importante na preparação do culto. Lembre-se de organizar sua ausência com antecedência para não sobrecarregar seus irmãos de ministério.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2.5 pt-2 border-t border-neutral-850">
                        <span class="block text-[10px] font-extrabold uppercase text-[#FFC107] tracking-wider">Diretrizes:</span>
                        <ul class="text-xs text-neutral-400 space-y-2 list-none pl-0">
                            <li class="flex items-start gap-2">
                                <span class="text-[#2E7EED] mt-0.5">•</span>
                                <span>Cadastre apenas quando for estritamente necessário.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#2E7EED] mt-0.5">•</span>
                                <span>Defina a data e o motivo de forma clara.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#2E7EED] mt-0.5">•</span>
                                <span><strong>Indique obrigatoriamente</strong> um substituto da equipe.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#2E7EED] mt-0.5">•</span>
                                <span>Opcional: Grave uma explicação de áudio para a liderança.</span>
                            </li>
                        </ul>
                    </div>

                    <p class="text-[11px] italic text-[#FFC107] leading-relaxed bg-[#FFC107]/5 border-l-2 border-[#FFC107] pl-3.5 py-1.5 rounded-r">
                        "Servi uns aos outros, cada um conforme o dom que recebeu, como bons despenseiros da multiforme graça de Deus." <br>
                        <span class="block text-right font-semibold mt-1">— 1 Pedro 4:10</span>
                    </p>

                    <button onclick="openModal()" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        Registrar Nova Ausência
                    </button>
                </div>

            </div>

            <!-- Coluna da Direita (Listagem de Ausências) -->
            <div class="md:col-span-2 space-y-6">
                
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2">
                        <span class="w-1.5 h-3 bg-[#2E7EED] rounded-full"></span>
                        Minhas Ausências Registradas
                    </h3>
                </div>

                <?php if (empty($my_items)): ?>
                    <div class="text-center py-12 bg-[#1A1B1F] border border-neutral-800 rounded-xl space-y-4 shadow-xl">
                        <div class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-500 mx-auto">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm text-neutral-400">Nenhuma ausência futura ou recente registrada por você.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($my_items as $item):
                            $startObj = new DateTime($item['start_date']);
                            $endObj = new DateTime($item['end_date']);
                            
                            $months_pt = [
                                '01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
                                '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
                                '09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ'
                            ];
                            
                            $monthStr = $months_pt[$startObj->format('m')] ?? 'MÊS';
                            $dayStr = $startObj->format('d');
                            
                            $dateRange = $startObj->format('d/m');
                            if ($item['start_date'] != $item['end_date']) {
                                $dateRange .= ' a ' . $endObj->format('d/m');
                            }

                            $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                        ?>
                            <!-- Card Bento de Ausência -->
                            <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 shadow-xl hover:border-neutral-750 transition-all duration-300 flex items-start gap-4 relative overflow-hidden">
                                
                                <!-- Indicador lateral esquerdo -->
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#2E7EED]"></div>
                                
                                <!-- Bloco de Data Bento -->
                                <div class="w-14 h-14 bg-[#121316] border border-neutral-800 rounded-lg flex flex-col items-center justify-center flex-shrink-0 text-center shadow-md">
                                    <span class="text-[9px] font-extrabold text-[#2E7EED] tracking-wider uppercase"><?= $monthStr ?></span>
                                    <span class="text-lg font-extrabold text-white leading-tight mt-0.5"><?= $dayStr ?></span>
                                </div>

                                <!-- Conteúdo Principal -->
                                <div class="flex-1 min-w-0 space-y-3.5">
                                    
                                    <!-- Header do Card -->
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="font-bold text-white text-base leading-snug">
                                                <?= htmlspecialchars($item['reason'] ?: 'Indisponibilidade') ?>
                                            </h4>
                                            
                                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-neutral-800 text-neutral-400 border border-neutral-750 text-[10px] font-bold">
                                                    <i data-lucide="calendar" class="w-3 h-3 text-[#2E7EED]"></i>
                                                    <?= $dateRange ?>
                                                </span>
                                                
                                                <?php if ($item['replacement_name']): ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-[#10B981]/10 text-[#10B981] border border-[#10B981]/20 text-[10px] font-bold">
                                                        <i data-lucide="user-check" class="w-3 h-3"></i>
                                                        Substituto: <?= htmlspecialchars($item['replacement_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-[#F43F5E]/10 text-[#F43F5E] border border-[#F43F5E]/20 text-[10px] font-bold">
                                                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                                        Sem substituto cadastrado!
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Ações rápidas (Edit/Delete) -->
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <button onclick="editItem(<?= $itemJson ?>)" title="Editar" class="w-8 h-8 rounded-lg bg-neutral-850 hover:bg-neutral-800 border border-neutral-750 text-neutral-400 hover:text-white flex items-center justify-center transition-colors active:scale-[0.9]">
                                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                            
                                            <form method="POST" onsubmit="return confirm('Tem certeza de que deseja cancelar este registro de ausência?');" class="m-0">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" title="Excluir" class="w-8 h-8 rounded-lg bg-[#F43F5E]/10 hover:bg-[#F43F5E]/20 border border-[#F43F5E]/20 text-[#F43F5E] flex items-center justify-center transition-colors active:scale-[0.9]">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Observações Adicionais e Player -->
                                    <?php if (!empty($item['observation']) || !empty($item['audio_path'])): ?>
                                        <div class="space-y-2 pt-2 border-t border-neutral-850">
                                            <?php if (!empty($item['observation'])): ?>
                                                <p class="text-xs text-neutral-400 leading-relaxed bg-[#121316]/50 border-l border-neutral-700/50 pl-3 py-1.5 rounded-r">
                                                    <?= nl2br(htmlspecialchars($item['observation'])) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($item['audio_path'])): ?>
                                                <div class="inline-flex items-center gap-3 bg-[#2E7EED]/5 border border-[#2E7EED]/20 p-2 rounded-xl w-full max-w-sm">
                                                    <div class="w-8 h-8 rounded-full bg-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] flex-shrink-0 animate-pulse">
                                                        <i data-lucide="mic" class="w-4 h-4"></i>
                                                    </div>
                                                    <audio controls class="h-8 flex-1 max-w-[240px] opacity-85 hover:opacity-100 transition-opacity">
                                                        <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/webm">
                                                        <source src="../<?= htmlspecialchars($item['audio_path']) ?>" type="audio/mp4">
                                                    </audio>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<!-- Modal Glassmorphic de Ausência -->
<div id="absenceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-md transition-all duration-300">
    <div id="formCard" class="bg-[#1A1B1F] border border-neutral-850 w-[92%] max-w-md max-h-[85vh] overflow-y-auto rounded-2xl p-6 shadow-2xl relative space-y-5 animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Botão Fechar -->
        <button type="button" onclick="closeModal()" class="absolute right-4 top-4 text-neutral-400 hover:text-white transition-colors active:scale-[0.9]">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <!-- Título -->
        <h3 id="formTitle" class="text-lg font-bold text-white flex items-center gap-2 border-b border-neutral-850 pb-3">
            <i data-lucide="calendar-plus" class="w-5 h-5 text-[#2E7EED]"></i>
            Registrar Ausência
        </h3>

        <!-- Formulário -->
        <form method="POST" id="absenceForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

            <!-- Datas Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Data de Início</label>
                    <input type="date" name="start_date" id="startDate" required class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Data de Fim</label>
                    <input type="date" name="end_date" id="endDate" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                </div>
            </div>

            <!-- Motivo -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Motivo Resumido</label>
                <input type="text" name="reason" id="reason" placeholder="Ex: Viagem de trabalho, Saúde" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
            </div>

            <!-- Observações & Gravador de Áudio -->
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Observações adicionais</label>
                    <textarea name="observation" id="observation" rows="2" placeholder="Explique os detalhes..." class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all"></textarea>
                </div>
                
                <!-- Upload do microfone -->
                <input type="file" name="audio_file" id="audioInput" accept="audio/*" class="hidden">

                <!-- Bento Audio Recorder Container -->
                <div class="rounded-xl border border-neutral-800 bg-[#121316]/50 p-3.5 text-center space-y-3">
                    <div id="recordingControls" class="flex justify-center">
                        
                        <!-- Iniciar Gravação -->
                        <button type="button" id="btnStartRecord" onclick="startRecording()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-neutral-800 hover:border-neutral-700 bg-[#1A1B1F] text-neutral-300 hover:text-white text-xs font-bold transition-all duration-200 active:scale-[0.97] cursor-pointer">
                            <i data-lucide="mic" class="w-4 h-4 text-[#2E7EED]"></i> 
                            Gravar Explicação de Áudio
                        </button>
                        
                        <!-- Parar Gravação -->
                        <button type="button" id="btnStopRecord" onclick="stopRecording()" class="hidden w-full items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-[#F43F5E]/30 bg-[#F43F5E]/10 text-[#F43F5E] text-xs font-bold transition-all cursor-pointer animate-pulse">
                            <i data-lucide="square" class="w-4 h-4 fill-[#F43F5E]"></i> 
                            Parar Gravação (Gravando...)
                        </button>

                    </div>

                    <!-- Preview Áudio Gravado -->
                    <div id="audioPreviewContainer" class="hidden space-y-2">
                        <audio id="audioPlayer" controls class="w-full h-8 opacity-90"></audio>
                        <button type="button" onclick="clearAudio()" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#F43F5E] hover:underline cursor-pointer">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Remover áudio gravado
                        </button>
                    </div>

                    <!-- Áudio Existente no Servidor (Edit mode) -->
                    <div id="existingAudioContainer" class="hidden pt-3 border-t border-neutral-800 space-y-1">
                        <p class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider">Áudio Salvo anteriormente:</p>
                        <audio id="existingAudioPlayer" controls class="w-full h-8 opacity-80"></audio>
                    </div>
                </div>
            </div>

            <!-- Substituto -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">
                    Substituto Recomendado <span class="text-[#F43F5E]">*</span>
                </label>
                <div class="relative">
                    <input type="text" list="users_datalist" id="replacementSearch" placeholder="Busque pelo nome na equipe..." required onchange="updateReplacementId(this)" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 pl-3.5 pr-10 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                    <input type="hidden" name="replacement_id" id="replacement_id_input">
                    <datalist id="users_datalist">
                        <?php foreach ($users_list as $u): ?>
                            <option data-id="<?= $u['id'] ?>" value="<?= htmlspecialchars($u['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <i data-lucide="search" class="absolute right-3.5 top-3 w-4 h-4 text-neutral-500"></i>
                </div>
            </div>

            <!-- Botões Formulário -->
            <div class="flex items-center gap-3 pt-3 border-t border-neutral-850">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-neutral-800 text-neutral-400 hover:text-white hover:bg-neutral-800/40 text-xs font-bold transition-all duration-250 text-center active:scale-[0.96]">
                    Cancelar
                </button>
                <button type="submit" id="submitBtn" class="flex-[2] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                    <span id="submitText">Registrar Ausência</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
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
        document.getElementById('btnStartRecord').style.display = 'none';
        document.getElementById('btnStopRecord').style.display = 'flex';
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Erro: O microfone não está disponível. Certifique-se de que a conexão é segura (HTTPS).');
            document.getElementById('btnStartRecord').style.display = 'flex';
            document.getElementById('btnStopRecord').style.display = 'none';
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.start();
            audioChunks = [];

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
            alert("Não foi possível acessar o microfone: " + err.message);
            document.getElementById('btnStartRecord').style.display = 'flex';
            document.getElementById('btnStopRecord').style.display = 'none';
        }
    }

    function stopRecording() {
        if (mediaRecorder) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
        document.getElementById('btnStartRecord').style.display = 'flex';
        document.getElementById('btnStopRecord').style.display = 'none';
    }

    function clearAudio() {
        document.getElementById('audioInput').value = '';
        document.getElementById('audioPreviewContainer').style.display = 'none';
    }

    function openModal() {
        const modal = document.getElementById('absenceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if(document.getElementById('formAction').value !== 'edit') cancelEdit();
    }

    function closeModal() {
        const modal = document.getElementById('absenceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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

        document.getElementById('formTitle').innerHTML = '<i data-lucide="edit-2" class="w-5 h-5 text-[#2E7EED]"></i> Editar Ausência';
        document.getElementById('submitText').innerText = 'Salvar Alterações';
        
        const modal = document.getElementById('absenceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function cancelEdit() {
        document.getElementById('absenceForm').reset();
        document.getElementById('formAction').value = 'add';
        ['formId', 'replacement_id_input'].forEach(id => document.getElementById(id).value = '');
        clearAudio();
        document.getElementById('existingAudioContainer').style.display = 'none';
        document.getElementById('existingAudioPlayer').src = '';
        document.getElementById('formTitle').innerHTML = '<i data-lucide="calendar-plus" class="w-5 h-5 text-[#2E7EED]"></i> Registrar Ausência';
        document.getElementById('submitText').innerText = 'Registrar Ausência';
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php renderAppFooter(); ?>