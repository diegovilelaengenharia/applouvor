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

        <div style="overflow-x: auto;">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Membro</th>
                        <th>Último Acesso</th>
                        <th>Frequência</th>
                        <th>Escalas (60d)</th>
                        <th>Leitura (30d)</th>
                        <th>Engagement Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): 
                        // Calcular tempo relativo
                        $last_login_ts = strtotime($member['last_login']);
                        $now = time();
                        $diff = $now - $last_login_ts;
                        
                        // Status logic
                        if ($member['last_login'] && $diff < 300) { // 5 min
                            $status_class = 'status-online';
                            $status_text = 'Online agora';
                            $time_text = 'Online';
                        } elseif ($member['last_login'] && $diff < 86400) { // 24h
                            $status_class = 'status-recent';
                            $status_text = 'Acesso recente';
                            $time_text = 'Há ' . floor($diff / 3600) . 'h';
                        } else {
                            $status_class = 'status-offline';
                            $status_text = 'Ausente';
                            $days = floor($diff / 86400);
                            $time_text = $member['last_login'] ? "Há $days dias" : 'Nunca acessou';
                        }

                        // Engagement Score (0-10)
                        // Login count weight (capped at 50) + Reading weight + Scale weight
                        $score = 0;
                        if ($member['last_login'] && $diff < 7 * 86400) $score += 3; // Logged in last week
                        $score += min($member['reading_activity'], 3); // Up to 3 points for reading
                        $score += min($member['scale_count'] * 2, 4); // Up to 4 points for scales
                        
                        $score_class = 'badge-low';
                        if ($score >= 7) $score_class = 'badge-high';
                        elseif ($score >= 4) $score_class = 'badge-med';
                    ?>
                    <tr>
                        <td data-label="Membro">
                            <div class="user-profile">
                                <div class="avatar-wrapper">
                                    <?php if ($member['avatar']): 
                                        $avatarUrl = strpos($member['avatar'], 'http') === 0 ? $member['avatar'] : '../assets/uploads/' . $member['avatar'];
                                    ?>
                                        <img src="<?= htmlspecialchars($avatarUrl) ?>" class="avatar-circle" alt="<?= htmlspecialchars($member['name']) ?>">
                                    <?php else: ?>
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="status-indicator <?= $status_class ?>" title="<?= $status_text ?>"></div>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($member['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= ucfirst($member['role']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Último Acesso">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);"><?= $time_text ?></div>
                                <?php if($member['last_login']): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-tertiary);"><?= date('d/m H:i', strtotime($member['last_login'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Frequência">
                            <div style="font-weight: 700; color: var(--text-primary);"><?= $member['login_count'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">acessos</div>
                        </td>
                        <td data-label="Escalas (60d)">
                            <div style="font-weight: 700; color: var(--text-primary);"><?= $member['scale_count'] ?></div>
                        </td>
                        <td data-label="Leitura (30d)">
                            <div style="font-weight: 700; color: var(--text-primary);"><?= $member['reading_activity'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">capítulos</div>
                        </td>
                        <td data-label="Engajamento">
                            <span class="badge <?= $score_class ?>">
                                <?php if($score >= 7): ?><i data-lucide="trending-up" width="14"></i><?php endif; ?>
                                <?= $score >= 7 ? 'Alto' : ($score >= 4 ? 'Médio' : 'Baixo') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>
