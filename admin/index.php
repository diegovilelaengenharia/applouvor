<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;

// --- DADOS REAIS ---
// 1. Avisos (Apenas alertas não lidos/recentes)
$avisos = [];
try {
    // Busca apenas urgentes ou importantes recentes
    $stmt = $pdo->query("
        SELECT count(*) as total, 
        (SELECT title FROM avisos WHERE archived_at IS NULL ORDER BY is_pinned DESC, created_at DESC LIMIT 1) as last_title
        FROM avisos WHERE archived_at IS NULL
    ");
    $avisosData = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAvisos = $avisosData['total'] ?? 0;
    $ultimoAviso = $avisosData['last_title'] ?? 'Nenhum aviso novo';
} catch (Exception $e) {
    $totalAvisos = 0;
    $ultimoAviso = '';
}

// 2. Minha Próxima Escala
$nextSchedule = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// 3. Aniversariantes (Quantidade no mês)
$niverCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())");
    $niverCount = $stmt->fetchColumn();
} catch (Exception $e) {
}

// Saudação baseada no horário
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

renderAppHeader('Início');
renderPageHeader('Visão Geral', 'Confira o que temos para hoje');
?>

<!-- Estilos da Nova Home (Vertical Feed) -->
<style>
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 24px 0 12px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-action {
        font-size: 0.85rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .feed-card {
        background: var(--bg-surface);
        border-radius: 16px;
        padding: 20px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .feed-card:active {
        transform: scale(0.98);
    }

    .feed-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.2rem;
        font-weight: 700;
    }

    /* Empty State */
    .empty-state {
        background: #f8fafc;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-muted);
        border: 1px dashed var(--border-color);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--bg-surface);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid var(--border-color);
        text-align: center;
        transition: transform 0.2s;
    }

    .stat-card:active {
        transform: scale(0.98);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 600;
    }
</style>

