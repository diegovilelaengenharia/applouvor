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

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .kpi-card {
        background: var(--bg-surface);
        padding: 24px;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-2px); }

    .kpi-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .user-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
    }

    .user-table th {
        text-align: left;
        padding: 16px 20px;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.85rem;
        background: var(--bg-surface-active);
        border-bottom: 1px solid var(--border-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .user-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        vertical-align: middle;
        background: var(--bg-surface);
        transition: background 0.15s;
    }

    .user-table tr:hover td {
        background: var(--bg-surface-active);
    }
    
    .user-table tr:last-child td {
        border-bottom: none;
    }

    /* Fixed Avatar Styles */
    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar-wrapper {
        position: relative;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
    }

    .avatar-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        object-fit: cover;
        border: 2px solid var(--bg-surface);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .status-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid var(--bg-surface);
    }

    .status-online { background-color: #10b981; }
    .status-recent { background-color: #f59e0b; }
    .status-offline { background-color: #ef4444; }

    /* Badges with Effects */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
    }

    .badge-high { 
        background: #dcfce7; color: #15803d; 
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.1);
    }
    .badge-med { 
        background: #fef9c3; color: #854d0e; 
        box-shadow: 0 2px 4px rgba(234, 179, 8, 0.1);
    }
    .badge-low { 
        background: #fee2e2; color: #991b1b; 
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .user-table thead { display: none; }
        .user-table, .user-table tbody, .user-table tr, .user-table td {
            display: block; width: 100%;
        }
        .user-table tr {
            margin-bottom: 16px;
            background: var(--bg-surface);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 12px;
            box-shadow: var(--shadow-sm);
        }
        .user-table td {
            padding: 10px 0;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: right;
        }
        .user-table td:last-child {
            border-bottom: none;
        }
        .user-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-align: left;
        }
        .user-profile {
            flex-direction: row;
            text-align: left;
        }
    }

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
