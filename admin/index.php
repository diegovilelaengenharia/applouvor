<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;
// Detectar Perfil para Filtro de Avisos
$userRole = $_SESSION['user_role'] ?? 'user';
$audienceFilter = ($userRole === 'admin') 
    ? "('all', 'admins', 'team', 'leaders')"
    : "('all', 'team')"; 

// --- DADOS REAIS ---
// 1. Avisos
$avisos = [];
$popupAviso = null;
$unreadCount = 0;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM avisos 
        WHERE archived_at IS NULL 
        AND (expires_at IS NULL OR expires_at >= CURDATE())
        AND target_audience IN $audienceFilter
        ORDER BY priority='urgent' DESC, created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAvisos = count($avisos);
    $ultimoAviso = $avisos[0]['title'] ?? 'Nenhum aviso novo';
    
    foreach ($avisos as $av) {
        if ($av['priority'] === 'urgent') {
            $popupAviso = $av;
            break; 
        }
    }
    
    $stmtCountRecent = $pdo->prepare("
        SELECT COUNT(*) FROM avisos 
        WHERE archived_at IS NULL 
        AND (expires_at IS NULL OR expires_at >= CURDATE())
        AND target_audience IN $audienceFilter
        AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    ");
    $stmtCountRecent->execute();
    $unreadCount = $stmtCountRecent->fetchColumn();

} catch (Exception $e) {
    $totalAvisos = 0;
    $ultimoAviso = '';
}

// 2. Escalas
$nextSchedule = null;
$totalSchedules = 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
    ");
    $stmtCount->execute([$userId]);
    $totalSchedules = $stmtCount->fetchColumn();
} catch (Exception $e) {
}

// 3. Repertório
$totalMusicas = 0;
$ultimaMusica = null;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM songs");
    $totalMusicas = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT title, artist FROM songs ORDER BY created_at DESC LIMIT 1");
    $ultimaMusica = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// 4. Aniversariantes
$aniversariantes = [];
try {
    $stmt = $pdo->query("SELECT name, DAY(birth_date) as dia, avatar FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE()) ORDER BY dia ASC");
    $aniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $niverCount = count($aniversariantes);
} catch (Exception $e) {
    $niverCount = 0;
}

// Saudação
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

renderAppHeader('Visão Geral');
?>

<!-- MODAL URGENTE AUTOMÁTICO -->
<?php if ($popupAviso): ?>
<div id="urgentModal" style="
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
    z-index: 2000; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
">
    <div style="
        background: #fff; width: 90%; max-width: 400px; border-radius: 20px; 
        padding: 24px; position: relative; text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-top: 6px solid #ef4444;
    ">
        <div style="background: #fee2e2; color: #dc2626; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <i data-lucide="alert-triangle" width="24"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: var(--font-h1); font-weight: 800; color: #1f2937;">Aviso Urgente</h3>
        <p style="margin: 0 0 16px 0; font-size: var(--font-h3); font-weight: 700; color: #374151;">
            <?= htmlspecialchars($popupAviso['title']) ?>
        </p>
        <div style="text-align: left; background: #f9fafb; padding: 12px; border-radius: 8px; font-size: var(--font-body); color: #4b5563; margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
            <?= $popupAviso['message'] ?>
        </div>
        <button onclick="closeUrgentModal()" style="
            width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px;
            font-weight: 700; font-size: var(--font-h3); cursor: pointer;
        ">
            Entendido
        </button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!sessionStorage.getItem('seen_urgent_<?= $popupAviso['id'] ?>')) {
            document.getElementById('urgentModal').style.display = 'flex';
        }
    });
    function closeUrgentModal() {
        document.getElementById('urgentModal').style.display = 'none';
        sessionStorage.setItem('seen_urgent_<?= $popupAviso['id'] ?>', 'true');
    }
