<?php
/**
 * Funções helper para renderizar cards dinamicamente no dashboard
 */

// ============================================================================
// UNIFIED CARD RENDERING SYSTEM
// ============================================================================

/**
 * Renderiza um card unificado do dashboard
 * Todos os cards compartilham a mesma estrutura HTML, variando apenas:
 * - Cores (por categoria)
 * - Conteúdo dinâmico
 * 
 * @param array $config Configuração do card com as seguintes chaves:
 *   - id: string - ID único do card
 *   - title: string - Título do card
 *   - icon: string - Nome do ícone Lucide
 *   - category: string - Categoria (gestao|espiritual|comunicacao)
 *   - url: string - URL de destino
 *   - badge: array|null - Configuração do badge ['count' => int, 'icon' => string, 'label' => string]
 *   - content: string - HTML do conteúdo dinâmico
 *   - footer: array - Configuração do footer ['text' => string, 'icon' => string|null]
 */
function renderUnifiedCard($config) {
    // Mapeamento de categoria para cor do card
    $categoryColorMap = [
        'gestao' => 'blue',
        'espiritualidade' => 'green',
        'comunicacao' => 'amber'
    ];
    
    // Mapeamento de categoria para tipo de badge
    $categoryBadgeMap = [
        'gestao' => 'badge-info',
        'espiritualidade' => 'badge-success',
        'comunicacao' => 'badge-warning'
    ];
    
    // Determinar cor do card
    $cardColor = $categoryColorMap[$config['category']] ?? 'blue';
    $extraClasses = $config['extra_classes'] ?? '';
    // Adiciona classe 'access-card' padrão e 'card-clickable' para comportamento de link
    $cardClass = "access-card card-clickable card-{$cardColor} {$extraClasses}";
    
    // Determinar tipo de badge (se existir)
    $badgeType = $config['badge']['type'] ?? $categoryBadgeMap[$config['category']] ?? 'badge-info';
    
    ?>
    <a href="<?= $config['url'] ?>" class="<?= $cardClass ?>" aria-label="Ver detalhes de <?= $config['title'] ?>" style="text-decoration: none; color: inherit;">
        <div class="card-body">
            <div class="flex-between mb-2">
                <div class="card-icon">
                    <i data-lucide="<?= $config['icon'] ?>"></i>
                </div>
                
                <?php if (isset($config['badge']) && $config['badge']['count'] > 0): ?>
                    <span class="card-badge <?= $badgeType ?><?= isset($config['badge']['pulse']) && $config['badge']['pulse'] ? ' badge-pulse' : '' ?>" 
                          aria-label="<?= $config['badge']['label'] ?>">
                        <?php if (isset($config['badge']['icon'])): ?>
                            <i data-lucide="<?= $config['badge']['icon'] ?>" style="width:14px;height:14px; margin-right:4px;"></i>
                        <?php endif; ?>
                        <?= $config['badge']['count'] ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <h3 class="card-title"><?= $config['title'] ?></h3>
            
            <div class="mt-3">
                <?= $config['content'] ?>
            </div>
        </div>
        
        <div class="card-footer">
            <span class="footer-text display-flex align-center gap-2" style="font-size: 0.85rem;">
                <?php if (isset($config['footer']['icon'])): ?>
                    <i data-lucide="<?= $config['footer']['icon'] ?>" style="width: 14px; height: 14px;"></i>
                <?php endif; ?>
                <?= $config['footer']['text'] ?>
            </span>
            <span class="link-text display-flex align-center gap-1" style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                Detalhes <i data-lucide="arrow-right" style="width:14px; height: 14px;"></i>
            </span>
        </div>
    </a>
    <?php
}

// ============================================================================
// LEGACY CARD FUNCTIONS (mantidas para compatibilidade)
// ============================================================================

// Renderizar card de Escalas (UNIFIED)
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
    
    // Preparar conteúdo dinâmico
    ob_start();
    if ($nextSchedule): ?>
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
    <?php endif;
    $content = ob_get_clean();
    
    // Renderizar usando sistema unificado
    renderUnifiedCard([
        'id' => 'escalas',
        'title' => 'Escalas',
        'icon' => 'calendar',
        'category' => 'gestao',
        'url' => 'escalas.php',
        'badge' => $totalSchedules > 0 ? [
            'count' => $totalSchedules,
            'icon' => 'calendar',
            'label' => "$totalSchedules escalas"
        ] : null,
        'content' => $content,
        'footer' => [
            'icon' => 'clock',
            'text' => $countdown
        ]
    ]);
}

