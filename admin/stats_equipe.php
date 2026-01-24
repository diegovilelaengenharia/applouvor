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

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .kpi-card {
        background: var(--bg-surface);
        padding: 20px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 0.9rem;
    }

    .user-table th {
        text-align: left;
        padding: 12px 16px;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.8rem;
        border-bottom: 2px solid var(--border-color);
        background: var(--bg-main);
    }

    .user-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
    }

    .user-table tr:last-child td {
        border-bottom: none;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        object-fit: cover;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .status-online { background-color: #10b981; }
    .status-recent { background-color: #f59e0b; }
    .status-offline { background-color: #ef4444; }

    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-high { background: #dcfce7; color: #166534; }
    .badge-med { background: #fef9c3; color: #854d0e; }
    .badge-low { background: #fee2e2; color: #991b1b; }
    
    @media (max-width: 768px) {
        .user-table thead { display: none; }
        .user-table, .user-table tbody, .user-table tr, .user-table td {
            display: block;
            width: 100%;
        }
        .user-table tr {
            margin-bottom: 16px;
            background: var(--bg-surface);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 12px;
        }
        .user-table td {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-table td:last-child {
            border-bottom: none;
        }
        .user-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .user-profile {
            margin-bottom: 8px;
        }
    }
</style>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">

    <!-- KPIs -->
    <div class="dashboard-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background: #eff6ff; color: #3b82f6;">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 800; line-height: 1;"><?= $total_members ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Membros Totais</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
                <i data-lucide="activity"></i>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 800; line-height: 1;"><?= $active_members_count ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;">Ativos (30 dias)</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background: #fffbeb; color: #f59e0b;">
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
            <button onclick="window.print()" style="background: transparent; border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <i data-lucide="printer" style="width: 16px;"></i> Imprimir
            </button>
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
                                <?php if ($member['avatar']): ?>
                                    <img src="<?= htmlspecialchars($member['avatar']) ?>" class="avatar-circle" alt="<?= htmlspecialchars($member['name']) ?>">
                                <?php else: ?>
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($member['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= ucfirst($member['role']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Último Acesso">
                            <div style="display: flex; align-items: center;">
                                <span class="status-dot <?= $status_class ?>" title="<?= $status_text ?>"></span>
                                <div>
                                    <div style="font-weight: 500; font-size: 0.9rem;"><?= $time_text ?></div>
                                    <?php if($member['last_login']): ?>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($member['last_login'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Frequência">
                            <div style="font-weight: 600;"><?= $member['login_count'] ?> <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted);">acessos</span></div>
                        </td>
                        <td data-label="Escalas (60d)">
                            <div style="font-weight: 600;"><?= $member['scale_count'] ?></div>
                        </td>
                        <td data-label="Leitura (30d)">
                            <div style="font-weight: 600;"><?= $member['reading_activity'] ?> <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted);">caps</span></div>
                        </td>
                        <td data-label="Engajamento">
                            <span class="badge <?= $score_class ?>">
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
