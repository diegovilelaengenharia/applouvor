<?php
// admin/boletim_estatistico.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Monitoramento de Usuários');
renderPageHeader('Monitoramento de Usuários', 'Métricas de acesso e engajamento');

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
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Total Membros</div>
            <div style="font-size: 2rem; font-weight: 800; color: #1e293b; margin-top: 8px;"><?= $total_users ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Acessos Hoje</div>
            <div style="font-size: 2rem; font-weight: 800; color: #166534; margin-top: 8px;"><?= $active_today ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Ativos (7d)</div>
            <div style="font-size: 2rem; font-weight: 800; color: #3b82f6; margin-top: 8px;"><?= $active_week ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Total Logins</div>
            <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6; margin-top: 8px;"><?= $total_logins ?></div>
        </div>
    </div>

    <!-- Tabela Detalhada -->
    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
        <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem;">Atividade dos Usuários</h3>
            <span style="font-size: 0.8rem; color: #94a3b8; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                Ordenado por último acesso
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="text-align: left; padding: 12px 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Usuário</th>
                        <th style="text-align: left; padding: 12px 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Função</th>
                        <th style="text-align: left; padding: 12px 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Último Acesso</th>
                        <th style="text-align: center; padding: 12px 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Qtd. Logins</th>
                        <th style="text-align: center; padding: 12px 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        // Avatar logic simplified for display
                        $initials = substr($u['name'], 0, 2);
                        $bg = $u['avatar'] ? "url('../assets/uploads/{$u['avatar']}')" : "linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%)";

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
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $bg ?>; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; font-size: 0.8rem; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <?php if (!$u['avatar']) echo strtoupper($initials); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($u['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px 20px;">
                                <span style="background: <?= $u['role'] === 'admin' ? '#fef3c7' : '#f1f5f9' ?>; color: <?= $u['role'] === 'admin' ? '#d97706' : '#64748b' ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="padding: 16px 20px; color: #475569; font-size: 0.9rem;">
                                <?= $last_seen ?>
                            </td>
                            <td style="padding: 16px 20px; text-align: center; font-weight: 600; color: #1e293b;">
                                <?= $u['login_count'] ?>
                            </td>
                            <td style="padding: 16px 20px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.03); padding: 4px 8px; border-radius: 6px;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= $status_color ?>;"></div>
                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?= $status_text ?></span>
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