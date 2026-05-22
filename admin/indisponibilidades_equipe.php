<?php
// admin/indisponibilidades_equipe.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkAdmin();

$success = '';
$error = '';

// --- PROCESSAR FORMULÁRIO (ADD/DELETE) ---
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
                    $_SESSION['success'] = "Ausência registrada com sucesso pela liderança!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Erro ao registrar: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
            }
        }
        // EXCLUIR
        elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM user_unavailability WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Registro de ausência removido com sucesso.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erro ao remover ausência: " . $e->getMessage();
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
    SELECT u.*, us.name as user_name, us.avatar, r.name as replacement_name 
    FROM user_unavailability u
    JOIN users us ON u.user_id = us.id
    LEFT JOIN users r ON u.replacement_id = r.id
    WHERE DATE_FORMAT(u.start_date, '%Y-%m') = :month
";

if ($userFilter) {
    $sql .= " AND u.user_id = :userId";
}

$sql .= " ORDER BY u.start_date ASC";

$absences = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':month', $monthFilter);
    if ($userFilter) $stmt->bindValue(':userId', $userFilter);
    $stmt->execute();
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Graceful degradation
}

$users = [];
try {
    $users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Graceful degradation
}

renderAppHeader('Ausências da Equipe');
?>

<div class="min-h-screen bg-[#121316] text-[#E2E8F0] px-4 py-8 md:px-8">
    <div class="max-w-5xl mx-auto space-y-8">

        <!-- Top Navigation -->
        <div class="flex items-center justify-between border-b border-neutral-800/80 pb-4">
            <a href="lider.php" class="inline-flex items-center gap-2 text-neutral-400 hover:text-white transition-colors text-sm font-medium group active:scale-[0.97]">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Voltar para Liderança
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

        <!-- Title -->
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white font-sans">
                Ausências da Equipe
            </h1>
            <p class="text-sm text-neutral-400 mt-2">
                Gerencie e visualize as indisponibilidades cadastradas pelos voluntários para organizar as escalas com tranquilidade.
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

        <!-- Bento Grid: Filtros e Controles -->
        <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 shadow-xl relative overflow-hidden">
            <!-- Decorative blur -->
            <div class="absolute -right-24 -bottom-24 w-48 h-48 rounded-full bg-[#2E7EED]/5 blur-3xl pointer-events-none"></div>

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-5 items-end relative">
                
                <!-- Mês de Referência -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Mês de Referência</label>
                    <input type="month" name="month" value="<?= $monthFilter ?>" onchange="this.form.submit()" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                </div>

                <!-- Filtrar por Membro -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Filtrar por Membro</label>
                    <select name="user_id" onchange="this.form.submit()" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold appearance-none cursor-pointer">
                        <option value="">Mostrar Todos os Membros</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botão Adicionar -->
                <button type="button" onclick="openModal()" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Lançar Ausência
                </button>

            </form>
        </div>

        <!-- Seção Principal de Listagem -->
        <div class="space-y-5">
            <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-[#2E7EED] rounded-full"></span>
                Ausências da Equipe Identificadas
            </h3>

            <?php if (empty($absences)): ?>
                <div class="text-center py-16 bg-[#1A1B1F] border border-neutral-800 rounded-xl space-y-4 shadow-xl">
                    <div class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-500 mx-auto">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </div>
                    <p class="text-sm text-neutral-400">Nenhuma ausência futura ou registrada para este filtro e mês.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($absences as $item): 
                        $startObj = new DateTime($item['start_date']);
                        $endObj = new DateTime($item['end_date']);
                        
                        $months_pt = [
                            '01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
                            '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
                            '09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ'
                        ];
                        $monthStr = $months_pt[$startObj->format('m')] ?? 'MÊS';
                        $dayStr = $startObj->format('d');
                        
                        $periodo = $startObj->format('d/m');
                        if ($item['start_date'] != $item['end_date']) {
                            $periodo .= ' a ' . $endObj->format('d/m');
                        }

                        // Cor baseada nas iniciais do avatar
                        $initial = strtoupper(substr($item['user_name'], 0, 1));
                        $colors = [
                            'A' => '#2E7EED', 'B' => '#10B981', 'C' => '#FFC107', 'D' => '#F43F5E',
                            'E' => '#2E7EED', 'F' => '#10B981', 'G' => '#FFC107', 'H' => '#F43F5E',
                            'I' => '#2E7EED', 'J' => '#10B981', 'K' => '#FFC107', 'L' => '#F43F5E',
                            'M' => '#2E7EED', 'N' => '#10B981', 'O' => '#FFC107', 'P' => '#F43F5E',
                            'Q' => '#2E7EED', 'R' => '#10B981', 'S' => '#FFC107', 'T' => '#F43F5E',
                            'U' => '#2E7EED', 'V' => '#10B981', 'W' => '#FFC107', 'X' => '#F43F5E',
                            'Y' => '#2E7EED', 'Z' => '#10B981'
                        ];
                        $avatarColor = $colors[$initial] ?? '#2E7EED';
                    ?>
                        <!-- Card Bento de Ausência de Voluntário -->
                        <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 shadow-xl hover:border-neutral-750 transition-all duration-300 flex items-start gap-4 relative overflow-hidden">
                            
                            <!-- Indicador lateral -->
                            <div class="absolute left-0 top-0 bottom-0 w-1" style="background-color: <?= $avatarColor ?>;"></div>

                            <!-- Bloco de Data Bento -->
                            <div class="w-14 h-14 bg-[#121316] border border-neutral-800 rounded-lg flex flex-col items-center justify-center flex-shrink-0 text-center shadow-md">
                                <span class="text-[9px] font-extrabold tracking-wider uppercase" style="color: <?= $avatarColor ?>;"><?= $monthStr ?></span>
                                <span class="text-lg font-extrabold text-white leading-tight mt-0.5"><?= $dayStr ?></span>
                            </div>

                            <!-- Dados e Justificativa -->
                            <div class="flex-1 min-w-0 space-y-3">
                                
                                <!-- Nome e Detalhes do Membro -->
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <!-- Avatar Circle -->
                                            <div class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[9px] text-white flex-shrink-0" style="background-color: <?= $avatarColor ?>;">
                                                <?= $initial ?>
                                            </div>
                                            <span class="font-extrabold text-[#E2E8F0] text-sm truncate"><?= htmlspecialchars($item['user_name']) ?></span>
                                        </div>
                                        
                                        <h4 class="font-bold text-white text-sm leading-snug">
                                            <?= htmlspecialchars($item['reason']) ?>
                                        </h4>
                                    </div>

                                    <!-- Excluir Ausência -->
                                    <form method="POST" onsubmit="return confirm('Tem certeza de que deseja remover esta ausência cadastrada?');" class="m-0 flex-shrink-0">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" title="Excluir Registro" class="w-7 h-7 rounded bg-[#F43F5E]/10 hover:bg-[#F43F5E]/20 border border-[#F43F5E]/20 text-[#F43F5E] flex items-center justify-center transition-colors active:scale-[0.9]">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Badges de Período e Substituto -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-neutral-800 text-neutral-400 border border-neutral-750 text-[10px] font-bold">
                                        <i data-lucide="calendar" class="w-3 h-3 text-[#2E7EED]"></i>
                                        Período: <?= $periodo ?>
                                    </span>
                                    
                                    <?php if ($item['replacement_name']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-[#10B981]/10 text-[#10B981] border border-[#10B981]/20 text-[10px] font-bold">
                                            <i data-lucide="user-check" class="w-3 h-3"></i>
                                            Substituto: <?= htmlspecialchars($item['replacement_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded bg-[#F43F5E]/10 text-[#F43F5E] border border-[#F43F5E]/20 text-[10px] font-bold">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            Sem substituto!
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Observações e Player de Justificativa -->
                                <?php if (!empty($item['observation']) || !empty($item['audio_path'])): ?>
                                    <div class="space-y-2 pt-2 border-t border-neutral-850">
                                        <?php if (!empty($item['observation'])): ?>
                                            <p class="text-xs text-neutral-400 leading-relaxed bg-[#121316]/50 border-l border-neutral-700/50 pl-3 py-1.5 rounded-r">
                                                <?= nl2br(htmlspecialchars($item['observation'])) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (!empty($item['audio_path'])): ?>
                                            <div class="inline-flex items-center gap-2.5 bg-[#2E7EED]/5 border border-[#2E7EED]/20 p-1.5 rounded-lg w-full max-w-xs">
                                                <div class="w-7 h-7 rounded-full bg-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] flex-shrink-0 animate-pulse">
                                                    <i data-lucide="mic" class="w-3.5 h-3.5"></i>
                                                </div>
                                                <audio controls class="h-6 flex-1 max-w-[200px] opacity-80 hover:opacity-100 transition-opacity">
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

<!-- Modal Glassmorphic de Lançamento de Ausência (Admin) -->
<div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-md transition-all duration-300">
    <div class="bg-[#1A1B1F] border border-neutral-850 w-[92%] max-w-md max-h-[85vh] overflow-y-auto rounded-2xl p-6 shadow-2xl relative space-y-5 animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Botão Fechar -->
        <button onclick="closeModal()" class="absolute right-4 top-4 text-neutral-400 hover:text-white transition-colors active:scale-[0.9]">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <!-- Título -->
        <h3 class="text-lg font-bold text-white flex items-center gap-2 border-b border-neutral-850 pb-3">
            <i data-lucide="calendar-plus" class="w-5 h-5 text-[#2E7EED]"></i>
            Lançar Ausência
        </h3>

        <!-- Formulário -->
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">

            <!-- Seleção do Membro -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Membro Voluntário</label>
                <select name="user_id" required class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold appearance-none cursor-pointer">
                    <option value="">Selecione o membro da equipe...</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Datas Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Data de Início</label>
                    <input type="date" name="start_date" required class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                </div>
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Data de Fim</label>
                    <input type="date" name="end_date" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                </div>
            </div>

            <!-- Motivo -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Motivo Resumido</label>
                <input type="text" name="reason" placeholder="Ex: Viagem de trabalho, Escala profissional" required class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
            </div>

            <!-- Observações -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Observações adicionais</label>
                <textarea name="observation" rows="2" placeholder="Detalhes ou anotações extras da liderança..." class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3.5 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all"></textarea>
            </div>

            <!-- Seleção do Substituto -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Substituto Definido</label>
                <select name="replacement_id" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-2.5 px-3 text-sm text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold appearance-none cursor-pointer">
                    <option value="">Sem substituto definido (Deixar em aberto)</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botões -->
            <div class="flex items-center gap-3 pt-3 border-t border-neutral-850">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-neutral-800 text-neutral-400 hover:text-white hover:bg-neutral-800/40 text-xs font-bold transition-all duration-250 text-center active:scale-[0.96]">
                    Cancelar
                </button>
                <button type="submit" class="flex-[2] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                    Registrar Ausência
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    function openModal() {
        const modal = document.getElementById('addModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    
    function closeModal() {
        const modal = document.getElementById('addModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php renderAppFooter(); ?>