</script>
<?php endif; ?>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        padding: 32px 20px;
        margin: -20px -20px 24px -20px;
        color: white;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 10px 30px rgba(4, 120, 87, 0.2);
    }

    .hero-greeting {
        font-size: var(--font-h1);
        font-weight: 800;
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
    }

    .hero-subtitle {
        font-size: var(--font-body);
        opacity: 0.95;
        font-weight: 500;
    }

    /* Quick Access Grid */
    .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 32px;
    }

    .access-card {
        position: relative;
        padding: 20px;
        border-radius: 20px;
        text-decoration: none;
        color: white;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .access-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .access-card:active {
        transform: scale(0.97);
    }

    .access-card:hover::before {
        left: 100%;
    }

    .card-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .card-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .card-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .card-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

    .card-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        backdrop-filter: blur(10px);
    }

    .card-title {
        font-size: var(--font-h3);
        font-weight: 700;
        margin: 0 0 4px 0;
        letter-spacing: -0.01em;
    }

    .card-info {
        font-size: var(--font-body-sm);
        opacity: 0.9;
        margin: 0;
        line-height: 1.4;
    }

    .card-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        padding: 4px 10px;
        border-radius: 12px;
        font-size: var(--font-caption);
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .section-title {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 16px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-action {
        font-size: var(--font-body-sm);
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .highlight-card {
        background: var(--bg-surface);
        border-radius: 16px;
        padding: 16px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }

    .highlight-card:active {
        transform: scale(0.98);
    }

    .highlight-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    @media (max-width: 375px) {
        .quick-access-grid { gap: 12px; }
        .access-card { min-height: 130px; padding: 16px; }
    }
</style>

<div style="max-width: 600px; margin: 0 auto;">
    
    <!-- HERO SECTION -->
    <div class="hero-section">
        <h1 class="hero-greeting"><?= $saudacao ?>, <?= $nomeUser ?>!</h1>
        <p class="hero-subtitle">Confira suas atividades e acesse rapidamente o que precisa</p>
    </div>

    <!-- QUICK ACCESS GRID -->
    <div class="quick-access-grid">
        
        <!-- Card: Escalas -->
        <a href="escalas.php" class="access-card card-blue">
            <div>
                <div class="card-icon">
                    <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 class="card-title">Escalas</h3>
                <p class="card-info">
                    <?php if ($nextSchedule): 
                        $date = new DateTime($nextSchedule['event_date']);
                        echo $date->format('d/m') . ' - ' . htmlspecialchars($nextSchedule['event_type']);
                    else: ?>
                        Nenhuma escala próxima
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($totalSchedules > 0): ?>
                <span class="card-badge"><?= $totalSchedules ?></span>
            <?php endif; ?>
        </a>

        <!-- Card: Repertório -->
        <a href="repertorio.php" class="access-card card-purple">
            <div>
                <div class="card-icon">
                    <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 class="card-title">Repertório</h3>
                <p class="card-info">
                    <?php if ($ultimaMusica): ?>
                        <?= htmlspecialchars($ultimaMusica['title']) ?>
                    <?php else: ?>
                        Nenhuma música cadastrada
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($totalMusicas > 0): ?>
                <span class="card-badge"><?= $totalMusicas ?></span>
            <?php endif; ?>
        </a>

        <!-- Card: Plano de Leitura -->
        <?php
        require_once '../includes/reading_plan.php';
        
        $stmtSet = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'reading_plan_start_date'");
        $stmtSet->execute([$userId]);
        $sDate = $stmtSet->fetchColumn() ?: date('Y-01-01');
        
        $startDateTime = new DateTime($sDate);
        $nowDateTime = new DateTime();
        $startDateTime->setTime(0,0,0); 
        $nowDateTime->setTime(0,0,0);
        
        $daysSinceStart = (int)$startDateTime->diff($nowDateTime)->format('%r%a');
        $planDayExpected = max(1, $daysSinceStart + 1); 
        
        $stmtProg = $pdo->prepare("SELECT month_num, day_num, verses_read FROM reading_progress WHERE user_id = ?");
        $stmtProg->execute([$userId]);
        $userProgress = [];
        while($row = $stmtProg->fetch(PDO::FETCH_ASSOC)) {
            $userProgress["{$row['month_num']}-{$row['day_num']}"] = json_decode($row['verses_read'], true) ?? [];
        }
        
        $displayDayGlobal = 1;
        for ($d = 1; $d <= 365; $d++) {
            $m = floor(($d - 1) / 25) + 1;
            $dayInMonth = (($d - 1) % 25) + 1;
            $dayVerses = $bibleReadingPlan[$m][$dayInMonth-1] ?? [];
            $totalVersesCount = count($dayVerses);
            $readVersesCount = count($userProgress["$m-$dayInMonth"] ?? []);
            
            if ($readVersesCount < $totalVersesCount) {
                $displayDayGlobal = $d;
                break;
            }
        }
        
        $curM = floor(($displayDayGlobal - 1) / 25) + 1;
        $curD = (($displayDayGlobal - 1) % 25) + 1;
        $targetVerses = $bibleReadingPlan[$curM][$curD-1] ?? [];
        $readVersesList = $userProgress["$curM-$curD"] ?? [];
        $readCount = count($readVersesList);
        $totalCount = count($targetVerses);
        $percentage = ($totalCount > 0) ? round(($readCount / $totalCount) * 100) : 0;
        ?>
        <a href="leitura.php" class="access-card card-green">
            <div>
                <div class="card-icon">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 class="card-title">Leitura</h3>
                <p class="card-info">
                    Dia <?= $curD ?> - <?= $readCount ?>/<?= $totalCount ?> lidos
                </p>
            </div>
            <?php if ($percentage > 0): ?>
                <span class="card-badge"><?= $percentage ?>%</span>
            <?php endif; ?>
        </a>

        <!-- Card: Avisos -->
        <a href="avisos.php" class="access-card card-orange">
            <div>
                <div class="card-icon">
                    <i data-lucide="bell" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 class="card-title">Avisos</h3>
                <p class="card-info">
                    <?php if ($totalAvisos > 0): ?>
                        <?= htmlspecialchars($ultimoAviso) ?>
                    <?php else: ?>
                        Nenhum aviso novo
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($unreadCount > 0): ?>
                <span class="card-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>

    </div>

    <!-- DESTAQUES: Próxima Escala -->
    <?php if ($nextSchedule): 
        $date = new DateTime($nextSchedule['event_date']);
        $monthName = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
    ?>
        <div class="section-title">
            <span>Próxima Escala</span>
            <a href="escalas.php" class="section-action">Ver todas</a>
        </div>
        
        <a href="escala_detalhe.php?id=<?= $nextSchedule['id'] ?>" class="highlight-card">
            <div style="
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                width: 50px; height: 56px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
                border-radius: 12px; color: white; text-align: center; line-height: 1; flex-shrink: 0;
            ">
                <span style="font-size: var(--font-h2); font-weight: 800;"><?= $date->format('d') ?></span>
                <span style="font-size: var(--font-caption); font-weight: 700; text-transform: uppercase; padding-top: 2px;"><?= $monthName ?></span>
            </div>

            <div style="flex: 1;">
                <h4 style="margin: 0; font-size: var(--font-h3); font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                <div style="font-size: var(--font-body-sm); color: var(--text-muted); margin-top: 4px;">
                    <?= $date->format('H:i') ?> • <?= $nextSchedule['location'] ?? 'Local não definido' ?>
                </div>
            </div>

            <div style="color: var(--text-muted);">
                <i data-lucide="chevron-right" style="width: 20px;"></i>
            </div>
        </a>
    <?php endif; ?>

    <!-- DESTAQUES: Aviso Importante -->
    <?php if ($totalAvisos > 0 && (!$popupAviso || $avisos[0]['priority'] !== 'urgent')): 
        $topAviso = $avisos[0];
        $pColors = [
            'urgent' => ['bg' => '#fef2f2', 'icon_bg' => '#ef4444'],
            'important' => ['bg' => '#fffbeb', 'icon_bg' => '#f59e0b'],
            'info' => ['bg' => '#fff7ed', 'icon_bg' => '#fb923c'],
            'event' => ['bg' => '#f0f9ff', 'icon_bg' => '#0ea5e9'],
            'general' => ['bg' => '#f8fafc', 'icon_bg' => '#cbd5e1'],
        ];
        
        $typeKey = $topAviso['priority'] === 'urgent' ? 'urgent' : ($topAviso['priority'] === 'important' ? 'important' : 'info');
        if($topAviso['type'] === 'event') $typeKey = 'event';
        $st = $pColors[$typeKey] ?? $pColors['general'];
    ?>
        <div class="section-title" style="margin-top: 24px;">
            <span>Avisos Recentes</span>
            <a href="avisos.php" class="section-action">Ver todos</a>
        </div>
        
        <a href="avisos.php" class="highlight-card" style="background: <?= $st['bg'] ?>;">
            <div class="highlight-icon" style="background: <?= $st['icon_bg'] ?>; color: white;">
                <i data-lucide="<?= $topAviso['type'] === 'event' ? 'calendar' : 'bell' ?>" style="width: 24px;"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0; font-size: var(--font-h3); font-weight: 700; color: var(--text-main);">
                    <?= htmlspecialchars($topAviso['title']) ?>
                </h4>
                <div style="margin: 4px 0 0 0; font-size: var(--font-body-sm); color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= strip_tags($topAviso['message']) ?>
                </div>
            </div>
        </a>
    <?php endif; ?>

    <!-- DESTAQUES: Aniversariantes -->
    <?php if ($niverCount > 0): ?>
        <div class="section-title" style="margin-top: 24px;">
            <span>Aniversariantes do Mês</span>
            <a href="aniversarios.php" class="section-action">Ver todos</a>
        </div>
        
        <?php 
        $maxNivers = min(3, $niverCount);
        for ($i = 0; $i < $maxNivers; $i++): 
            $niver = $aniversariantes[$i];
        ?>
            <a href="aniversarios.php" class="highlight-card" style="margin-bottom: 8px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); border-radius: 12px; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-weight: 800; font-size: var(--font-h3); line-height: 1;"><?= $niver['dia'] ?></span>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0; font-size: var(--font-body); font-weight: 700; color: var(--text-main);">
                        <?= htmlspecialchars($niver['name']) ?>
                    </h4>
                    <span style="font-size: var(--font-caption); color: #db2777;">Parabéns! 🎉</span>
                </div>
            </a>
        <?php endfor; ?>
    <?php endif; ?>

<?php renderAppFooter(); ?>