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


    <!-- BOLETIM DE ESTATÍSTICAS -->
    <div class="section-title">
        <span>Boletim de Estatísticas</span>
    </div>

    <div class="stats-grid">
        <?php
        // Total de Membros
        $totalMembros = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Total de Escalas
        $totalEscalas = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();

        // Total de Músicas
        $totalMusicas = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
        ?>

        <div class="stat-card" style="border-color: #dbeafe; background: #eff6ff;">
            <div class="stat-value" style="color: #2563eb;"><?= $totalMembros ?></div>
            <div class="stat-label">Membros</div>
        </div>

        <div class="stat-card" style="border-color: #d1fae5; background: #ecfdf5;">
            <div class="stat-value" style="color: #047857;"><?= $totalEscalas ?></div>
            <div class="stat-label">Escalas</div>
        </div>

        <div class="stat-card" style="border-color: #fce7f3; background: #fdf2f8;">
            <div class="stat-value" style="color: #db2777;"><?= $totalMusicas ?></div>
            <div class="stat-label">Músicas</div>
        </div>

        <div class="stat-card" style="border-color: #ffedd5; background: #fff7ed;">
            <div class="stat-value" style="color: #ea580c;"><?= $niverCount ?></div>
            <div class="stat-label">Aniversários</div>
        </div>
    </div>


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
        <a href="repertorio.php" class="section-action">Ver tudo</a>
    </div>

    <div class="feed-card" style="background: #eff6ff; border: 1px solid #dbeafe;">
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
        </a>


    </div>

    <?php renderAppFooter(); ?>