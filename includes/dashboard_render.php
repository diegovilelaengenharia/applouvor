<?php
/**
 * Funções helper para renderizar cards dinamicamente no dashboard
 */

// Renderizar card de Escalas (PRO)
function renderCardEscalas($nextSchedule, $totalSchedules) {
    ?>
    <a href="escalas.php" class="access-card card-blue">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
                </div>
                <?php if ($totalSchedules > 0): ?>
                    <span class="card-badge"><?= $totalSchedules ?></span>
                <?php endif; ?>
            </div>
            
            <h3 class="card-title">Escalas</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($nextSchedule): 
                    $date = new DateTime($nextSchedule['event_date']);
                    $roleFn = $nextSchedule['my_role'] ?? 'Membro da Equipe';
                ?>
                    <div style="font-weight: 600; color: #1e40af; font-size: 0.9rem; margin-bottom: 2px;">
                        <?= $date->format('d/m') ?> - <?= htmlspecialchars($nextSchedule['event_type']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #1d4ed8; opacity: 0.9;">
                        <?= htmlspecialchars($roleFn) ?>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.8;">Nenhuma escala próxima</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Repertório (PRO)
function renderCardRepertorio($ultimaMusica, $totalMusicas) {
    ?>
    <a href="repertorio.php" class="access-card card-blue">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                </div>
                <span class="card-badge"><?= $totalMusicas ?></span>
            </div>

            <h3 class="card-title">Repertório</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($ultimaMusica): ?>
                    <div style="font-weight: 600; color: #1e40af; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($ultimaMusica['title']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #1d4ed8; opacity: 0.9; display: flex; align-items: center; gap: 4px;">
                        <span><?= htmlspecialchars($ultimaMusica['artist']) ?></span>
                        <?php if(!empty($ultimaMusica['tone'])): ?>
                            <span style="background: rgba(255,255,255,0.5); padding: 1px 4px; border-radius: 4px; font-weight: 700; font-size: 0.65rem; border: 1px solid rgba(29, 78, 216, 0.2);">
                                <?= htmlspecialchars($ultimaMusica['tone']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.8;">Biblioteca vazia</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Membros (PRO - NEW)
function renderCardMembros($totalMembros, $stats) {
    $percent = $totalMembros > 0 ? round(($stats['vocals'] / $totalMembros) * 100) : 0;
    ?>
    <a href="membros.php" class="access-card card-blue">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
                <span class="card-badge"><?= $totalMembros ?></span>
            </div>

            <h3 class="card-title">Membros</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <div style="display: flex; gap: 8px; font-size: 0.75rem; color: #1e40af; font-weight: 500;">
                    <span title="Vocais">🎤 <?= $stats['vocals'] ?></span>
                    <span title="Instrumentistas">🎸 <?= $stats['instrumentalists'] ?></span>
                </div>
                <div style="width: 100%; height: 4px; background: rgba(255,255,255,0.5); border-radius: 2px; margin-top: 6px; overflow: hidden;">
                    <div style="width: <?= $percent ?>%; height: 100%; background: #2563eb;"></div>
                </div>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Leitura
function renderCardLeitura($pdo, $userId) {
    require_once '../includes/reading_plan.php';
    
    $stmtSet = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'reading_plan_start_date'");
    $stmtSet->execute([$userId]);
    $sDate = $stmtSet->fetchColumn() ?: date('Y-01-01');
    
    $startDateTime = new DateTime($sDate);
    $nowDateTime = new DateTime();
    $startDateTime->setTime(0,0,0); 
    $nowDateTime->setTime(0,0,0);
    
    $daysSinceStart = (int)$startDateTime->diff($nowDateTime)->format('%r%a');
    
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
        $key = "$m-$dayInMonth";
        $versesRead = $userProgress[$key] ?? [];
        $totalVersesForDay = count($READING_PLAN[$m][$dayInMonth] ?? []);
        $readVersesCount = count($versesRead);
        if ($readVersesCount < $totalVersesForDay) {
            $displayDayGlobal = $d;
            break;
        }
    }
    
    $currentMonth = floor(($displayDayGlobal - 1) / 25) + 1;
    $currentDayInMonth = (($displayDayGlobal - 1) % 25) + 1;
    $todayVerses = $READING_PLAN[$currentMonth][$currentDayInMonth] ?? [];
    $todayKey = "$currentMonth-$currentDayInMonth";
    $todayRead = $userProgress[$todayKey] ?? [];
    $todayProgress = count($todayRead);
    $todayTotal = count($todayVerses);
    $percentToday = $todayTotal > 0 ? round(($todayProgress / $todayTotal) * 100) : 0;
    ?>
    <a href="leitura.php" class="access-card card-green">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <span class="card-badge"><?= $percentToday ?>%</span>
            </div>
            <h3 class="card-title">Leitura</h3>
            <p class="card-info" style="margin-top: 8px;">
                <span style="display: block; font-weight: 600; color: #059669;">Dia <?= $displayDayGlobal ?></span>
                <span style="font-size: 0.75rem;">Progresso atual</span>
            </p>
        </div>
    </a>
    <?php
}

// Renderizar card de Avisos (PRO)
function renderCardAvisos($ultimoAviso, $unreadCount) {
    ?>
    <a href="avisos.php" class="access-card card-violet">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="bell" style="width: 24px; height: 24px;"></i>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <span class="card-badge" style="background: #ef4444; color: white; border: none;"><?= $unreadCount ?></span>
                <?php endif; ?>
            </div>
            <h3 class="card-title">Avisos</h3>
            <p class="card-info" style="margin-top: 8px; line-height: 1.2;">
                <?= htmlspecialchars($ultimoAviso) ?>
            </p>
        </div>
    </a>
    <?php
}

// Renderizar card de Aniversariantes (PRO)
function renderCardAniversariantes($niverCount, $proximo = null) {
    ?>
    <a href="aniversarios.php" class="access-card card-violet">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="cake" style="width: 24px; height: 24px;"></i>
                </div>
                <span class="card-badge"><?= $niverCount ?></span>
            </div>
            <h3 class="card-title">Nivers</h3>
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($proximo): ?>
                    <div style="font-weight: 600; color: #a21caf; font-size: 0.9rem;">
                         <?= htmlspecialchars(explode(' ', $proximo['name'])[0]) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #c026d3;">
                        Dia <?= $proximo['dia'] ?>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.8;">Ninguém mais este mês</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Devocional
function renderCardDevocional() {
    ?>
    <a href="devocionais.php" class="access-card card-green">
        <div>
            <div class="card-icon">
                <i data-lucide="sunrise" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Devocional</h3>
            <p class="card-info">Reflexão diária</p>
        </div>
    </a>
    <?php
}

// Renderizar card de Oração
function renderCardOracao($count = 0) {
    ?>
    <a href="oracao.php" class="access-card card-green">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="heart" style="width: 24px; height: 24px;"></i>
                </div>
                <?php if ($count > 0): ?>
                    <span class="card-badge" style="background: #059669; color: white; border: none;"><?= $count ?></span>
                <?php endif; ?>
            </div>
            <h3 class="card-title">Oração</h3>
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($count > 0): ?>
                    <span style="font-weight: 600; color: #047857; font-size: 0.9rem;"><?= $count ?> pedidos</span>
                    <span style="font-size: 0.75rem; color: #059669; opacity: 0.9;"> ativos na lista</span>
                <?php else: ?>
                    <span style="opacity: 0.8;">Nenhum pedido ativo</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card genérico melhorado
function renderCardGeneric($cardId, $cardDef) {
    // Mapeamento de cor definida para classe CSS
    // Se não encontrar, usa a lógica de categoria como fallback
    $colorMap = [
        '#047857' => 'card-emerald',
        '#7c3aed' => 'card-violet',
        '#2563eb' => 'card-blue',
        '#475569' => 'card-slate',
        '#0891b2' => 'card-cyan',
        '#4f46e5' => 'card-indigo',
        '#e11d48' => 'card-rose',
        '#d97706' => 'card-amber',
        '#c026d3' => 'card-fuchsia',
        '#dc2626' => 'card-red',
    ];

    $definedColor = $cardDef['color'] ?? '';
    if (isset($colorMap[$definedColor])) {
        $colorClass = $colorMap[$definedColor];
    } else {
        // Fallback por categoria
        $colorClass = match($cardDef['category']) {
            'Gestão' => 'card-emerald',
            'Espírito' => 'card-indigo',
            'Comunica' => 'card-amber',
            'Admin' => 'card-red',
            'Extras' => 'card-slate',
            default => 'card-emerald'
        };
    }

    // Personalizações por ID (apenas descrições)
    $desc = match($cardId) {
        'agenda' => 'Eventos e compromissos',
        'indisponibilidade' => 'Gerenciar ausências',
        'relatorios' => 'Métricas e histórico',
        'stats_repertorio' => 'Análise de músicas',
        'stats_escalas' => 'Análise de escalas',
        default => '' // Não mostrar nada se não tiver descrição específica
    };
    ?>
    <a href="<?= $cardDef['url'] ?>" class="access-card <?= $colorClass ?>">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon">
                    <i data-lucide="<?= $cardDef['icon'] ?>" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($cardDef['title']) ?></h3>
            
            <?php if ($desc): ?>
                <p class="card-info" style="margin-top: 8px;"><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php
}

// Função principal para renderizar card baseado no ID
// ATUALIZADA ASSINATURA ($data)
function renderDashboardCard($cardId, $data) {
    global $allCardsDefinitions;
    
    switch($cardId) {
        case 'escalas':
            renderCardEscalas($data['nextSchedule'], $data['totalSchedules']);
            break;
        case 'repertorio':
            renderCardRepertorio($data['ultimaMusica'], $data['totalMusicas']);
            break;
        // NOVO CASO PARA MEMBROS (Membros agora é um card especial)
        case 'membros':
            renderCardMembros($data['totalMembros'], $data['statsMembros']);
            break;
        case 'leitura':
            renderCardLeitura($data['pdo'], $data['userId']);
            break;
        case 'avisos':
            renderCardAvisos($data['ultimoAviso'], $data['unreadCount']);
            break;
        case 'aniversariantes':
            renderCardAniversariantes($data['niverCount'], $data['proximoNiver'] ?? null);
            break;
        case 'devocional':
            renderCardDevocional();
            break;
        case 'oracao':
            renderCardOracao($data['oracaoCount'] ?? 0);
            break;
        default:
            // Para cards ainda não implementados, renderizar card genérico
            if (isset($allCardsDefinitions[$cardId])) {
                renderCardGeneric($cardId, $allCardsDefinitions[$cardId]);
            }
            break;
    }
}


