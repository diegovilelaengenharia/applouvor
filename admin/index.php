<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;
// Detectar Perfil para Filtro de Avisos
$userRole = $_SESSION['user_role'] ?? 'user'; // 'admin' ou 'user'
// Se tiver lógica de grupos (louvor, vocal, etc), pode refinar. Por enquanto:
// Admins veem 'admins' e 'all'. User vê 'team' e 'all'.
$audienceFilter = ($userRole === 'admin') 
    ? "('all', 'admins', 'team', 'leaders')" // Admin vê tudo por enquanto para moderar, ou ajustamos? Melhor ver tudo.
    : "('all', 'team')"; 

// --- DADOS REAIS ---
// 1. Avisos (Lógica Avançada)
$avisos = [];
$popupAviso = null;
$unreadCount = 0;

try {
    // Buscar avisos VÁLIDOS (não expirados ou expirados hoje) e do PÚBLICO CERTO
    // Ordenar por Urgência depois Data
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
    
    // Checar Urgente para Popup (Apenas o mais recente urgente)
    foreach ($avisos as $av) {
        if ($av['priority'] === 'urgent') {
            $popupAviso = $av;
            break; 
        }
    }
    
    // Contagem de Não Lidos (Simulado via Cookie 'last_viewed_notice' no JS ou apenas total recente)
    // Para simplificar: Se tiver aviso criado nos últimos 3 dias, mostra dot.
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

// 2. Minha Próxima Escala
$nextSchedule = null;
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
} catch (Exception $e) {
}

// 3. Aniversariantes (Quantidade no mês)
$niverCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())");
    $niverCount = $stmt->fetchColumn();
} catch (Exception $e) {
}

// Saudação baseada no horário
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

renderAppHeader('Início');
renderPageHeader('Visão Geral', 'Confira o que temos para hoje');
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
        <h3 style="margin: 0 0 8px 0; font-size: 1.25rem; font-weight: 800; color: #1f2937;">Aviso Urgente</h3>
        <p style="margin: 0 0 16px 0; font-size: 1rem; font-weight: 700; color: #374151;">
            <?= htmlspecialchars($popupAviso['title']) ?>
        </p>
        <div style="text-align: left; background: #f9fafb; padding: 12px; border-radius: 8px; font-size: 0.9rem; color: #4b5563; margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
            <?= $popupAviso['message'] ?>
        </div>
        <button onclick="closeUrgentModal()" style="
            width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px;
            font-weight: 700; font-size: 1rem; cursor: pointer;
        ">
            Entendido
        </button>
    </div>
</div>
<script>
    // Mostrar modal se não tiver visto (usando sessionStorage para só mostrar 1x por sessão ou localStorage para 1x ever)
    // Para URGENTE, geralmente mostra sempre até ser expirado ou marcado como lido. Vamos usar Session por enquanto.
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

<!-- Estilos da Nova Home (Vertical Feed) -->
<style>
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 20px 0 10px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-action {
        font-size: 0.8rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .feed-card {
        background: var(--bg-surface);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .feed-card:active {
        transform: scale(0.98);
    }

    .feed-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
        font-weight: 700;
    }

    /* Empty State */
    .empty-state {
        background: #f8fafc;
        border-radius: 10px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-muted);
        border: 1px dashed var(--border-color);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: var(--bg-surface);
        border-radius: 12px;
        padding: 12px;
        border: 1px solid var(--border-color);
        text-align: center;
        transition: transform 0.2s;
    }

    .stat-card:active {
        transform: scale(0.98);
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 2px;
        color: var(--text-main);
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
    }
</style>

