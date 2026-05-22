<?php
// admin/boletim_estatistico.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkAdmin();

renderAppHeader('Boletim Estatístico');
renderPageHeader('Boletim Estatístico', 'Métricas de uso e engajamento');

// --- BUSCAR DADOS DE USUÁRIOS ---
$stmt = $pdo->query("
    SELECT id, name, role, avatar, last_login, login_count 
    FROM users 
    ORDER BY last_login DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métricas Gerais
$total_users = count($users);
$active_today = 0;
$active_week = 0;
$total_logins = 0;

foreach ($users as $u) {
    if ($u['last_login']) {
        $last = strtotime($u['last_login']);
        if (date('Y-m-d', $last) === date('Y-m-d')) {
            $active_today++;
        }
        if ($last >= strtotime('-7 days')) {
            $active_week++;
        }
    }
    $total_logins += $u['login_count'];
}
?>

<div class="container fade-in-up">

    <!-- Cards de Resumo -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px;">
        <div style="background: var(--bg-card); padding: 16px; border-radius: 12px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Total Membros</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-top: 4px;"><?= $total_users ?></div>
        </div>
        <div style="background: var(--bg-card); padding: 16px; border-radius: 12px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Acessos Hoje</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-green); margin-top: 4px;"><?= $active_today ?></div>
        </div>
        <div style="background: var(--bg-card); padding: 16px; border-radius: 12px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Ativos (7d)</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 4px;"><?= $active_week ?></div>
        </div>
        <div style="background: var(--bg-card); padding: 16px; border-radius: 12px; border: 1px solid var(--border-subtle); box-shadow: var(--shadow-sm);">
            <div style="color: var(--text-secondary); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Total Logins</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #8b5cf6; margin-top: 4px;"><?= $total_logins ?></div>
        </div>
    </div>

    <!-- Tabela Detalhada -->
    <div style="background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-subtle); overflow: hidden; box-shadow: var(--shadow-sm);">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; color: var(--text-primary); font-size: 1rem; font-weight: 700;">Atividade</h3>
            <span style="font-size: 0.7rem; color: var(--text-muted); background: var(--bg-surface); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-subtle);">
                Por último acesso
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="background: var(--bg-surface); border-bottom: 1px solid var(--border-subtle);">
                        <th style="text-align: left; padding: 12px 16px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Usuário</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Função</th>
                        <th style="text-align: left; padding: 12px 16px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Último Acesso</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Logins</th>
                        <th style="text-align: center; padding: 12px 16px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        // Avatar logic simplified for display
                        $initials = substr($u['name'], 0, 2);
                        $bg = $u['avatar'] ? "url('../uploads/{$u['avatar']}')" : "#e2e8f0";

                        // Tempo decorrido
                        $last_seen = 'Nunca';
                        $status_color = '#cbd5e1'; // Cinza (Nunca/Offline há muito tempo)
                        $status_text = 'Inativo';

                        if ($u['last_login']) {
                            $time = strtotime($u['last_login']);
                            $diff = time() - $time;

                            if ($diff < 300) { // 5 min
                                $last_seen = 'Agora mesmo';
                                $status_color = '#22c55e'; // Verde
                                $status_text = 'Online';
                            } elseif ($diff < 3600) {
                                $last_seen = floor($diff / 60) . ' min atrás';
                                $status_color = '#84cc16'; // Verde claro
                                $status_text = 'Recente';
                            } elseif ($diff < 86400) {
                                $last_seen = floor($diff / 3600) . ' horas atrás';
                                $status_color = '#3b82f6'; // Azul
                                $status_text = 'Hoje';
                            } else {
                                $last_seen = date('d/m/Y H:i', $time);
                                if ($diff > 2592000) { // 30 dias
                                    $status_color = '#ef4444'; // Vermelho
                                    $status_text = 'Ausente';
                                } else {
                                    $status_color = '#94a3b8';
                                    $status_text = 'Offline';
                                }
                            }
                        }
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-subtle); transition: background 0.1s;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px 16px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $bg ?>; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; font-size: 0.75rem; border: 2px solid var(--bg-card); box-shadow: var(--shadow-sm);">
                                        <?php if (!$u['avatar']) echo strtoupper($initials); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;"><?= htmlspecialchars($u['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="background: <?= $u['role'] === 'admin' ? 'rgba(245, 158, 11, 0.1)' : 'var(--bg-surface)' ?>; color: <?= $u['role'] === 'admin' ? '#d97706' : 'var(--text-secondary)' ?>; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; color: var(--text-secondary); font-size: 0.85rem;">
                                <?= $last_seen ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">
                                <?= $u['login_count'] ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.03); padding: 4px 8px; border-radius: 6px;">
                                    <div style="width: 6px; height: 6px; border-radius: 50%; background: <?= $status_color ?>;"></div>
                                    <span style="font-size: 0.7rem; color: var(--text-secondary); font-weight: 600;"><?= $status_text ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderAppFooter(); ?>