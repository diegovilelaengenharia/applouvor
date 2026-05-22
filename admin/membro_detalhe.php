<?php
// admin/membro_detalhe.php
require_once '../src/helpers/auth.php';
checkAdmin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

if (!isset($_GET['id'])) {
    header('Location: membros.php');
    exit;
}

$id = $_GET['id'];

// Processar atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, instrument = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([
        $_POST['name'],
        $_POST['instrument'],
        $_POST['phone'],
        $_POST['email'] ?? null,
        $id
    ]);
    header("Location: membro_detalhe.php?id=$id&updated=1");
    exit;
}

// Buscar dados do membro
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: membros.php');
    exit;
}

// Buscar histórico de escalas
$stmtHistory = $pdo->prepare("
    SELECT s.*,
           (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs,
           su.status as presence_status,
           su.absence_note
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ?
    ORDER BY s.event_date DESC
");
$stmtHistory->execute([$id]);
$schedules = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Calcular estatísticas
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_escalas,
        MIN(s.event_date) as primeira_escala,
        MAX(s.event_date) as ultima_escala
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ?
");
$stmtStats->execute([$id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Calcular frequência média
$frequencia_media = 0;
if ($stats['total_escalas'] > 1 && $stats['primeira_escala'] && $stats['ultima_escala']) {
    $primeira = new DateTime($stats['primeira_escala']);
    $ultima = new DateTime($stats['ultima_escala']);
    $dias_total = $primeira->diff($ultima)->days;
    $frequencia_media = $dias_total > 0 ? round($dias_total / ($stats['total_escalas'] - 1), 1) : 0;
}

// Calcular breakdown de presença
$totalEscalas = count($schedules);
$totalPresente = 0;
$totalFaltou = 0;
$totalJustificou = 0;

foreach ($schedules as $sc) {
    $st = $sc['presence_status'] ?? 'pending';
    if (in_array($st, ['confirmed', 'pending'])) $totalPresente++;
    elseif ($st === 'absent') $totalFaltou++;
    elseif ($st === 'absent_justified') $totalJustificou++;
}

$taxaPresenca = $totalEscalas > 0
    ? round(($totalPresente / $totalEscalas) * 100)
    : 0;

// Alerta pastoral — últimas 4 escalas (admin only)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$pastoralAlert = false;
if ($isAdmin && count($schedules) >= 4) {
    $last4 = array_slice($schedules, 0, 4);
    $ausenciasRecentes = 0;
    foreach ($last4 as $sc) {
        $st = $sc['presence_status'] ?? 'pending';
        if (in_array($st, ['absent', 'absent_justified', 'declined'])) {
            $ausenciasRecentes++;
        }
    }
    $taxaRecente = round((4 - $ausenciasRecentes) / 4 * 100);
    if ($ausenciasRecentes >= 2 && $taxaRecente < 60) {
        $pastoralAlert = [
            'ausencias' => $ausenciasRecentes,
            'taxa' => $taxaRecente
        ];
    }
}

// Buscar próxima escala
$stmtNext = $pdo->prepare("
    SELECT s.*
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ? AND s.event_date >= CURDATE()
    ORDER BY s.event_date ASC
    LIMIT 1
");
$stmtNext->execute([$id]);
$nextSchedule = $stmtNext->fetch(PDO::FETCH_ASSOC);

$activeTab = $_GET['tab'] ?? 'historico';
renderAppHeader('Detalhes do Membro');
?>

<div class="min-h-screen bg-[#121316] text-[#E2E8F0] px-4 py-8 md:px-8">
    <div class="max-w-5xl mx-auto space-y-8">
        
        <!-- Header de Navegação Superior -->
        <div class="flex items-center justify-between border-b border-neutral-800/80 pb-4">
            <a href="membros.php" class="inline-flex items-center gap-2 text-neutral-400 hover:text-white transition-colors text-sm font-medium group active:scale-[0.97]">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                Voltar para a Equipe
            </a>
            
            <div class="flex items-center gap-2">
                <a href="index.php" title="Painel Admin" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </a>
                <a href="../app/index.php" title="Visualização Mobile" class="w-9 h-9 rounded-lg bg-[#1A1B1F] border border-neutral-800 flex items-center justify-center text-neutral-400 hover:text-white transition-colors active:scale-[0.95]">
                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <!-- Perfil Bento Header Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1A1B1F] to-[#141518] border border-neutral-800 p-6 md:p-8 shadow-2xl flex flex-col md:flex-row items-center md:items-start gap-6">
            
            <!-- Avatar Inicial com Iniciais Grandes -->
            <?php
            $initial = strtoupper(substr($member['name'], 0, 1));
            // Escolhe uma cor baseada na inicial
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
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center font-extrabold text-3xl text-white shadow-lg relative flex-shrink-0" style="background: <?= $avatarColor ?>; box-shadow: 0 8px 24px <?= $avatarColor ?>15;">
                <?= $initial ?>
                <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-[#121316] border-2 border-neutral-800 flex items-center justify-center text-[10px] text-white">✓</span>
            </div>

            <!-- Dados principais -->
            <div class="text-center md:text-left space-y-3 flex-1">
                <div class="flex flex-col md:flex-row md:items-center gap-2 justify-center md:justify-start">
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white font-sans">
                        <?= htmlspecialchars($member['name']) ?>
                    </h1>
                    <?php if ($member['role'] === 'admin'): ?>
                        <span class="inline-flex self-center md:self-auto px-2 py-0.5 rounded bg-[#FFC107]/10 border border-[#FFC107]/20 text-[#FFC107] text-[10px] font-bold uppercase tracking-wider">
                            Líder / Admin
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-4 gap-y-2 text-sm text-neutral-400">
                    <span class="inline-flex items-center gap-1.5 bg-neutral-800/40 px-3 py-1 rounded-full border border-neutral-800/60">
                        <i data-lucide="music" class="w-3.5 h-3.5 text-[#2E7EED]"></i>
                        <?= htmlspecialchars($member['instrument'] ?: 'Não definido') ?>
                    </span>
                    <?php if (!empty($member['phone'])): ?>
                        <span class="inline-flex items-center gap-1.5 bg-neutral-800/40 px-3 py-1 rounded-full border border-neutral-800/60">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-[#10B981]"></i>
                            <?= htmlspecialchars($member['phone']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($member['email'])): ?>
                        <span class="inline-flex items-center gap-1.5 bg-neutral-800/40 px-3 py-1 rounded-full border border-neutral-800/60">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-neutral-400"></i>
                            <?= htmlspecialchars($member['email']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fundo decorativo sutil -->
            <div class="absolute -right-24 -bottom-24 w-48 h-48 rounded-full bg-[#2E7EED]/5 blur-3xl pointer-events-none"></div>
        </div>

        <!-- Alerta Pastoral Avançado (se houver) -->
        <?php if ($pastoralAlert): ?>
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#1A1B1F] to-[#1A1515] border border-[#F43F5E]/30 p-5 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-[#F43F5E]/10 border border-[#F43F5E]/20 flex items-center justify-center text-[#F43F5E] flex-shrink-0">
                        <i data-lucide="heart-handshake" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-white">Suporte Pastoral Recomendado</h4>
                        <p class="text-xs text-neutral-400 leading-relaxed max-w-2xl">
                            O voluntário acumulou <strong><?= $pastoralAlert['ausencias'] ?> ausência<?= $pastoralAlert['ausencias'] > 1 ? 's' : '' ?></strong> nas últimas 4 escalas agendadas (Taxa recente de presença de apenas <strong><?= $pastoralAlert['taxa'] ?>%</strong>). Considere entrar em contato para oferecer apoio.
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($member['phone'])): 
                    // Limpar telefone para WhatsApp Link
                    $whatsappPhone = preg_replace('/[^0-9]/', '', $member['phone']);
                    // Adicionar DDI Brasil 55 se necessário
                    if (strlen($whatsappPhone) === 11 || strlen($whatsappPhone) === 10) {
                        $whatsappPhone = '55' . $whatsappPhone;
                    }
                    $message = urlencode("Olá " . explode(' ', trim($member['name']))[0] . "! Passando para saber como você está e conversar um pouco. Deus abençoe!");
                ?>
                    <a href="https://wa.me/<?= $whatsappPhone ?>?text=<?= $message ?>" target="_blank" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-[#10B981] hover:bg-[#059669] text-white text-xs font-bold transition-all duration-200 shadow-md shadow-[#10B981]/15 active:scale-[0.97] flex-shrink-0">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                        Enviar Mensagem (WhatsApp)
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Bento Grid de KPIs e Métricas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            
            <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800/80 p-4 shadow-lg hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300">
                <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Escalas</span>
                <span class="block text-3xl font-extrabold text-white mt-1"><?= $stats['total_escalas'] ?? 0 ?></span>
                <span class="block text-[10px] text-neutral-500 mt-0.5">Escalado até hoje</span>
            </div>

            <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800/80 p-4 shadow-lg hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300">
                <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Frequência</span>
                <span class="block text-3xl font-extrabold text-[#2E7EED] mt-1"><?= $frequencia_media ?>d</span>
                <span class="block text-[10px] text-neutral-500 mt-0.5">Intervalo médio de dias</span>
            </div>

            <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800/80 p-4 shadow-lg hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300">
                <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Última</span>
                <span class="block text-3xl font-extrabold text-white mt-1">
                    <?= $stats['ultima_escala'] ? (new DateTime($stats['ultima_escala']))->format('d/m') : '-' ?>
                </span>
                <span class="block text-[10px] text-neutral-500 mt-0.5">Data do último serviço</span>
            </div>

            <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800/80 p-4 shadow-lg hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300">
                <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Próxima</span>
                <span class="block text-3xl font-extrabold text-[#FFC107] mt-1">
                    <?= $nextSchedule ? (new DateTime($nextSchedule['event_date']))->format('d/m') : '-' ?>
                </span>
                <span class="block text-[10px] text-neutral-500 mt-0.5">Próxima escala marcada</span>
            </div>

        </div>

        <!-- Bento Card Breakdown de Presença -->
        <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 shadow-xl">
            <h3 class="text-sm font-bold tracking-wider uppercase text-neutral-300 mb-4 flex items-center gap-2">
                <span class="w-1.5 h-3 bg-[#2E7EED] rounded-full"></span>
                Histórico de Presenças
            </h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                
                <div class="rounded-lg bg-[#10B981]/5 border border-[#10B981]/10 p-4 text-center">
                    <span class="block text-2xl font-extrabold text-[#10B981]"><?= $totalPresente ?></span>
                    <span class="block text-[10px] font-bold text-neutral-400 uppercase tracking-wide mt-1">Presente</span>
                </div>

                <div class="rounded-lg bg-[#F43F5E]/5 border border-[#F43F5E]/10 p-4 text-center">
                    <span class="block text-2xl font-extrabold text-[#F43F5E]"><?= $totalFaltou ?></span>
                    <span class="block text-[10px] font-bold text-neutral-400 uppercase tracking-wide mt-1">Faltou</span>
                </div>

                <div class="rounded-lg bg-[#FFC107]/5 border border-[#FFC107]/10 p-4 text-center">
                    <span class="block text-2xl font-extrabold text-[#FFC107]"><?= $totalJustificou ?></span>
                    <span class="block text-[10px] font-bold text-neutral-400 uppercase tracking-wide mt-1">Justificou</span>
                </div>

                <div class="rounded-lg bg-[#2E7EED]/5 border border-[#2E7EED]/20 p-4 text-center relative overflow-hidden">
                    <span class="block text-2xl font-extrabold text-[#2E7EED]"><?= $taxaPresenca ?>%</span>
                    <span class="block text-[10px] font-bold text-neutral-400 uppercase tracking-wide mt-1">Taxa Geral</span>
                    <div class="absolute -right-6 -bottom-6 w-12 h-12 rounded-full bg-[#2E7EED]/5 pointer-events-none"></div>
                </div>

            </div>
        </div>

        <!-- Seção de Abas & Abas Flutuantes -->
        <div class="space-y-6">
            
            <!-- Abas Flutuantes com Design de Pílula -->
            <div class="flex justify-center">
                <div class="inline-flex bg-[#1A1B1F] border border-neutral-800 p-1 rounded-full shadow-lg gap-1">
                    <a href="?id=<?= $id ?>&tab=historico" class="px-6 py-2 rounded-full text-sm font-bold transition-all duration-200 active:scale-[0.97] <?= $activeTab === 'historico' ? 'bg-[#2E7EED] text-white shadow-md' : 'text-neutral-400 hover:text-white' ?>">
                        Lista de Escalas
                    </a>
                    <a href="?id=<?= $id ?>&tab=dados" class="px-6 py-2 rounded-full text-sm font-bold transition-all duration-200 active:scale-[0.97] <?= $activeTab === 'dados' ? 'bg-[#2E7EED] text-white shadow-md' : 'text-neutral-400 hover:text-white' ?>">
                        Dados do Membro
                    </a>
                </div>
            </div>

            <!-- CONTEÚDO DA ABA: HISTÓRICO -->
            <div class="space-y-4" style="display: <?= $activeTab === 'historico' ? 'block' : 'none' ?>;">
                <?php if (empty($schedules)): ?>
                    <div class="text-center py-12 bg-[#1A1B1F] border border-neutral-800/80 rounded-xl space-y-4">
                        <div class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-500 mx-auto">
                            <i data-lucide="calendar-x" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm text-neutral-400">Nenhuma escala encontrada no histórico deste membro.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($schedules as $schedule):
                            $date = new DateTime($schedule['event_date']);
                            $pStatus = $schedule['presence_status'] ?? 'pending';
                            
                            // Mapeamento de status litúrgicos
                            $statusStyle = match($pStatus) {
                                'confirmed'        => ['bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20', 'Presente', 'check-circle'],
                                'absent'           => ['bg-[#F43F5E]/10 text-[#F43F5E] border-[#F43F5E]/20', 'Faltou', 'x-circle'],
                                'absent_justified' => ['bg-[#FFC107]/10 text-[#FFC107] border-[#FFC107]/20', 'Justificou', 'alert-circle'],
                                'declined'         => ['bg-neutral-800 text-neutral-400 border-neutral-700/60', 'Recusou', 'slash'],
                                default            => ['bg-neutral-850 text-neutral-500 border-neutral-800', 'Pendente', 'clock'],
                            };
                            [$styleClass, $label, $icon] = $statusStyle;
                        ?>
                            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-2">
                                        <h4 class="font-bold text-white group-hover:text-[#2E7EED] transition-colors leading-snug">
                                            <?= htmlspecialchars($schedule['event_type']) ?>
                                        </h4>
                                        
                                        <div class="flex items-center gap-2 text-xs text-neutral-400">
                                            <span><?= $date->format('d/m/Y') ?></span>
                                            <span>•</span>
                                            <span><?= $schedule['total_songs'] ?> música<?= $schedule['total_songs'] != 1 ? 's' : '' ?></span>
                                        </div>

                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border text-[10px] font-bold tracking-wide uppercase <?= $styleClass ?>">
                                            <i data-lucide="<?= $icon ?>" class="w-3 h-3"></i>
                                            <?= $label ?>
                                        </span>

                                        <?php if (in_array($pStatus, ['absent','absent_justified']) && !empty($schedule['absence_note'])): ?>
                                            <p class="text-xs text-neutral-500 italic bg-[#121316]/50 border-l border-neutral-700/50 pl-2.5 py-1 mt-2">
                                                "<?= htmlspecialchars($schedule['absence_note']) ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-1 transition-transform flex-shrink-0 mt-0.5"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CONTEÚDO DA ABA: DADOS -->
            <div style="display: <?= $activeTab === 'dados' ? 'block' : 'none' ?>;">
                <?php if (isset($_GET['updated'])): ?>
                    <div class="mb-6 p-4 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-sm font-semibold flex items-center gap-2 shadow-lg">
                        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                        Dados do voluntário atualizados com sucesso no banco de dados!
                    </div>
                <?php endif; ?>

                <div class="rounded-xl bg-[#1A1B1F] border border-neutral-800 p-6 md:p-8 shadow-2xl relative">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Nome Completo -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Nome Completo</label>
                                <div class="relative">
                                    <input type="text" name="name" value="<?= htmlspecialchars($member['name']) ?>" required class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all font-semibold">
                                    <i data-lucide="user" class="absolute right-3.5 top-3.5 w-4 h-4 text-neutral-500"></i>
                                </div>
                            </div>

                            <!-- Instrumento -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Instrumento / Função</label>
                                <div class="relative">
                                    <input type="text" name="instrument" value="<?= htmlspecialchars($member['instrument'] ?? '') ?>" placeholder="Ex: Guitarra, Voz" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                    <i data-lucide="music" class="absolute right-3.5 top-3.5 w-4 h-4 text-neutral-500"></i>
                                </div>
                            </div>

                            <!-- Telefone -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Telefone</label>
                                <div class="relative">
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '') ?>" placeholder="Ex: (21) 99999-9999" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                    <i data-lucide="phone" class="absolute right-3.5 top-3.5 w-4 h-4 text-neutral-500"></i>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-neutral-400 uppercase tracking-wider">Email</label>
                                <div class="relative">
                                    <input type="email" name="email" value="<?= htmlspecialchars($member['email'] ?? '') ?>" placeholder="Ex: voluntario@email.com" class="w-full bg-[#121316] border border-neutral-800 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED]/20 transition-all">
                                    <i data-lucide="mail" class="absolute right-3.5 top-3.5 w-4 h-4 text-neutral-500"></i>
                                </div>
                            </div>

                        </div>

                        <!-- Botão de Ação -->
                        <div class="pt-4 border-t border-neutral-850 flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-[#2E7EED] hover:bg-[#1C66CE] text-white text-sm font-bold transition-all duration-200 shadow-md shadow-[#2E7EED]/10 active:scale-[0.98]">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
<?php renderAppFooter(); ?>