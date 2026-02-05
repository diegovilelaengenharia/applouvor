<?php
// admin/escalas_gestao.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

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


renderAppHeader('Gestão de Escalas');
renderPageHeader('Gestão de Escalas', 'Controle, Análise e Dispoinibilidade');
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* Mobile Optimization */
    @media (max-width: 768px) {
        .container, div[style*="max-width: 1100px"] {
            padding: 0 10px !important;
        }
        .filter-bar {
            padding: 12px !important;
            flex-direction: column;
            align-items: stretch !important;
            gap: 12px !important;
        }
        .filter-bar form {
            width: 100%;
            justify-content: space-between;
        }
        .filter-bar select {
            flex: 1;
        }
        .buttons-container {
            width: 100%;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .kpi-card, div[style*="background: white"] {
            padding: 12px !important;
        }
        h2 { font-size: 1.2rem !important; }
        h3 { font-size: 1rem !important; }
        .view-section {
            padding-bottom: 60px; /* Space for bottom nav if exists */
        }
    }
</style>

<div style="max-width: 1100px; margin: 0 auto; padding: 0 16px;">
    
    <!-- Header com Exportação -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: var(--text-main);">Painel de Gestão</h2>
        
        <div class="buttons-container" style="display: flex; gap: 10px;">

            
            <!-- Botão PDF/Imprimir (NOVO RELATÓRIO) -->
            <a href="escalas_relatorio_print.php?period=<?= $period ?>&year=<?= $year ?>&month=<?= $month ?>&semester=<?= $semester ?? '' ?>" target="_blank" style="text-decoration: none;">
                <button style="padding: 8px 14px; background: var(--rose-600); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-size: 0.9rem;">
                    <i data-lucide="printer" style="width: 16px;"></i> PDF
                </button>
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-bar" style="background: var(--bg-surface); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
        
        <form method="GET" action="escalas_gestao.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <select name="period" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Mensal</option>
                <option value="semester" <?= $period == 'semester' ? 'selected' : '' ?>>Semestral</option>
                <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Anual</option>
            </select>

            <select name="year" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                <?php for($y = date('Y')-1; $y <= date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <?php if ($period == 'month'): ?>
                <select name="month" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                    <?php 
                    $months = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
                    foreach($months as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $month == $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($period == 'semester'): ?>
                 <select name="semester" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body);">
                    <option value="1" <?= $semester == 1 ? 'selected' : '' ?>>1º Semestre</option>
                    <option value="2" <?= $semester == 2 ? 'selected' : '' ?>>2º Semestre</option>
                </select>
            <?php endif; ?>
        </form>

        <div style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">
            <?= $titlePeriod ?>
        </div>
    </div>

    <!-- ABAS -->
    <div class="nav-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
        <button onclick="switchView('list')" id="btn-list" style="padding: 10px 20px; border-radius: 8px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer; white-space: nowrap;">Listagem</button>
        <button onclick="switchView('stats')" id="btn-stats" style="padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-muted); font-weight: 600; cursor: pointer; white-space: nowrap;">Estatísticas</button>
        <button onclick="switchView('absence')" id="btn-absence" style="padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-muted); font-weight: 600; cursor: pointer; white-space: nowrap;">Ausências</button>
    </div>

    <!-- 1. LISTAGEM -->
    <div id="view-list" class="view-section">
        <?php if (empty($schedules)): ?>
            <div style="text-align: center; padding: 40px; background: var(--bg-surface); border-radius: 12px; border: 1px dashed var(--border-color);">
                <i data-lucide="calendar-off" style="width: 48px; color: var(--text-muted); margin-bottom: 12px;"></i>
                <h3 style="margin: 0; color: var(--text-main);">Nenhuma escala encontrada</h3>
                <p style="color: var(--text-muted);">Tente alterar os filtros de período.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($schedules as $s): 
                    $d = new DateTime($s['event_date']);
                    $isPassed = $d < new DateTime('today');
                ?>
                    <div style="background: var(--bg-surface); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 16px; opacity: <?= $isPassed ? '0.8' : '1' ?>;">
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <div style="text-align: center; min-width: 50px; padding: 6px; background: var(--bg-body); border-radius: 8px;">
                                <div style="font-weight: 700; font-size: 1.2rem; color: var(--text-main); line-height: 1;"><?= $d->format('d') ?></div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;"><?= strftime('%b', $d->getTimestamp()) ?></div>
                            </div>
                            <div>
                                <h3 style="margin: 0 0 4px 0; font-size: 1rem; color: var(--text-main); font-weight: 600;">
                                    <?= htmlspecialchars($s['event_type']) ?>
                                    <?php if($isPassed): ?> <span style="font-size: 0.7rem; background: var(--slate-100); padding: 2px 6px; border-radius: 4px; color: var(--slate-500);">Concluída</span><?php endif; ?>
                                </h3>
                                <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; gap: 12px;">
                                    <span><i data-lucide="clock" style="width: 12px; vertical-align: middle;"></i> <?= substr($s['event_time'],0,5) ?></span>
                                    <span><i data-lucide="users" style="width: 12px; vertical-align: middle;"></i> <?= $s['total_users'] ?></span>
                                    <span><i data-lucide="music" style="width: 12px; vertical-align: middle;"></i> <?= $s['total_songs'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="escala_detalhe.php?id=<?= $s['id'] ?>" style="
                                background: var(--slate-50); color: var(--slate-600); padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; border: 1px solid var(--slate-100); display: flex; align-items: center; gap: 4px;
                            ">
                                <i data-lucide="edit-2" style="width: 14px;"></i> Editar
                            </a>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja EXCLUIR esta escala?');" style="margin: 0;">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" style="
                                    background: var(--rose-50); color: var(--rose-600); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--rose-100); cursor: pointer; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 4px;
                                ">
                                    <i data-lucide="trash-2" style="width: 14px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. ESTATÍSTICAS COMPLETAS (Unificado) -->
    <div id="view-stats" class="view-section" style="display: none;">
        
        <!-- KPIs Globais -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); border-left: 4px solid var(--lavender-600);">
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total de Escalas</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--lavender-600);"><?= $totalEscalas ?></div>
            </div>
            <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); border-left: 4px solid #10b981;">
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Taxa de Confirmação</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #10b981;"><?= $taxaConfirmacao ?>%</div>
            </div>
            <div style="background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); border-left: 4px solid var(--yellow-500);">
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Escalas Pendentes</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--yellow-500);"><?= $escalasPendentes ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            
            <!-- Tipos de Culto -->
            <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Por Tipo de Culto</h3>
                <?php if (empty($escalasPorTipo)): ?>
                    <p style="color: var(--text-muted);">Sem dados.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                        <?php foreach ($escalasPorTipo as $tipo): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-body);">
                                <span style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($tipo['event_type']) ?></span>
                                <span style="background: var(--slate-50); color: var(--slate-600); padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                    <?= $tipo['total'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ranking Participação -->
            <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color); grid-row: span 2;">
                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Ranking de Participação</h3>
                <?php if (empty($rankingParticipacao)): ?>
                    <p style="color: var(--text-muted);">Sem dados.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 8px; max-height: 500px; overflow-y: auto;">
                        <?php foreach ($rankingParticipacao as $idx => $membro): 
                             $colorClass = $idx === 0 ? '#fbbf24' : ($idx === 1 ? '#9ca3af' : ($idx === 2 ? 'var(--yellow-600)' : 'var(--slate-200)'));
                             $textColor = $idx < 3 ? 'white' : 'var(--slate-500)';
                        ?>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 10px; background: var(--bg-body); border-radius: 10px;">
                                <div style="width: 24px; height: 24px; background: <?= $colorClass ?>; color: <?= $textColor ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem;">
                                    <?= $idx + 1 ?>
                                </div>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $membro['avatar_color'] ?: 'var(--slate-300)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                    <?= strtoupper(substr($membro['name'], 0, 1)) ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;"><?= htmlspecialchars($membro['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($membro['instrument'] ?: 'Membro') ?></div>
                                </div>
                                <div style="font-weight: 700; color: var(--primary); font-size: 1rem;">
                                    <?= $membro['participacoes'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Músicas (Repertório) -->
            <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Músicas Mais Tocadas</h3>
                <?php if (empty($topSongs)): ?>
                    <p style="color: var(--text-muted);">Sem dados.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach (array_slice($topSongs, 0, 5) as $musica): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($musica['title']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($musica['artist']) ?>
                                    </div>
                                </div>
                                <span style="background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; margin-left: 8px;">
                                    <?= $musica['vezes'] ?>x
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- 3. AUSÊNCIAS -->
    <div id="view-absence" class="view-section" style="display: none;">
         <!-- Cards Resumo Ausências -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;">Registros de Ausência</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--rose-500);"><?= $totalRegistrosAusencia ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
            <!-- Top Ausentes -->
            <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Mais Indisponíveis (Dias)</h3>
                <?php if (empty($topAusentes)): ?>
                    <p style="color: var(--text-muted);">Sem registros.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach($topAusentes as $userId => $data): ?>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $data['avatar_color'] ?: 'var(--slate-300)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                    <?= strtoupper(substr($data['name'], 0, 1)) ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 600; color: var(--text-main);">
                                        <span><?= htmlspecialchars($data['name']) ?></span>
                                        <span><?= $data['total_days'] ?> dias</span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $data['count'] ?> registros</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lista Completa -->
            <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Detalhes</h3>
                <?php if (empty($absences)): ?>
                    <p style="color: var(--text-muted);">Nenhuma ausência.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px; max-height: 400px; overflow-y: auto;">
                        <?php foreach($absences as $ab): 
                            $start = date('d/m', strtotime($ab['start_date']));
                            $end = date('d/m', strtotime($ab['end_date']));
                            $periodStr = ($start == $end) ? $start : "$start a $end";
                        ?>
                            <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--slate-100);">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--rose-500);"></div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($ab['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $periodStr ?></div>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">
                                    <?= htmlspecialchars($ab['reason']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<script>
    function switchView(viewName) {
        // Hide all sections
        document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
        
        // Reset buttons
        const ids = ['list', 'stats', 'absence'];
        ids.forEach(id => {
            const btn = document.getElementById('btn-' + id);
            if(btn) {
                btn.style.background = 'var(--bg-surface)';
                btn.style.color = 'var(--text-muted)';
                btn.style.border = '1px solid var(--border-color)';
            }
        });

        // Show active section and highlight button
        document.getElementById('view-' + viewName).style.display = 'block';
        const activeBtn = document.getElementById('btn-' + viewName);
        if(activeBtn) {
            activeBtn.style.background = 'var(--primary)';
            activeBtn.style.color = 'white';
            activeBtn.style.border = 'none';
        }
    }
</script>

<?php renderAppFooter(); ?>