<div style="max-width: 600px; margin: 0 auto;">

    <!-- AVISOS -->
    <div class="section-title">
        <span>Avisos <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $totalAvisos ?>)</span></span>
        <?php if ($totalAvisos > 0): ?>
            <a href="avisos.php" class="section-action">Ver todos</a>
        <?php endif; ?>
    </div>

    <?php if ($totalAvisos > 0): ?>
        <a href="avisos.php" class="feed-card" style="background: #fff7ed; border-color: #ffedd5;">
            <div class="feed-icon" style="background: #ffedd5; color: #ea580c;">
                <i data-lucide="bell"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #9a3412;">Novo Aviso</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: #c2410c; line-height: 1.4;">
                    <?= htmlspecialchars($ultimoAviso) ?>
                </p>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="bell-off" style="width: 20px;"></i>
            <span style="font-size: 0.9rem">Lista vazia.</span>
        </div>
    <?php endif; ?>





    <!-- MINHAS ESCALAS -->
    <div class="section-title">
        <span>Minhas escalas <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $nextSchedule ? '1' : '0' ?>)</span></span>
        <a href="escalas.php?mine=1" class="section-action">Ver todas</a>
    </div>

    <?php if ($nextSchedule):
        $date = new DateTime($nextSchedule['event_date']);
        $monthName = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][$date->format('n') - 1];
    ?>
        <a href="escala_detalhe.php?id=<?= $nextSchedule['id'] ?>" class="feed-card">
            <!-- Date Box -->
            <div style="
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                width: 50px; height: 56px; background: #f1f5f9; border-radius: 10px;
                color: var(--text-main); text-align: center; line-height: 1; flex-shrink: 0;
            ">
                <span style="font-size: 1.1rem; font-weight: 800;"><?= $date->format('d') ?></span>
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); padding-top: 2px;"><?= $monthName ?></span>
            </div>

            <div style="flex: 1;">
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; color: var(--text-muted); font-size: 0.85rem;">
                    <!-- Mini Avatars (Simulated) -->
                    <div style="display: flex; padding-left: 8px;">
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #cbd5e1; border: 2px solid white; margin-left: -8px;"></span>
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #94a3b8; border: 2px solid white; margin-left: -8px;"></span>
                        <span style="width: 20px; height: 20px; border-radius: 50%; background: #64748b; border: 2px solid white; margin-left: -8px;"></span>
                    </div>
                    <span>• <?= $saudacao == 'Bom dia' ? 'Manhã' : 'Noite' ?></span>
                </div>
            </div>

            <div style="color: var(--text-muted);">
                <i data-lucide="chevron-right" style="width: 20px;"></i>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="calendar-off" style="width: 20px;"></i>
            <span style="font-size: 0.9rem;">Lista vazia.</span>
        </div>
    <?php endif; ?>


    <!-- ANIVERSARIANTES -->
    <div class="section-title">
        <span>Aniversariantes <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= $niverCount ?>)</span></span>
        <a href="aniversarios.php" class="section-action">Ver todos</a>
    </div>

    <?php if ($niverCount > 0): ?>
        <a href="aniversarios.php" class="feed-card" style="background: #fdf2f8; border-color: #fbcfe8;">
            <div class="feed-icon" style="background: #fbcfe8; color: #db2777;">
                <i data-lucide="cake"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #be185d;"><?= $niverCount ?> aniversariantes</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: #db2777;">
                    Celebre a vida dos irmãos este mês!
                </p>
            </div>
        </a>
    <?php else: ?>
        <div class="empty-state">
            <i data-lucide="party-popper" style="width: 20px;"></i>
            <span style="font-size: 0.9rem;">Lista vazia.</span>
        </div>
    <?php endif; ?>

    <!-- MAIS TOCADAS -->
    <div class="section-title">
        <span>Mais tocadas</span>
        <button onclick="openModal('modal-top-louvores')" class="section-action" style="background: none; border: none; cursor: pointer; color: #2563eb; font-size: 0.85rem; font-weight: 600;">Ver tudo</button>
    </div>

    <div onclick="openModal('modal-top-louvores')" class="feed-card" style="background: #eff6ff; border: 1px solid #dbeafe; cursor: pointer;">
        <div class="feed-icon" style="background: #dbeafe; color: #2563eb;">
            <i data-lucide="music"></i>
        </div>
        <div>
            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #1e40af;">Top Louvores</h4>
            <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: #2563eb;">
                Confira o que está em alta no repertório.
            </p>
        </div>
        <div style="margin-left: auto; color: #2563eb;">
            <i data-lucide="chevron-right" style="width: 20px;"></i>
        </div>
    </div>

    <!-- Top Louvores Modal -->
    <div id="modal-top-louvores" class="modal-overlay">
        <div class="modal-container fade-in-up" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Top Louvores 🎵</h3>
                <button class="modal-close" onclick="closeModal('modal-top-louvores')">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <!-- Tabs -->
            <div style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                <button onclick="switchTab('tab-month')" class="tab-btn active" id="btn-tab-month" style="flex: 1; padding: 8px; border-radius: 8px; border: none; background: transparent; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;">
                    Mês
                </button>
                <button onclick="switchTab('tab-semester')" class="tab-btn" id="btn-tab-semester" style="flex: 1; padding: 8px; border-radius: 8px; border: none; background: transparent; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;">
                    Semestre
                </button>
                <button onclick="switchTab('tab-year')" class="tab-btn" id="btn-tab-year" style="flex: 1; padding: 8px; border-radius: 8px; border: none; background: transparent; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;">
                    Ano
                </button>
            </div>

            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">

                <!-- Tab Content: Mês -->
                <div id="tab-month" class="tab-content">
                    <?php if (empty($topMonth)): ?>
                        <div class="empty-state">Sem dados para este mês.</div>
                    <?php else: ?>
                        <?php foreach ($topMonth as $index => $song): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 24px; height: 24px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($song['title']) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?></div>
                                    </div>
                                </div>
                                <div style="font-weight: 700; color: #3b82f6; font-size: 0.9rem;">
                                    <?= $song['play_count'] ?>x
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Tab Content: Semestre -->
                <div id="tab-semester" class="tab-content" style="display: none;">
                    <?php if (empty($topSemester)): ?>
                        <div class="empty-state">Sem dados para o semestre.</div>
                    <?php else: ?>
                        <?php foreach ($topSemester as $index => $song): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 24px; height: 24px; background: #f0fdf4; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($song['title']) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?></div>
                                    </div>
                                </div>
                                <div style="font-weight: 700; color: #16a34a; font-size: 0.9rem;">
                                    <?= $song['play_count'] ?>x
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Tab Content: Ano -->
                <div id="tab-year" class="tab-content" style="display: none;">
                    <?php if (empty($topYear)): ?>
                        <div class="empty-state">Sem dados para o ano.</div>
                    <?php else: ?>
                        <?php foreach ($topYear as $index => $song): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 24px; height: 24px; background: #fff7ed; color: #c2410c; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($song['title']) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?></div>
                                    </div>
                                </div>
                                <div style="font-weight: 700; color: #c2410c; font-size: 0.9rem;">
                                    <?= $song['play_count'] ?>x
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            // Deactivate all buttons
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active');
                el.style.background = 'transparent';
                el.style.color = '#64748b';
            });

            // Show selected content
            document.getElementById(tabId).style.display = 'block';

            // Activate selected button
            const btn = document.getElementById('btn-' + tabId);
            btn.classList.add('active');
            btn.style.background = '#f1f5f9';
            btn.style.color = '#1e293b';
        }

        // Set default tab style logic in CSS or inline above
        document.querySelector('.tab-btn.active').style.background = '#f1f5f9';
        document.querySelector('.tab-btn.active').style.color = '#1e293b';
    </script>

    <?php renderAppFooter(); ?>