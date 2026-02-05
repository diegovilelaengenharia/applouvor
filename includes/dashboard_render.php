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
    <a href="escalas.php" class="access-card card-blue" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                    <i data-lucide="calendar" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <?php if ($totalSchedules > 0): ?>
                    <span class="card-badge" style="background: #2563eb; color: white; border: none; font-weight: 700;"><?= $totalSchedules ?></span>
                <?php endif; ?>
            </div>
            
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Escalas</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($nextSchedule): ?>
                    <div style="background: rgba(37, 99, 235, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 3px;">
                            <?= $dateDisplay ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #3b82f6; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                            <?= $roleDisplay ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">
                            <i data-lucide="clock" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                            <?= $countdown ?>
                        </span>
                        <span style="font-size: 0.7rem; color: #3b82f6; font-weight: 600; text-transform: uppercase;">Ver Detalhes →</span>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.7; font-size: 0.85rem;">Nenhuma escala próxima</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Repertório (PRO - ENHANCED)
function renderCardRepertorio($ultimaMusica, $totalMusicas) {
    ?>
    <a href="repertorio.php" class="access-card card-blue" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                    <i data-lucide="music" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <span class="card-badge" style="background: #2563eb; color: white; border: none; font-weight: 700;"><?= $totalMusicas ?></span>
            </div>

            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Repertório</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($ultimaMusica): ?>
                    <div style="background: rgba(37, 99, 235, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 3px;">
                            <?= htmlspecialchars($ultimaMusica['title']) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #3b82f6; display: flex; align-items: center; gap: 4px; justify-content: space-between;">
                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($ultimaMusica['artist']) ?></span>
                            <?php if(!empty($ultimaMusica['tone'])): ?>
                                <span style="background: #2563eb; color: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.65rem; flex-shrink: 0;">
                                    <?= htmlspecialchars($ultimaMusica['tone']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                            <i data-lucide="library" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                            <?= $totalMusicas ?> músicas
                        </span>
                        <span style="font-size: 0.7rem; color: #3b82f6; font-weight: 600; text-transform: uppercase;">Explorar →</span>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.7; font-size: 0.85rem;">Biblioteca vazia</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Membros (PRO - ENHANCED)
function renderCardMembros($totalMembros, $stats) {
    // Calculate percentages relative to total members (can exceed 100% combined due to multi-role)
    $percentVocals = $totalMembros > 0 ? min(100, round(($stats['vocals'] / $totalMembros) * 100)) : 0;
    ?>
    <a href="membros.php" class="access-card card-blue" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                    <i data-lucide="users" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <span class="card-badge" style="background: #2563eb; color: white; border: none; font-weight: 700;"><?= $totalMembros ?></span>
            </div>

            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Membros</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <div style="background: rgba(37, 99, 235, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-around; margin-bottom: 6px;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.2rem; font-weight: 700; color: #2563eb;"><?= $stats['vocals'] ?></div>
                            <div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">Vocais</div>
                        </div>
                        <div style="width: 1px; background: rgba(37, 99, 235, 0.2);"></div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.2rem; font-weight: 700; color: #2563eb;"><?= $stats['instrumentalists'] ?></div>
                            <div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">Instrumentos</div>
                        </div>
                    </div>
                </div>
                <span style="font-size: 0.7rem; color: #3b82f6; font-weight: 600; text-transform: uppercase; float: right;">Ver Equipe →</span>
            </div>
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
    <a href="leitura.php" class="access-card card-green" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #059669; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    <i data-lucide="book-open" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <span class="card-badge" style="background: #059669; color: white; border: none; font-weight: 700;"><?= $percentToday ?>%</span>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Leitura Bíblica</h3>
            <div class="card-info" style="margin-top: 8px;">
                <div style="background: rgba(5, 150, 105, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <div>
                            <div style="font-size: 1.3rem; font-weight: 700; color: #047857;">Dia <?= $displayDayGlobal ?></div>
                            <div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">de 365 dias</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.3rem; font-weight: 700; color: #047857;"><?= $percentYear ?>%</div>
                            <div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">completo</div>
                        </div>
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.5); border-radius: 3px; overflow: hidden;">
                        <div style="width: <?= $percentYear ?>%; height: 100%; background: #059669; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                        <i data-lucide="check-circle" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                        Hoje: <?= $todayProgress ?>/<?= $todayTotal ?>
                    </span>
                    <span style="font-size: 0.7rem; color: #059669; font-weight: 600; text-transform: uppercase;">Ler Agora →</span>
                </div>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Avisos (PRO - ENHANCED)
function renderCardAvisos($ultimoAviso, $unreadCount) {
    ?>
    <a href="avisos.php" class="access-card card-violet" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #7c3aed; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">
                    <i data-lucide="bell" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <span class="card-badge" style="background: #ef4444; color: white; border: none; font-weight: 700; animation: pulse 2s infinite;"><?= $unreadCount ?></span>
                <?php endif; ?>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Avisos</h3>
            <div class="card-info" style="margin-top: 8px;">
                <div style="background: rgba(124, 58, 237, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                    <p style="margin: 0; line-height: 1.3; font-size: 0.85rem; color: #6d28d9; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= htmlspecialchars($ultimoAviso) ?>
                    </p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <?php if ($unreadCount > 0): ?>
                        <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700;">
                            <i data-lucide="alert-circle" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                            <?= $unreadCount ?> novo(s)
                        </span>
                    <?php else: ?>
                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Tudo em dia</span>
                    <?php endif; ?>
                    <span style="font-size: 0.7rem; color: #7c3aed; font-weight: 600; text-transform: uppercase;">Ver Todos →</span>
                </div>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Aniversariantes (PRO - ENHANCED)
function renderCardAniversariantes($niverCount, $proximo = null) {
    ?>
    <a href="aniversarios.php" class="access-card card-violet" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #7c3aed; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">
                    <i data-lucide="cake" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <span class="card-badge" style="background: #7c3aed; color: white; border: none; font-weight: 700;"><?= $niverCount ?></span>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Aniversariantes</h3>
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($proximo): ?>
                    <div style="background: rgba(124, 58, 237, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #6d28d9; font-size: 0.95rem; margin-bottom: 3px;">
                            🎉 <?= htmlspecialchars(explode(' ', $proximo['name'])[0]) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #7c3aed; font-weight: 600;">
                            Próximo: Dia <?= $proximo['dia'] ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                            <?= $niverCount ?> neste mês
                        </span>
                        <span style="font-size: 0.7rem; color: #7c3aed; font-weight: 600; text-transform: uppercase;">Ver Lista →</span>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.7; font-size: 0.85rem;">Ninguém mais este mês</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Devocional (ENHANCED)
function renderCardDevocional() {
    $hoje = date('d/m');
    ?>
    <a href="devocionais.php" class="access-card card-green" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #059669; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    <i data-lucide="sunrise" style="width: 24px; height: 24px; color: white;"></i>
                </div>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Devocional</h3>
            <div class="card-info" style="margin-top: 8px;">
                <div style="background: rgba(5, 150, 105, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                    <div style="font-weight: 700; color: #047857; font-size: 0.95rem; margin-bottom: 3px;">
                        ☀️ Reflexão Diária
                    </div>
                    <div style="font-size: 0.75rem; color: #059669; font-weight: 600;">
                        Hoje, <?= $hoje ?>
                    </div>
                </div>
                <span style="font-size: 0.7rem; color: #059669; font-weight: 600; text-transform: uppercase; float: right;">Ler Devocional →</span>
            </div>
        </div>
    </a>
    <?php
}

// Renderizar card de Oração (ENHANCED)
function renderCardOracao($count = 0) {
    ?>
    <a href="oracao.php" class="access-card card-green" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #059669; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    <i data-lucide="heart" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <?php if ($count > 0): ?>
                    <span class="card-badge" style="background: #059669; color: white; border: none; font-weight: 700;"><?= $count ?></span>
                <?php endif; ?>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Oração</h3>
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($count > 0): ?>
                    <div style="background: rgba(5, 150, 105, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #047857; font-size: 1.2rem; margin-bottom: 3px;">
                            <?= $count ?> <?= $count == 1 ? 'pedido' : 'pedidos' ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #059669; font-weight: 600;">
                            🙏 Ativos na lista
                        </div>
                    </div>
                    <span style="font-size: 0.7rem; color: #059669; font-weight: 600; text-transform: uppercase; float: right;">Ver Lista →</span>
                <?php else: ?>
                    <div style="background: rgba(5, 150, 105, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <span style="font-size: 0.85rem; color: #047857; font-weight: 600;">Nenhum pedido ativo</span>
                    </div>
                    <span style="font-size: 0.7rem; color: #059669; font-weight: 600; text-transform: uppercase; float: right;">Fazer Pedido →</span>
                <?php endif; ?>
            </div>
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
    <a href="agenda.php" class="access-card card-blue" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                    <i data-lucide="calendar-days" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <?php if ($totalEvents > 0): ?>
                    <span class="card-badge" style="background: #2563eb; color: white; border: none; font-weight: 700;"><?= $totalEvents ?></span>
                <?php endif; ?>
            </div>
            
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Agenda</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <?php if ($nextEvent): ?>
                    <div style="background: rgba(37, 99, 235, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= $eventName ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #3b82f6; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                            <?= $dateDisplay ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">
                            <i data-lucide="clock" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                            <?= $countdown ?>
                        </span>
                        <span style="font-size: 0.7rem; color: #3b82f6; font-weight: 600; text-transform: uppercase;">Ver Agenda →</span>
                    </div>
                <?php else: ?>
                    <span style="opacity: 0.7; font-size: 0.85rem;">Nenhum evento agendado</span>
                <?php endif; ?>
            </div>
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
    <a href="historico.php" class="access-card card-blue" style="position: relative; overflow: hidden;">
        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="card-icon" style="background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                    <i data-lucide="history" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <?php if ($sugestoesCount > 0): ?>
                    <span class="card-badge" style="background: #ef4444; color: white; border: none; font-weight: 700;" title="Sugestões de músicas">
                        <i data-lucide="lightbulb" style="width: 10px; height: 10px; display: inline-block; vertical-align: middle;"></i>
                        <?= $sugestoesCount ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 8px;">Histórico</h3>
            
            <div class="card-info" style="margin-top: 8px;">
                <div style="background: rgba(37, 99, 235, 0.1); padding: 8px; border-radius: 8px; margin-bottom: 6px;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 3px;">
                        Último: <?= $dateDisplay ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #3b82f6; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= $typeDisplay ?>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                        Análise Completa
                    </span>
                    <span style="font-size: 0.7rem; color: #3b82f6; font-weight: 600; text-transform: uppercase;">Ver →</span>
                </div>
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


