<?php
// admin/stats_equipe.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Engajamento da Equipe');
renderPageHeader('Engajamento', 'Estatísticas de Acesso');

// --- QUERIES ---

// 1. Total de Membros
$total_members = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Membros Ativos (login nos últimos 30 dias)
$active_members_count = $pdo->query("SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// 3. Taxa de Retenção
$retention_rate = $total_members > 0 ? round(($active_members_count / $total_members) * 100) : 0;

// 4. Lista Detalhada de Membros
// Vamos cruzar com leituras e escalas para medir engajamento real
$query = "
    SELECT 
        u.id, u.name, u.role, u.last_login, u.login_count, u.avatar,
        (SELECT COUNT(*) FROM reading_progress rp WHERE rp.user_id = u.id AND rp.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as reading_activity,
        (SELECT COUNT(*) FROM schedule_users su JOIN schedules s ON su.schedule_id = s.id WHERE su.user_id = u.id AND s.event_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)) as scale_count
    FROM users u
    ORDER BY u.last_login DESC
";
$members = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>



<div style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">

    <!-- KPIs -->
    <div class="dashboard-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background: var(--slate-50); color: var(--slate-500);">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 800; line-height: 1;"><?= $total_members ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Membros Totais</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background: var(--sage-50); color: var(--sage-500);">
                <i data-lucide="activity"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 800; line-height: 1;"><?= $active_members_count ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Ativos (30 dias)</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background: var(--yellow-50); color: var(--yellow-500);">
                <i data-lucide="target"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 800; line-height: 1;"><?= $retention_rate ?>%</div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Taxa de Retenção</div>
            </div>
        </div>
    </div>

    <!-- Tabela de Membros -->
    <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow-sm);">
        <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Atividade Detalhada</h3>

        </div>

            <div style="display: flex; flex-direction: column; gap: 12px; padding: 16px;">
                <?php foreach ($members as $member): 
                    // Calcular tempo relativo
                    $last_login_ts = strtotime($member['last_login']);
                    $now = time();
                    $diff = $now - $last_login_ts;
                    
                    // Status logic
                    if ($member['last_login'] && $diff < 300) { // 5 min
                        $status_class = 'status-online';
                        $status_color = 'var(--green-500)';
                        $time_text = 'Online';
                    } elseif ($member['last_login'] && $diff < 86400) { // 24h
                        $status_class = 'status-recent';
                        $status_color = 'var(--blue-500)';
                        $time_text = 'Há ' . floor($diff / 3600) . 'h';
                    } else {
                        $status_class = 'status-offline';
                        $status_color = 'var(--slate-400)';
                        $days = floor($diff / 86400);
                        $time_text = $member['last_login'] ? "Há $days dias" : 'Nunca';
                    }

                    // Engagement Score (0-10)
                    $score = 0;
                    if ($member['last_login'] && $diff < 7 * 86400) $score += 3;
                    $score += min($member['reading_activity'], 3);
                    $score += min($member['scale_count'] * 2, 4);
                    
                    $score_title = $score >= 7 ? 'Baixo Risco (Ativo)' : ($score >= 4 ? 'Atenção (Morno)' : 'Risco de Evasão (Frio)');
                    $score_bg = $score >= 7 ? 'var(--green-50)' : ($score >= 4 ? 'var(--yellow-50)' : 'var(--red-50)');
                    $score_color = $score >= 7 ? 'var(--green-700)' : ($score >= 4 ? 'var(--yellow-700)' : 'var(--red-700)');
                ?>
                <div class="compact-card" style="position: relative; padding: 16px; border-left: 4px solid <?= $score_color ?>;">
                    <div class="avatar-wrapper" style="width: 48px; height: 48px; min-width: 48px;">
                        <?php if ($member['avatar']): 
                            $avatarUrl = strpos($member['avatar'], 'http') === 0 ? $member['avatar'] : '../assets/uploads/' . $member['avatar'];
                        ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" class="avatar-circle" alt="<?= htmlspecialchars($member['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <div class="avatar-circle" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--slate-100); color: var(--slate-600); border-radius: 50%; font-weight: 800; font-size: 1.2rem;">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="status-indicator" style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: <?= $status_color ?>; border: 2px solid white; border-radius: 50%;"></div>
                    </div>
                    
                    <div class="compact-card-content" style="flex: 1; margin-left: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div class="compact-card-title" style="font-size: 1.05rem; font-weight: 800; margin-bottom: 2px;">
                                    <?= htmlspecialchars($member['name']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-tertiary); margin-bottom: 8px;">
                                    <?= ucfirst($member['role']) ?> • Acesso: <?= $time_text ?>
                                </div>
                            </div>
                            <span style="background: <?= $score_bg ?>; color: <?= $score_color ?>; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 20px;">
                                Score: <?= $score ?>/10
                            </span>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 8px;">
                            <div style="background: var(--slate-50); padding: 6px 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid var(--slate-100);">
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--slate-800);"><?= $member['scale_count'] ?></div>
                                <div style="font-size: 0.7rem; color: var(--slate-500); text-transform: uppercase;">Escalas</div>
                            </div>
                            <div style="background: var(--slate-50); padding: 6px 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid var(--slate-100);">
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--slate-800);"><?= $member['reading_activity'] ?></div>
                                <div style="font-size: 0.7rem; color: var(--slate-500); text-transform: uppercase;">Aulas</div>
                            </div>
                            <div style="background: var(--slate-50); padding: 6px 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid var(--slate-100);">
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--slate-800);"><?= $member['login_count'] ?></div>
                                <div style="font-size: 0.7rem; color: var(--slate-500); text-transform: uppercase;">Acessos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
    </div>

</div>

<?php renderAppFooter(); ?>