// Renderizar card de Repertório (UNIFIED)
function renderCardRepertorio($ultimaMusica, $totalMusicas) {
    ob_start();
    if ($ultimaMusica): ?>
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
    <?php endif;
    $content = ob_get_clean();
    
    renderUnifiedCard([
        'id' => 'repertorio',
        'title' => 'Repertório',
        'icon' => 'music',
        'category' => 'gestao',
        'url' => 'repertorio.php',
        'badge' => ['count' => $totalMusicas, 'icon' => 'music', 'label' => "$totalMusicas músicas"],
        'content' => $content,
        'footer' => ['icon' => 'library', 'text' => "$totalMusicas músicas"]
    ]);
}

// Renderizar card de Membros (UNIFIED)
function renderCardMembros($totalMembros, $stats) {
    ob_start();
    ?>
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
    <?php
    $content = ob_get_clean();
    
    renderUnifiedCard([
        'id' => 'membros',
        'title' => 'Membros',
        'icon' => 'users',
        'category' => 'gestao',
        'url' => 'membros.php',
        'badge' => ['count' => $totalMembros, 'icon' => 'users', 'label' => "$totalMembros membros"],
        'content' => $content,
        'footer' => ['text' => 'Ver equipe completa']
    ]);
}

// Renderizar card de Leitura (UNIFIED)
function renderCardLeitura($data) {
    // Dados extraídos do controller (dashboard_data.php)
    $percentYear = $data['percentYear'] ?? 0;
    $percentToday = $data['percentToday'] ?? 0;
    $todayProgress = $data['todayProgress'] ?? 0;
    $todayTotal = $data['todayTotal'] ?? 0;
    $displayDayGlobal = $data['displayDayGlobal'] ?? 1;

    ob_start();
    ?>
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
    <?php
    $content = ob_get_clean();
    
    renderUnifiedCard([
        'id' => 'leitura',
        'title' => 'Leitura Bíblica',
        'icon' => 'book-open',
        'category' => 'espiritualidade',
        'url' => 'leitura.php',
        'badge' => ['count' => $percentToday, 'label' => "$percentToday% completo hoje"],
        'content' => $content,
        'footer' => [
            'icon' => 'check-circle',
            'text' => "Hoje: $todayProgress/$todayTotal"
        ]
    ]);
}

// Renderizar card de Avisos (UNIFIED)
function renderCardAvisos($ultimoAviso, $unreadCount) {
    ob_start();
    ?>
    <div class="info-highlight">
        <p class="text-clamp-2">
            <?= htmlspecialchars($ultimoAviso) ?>
        </p>
    </div>
    <?php
    $content = ob_get_clean();
    
    $footerText = 'Tudo em dia';
    if ($unreadCount > 0) {
        $footerText = '<span class="text-urgent"><i data-lucide="alert-circle" class="icon-tiny"></i> ' . $unreadCount . ' novo(s)</span>';
    }

    renderUnifiedCard([
        'id' => 'avisos',
        'title' => 'Avisos',
        'icon' => 'bell',
        'category' => 'comunicacao',
        'url' => 'avisos.php',
        'extra_classes' => ($unreadCount > 0) ? 'card-urgent-glow' : '',
        'badge' => $unreadCount > 0 ? [
            'count' => $unreadCount,
            'icon' => 'bell',
            'label' => "$unreadCount avisos não lidos",
            'pulse' => true,
            'type' => 'badge-warning'
        ] : null,
        'content' => $content,
        'footer' => [
            'text' => $footerText
        ]
    ]);
}

// Renderizar card de Aniversariantes (UNIFIED)
function renderCardAniversariantes($niverCount, $proximo = null) {
    ob_start();
    if ($proximo): ?>
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
    <?php endif;
    $content = ob_get_clean();

    renderUnifiedCard([
        'id' => 'aniversariantes',
        'title' => 'Aniversariantes',
        'icon' => 'cake',
        'category' => 'comunicacao',
        'url' => 'aniversarios.php',
        'badge' => [
            'count' => $niverCount,
            'icon' => 'cake',
            'label' => "$niverCount aniversariantes este mês",
            'type' => 'badge-warning'
        ],
        'content' => $content,
        'footer' => [
            'text' => "$niverCount neste mês"
        ]
    ]);
}