<div style="max-width: 600px; margin: 0 auto;">

    <!-- AVISOS -->
    <div class="section-title">
        <span>Avisos <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $totalAvisos ?>)</span></span>
        <?php if ($totalAvisos > 0): ?>
            <a href="avisos.php" class="section-action">Ver todos</a>
        <?php endif; ?>
    </div>

    <?php if ($totalAvisos > 0): 
        $topAviso = $avisos[0]; // O mais relevante da query filtrada
        
        $pColors = [
            'urgent' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'icon_bg' => '#ef4444', 'icon_color' => 'white', 'text_title' => '#991b1b', 'text_body' => '#7f1d1d'],
            'important' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'icon_bg' => '#f59e0b', 'icon_color' => 'white', 'text_title' => '#92400e', 'text_body' => '#78350f'],
            'info' => ['bg' => '#fff7ed', 'border' => '#ffedd5', 'icon_bg' => '#ffedd5', 'icon_color' => '#ea580c', 'text_title' => '#9a3412', 'text_body' => '#c2410c'],
            'event' => ['bg' => '#f0f9ff', 'border' => '#bae6fd', 'icon_bg' => '#0ea5e9', 'icon_color' => 'white', 'text_title' => '#075985', 'text_body' => '#0369a1'],
            'general' => ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'icon_bg' => '#cbd5e1', 'icon_color' => '#475569', 'text_title' => '#334155', 'text_body' => '#475569'],
        ];
        
        // Mapear tipos antigos ou novos
        $typeKey = $topAviso['priority'] === 'urgent' ? 'urgent' : ($topAviso['priority'] === 'important' ? 'important' : 'info');
        if($topAviso['type'] === 'event') $typeKey = 'event';

        $st = $pColors[$typeKey] ?? $pColors['general'];
    ?>
        <a href="avisos.php" class="feed-card" style="background: <?= $st['bg'] ?>; border-color: <?= $st['border'] ?>;">
            <div class="feed-icon" style="background: <?= $st['icon_bg'] ?>; color: <?= $st['icon_color'] ?>;">
                <i data-lucide="<?= $topAviso['type'] === 'event' ? 'calendar' : ($topAviso['priority'] === 'urgent' ? 'alert-triangle' : 'bell') ?>"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: <?= $st['text_title'] ?>; display: flex; align-items: center; gap: 6px;">
                    <?= htmlspecialchars($topAviso['title']) ?>
                    <?php if($topAviso['priority'] === 'urgent'): ?>
                        <span style="font-size: 0.65rem; background: #dc2626; color: white; padding: 2px 6px; border-radius: 4px;">URGENTE</span>
                    <?php endif; ?>
                </h4>
                <div style="margin: 4px 0 0 0; font-size: 0.9rem; color: <?= $st['text_body'] ?>; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= strip_tags($topAviso['message']) ?>
                </div>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="bell-off" style="width: 20px;"></i>
            <span style="font-size: 0.9rem">Lista vazia.</span>
        </div>
    <?php endif; ?>

    <!-- WIDGET: LEITURA BÍBLICA (Detalhado) -->
    <?php
    require_once '../includes/reading_plan.php';

    // 1. Calcular Dia Esperado pelo Plano
    $stmtSet = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'reading_plan_start_date'");
    $stmtSet->execute([$userId]);
    $sDate = $stmtSet->fetchColumn() ?: date('Y-01-01');
    
    $startDateTime = new DateTime($sDate);
    $nowDateTime = new DateTime();
    $startDateTime->setTime(0,0,0); 
    $nowDateTime->setTime(0,0,0);
    
    // Dias passados desde o início (Index Base 1)
    $daysSinceStart = (int)$startDateTime->diff($nowDateTime)->format('%r%a');
    $planDayExpected = max(1, $daysSinceStart + 1); 

    // 2. Carregar Progresso do Usuário
    $stmtProg = $pdo->prepare("SELECT month_num, day_num, verses_read FROM reading_progress WHERE user_id = ?");
    $stmtProg->execute([$userId]);
    $userProgress = [];
    while($row = $stmtProg->fetch(PDO::FETCH_ASSOC)) {
        $userProgress["{$row['month_num']}-{$row['day_num']}"] = json_decode($row['verses_read'], true) ?? [];
    }

    // 3. Encontrar o "Próximo Dia a Ler" (Primeiro incompleto)
    $displayDayGlobal = 1;
    $foundIncomplete = false;
    $maxDays = 300; // Lookahead limit (approx. 1 year)

    for ($d = 1; $d <= 365; $d++) {
        $m = floor(($d - 1) / 25) + 1;
        $dayInMonth = (($d - 1) % 25) + 1;
        
        // Versículos totais do dia $d
        $dayVerses = $bibleReadingPlan[$m][$d-1] ?? []; // Array index is 0-based relative to month start in our PHP structure?
        // Wait, reading_plan.php structure: 1 => [ 0 => [...], 1 => [...] ]
        // So month 1 day 1 is at $bibleReadingPlan[1][0]. Correct.
        // Wait, $d=1 -> m=1, dayInMonth=1 -> index=0. Correct.
        // What about month 2? $d=26 -> m=2, dayInMonth=1.
        // Index needs to be dayInMonth - 1. 
        
        $dayVerses = $bibleReadingPlan[$m][$dayInMonth-1] ?? [];
        $totalVersesCount = count($dayVerses);
        
        $readVersesCount = count($userProgress["$m-$dayInMonth"] ?? []);
        
        if ($readVersesCount < $totalVersesCount) {
            $displayDayGlobal = $d;
            $foundIncomplete = true;
            break;
        }
    }
    
    // Se completou tudo até 365, mostra o último ou parabéns.
    if (!$foundIncomplete && $d > 365) {
        $displayDayGlobal = 365; // Ou lidar com "Concluído"
        // Show Day 365 completed
    }

    // Preparar dados para exibição
    $curM = floor(($displayDayGlobal - 1) / 25) + 1;
    $curD = (($displayDayGlobal - 1) % 25) + 1;
    $targetVerses = $bibleReadingPlan[$curM][$curD-1] ?? [];
    
    $readVersesList = $userProgress["$curM-$curD"] ?? [];
    $readCount = count($readVersesList);
    $totalCount = count($targetVerses);
    
    $percentage = ($totalCount > 0) ? ($readCount / $totalCount) * 100 : 0;

    // 4. Determinar Status e Cores (Conforme Pedido)
    // Cores pedidas: 
    // - Amarelo (Incompleto/Parcial)
    // - Verde (Concluído) -> Só apareceria se olharmos um dia passado, mas aqui focamos no "atual".
    //   Se $readCount == $totalCount, é verde. (Caso raro aqui se o loop pegar o incompleto, a menos que todos estejam completos).
    // - Branco/Cinza (Não iniciou)
    
    $statusColor = '#f1f5f9'; // Branco/Cinza default
    $statusText = 'Não iniciado';
    $icon = 'circle';
    $iconColor = '#94a3b8';
    $borderColor = '#e2e8f0'; 
    $bgColor = '#ffffff';

    if ($readCount == 0) {
        // NÃO INICIOU
        $statusText = 'A iniciar';
        $icon = 'circle'; 
        // Se estiver atrasado (Global Day < Expected), talvez dar um tom mais urgente no texto, mas manter icone branco conforme pedido.
        // O user pediu: "Quando estiver branco, nao inicinou."
    } elseif ($readCount < $totalCount) {
        // PARCIAL (AMARELO)
        $statusText = 'Em andamento';
        $statusColor = '#fef3c7'; // Output bg? No, icon color mainly.
        $icon = 'pie-chart'; 
        $iconColor = '#d97706'; // Amarelo escuro
        $borderColor = '#fbbf24'; 
        $bgColor = '#fffbeb';
    } else {
        // CONCLUÍDO (VERDE)
        $statusText = 'Concluído';
        $icon = 'check-circle-2';
        $iconColor = '#16a34a';
        $borderColor = '#86efac';
        $bgColor = '#f0fdf4';
    }

    // Cálculo de Atraso para Texto de Incentivo
    $delay = $planDayExpected - $displayDayGlobal;
    // Se delay > 0: Atrasado.
    // Se delay == 0: Em dia (fazendo o de hoje).
    // Se delay < 0: Adiantado.

    $incentivo = "";
    if ($delay > 3) {
        $incentivo = "<span style='color: #ef4444; font-weight: 600;'>Você está $delay dias atrasado.</span> Não desista, leia um pouco agora!";
    } elseif ($delay > 0) {
        $incentivo = "<span style='color: #f59e0b; font-weight: 600;'>Faltam $delay dias para alcançar.</span> Vamos ler?";
    } elseif ($delay == 0) {
        $incentivo = "<span style='color: #059669; font-weight: 600;'>Leitura de Hoje.</span> Mantenha a constância!";
    } else {
        $incentivo = "<span style='color: #059669; font-weight: 600;'>Você está adiantado!</span> Continue assim.";
    }
    ?>

    <div class="section-title">
        <span>Leitura Bíblica</span>
        <a href="leitura.php" class="section-action">Abrir Plano</a>
    </div>
    
    <!-- Card Principal -->
    <a href="leitura.php" class="feed-card" style="display: block; background: <?= $readCount > 0 ? $bgColor : '#ffffff' ?>; border-color: <?= $readCount > 0 ? $borderColor : '#e2e8f0' ?>;">
        
        <!-- Header do Card: Dia e Ícone de Status -->
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <div>
                <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main);">Dia <?= $curD ?></h4>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                    <?= $incentivo ?>
                </div>
            </div>
            
            <!-- Ícone de Status (Seguindo regra: Amarelo=Parcial, Verde=Full, Branco=0) -->
            <div style="
                width: 32px; height: 32px; border-radius: 50%; 
                display: flex; align-items: center; justify-content: center;
                background: <?= $readCount > 0 ? ($readCount == $totalCount ? '#dcfce7' : '#fef3c7') : '#f1f5f9' ?>;
                color: <?= $readCount > 0 ? ($readCount == $totalCount ? '#16a34a' : '#d97706') : '#94a3b8' ?>;
            ">
                <i data-lucide="<?= $icon ?>" width="18"></i>
            </div>
        </div>

        <!-- Lista de Versículos -->
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <?php foreach ($targetVerses as $idx => $verse): 
                $isRead = in_array($idx, $readVersesList);
                $vColor = $isRead ? '#16a34a' : 'var(--text-main)';
                $vIcon = $isRead ? 'check-circle' : 'circle';
                $vOpac = $isRead ? '1' : '1';
                $textDecor = $isRead ? 'none' : 'none'; // Talvez riscar? Não, fica feio.
            ?>
                <div style="
                    display: flex; align-items: center; gap: 10px; 
                    padding: 8px; border-radius: 8px; 
                    background: <?= $isRead ? '#f0fdf4' : '#f8fafc' ?>;
                    border: 1px solid <?= $isRead ? '#bbf7d0' : '#e2e8f0' ?>;
                ">
                    <i data-lucide="<?= $vIcon ?>" width="16" color="<?= $isRead ? '#16a34a' : '#cbd5e1' ?>"></i>
                    <span style="font-size: 0.9rem; font-weight: 600; color: <?= $vColor ?>; text-decoration: <?= $textDecor ?>;">
                        <?= $verse ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Botão de Ação -->
        <div style="margin-top: 12px; text-align: center;">
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                <?= $readCount ?> de <?= $totalCount ?> lidos
            </span>
        </div>
    </a>






    <!-- MINHAS ESCALAS -->
    <div class="section-title">
        <span>Minhas escalas <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $nextSchedule ? '1' : '0' ?>)</span></span>
        <a href="escalas.php?mine=1" class="section-action">Ver todas</a>
    </div>

    <?php if ($nextSchedule):
        $date = new DateTime($nextSchedule['event_date']);
        $monthName = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
    ?>
        <a href="escala_detalhe.php?id=<?= $nextSchedule['id'] ?>" class="feed-card">
            <!-- Date Box -->
            <div style="
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                width: 50px; height: 56px; background: #f1f5f9; border-radius: 10px;
                color: var(--text-main); text-align: center; line-height: 1; flex-shrink: 0;
            ">
                <span style="font-size: 1.1rem; font-weight: 800;"><?= $date->format('d') ?></span>
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); padding-top: 2px;"><?= $monthName ?></span>
            </div>

            <div style="flex: 1;">
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; color: var(--text-muted); font-size: 0.85rem;">
                    <!-- Mini Avatars (Simulated) -->
                    <div style="display: flex; padding-left: 8px;">
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #cbd5e1; border: 2px solid white; margin-left: -8px;"></span>
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #94a3b8; border: 2px solid white; margin-left: -8px;"></span>
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #64748b; border: 2px solid white; margin-left: -8px;"></span>
                    </div>
                    <span>• <?= $saudacao == 'Bom dia' ? 'Manhã' : 'Noite' ?></span>
                </div>
            </div>

            <div style="color: var(--text-muted);">
                <i data-lucide="chevron-right" style="width: 20px;"></i>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="calendar-off" style="width: 20px;"></i>
            <span style="font-size: 0.9rem;">Lista vazia.</span>
        </div>
    <?php endif; ?>


    <!-- ANIVERSARIANTES -->
    <div class="section-title">
        <span>Aniversariantes <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $niverCount ?>)</span></span>
        <a href="aniversarios.php" class="section-action">Ver todos</a>
    </div>

    <?php if ($niverCount > 0): ?>
        <a href="aniversarios.php" class="feed-card" style="background: #fdf2f8; border-color: #fbcfe8;">
            <div class="feed-icon" style="background: #fbcfe8; color: #db2777;">
                <i data-lucide="cake"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #be185d;"><?= $niverCount ?> aniversariantes</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: #db2777;">
                    Celebre a vida dos irmãos este mês!
                </p>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="party-popper" style="width: 20px;"></i>
            <span style="font-size: 0.9rem;">Lista vazia.</span>
        </div>
    <?php endif; ?>



    <?php renderAppFooter(); ?>