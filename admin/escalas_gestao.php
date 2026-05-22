<?php
// admin/escalas_gestao.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkAdmin();

// --- Processamento de Exclusão (Escala) ---
if (isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$deleteId]);
        $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$deleteId]);
        $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$deleteId]);
        $pdo->commit();
        header("Location: escalas_gestao.php?success=deleted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erro ao excluir: " . $e->getMessage();
    }
}

// --- Filtros ---
$period = $_GET['period'] ?? 'month'; // month, semester, year
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Definir datas de início e fim baseadas no filtro
if ($period === 'month') {
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $titlePeriod = "Mês: " . date('m/Y', strtotime($startDate));
} elseif ($period === 'semester') {
    $semester = $_GET['semester'] ?? (date('m') <= 6 ? 1 : 2);
    if ($semester == 1) {
        $startDate = "$year-01-01";
        $endDate = "$year-06-30";
        $titlePeriod = "1º Semestre de $year";
    } else {
        $startDate = "$year-07-01";
        $endDate = "$year-12-31";
        $titlePeriod = "2º Semestre de $year";
    }
} elseif ($period === 'year') {
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $titlePeriod = "Ano de $year";
}

// --- QUERIES UNIFICADAS ---

// 1. Listagem de Escalas
$stmtSchedules = $pdo->prepare("
    SELECT s.*, 
    (SELECT COUNT(*) FROM schedule_users WHERE schedule_id = s.id) as total_users,
    (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs
    FROM schedules s 
    WHERE s.event_date BETWEEN ? AND ? 
    ORDER BY s.event_date ASC, s.event_time ASC
");
$stmtSchedules->execute([$startDate, $endDate]);
$schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);

$totalEscalas = count($schedules);

// 2. Estatísticas Gerais (KPIs)
// Taxa de Confirmação
$stmtTaxa = $pdo->prepare("
    SELECT 
        ROUND((COUNT(CASE WHEN su.status = 'confirmed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) as taxa
    FROM schedule_users su
    JOIN schedules s ON su.schedule_id = s.id
    WHERE s.event_date BETWEEN ? AND ?
");
$stmtTaxa->execute([$startDate, $endDate]);
$taxaConfirmacao = $stmtTaxa->fetchColumn() ?: 0;

// Escalas Pendentes (Futuras e não confirmadas)
$stmtPendentes = $pdo->prepare("
    SELECT COUNT(DISTINCT su.schedule_id)
    FROM schedule_users su
    JOIN schedules s ON su.schedule_id = s.id
    WHERE s.event_date BETWEEN ? AND ? 
    AND su.status != 'confirmed' 
    AND s.event_date >= CURDATE()
");
$stmtPendentes->execute([$startDate, $endDate]);
$escalasPendentes = $stmtPendentes->fetchColumn();

// Tipos de Culto
$stmtTypes = $pdo->prepare("
    SELECT event_type, COUNT(*) as total
    FROM schedules
    WHERE event_date BETWEEN ? AND ?
    GROUP BY event_type
    ORDER BY total DESC
");
$stmtTypes->execute([$startDate, $endDate]);
$escalasPorTipo = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

// 3. Participação (Ranking Membros)
$stmtMembers = $pdo->prepare("
    SELECT u.name, u.avatar_color, u.instrument, COUNT(su.schedule_id) as participacoes
    FROM users u
    JOIN schedule_users su ON u.id = su.user_id
    JOIN schedules s ON su.schedule_id = s.id
    WHERE s.event_date BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY participacoes DESC, u.name ASC
    LIMIT 20
");
$stmtMembers->execute([$startDate, $endDate]);
$rankingParticipacao = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// 4. Repertório (Top Músicas - Adicionado Novamente)
$stmtSongs = $pdo->prepare("
    SELECT so.title, so.artist, COUNT(ss.song_id) as vezes
    FROM songs so
    JOIN schedule_songs ss ON so.id = ss.song_id
    JOIN schedules s ON ss.schedule_id = s.id
    WHERE s.event_date BETWEEN ? AND ?
    GROUP BY so.id
    ORDER BY vezes DESC
    LIMIT 10
");
$stmtSongs->execute([$startDate, $endDate]);
$topSongs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

// 5. Ausências
$stmtAbsences = $pdo->prepare("
    SELECT ua.*, u.name, u.avatar_color
    FROM user_unavailability ua
    JOIN users u ON ua.user_id = u.id
    WHERE (ua.start_date BETWEEN ? AND ?) OR (ua.end_date BETWEEN ? AND ?)
    ORDER BY ua.start_date ASC
");
$stmtAbsences->execute([$startDate, $endDate, $startDate, $endDate]);
$absences = $stmtAbsences->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas de Ausência
$totalRegistrosAusencia = count($absences);
$topAusentes = [];
foreach ($absences as $ab) {
    if (!isset($topAusentes[$ab['user_id']])) {
        $topAusentes[$ab['user_id']] = [
            'name' => $ab['name'],
            'avatar_color' => $ab['avatar_color'],
            'total_days' => 0,
            'count' => 0
        ];
    }
    // Calcular dias
    $s = max($startDate, $ab['start_date']);
    $e = min($endDate, $ab['end_date']);
    $startObj = new DateTime($s);
    $endObj = new DateTime($e);
    
    if ($startObj <= $endObj) {
        $days = $endObj->diff($startObj)->days + 1;
        $topAusentes[$ab['user_id']]['total_days'] += $days;
    }
    $topAusentes[$ab['user_id']]['count']++;
}
usort($topAusentes, function($a, $b) { return $b['total_days'] - $a['total_days']; });
$topAusentes = array_slice($topAusentes, 0, 5);

$monthsShort = [
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
    5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
];

renderAppHeader('Gestão de Escalas');
renderPageHeader('Gestão de Escalas', 'Controle, Análise e Disponibilidade');
?>

<!-- Chart.js para visual wow premium de relatórios e tipos de culto -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .bento-card-stat {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-card-stat {
        background: var(--bg-surface, #1A1B1F);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .bento-card-stat:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        transform: translateY(-2px);
    }
    .stat-badge-glow {
        position: relative;
    }
    .stat-badge-glow::after {
        content: '';
        position: absolute;
        inset: -2px;
        background: inherit;
        border-radius: inherit;
        filter: blur(8px);
        opacity: 0.15;
        z-index: -1;
    }
</style>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-4 mb-24 font-hanken">
    
    <!-- Top Bar com Exportação e Titulo Principal -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-on-background tracking-tight">Painel de Liderança</h2>
            <p class="text-xs md:text-sm text-secondary">Estatísticas, rankings de membros e agenda ministerial.</p>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Botão PDF/Imprimir (NOVO RELATÓRIO) -->
            <a href="escalas_relatorio_print.php?period=<?= $period ?>&year=<?= $year ?>&month=<?= $month ?>&semester=<?= $semester ?? '' ?>" target="_blank" class="w-full md:w-auto text-decoration-none">
                <button class="w-full md:w-auto bg-rose-600 hover:brightness-110 active:scale-[0.98] transition-all text-white px-5 py-2.5 rounded-full font-semibold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Relatório PDF
                </button>
            </a>
        </div>
    </div>

    <!-- Filtros de Período Premium -->
    <div class="bento-card-stat rounded-2xl p-5 mb-8 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
        
        <form method="GET" action="escalas_gestao.php" class="flex flex-wrap gap-3 items-center w-full md:w-auto">
            <div class="flex bg-ghost-gray dark:bg-surface-variant/20 p-1 rounded-xl border border-outline-variant/30">
                <select name="period" onchange="this.form.submit()" class="bg-transparent text-xs font-semibold uppercase tracking-wider text-on-background px-3 py-1.5 border-none rounded-lg focus:ring-0 cursor-pointer">
                    <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Mensal</option>
                    <option value="semester" <?= $period == 'semester' ? 'selected' : '' ?>>Semestral</option>
                    <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Anual</option>
                </select>
            </div>

            <div class="flex bg-ghost-gray dark:bg-surface-variant/20 p-1 rounded-xl border border-outline-variant/30">
                <select name="year" onchange="this.form.submit()" class="bg-transparent text-xs font-semibold uppercase tracking-wider text-on-background px-3 py-1.5 border-none rounded-lg focus:ring-0 cursor-pointer">
                    <?php for($y = date('Y')-1; $y <= date('Y')+1; $y++): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if ($period == 'month'): ?>
                <div class="flex bg-ghost-gray dark:bg-surface-variant/20 p-1 rounded-xl border border-outline-variant/30">
                    <select name="month" onchange="this.form.submit()" class="bg-transparent text-xs font-semibold uppercase tracking-wider text-on-background px-3 py-1.5 border-none rounded-lg focus:ring-0 cursor-pointer">
                        <?php 
                        $months = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
                        foreach($months as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $month == $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($period == 'semester'): ?>
                <div class="flex bg-ghost-gray dark:bg-surface-variant/20 p-1 rounded-xl border border-outline-variant/30">
                    <select name="semester" onchange="this.form.submit()" class="bg-transparent text-xs font-semibold uppercase tracking-wider text-on-background px-3 py-1.5 border-none rounded-lg focus:ring-0 cursor-pointer">
                        <option value="1" <?= $semester == 1 ? 'selected' : '' ?>>1º Semestre</option>
                        <option value="2" <?= $semester == 2 ? 'selected' : '' ?>>2º Semestre</option>
                    </select>
                </div>
            <?php endif; ?>
        </form>

        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-worship-blue animate-pulse"></span>
            <span class="font-bold text-sm tracking-wider uppercase text-worship-blue">
                <?= $titlePeriod ?>
            </span>
        </div>
    </div>

    <!-- Abas Premium com Visual Glass e Micro-animações -->
    <div class="flex bg-ghost-gray dark:bg-surface-variant/10 p-1.5 rounded-full border border-outline-variant/30 w-fit mb-8 overflow-x-auto max-w-full">
        <button onclick="switchView('list')" id="btn-list" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-worship-blue text-white shadow-sm">
            Listagem
        </button>
        <button onclick="switchView('stats')" id="btn-stats" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-transparent text-secondary dark:text-on-surface-variant hover:text-worship-blue">
            Estatísticas
        </button>
        <button onclick="switchView('absence')" id="btn-absence" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 bg-transparent text-secondary dark:text-on-surface-variant hover:text-worship-blue">
            Ausências
        </button>
    </div>

    <!-- 1. ABA DE LISTAGEM -->
    <div id="view-list" class="view-section space-y-4">
        <?php if (empty($schedules)): ?>
            <div class="bg-white dark:bg-deep-navy border border-dashed border-outline-variant/60 rounded-2xl p-16 text-center flex flex-col items-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-ghost-gray dark:bg-surface-variant/30 flex items-center justify-center mb-4 border border-outline-variant/40">
                    <i data-lucide="calendar-x" class="w-8 h-8 text-secondary"></i>
                </div>
                <h3 class="font-headline-md text-lg font-bold text-on-background mb-1">Nenhuma escala cadastrada</h3>
                <p class="font-body-md text-secondary text-sm">Altere o filtro de período ou adicione novas escalas.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($schedules as $s): 
                    $d = new DateTime($s['event_date']);
                    $isPassed = $d < new DateTime('today');
                    $monthShortStr = $monthsShort[(int)$d->format('n')];
                ?>
                    <div class="bento-card-stat rounded-2xl p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 <?= $isPassed ? 'opacity-70' : '' ?>">
                        <div class="flex items-center gap-4">
                            <!-- Calendário Pequeno Minimalista -->
                            <div class="flex flex-col items-center justify-center min-w-[56px] h-[56px] bg-ghost-gray dark:bg-surface-variant/30 border border-outline-variant/20 rounded-xl px-2">
                                <span class="font-bold text-lg text-on-background leading-none"><?= $d->format('d') ?></span>
                                <span class="text-[10px] uppercase font-bold text-secondary tracking-widest mt-0.5"><?= $monthShortStr ?></span>
                            </div>
                            
                            <div>
                                <h3 class="font-bold text-base text-on-background flex flex-wrap items-center gap-2">
                                    <?= htmlspecialchars($s['event_type']) ?>
                                    <?php if($isPassed): ?> 
                                        <span class="text-[10px] bg-outline-variant/30 text-secondary px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Concluído</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-xs text-secondary mt-1">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-secondary"></i> 
                                        <?= substr($s['event_time'], 0, 5) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="users" class="w-3.5 h-3.5 text-secondary"></i> 
                                        <?= $s['total_users'] ?> integrantes
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="music" class="w-3.5 h-3.5 text-secondary"></i> 
                                        <?= $s['total_songs'] ?> músicas
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Ações de Gestão -->
                        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                            <a href="escala_detalhe.php?id=<?= $s['id'] ?>" class="bg-ghost-gray hover:bg-outline-variant/20 border border-outline-variant/40 dark:bg-surface-variant/10 text-on-background px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 transition-all">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Editar
                            </a>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR esta escala ministerial permanentemente?');" class="margin-0">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 p-2 rounded-xl flex items-center justify-center transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. ABA DE ESTATÍSTICAS COMPLETAS -->
    <div id="view-stats" class="view-section space-y-8" style="display: none;">
        
        <!-- KPIs Bento Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Escalas Totais -->
            <div class="bento-card-stat rounded-2xl p-6 border-l-4 border-l-worship-blue flex flex-col justify-between">
                <span class="text-xs font-bold text-secondary tracking-widest uppercase mb-4">Total de Cultos</span>
                <div class="flex items-baseline justify-between mt-auto">
                    <span class="text-4xl font-extrabold text-on-background tracking-tight"><?= $totalEscalas ?></span>
                    <span class="p-2 rounded-xl bg-worship-blue/10 text-worship-blue">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </span>
                </div>
            </div>
            
            <!-- Confirmações -->
            <div class="bento-card-stat rounded-2xl p-6 border-l-4 border-l-emerald-500 flex flex-col justify-between">
                <span class="text-xs font-bold text-secondary tracking-widest uppercase mb-4">Taxa de Confirmação</span>
                <div class="flex items-baseline justify-between mt-auto">
                    <span class="text-4xl font-extrabold text-emerald-500 tracking-tight"><?= $taxaConfirmacao ?>%</span>
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-500">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </span>
                </div>
            </div>
            
            <!-- Pendentes -->
            <div class="bento-card-stat rounded-2xl p-6 border-l-4 border-l-altar-gold flex flex-col justify-between">
                <span class="text-xs font-bold text-secondary tracking-widest uppercase mb-4">Cultos Pendentes</span>
                <div class="flex items-baseline justify-between mt-auto">
                    <span class="text-4xl font-extrabold text-altar-gold tracking-tight"><?= $escalasPendentes ?></span>
                    <span class="p-2 rounded-xl bg-altar-gold/10 text-altar-gold">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Estatísticas Gráficas e Participação -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                <!-- Tipos de Culto Chart -->
                <div class="bento-card-stat rounded-2xl p-6">
                    <h3 class="font-bold text-base text-on-background mb-6 flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-worship-blue"></i>
                        Cultos por Tipo
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div class="relative max-w-[200px] mx-auto md:mx-0">
                            <canvas id="typeChart"></canvas>
                        </div>
                        
                        <div class="space-y-3">
                            <?php if (empty($escalasPorTipo)): ?>
                                <p class="text-xs text-secondary">Nenhum dado cadastrado.</p>
                            <?php else: ?>
                                <?php foreach ($escalasPorTipo as $idx => $tipo): ?>
                                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-outline-variant/10 bg-ghost-gray/30 dark:bg-surface-variant/10 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: <?= ['#2E7EED', '#FFC107', '#10B981', '#EF4444', '#8B5CF6'][$idx % 5] ?>;"></span>
                                            <span class="font-bold text-on-background"><?= htmlspecialchars($tipo['event_type']) ?></span>
                                        </div>
                                        <span class="font-extrabold text-secondary"><?= $tipo['total'] ?> cultos</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Músicas Mais Tocadas -->
                <div class="bento-card-stat rounded-2xl p-6">
                    <h3 class="font-bold text-base text-on-background mb-6 flex items-center gap-2">
                        <i data-lucide="flame" class="w-4 h-4 text-altar-gold"></i>
                        Músicas Mais Tocadas
                    </h3>
                    
                    <?php if (empty($topSongs)): ?>
                        <div class="text-center py-6">
                            <p class="text-xs text-secondary">Nenhuma música tocada no período.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($topSongs as $idx => $musica): ?>
                                <div class="flex items-center justify-between p-3.5 bg-ghost-gray/20 dark:bg-surface-variant/10 rounded-xl border border-outline-variant/20 hover:border-worship-blue transition-all">
                                    <div class="min-w-0 pr-2">
                                        <h4 class="font-bold text-xs text-on-background truncate"><?= htmlspecialchars($musica['title']) ?></h4>
                                        <p class="text-[10px] text-secondary truncate mt-0.5"><?= htmlspecialchars($musica['artist']) ?></p>
                                    </div>
                                    <span class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/10 px-2.5 py-1 rounded-lg font-extrabold text-[10px] whitespace-nowrap">
                                        <?= $musica['vezes'] ?>x
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ranking de Participação de Membros -->
            <div class="bento-card-stat rounded-2xl p-6 flex flex-col">
                <h3 class="font-bold text-base text-on-background mb-6 flex items-center gap-2">
                    <i data-lucide="trophy" class="w-4 h-4 text-altar-gold"></i>
                    Participação por Membro
                </h3>
                
                <?php if (empty($rankingParticipacao)): ?>
                    <p class="text-xs text-secondary text-center py-10">Nenhuma participação registrada.</p>
                <?php else: 
                    $maxParticipacoes = max(array_column($rankingParticipacao, 'participacoes')) ?: 1;
                ?>
                    <div class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
                        <?php foreach ($rankingParticipacao as $idx => $membro): 
                            $isTop = $idx < 3;
                            $medalColor = $idx === 0 ? 'bg-amber-400 text-white' : ($idx === 1 ? 'bg-slate-300 text-slate-800' : ($idx === 2 ? 'bg-amber-600 text-white' : 'bg-outline-variant/30 text-secondary'));
                            $percentage = round(($membro['participacoes'] / $maxParticipacoes) * 100);
                        ?>
                            <div class="flex items-center gap-3">
                                <!-- Rank Medal -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-black text-[10px] shrink-0 <?= $medalColor ?>">
                                    <?= $idx + 1 ?>
                                </div>
                                
                                <!-- User Avatar -->
                                <div class="w-9 h-9 rounded-full font-bold text-xs text-white flex items-center justify-center shrink-0" style="background-color: <?= $membro['avatar_color'] ?: '#3B82F6' ?>;">
                                    <?= strtoupper(substr($membro['name'], 0, 1)) ?>
                                </div>
                                
                                <!-- Nome, Instrumento e Barra de Progresso Visual -->
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-center text-xs mb-1 gap-2">
                                        <span class="font-bold text-on-background truncate"><?= htmlspecialchars($membro['name']) ?></span>
                                        <span class="font-extrabold text-worship-blue shrink-0"><?= $membro['participacoes'] ?></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-outline-variant/20 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-worship-blue/80 transition-all duration-500" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <span class="text-[9px] text-secondary mt-0.5 block truncate"><?= htmlspecialchars($membro['instrument'] ?: 'Vocal') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- 3. ABA DE AUSÊNCIAS E INDISPONIBILIDADES -->
    <div id="view-absence" class="view-section space-y-6" style="display: none;">
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Total de Registros de Ausência -->
            <div class="bento-card-stat rounded-2xl p-6 border-l-4 border-l-rose-500 flex flex-col justify-between">
                <span class="text-xs font-bold text-secondary tracking-widest uppercase mb-4">Ausências Totais</span>
                <div class="flex items-baseline justify-between mt-auto">
                    <span class="text-4xl font-extrabold text-rose-500 tracking-tight"><?= $totalRegistrosAusencia ?></span>
                    <span class="p-2 rounded-xl bg-rose-500/10 text-rose-500">
                        <i data-lucide="user-x" class="w-6 h-6"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Ranking de Indisponibilidade -->
            <div class="bento-card-stat rounded-2xl p-6">
                <h3 class="font-bold text-base text-on-background mb-6 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-rose-500"></i>
                    Integrantes Mais Indisponíveis (Dias)
                </h3>
                
                <?php if (empty($topAusentes)): ?>
                    <p class="text-xs text-secondary text-center py-10">Nenhum registro de indisponibilidade ativa.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($topAusentes as $userId => $data): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full font-bold text-xs text-white flex items-center justify-center shrink-0" style="background-color: <?= $data['avatar_color'] ?: '#6b7280' ?>;">
                                    <?= strtoupper(substr($data['name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-center text-xs font-semibold text-on-background">
                                        <span class="truncate"><?= htmlspecialchars($data['name']) ?></span>
                                        <span class="text-rose-500 font-extrabold whitespace-nowrap"><?= $data['total_days'] ?> dias indisponível</span>
                                    </div>
                                    <p class="text-[10px] text-secondary mt-0.5"><?= $data['count'] ?> registros inseridos</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lista de Ausências Detalhada -->
            <div class="bento-card-stat rounded-2xl p-6">
                <h3 class="font-bold text-base text-on-background mb-6 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-rose-500"></i>
                    Lista Detalhada de Indisponibilidades
                </h3>
                
                <?php if (empty($absences)): ?>
                    <p class="text-xs text-secondary text-center py-10">Nenhuma ausência agendada no período.</p>
                <?php else: ?>
                    <div class="space-y-4 max-h-[380px] overflow-y-auto pr-1">
                        <?php foreach($absences as $ab): 
                            $start = date('d/m', strtotime($ab['start_date']));
                            $end = date('d/m', strtotime($ab['end_date']));
                            $periodStr = ($start == $end) ? $start : "$start a $end";
                        ?>
                            <div class="flex items-start gap-3 pb-3.5 border-b border-outline-variant/10 text-xs last:border-0 last:pb-0">
                                <div class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0 mt-1"></div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-baseline gap-2">
                                        <h4 class="font-bold text-on-background truncate"><?= htmlspecialchars($ab['name']) ?></h4>
                                        <span class="font-extrabold text-rose-500 shrink-0"><?= $periodStr ?></span>
                                    </div>
                                    <p class="text-[10px] text-secondary mt-0.5 italic">Motivo: <?= htmlspecialchars($ab['reason'] ?: 'Não especificado') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</main>

<script>
    function switchView(viewName) {
        // Ocultar todas as seções
        document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
        
        // Resetar botões
        const ids = ['list', 'stats', 'absence'];
        ids.forEach(id => {
            const btn = document.getElementById('btn-' + id);
            if(btn) {
                btn.classList.remove('bg-worship-blue', 'text-white', 'shadow-sm');
                btn.classList.add('bg-transparent', 'text-secondary', 'dark:text-on-surface-variant');
            }
        });

        // Mostrar a seção ativa e estilizar o botão correspondente
        const activeSection = document.getElementById('view-' + viewName);
        if (activeSection) {
            activeSection.style.display = 'block';
        }
        
        const activeBtn = document.getElementById('btn-' + viewName);
        if(activeBtn) {
            activeBtn.classList.add('bg-worship-blue', 'text-white', 'shadow-sm');
            activeBtn.classList.remove('bg-transparent', 'text-secondary', 'dark:text-on-surface-variant');
        }
    }

    // Inicialização do Gráfico Doughnut para "Cultos por Tipo"
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('typeChart');
        if (ctx) {
            const isDark = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
            const dataTypes = <?= json_encode(array_column($escalasPorTipo, 'event_type')) ?>;
            const dataTotals = <?= json_encode(array_column($escalasPorTipo, 'total')) ?>;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dataTypes,
                    datasets: [{
                        data: dataTotals,
                        backgroundColor: [
                            '#2E7EED', // Worship Blue
                            '#FFC107', // Altar Gold
                            '#10B981', // Emerald
                            '#EF4444', // Red-Rose
                            '#8B5CF6'  // Violet (fallback index only)
                        ],
                        borderWidth: isDark ? 2 : 1,
                        borderColor: isDark ? '#1A1B1F' : '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            padding: 10,
                            titleFont: { family: 'Hanken Grotesk', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Hanken Grotesk', size: 12 },
                            backgroundColor: isDark ? '#121316' : '#ffffff',
                            titleColor: isDark ? '#ffffff' : '#121316',
                            bodyColor: isDark ? '#a1a1aa' : '#4b5563',
                            borderColor: 'rgba(46, 126, 237, 0.2)',
                            borderWidth: 1
                        }
                    },
                    cutout: '75%'
                }
            });
        }
    });
</script>

<?php renderAppFooter(); ?>