// Renderizar card de Devocional (UNIFIED)
function renderCardDevocional() {
    $hoje = date('d/m');
    
    ob_start();
    ?>
    <div class="info-highlight">
        <div class="highlight-title">
            ☀️ Reflexão Diária
        </div>
        <div class="highlight-subtitle">
            Hoje, <?= $hoje ?>
        </div>
    </div>
    <?php
    $content = ob_get_clean();

    renderUnifiedCard([
        'id' => 'devocional',
        'title' => 'Devocional',
        'icon' => 'sunrise',
        'category' => 'espiritualidade',
        'url' => 'devocionais.php',
        'badge' => null,
        'content' => $content,
        'footer' => [
            'text' => 'Separar um tempo'
        ]
    ]);
}

// Renderizar card de Oração (UNIFIED)
function renderCardOracao($count = 0) {
    ob_start();
    ?>
    <div class="info-highlight">
        <?php if ($count > 0): ?>
            <div class="highlight-title big">
                <?= $count ?> <?= $count == 1 ? 'pedido' : 'pedidos' ?>
            </div>
            <div class="highlight-subtitle">
                🙏 Ativos na lista
            </div>
        <?php else: ?>
            <span class="highlight-subtitle">Nenhum pedido ativo</span>
        <?php endif; ?>
    </div>
    <?php
    $content = ob_get_clean();

    renderUnifiedCard([
        'id' => 'oracao',
        'title' => 'Oração',
        'icon' => 'heart',
        'category' => 'espiritualidade',
        'url' => 'oracao.php',
        'badge' => $count > 0 ? [
            'count' => $count,
            'icon' => 'heart',
            'label' => "$count pedidos de oração"
        ] : null,
        'content' => $content,
        'footer' => [
            'text' => 'Intercessão'
        ]
    ]);
}

// Renderizar card de Agenda (UNIFIED)
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
    
    ob_start();
    if ($nextEvent): ?>
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
    <?php endif;
    $content = ob_get_clean();

    renderUnifiedCard([
        'id' => 'agenda',
        'title' => 'Agenda',
        'icon' => 'calendar-days',
        'category' => 'gestao',
        'url' => 'agenda.php',
        'badge' => $totalEvents > 0 ? [
            'count' => $totalEvents,
            'icon' => 'calendar-days',
            'label' => "$totalEvents eventos agendados"
        ] : null,
        'content' => $content,
        'footer' => [
            'icon' => 'clock',
            'text' => $countdown
        ]
    ]);
}

// Renderizar card de Histórico (UNIFIED)
function renderCardHistorico($data) {
    $lastCulto = $data['last_culto'];
    $sugestoesCount = $data['sugestoes_count'];
    $dateDisplay = $lastCulto ? date('d/m', strtotime($lastCulto['event_date'])) : '--/--';
    $typeDisplay = $lastCulto ? htmlspecialchars($lastCulto['event_type']) : 'Nenhum registro';
    
    ob_start();
    ?>
    <div class="info-highlight">
        <div class="info-primary">
            Último: <?= $dateDisplay ?>
        </div>
        <div class="info-secondary text-truncate">
            <?= $typeDisplay ?>
        </div>
    </div>
    <?php
    $content = ob_get_clean();

    renderUnifiedCard([
        'id' => 'historico',
        'title' => 'Histórico',
        'icon' => 'history',
        'category' => 'gestao',
        'url' => 'historico.php',
        'badge' => $sugestoesCount > 0 ? [
            'count' => $sugestoesCount,
            'icon' => 'lightbulb',
            'label' => "$sugestoesCount sugestões",
            'type' => 'badge-info' // Using standard info badge instead of text-urgent
        ] : null,
        'content' => $content,
        'footer' => [
            'text' => 'Análise Completa'
        ]
    ]);
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
    <a href="<?= $cardDef['url'] ?>" class="access-card card-clickable <?= $colorClass ?>" style="position: relative; overflow: hidden; text-decoration: none; color: inherit;">
        <div class="card-body">
            <div class="card-icon">
                <i data-lucide="<?= $cardDef['icon'] ?>"></i>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($cardDef['title']) ?></h3>
            
            <?php if ($desc): ?>
                <p class="text-secondary mt-2" style="font-size: 0.9rem;"><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <span class="footer-text">Acessar</span>
            <span class="link-text display-flex align-center gap-1" style="font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                Detalhes <i data-lucide="arrow-right" style="width:14px; height: 14px;"></i>
            </span>
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
            renderCardLeitura($data['leituraData'] ?? []);
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
