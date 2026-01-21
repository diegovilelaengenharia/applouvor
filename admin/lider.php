<?php
// admin/lider.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Painel do Líder');

// --- DADOS DO DASHBOARD ---
$today = date('Y-m-d');

// 1. Próxima Escala
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE event_date >= ? ORDER BY event_date ASC, event_time ASC LIMIT 1");
    $stmt->execute([$today]);
    $next_scale = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $next_scale = null;
}

// 2. Ausências Futuras (Contagem)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_unavailability WHERE start_date >= ?");
    $stmt->execute([$today]);
    $absences_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $absences_count = 0;
}

// 3. Usuários Online (últimos 10 min) e Total
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute();
    $online_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_members = $stmt->fetchColumn();
} catch (Exception $e) {
    $online_count = 0;
    $total_members = 0;
}

// 4. Notificações Recentes
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $notifications = [];
}

?>

<style>
    .lider-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        padding: 32px 24px;
        border-radius: 0 0 24px 24px;
        margin: -16px -16px 24px -16px;
        /* Negativo para encostar nas bordas mobile */
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
        position: relative;
        overflow: hidden;
    }

    .lider-hero::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(34, 197, 94, 0.2) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(40px);
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .section-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 12px;
        margin-top: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Layout Principal Desktop (Padrão) */
    .main-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .tool-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }

    /* --- MOBILE OPTIMIZATIONS --- */
    @media (max-width: 768px) {
        .lider-hero {
            padding: 24px 20px;
            margin-bottom: 20px;
        }

        .lider-hero h1 {
            font-size: 1.5rem !important;
        }

        .main-layout {
            grid-template-columns: 1fr;
            /* Coluna única */
        }

        /* Horizontal Scroll para Status no Mobile */
        .stats-row {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            padding-bottom: 16px;
            gap: 12px;
            margin-left: -16px;
            margin-right: -16px;
            padding-left: 16px;
            padding-right: 16px;
            -webkit-overflow-scrolling: touch;
        }

        .stat-card {
            min-width: 260px;
            scroll-snap-align: center;
        }

        /* Ajuste do Grid de Ferramentas no Mobile */
        .tool-grid {
            grid-template-columns: 1fr 1fr;
        }

        .section-title {
            margin-top: 8px;
        }
    }

    .tool-card {
        background: linear-gradient(to bottom right, #ffffff, #f8fafc);
        border: 1px solid #eff6ff;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
    }

    .tool-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.08);
        border-color: #e2e8f0;
    }

    .tool-card i {
        width: 32px;
        height: 32px;
        margin-bottom: 4px;
        transition: transform 0.2s;
    }

    .tool-card:hover i {
        transform: scale(1.1);
    }

    /* Cores Específicas */
    .theme-members {
        color: #3b82f6;
        background: #eff6ff;
    }

    .theme-scale {
        color: #8b5cf6;
        background: #f5f3ff;
    }

    .theme-notices {
        color: #f59e0b;
        background: #fffbeb;
    }

    .theme-stats {
        color: #10b981;
        background: #ecfdf5;
    }

    .theme-absences {
        color: #ef4444;
        background: #fef2f2;
    }

    /* Notification List */
    .notif-item {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ef4444;
        margin-top: 6px;
    }
</style>

<div class="lider-hero fade-in-down">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div style="font-size: 0.85rem; opacity: 0.8; margin-bottom: 4px;">Paz, Líder!</div>
            <h1 style="margin: 0; font-size: 1.75rem; font-weight: 800; background: linear-gradient(to right, #ffffff, #cbd5e1); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Gestão do Ministério
            </h1>
            <p style="margin: 8px 0 0 0; opacity: 0.7; font-size: 0.95rem; max-width: 400px;">
                "Quem governa, faça-o com dedicação." <span style="font-size: 0.8rem; font-style: italic;">(Romanos 12:8)</span>
            </p>
        </div>
        <a href="../index.php" style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 12px; color: white; display: flex;">
            <i data-lucide="home" style="width: 20px;"></i>
        </a>
    </div>
</div>

