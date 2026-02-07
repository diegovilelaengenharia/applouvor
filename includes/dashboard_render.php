<?php
/**
 * Funções helper para renderizar cards dinamicamente no dashboard
 */

// Renderizar card de Escalas (PRO - ENHANCED)
function renderCardEscalas($nextSchedule, $totalSchedules) {
    $countdown = '';
    $dateDisplay = '';
    $roleDisplay = 'Membro da Equipe';
    
    if ($nextSchedule) {
        $date = new DateTime($nextSchedule['event_date']);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days == 0) {
            $countdown = 'HOJE!';
        } elseif ($diff->days == 1) {
            $countdown = 'Amanhã';
        } else {
            $countdown = 'em ' . $diff->days . ' dias';
        }
        
        $dateDisplay = $date->format('d/m') . ' - ' . htmlspecialchars($nextSchedule['event_type']);
        $roleDisplay = htmlspecialchars($nextSchedule['my_role'] ?? 'Membro da Equipe');
    }
    ?>
    <a href="escalas.php" class="access-card card-blue" aria-label="Ver detalhes de Escalas">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="calendar"></i>
            </div>
            <?php if ($totalSchedules > 0): ?>
                <span class="card-badge badge-info" aria-label="<?= $totalSchedules ?> escalas">
                    <i data-lucide="calendar" style="width:14px;height:14px;"></i>
                    <?= $totalSchedules ?>
                </span>
            <?php endif; ?>
            
            <h3 class="card-title">Escalas</h3>
            
            <div class="card-info">
                <?php if ($nextSchedule): ?>
                    <div class="info-highlight">
                        <div class="info-primary">
                            <?= $dateDisplay ?>
                        </div>
                        <div class="info-secondary">
                            <i data-lucide="user" class="icon-tiny"></i>
                            <?= $roleDisplay ?>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="text-muted">Nenhuma escala próxima</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                <i data-lucide="clock" class="icon-tiny"></i>
                <?= $countdown ?>
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Repertório (PRO - ENHANCED)
function renderCardRepertorio($ultimaMusica, $totalMusicas) {
    ?>
    <a href="repertorio.php" class="access-card card-blue" aria-label="Ver detalhes do Repertório">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="music"></i>
            </div>
            <span class="card-badge badge-info" aria-label="<?= $totalMusicas ?> músicas">
                <i data-lucide="music" style="width:14px;height:14px;"></i>
                <?= $totalMusicas ?>
            </span>

            <h3 class="card-title">Repertório</h3>
            
            <div class="card-info">
                <?php if ($ultimaMusica): ?>
                    <div class="info-highlight">
                        <div class="info-primary text-truncate">
                            <?= htmlspecialchars($ultimaMusica['title']) ?>
                        </div>
                        <div class="info-secondary flex-between">
                            <span class="text-truncate"><?= htmlspecialchars($ultimaMusica['artist']) ?></span>
                            <?php if(!empty($ultimaMusica['tone'])): ?>
                                <span class="tone-tag">
                                    <?= htmlspecialchars($ultimaMusica['tone']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="text-muted">Biblioteca vazia</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                <i data-lucide="library" class="icon-tiny"></i>
                <?= $totalMusicas ?> músicas
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Membros (PRO - ENHANCED)
function renderCardMembros($totalMembros, $stats) {
    ?>
    <a href="membros.php" class="access-card card-blue" aria-label="Ver detalhes de Membros">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="users"></i>
            </div>
            <span class="card-badge badge-info" aria-label="<?= $totalMembros ?> membros">
                <i data-lucide="users" style="width:14px;height:14px;"></i>
                <?= $totalMembros ?>
            </span>

            <h3 class="card-title">Membros</h3>
            
            <div class="card-info">
                <div class="info-highlight">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['vocals'] ?></div>
                            <div class="stat-label">Vocais</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $stats['instrumentalists'] ?></div>
                            <div class="stat-label">Instrumentos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">Ver equipe completa</span>
            <span class="link-text float-right">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Leitura (ENHANCED)
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
    $percentYear = round(($displayDayGlobal / 365) * 100);
    ?>
    <a href="leitura.php" class="access-card card-green" aria-label="Ver detalhes da Leitura Bíblica">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="book-open"></i>
            </div>
            <span class="card-badge badge-success" aria-label="<?= $percentToday ?>% completo hoje">
                <?= $percentToday ?>%
            </span>
            <h3 class="card-title">Leitura Bíblica</h3>
            <div class="card-info">
                <div class="info-highlight">
                    <div class="flex-between mb-1">
                        <div>
                            <div class="highlight-title">Dia <?= $displayDayGlobal ?></div>
                            <div class="stat-label">de 365 dias</div>
                        </div>
                        <div class="text-right">
                            <div class="highlight-title"><?= $percentYear ?>%</div>
                            <div class="stat-label">completo</div>
                        </div>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?= $percentYear ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                <i data-lucide="check-circle" class="icon-tiny"></i>
                Hoje: <?= $todayProgress ?>/<?= $todayTotal ?>
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Avisos (PRO - ENHANCED)
function renderCardAvisos($ultimoAviso, $unreadCount) {
    // Definir classe extra para avisos não lidos
    $extraClass = ($unreadCount > 0) ? 'card-urgent-glow' : '';
    ?>
    <a href="avisos.php" class="access-card card-amber <?= $extraClass ?>" aria-label="Ver todos os Avisos">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="bell"></i>
            </div>
            <?php if ($unreadCount > 0): ?>
                <span class="card-badge badge-warning badge-pulse" aria-label="<?= $unreadCount ?> avisos não lidos">
                    <i data-lucide="bell" style="width:14px;height:14px;"></i>
                    <?= $unreadCount ?>
                </span>
            <?php endif; ?>
            <h3 class="card-title">Avisos</h3>
            <div class="card-info">
                <div class="info-highlight">
                    <p class="text-clamp-2">
                        <?= htmlspecialchars($ultimoAviso) ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="card-footer-row">
            <?php if ($unreadCount > 0): ?>
                <span class="footer-text text-urgent">
                    <i data-lucide="alert-circle" class="icon-tiny"></i>
                    <?= $unreadCount ?> novo(s)
                </span>
            <?php else: ?>
                <span class="footer-text">Tudo em dia</span>
            <?php endif; ?>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Aniversariantes (PRO - ENHANCED)
function renderCardAniversariantes($niverCount, $proximo = null) {
    ?>
    <a href="aniversarios.php" class="access-card card-amber" aria-label="Ver lista de Aniversariantes">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="cake"></i>
            </div>
            <span class="card-badge badge-warning" aria-label="<?= $niverCount ?> aniversariantes este mês">
                <i data-lucide="cake" style="width:14px;height:14px;"></i>
                <?= $niverCount ?>
            </span>
            <h3 class="card-title">Aniversariantes</h3>
            <div class="card-info">
                <?php if ($proximo): ?>
                    <div class="info-highlight">
                        <div class="highlight-title">
                            🎉 <?= htmlspecialchars(explode(' ', $proximo['name'])[0]) ?>
                        </div>
                        <div class="highlight-subtitle">
                            Próximo: Dia <?= $proximo['dia'] ?>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="text-muted">Ninguém mais este mês</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                <?= $niverCount ?> neste mês
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Devocional (ENHANCED)
function renderCardDevocional() {
    $hoje = date('d/m');
    ?>
    <a href="devocionais.php" class="access-card card-green" aria-label="Ver Devocional diário">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="sunrise"></i>
            </div>
            <h3 class="card-title">Devocional</h3>
            <div class="card-info">
                <div class="info-highlight">
                    <div class="highlight-title">
                        ☀️ Reflexão Diária
                    </div>
                    <div class="highlight-subtitle">
                        Hoje, <?= $hoje ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">Separar um tempo</span>
            <span class="link-text float-right">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Oração (ENHANCED)
function renderCardOracao($count = 0) {
    ?>
    <a href="oracao.php" class="access-card card-green" aria-label="Ver pedidos de Oração">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="heart"></i>
            </div>
            <?php if ($count > 0): ?>
                <span class="card-badge badge-success" aria-label="<?= $count ?> pedidos de oração">
                    <i data-lucide="heart" style="width:14px;height:14px;"></i>
                    <?= $count ?>
                </span>
            <?php endif; ?>
            <h3 class="card-title">Oração</h3>
            <div class="card-info">
                <?php if ($count > 0): ?>
                    <div class="info-highlight">
                        <div class="highlight-title big">
                            <?= $count ?> <?= $count == 1 ? 'pedido' : 'pedidos' ?>
                        </div>
                        <div class="highlight-subtitle">
                            🙏 Ativos na lista
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-highlight">
                        <span class="highlight-subtitle">Nenhum pedido ativo</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">Intercessão</span>
            <?php if ($count > 0): ?>
                <span class="link-text float-right">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
            <?php else: ?>
                <span class="link-text float-right">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
            <?php endif; ?>
        </div>
    </a>
    <?php
}

// Renderizar card de Agenda (ENHANCED)
function renderCardAgenda($nextEvent, $totalEvents) {
    $countdown = '';
    $dateDisplay = '';
    $eventName = 'Nenhum evento próximo';
    
    if ($nextEvent) {
        $date = new DateTime($nextEvent['start_datetime']);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days == 0) {
            $countdown = 'HOJE!';
        } elseif ($diff->days == 1) {
            $countdown = 'Amanhã';
        } else {
            $countdown = 'em ' . $diff->days . ' dias';
        }
        
        $dateDisplay = $date->format('d/m \à\s H:i');
        $eventName = htmlspecialchars($nextEvent['title'] ?? 'Evento');
    }
    ?>
    <a href="agenda.php" class="access-card card-blue" aria-label="Ver Agenda de eventos">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="calendar-days"></i>
            </div>
            <?php if ($totalEvents > 0): ?>
                <span class="card-badge badge-info" aria-label="<?= $totalEvents ?> eventos agendados">
                    <i data-lucide="calendar-days" style="width:14px;height:14px;"></i>
                    <?= $totalEvents ?>
                </span>
            <?php endif; ?>
            
            <h3 class="card-title">Agenda</h3>
            
            <div class="card-info">
                <?php if ($nextEvent): ?>
                    <div class="info-highlight">
                        <div class="info-primary text-truncate">
                            <?= $eventName ?>
                        </div>
                        <div class="info-secondary">
                            <i data-lucide="calendar" class="icon-tiny"></i>
                            <?= $dateDisplay ?>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="text-muted">Nenhum evento agendado</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                <i data-lucide="clock" class="icon-tiny"></i>
                <?= $countdown ?>
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
        </div>
    </a>
    <?php
}

// Renderizar card de Histórico (NEW - Styled as Blue/Management)
function renderCardHistorico($data) {
    $lastCulto = $data['last_culto'];
    $sugestoesCount = $data['sugestoes_count'];
    $dateDisplay = $lastCulto ? date('d/m', strtotime($lastCulto['event_date'])) : '--/--';
    $typeDisplay = $lastCulto ? htmlspecialchars($lastCulto['event_type']) : 'Nenhum registro';
    ?>
    <a href="historico.php" class="access-card card-blue" aria-label="Ver Histórico de cultos">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="history"></i>
            </div>
            <?php if ($sugestoesCount > 0): ?>
                <span class="card-badge text-urgent" title="Sugestões de músicas">
                    <i data-lucide="lightbulb" class="icon-tiny"></i>
                    <?= $sugestoesCount ?>
                </span>
            <?php endif; ?>
            
            <h3 class="card-title">Histórico</h3>
            
            <div class="card-info">
                <div class="info-highlight">
                    <div class="info-primary">
                        Último: <?= $dateDisplay ?>
                    </div>
                    <div class="info-secondary text-truncate">
                        <?= $typeDisplay ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">
                Análise Completa
            </span>
            <span class="link-text">Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
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
    <a href="<?= $cardDef['url'] ?>" class="access-card <?= $colorClass ?>" style="position: relative; overflow: hidden;">
        <div class="card-content">
            <div class="card-icon">
                <i data-lucide="<?= $cardDef['icon'] ?>"></i>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($cardDef['title']) ?></h3>
            
            <?php if ($desc): ?>
                <p class="card-info" style="margin-top: 8px;"><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
        </div>
        <div class="card-footer-row">
            <span class="footer-text">Acessar</span>
            <span class="link-text">Ver Detalhes <i data-lucide="arrow-right" style="width:14px;"></i></span>
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
        case 'agenda':
            renderCardAgenda($data['nextEvent'] ?? null, $data['totalEvents'] ?? 0);
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
        case 'historico':
            renderCardHistorico($data['historicoData'] ?? ['last_culto' => null, 'sugestoes_count' => 0]);
            break;
        default:
            // Para cards ainda não implementados, renderizar card genérico
            if (isset($allCardsDefinitions[$cardId])) {
                renderCardGeneric($cardId, $allCardsDefinitions[$cardId]);
            }
            break;
    }
}