<div id="app-content-inner" style="padding: 0 16px 40px 16px; max-width: 1000px; margin: 0 auto;">

    <!-- 1. Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px;">

        <!-- Next Event Card -->
        <a href="escalas.php" class="stat-card">
            <div class="stat-icon" style="background: #f5f3ff; color: #7c3aed;">
                <i data-lucide="calendar"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Próxima Escala</div>
                <?php if ($next_scale): ?>
                    <div style="font-weight: 700; color: #1e293b; font-size: 1rem;">
                        <?= date('d/m', strtotime($next_scale['event_date'])) ?> - <?= date('H:i', strtotime($next_scale['event_time'])) ?>
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        <?= htmlspecialchars($next_scale['event_type']) ?>
                    </div>
                <?php else: ?>
                    <div style="font-weight: 600; color: #94a3b8;">Nenhuma agendada</div>
                <?php endif; ?>
            </div>
        </a>

        <!-- Absences Card -->
        <a href="indisponibilidade.php" class="stat-card">
            <div class="stat-icon" style="background: #fef2f2; color: #dc2626;">
                <i data-lucide="user-x"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Ausências Futuras</div>
                <div style="font-weight: 800; color: #1e293b; font-size: 1.5rem; line-height: 1;">
                    <?= $absences_count ?>
                </div>
                <div style="font-size: 0.75rem; color: #ef4444; font-weight: 600;">Ver detalhes &rarr;</div>
            </div>
        </a>

        <!-- Online Card -->
        <a href="monitoramento_usuarios.php" class="stat-card">
            <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">
                <i data-lucide="activity"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Time Online</div>
                <div style="display: flex; align-items: baseline; gap: 4px;">
                    <span style="font-weight: 800; color: #1e293b; font-size: 1.5rem; line-height: 1;"><?= $online_count ?></span>
                    <span style="font-size: 0.85rem; color: #64748b;">/ <?= $total_members ?></span>
                </div>
                <div style="font-size: 0.75rem; color: #10b981; font-weight: 600;">Status do sistema &rarr;</div>
            </div>
        </a>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

        <!-- Left Column: Tools -->
        <div>
            <div class="section-title"><i data-lucide="grid"></i> Central de Controle</div>

            <div class="tool-grid">
                <!-- Membros -->
                <a href="membros.php" class="tool-card">
                    <div style="background: #eff6ff; padding: 12px; border-radius: 50%; color: #3b82f6;">
                        <i data-lucide="users"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Membros</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Equipe e Funções</div>
                    </div>
                </a>

                <!-- Escalas -->
                <a href="escalas.php" class="tool-card">
                    <div style="background: #f5f3ff; padding: 12px; border-radius: 50%; color: #8b5cf6;">
                        <i data-lucide="calendar-days"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Escalas</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Agenda do Mês</div>
                    </div>
                </a>

                <!-- Avisos -->
                <a href="avisos.php" class="tool-card">
                    <div style="background: #fffbeb; padding: 12px; border-radius: 50%; color: #f59e0b;">
                        <i data-lucide="bell"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Mural</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Criar Alertas</div>
                    </div>
                </a>

                <!-- Monitoramento -->
                <a href="monitoramento_usuarios.php" class="tool-card">
                    <div style="background: #ecfdf5; padding: 12px; border-radius: 50%; color: #10b981;">
                        <i data-lucide="activity"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Monitor</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Logs e Acessos</div>
                    </div>
                </a>

                <!-- Configs -->
                <a href="configuracoes.php" class="tool-card">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 50%; color: #64748b;">
                        <i data-lucide="settings"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Ajustes</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Sistema</div>
                    </div>
                </a>

                <!-- Repertório Analytics (Placeholder) -->
                <a href="#" onclick="alert('Em breve: Analise quais músicas são mais tocadas!')" class="tool-card" style="opacity: 0.7;">
                    <div style="background: #f0f9ff; padding: 12px; border-radius: 50%; color: #0ea5e9;">
                        <i data-lucide="bar-chart-2"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;">Repertório</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Estatísticas</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Right Column: Notifications Feed -->
        <div>
            <div class="section-title"><i data-lucide="inbox"></i> Notificações</div>

            <div style="background: white; border-radius: 16px; padding: 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;">
                <?php if (empty($notifications)): ?>
                    <div style="padding: 24px; text-align: center; color: #94a3b8;">
                        <i data-lucide="check-circle" style="width: 32px; height: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div style="font-size: 0.9rem;">Tudo em dia!</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="notif-item">
                            <div class="notif-dot" style="background: <?= $n['is_read'] ? '#cbd5e1' : '#f59e0b' ?>"></div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.9rem; color: #1e293b;">
                                    <?= htmlspecialchars($n['title']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #64748b; margin-top: 2px;">
                                    <?= htmlspecialchars($n['message']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;">
                                    <?= date('d/m H:i', strtotime($n['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="#" class="ripple" style="display: block; padding: 12px; text-align: center; font-size: 0.85rem; color: #3b82f6; font-weight: 600; border-top: 1px solid #f1f5f9; text-decoration: none;">
                    Ver todas
                </a>
            </div>

            <!-- Quick Tip -->
            <div style="margin-top: 24px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 16px; border-radius: 16px; border: 1px solid #fde68a;">
                <div style="display: flex; gap: 8px; color: #d97706; font-weight: 700; font-size: 0.9rem; margin-bottom: 4px;">
                    <i data-lucide="lightbulb" style="width: 16px;"></i> Dica do Dia
                </div>
                <p style="margin: 0; font-size: 0.85rem; color: #92400e; line-height: 1.5;">
                    Verifique as ausências com antecedência para evitar buracos na escala de Domingo.
                </p>
            </div>
        </div>

    </div>

</div>

<script>
    // Responsive grid adjustment
    function adjustGrid() {
        const grid = document.querySelector('.tool-grid');
        const container = document.querySelector('#app-content-inner > div:last-child');

        if (window.innerWidth <= 768) {
            container.style.gridTemplateColumns = '1fr';
        } else {
            container.style.gridTemplateColumns = '2fr 1fr';
        }
    }

    window.addEventListener('resize', adjustGrid);
    adjustGrid(); // Init
</script>

<?php
// No footer padrão para manter o estilo limpo do dashboard, ou incluir se necessário
// renderAppFooter(); 
?>
<!-- Fechamento manual para scripts -->
<script src="../assets/js/main.js"></script>
<script>
    lucide.createIcons();
</script>
</body>

</html>