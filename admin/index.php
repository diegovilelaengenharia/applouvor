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

renderAppHeader('Início');
?>

<!-- Estilos Específicos para Harmonia -->
<style>
    .dashboard-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        border-color: #e2e8f0;
    }

    .card-icon-bg {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        font-size: 1.2rem;
    }

    .card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.3;
    }

    .card-subtext {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .status-indicator {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .section-title {
        font-size: 1.1rem;
        color: #1e293b;
        font-weight: 800;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<!-- Hero Simples e Saudação -->
<div style="padding: 24px 20px 32px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 800;">Olá, <?= explode(' ', $_SESSION['user_name'] ?? 'Membro')[0] ?>! 👋</h1>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Bem-vindo ao LouveApp</p>
        </div>
        <?php renderGlobalNavButtons(); ?>
    </div>

    <!-- Banner Principal (Se tiver escala) -->
    <?php if ($nextSchedule):
        $date = new DateTime($nextSchedule['event_date']);
        $isToday = $date->format('Y-m-d') === date('Y-m-d');
    ?>
        <a href="escalas.php?mine=1" class="ripple" style="
            display: block;
            background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
            border-radius: 20px;
            padding: 24px;
            color: white;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(4, 120, 87, 0.2);
            position: relative;
            overflow: hidden;
            margin-bottom: 32px;
        ">
            <div style="position: absolute; right: -20px; bottom: -20px; opacity: 0.1;">
                <i data-lucide="mic-2" style="width: 120px; height: 120px;"></i>
            </div>

            <div style="position: relative; z-index: 1;">
                <div style="display: inline-block; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 12px; backdrop-filter: blur(4px);">
                    <?= $isToday ? 'É HOJE!' : 'PRÓXIMA ESCALA' ?>
                </div>
                <h2 style="margin: 0 0 8px 0; font-size: 1.4rem; font-weight: 700;"><?= htmlspecialchars($nextSchedule['event_type']) ?></h2>
                <div style="display: flex; align-items: center; gap: 8px; opacity: 0.9; font-size: 0.95rem;">
                    <i data-lucide="calendar" style="width: 16px;"></i>
                    <span><?= $date->format('d/m') ?> (<?= ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][$date->format('w')] ?>)</span>
                    <span>•</span>
                    <i data-lucide="clock" style="width: 16px;"></i>
                    <span>19:00</span>
                </div>
            </div>
        </a>
    <?php endif; ?>

    <!-- Grid de Sinalizações -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">

        <!-- Avisos (Card Harmônico) -->
        <a href="avisos.php" class="dashboard-card ripple">
            <?php if ($totalAvisos > 0): ?>
                <div class="status-indicator" style="background: #ef4444; box-shadow: 0 0 0 4px #fee2e2;"></div>
            <?php endif; ?>

            <div>
                <div class="card-icon-bg" style="background: #fff7ed; color: #f59e0b;">
                    <i data-lucide="bell" style="width: 24px;"></i>
                </div>
                <div class="card-title">Quadro de Avisos</div>
                <div class="card-value" style="font-size: 1rem; font-weight: 600;">
                    <?php if ($totalAvisos > 0): ?>
                        <?= mb_strimwidth($ultimoAviso, 0, 25, '...') ?>
                    <?php else: ?>
                        Tudo tranquilo
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-subtext">
                <?= $totalAvisos ?> comunicado<?= $totalAvisos != 1 ? 's' : '' ?>
            </div>
        </a>

        <!-- Aniversariantes (Card Harmônico) -->
        <a href="aniversarios.php" class="dashboard-card ripple">
            <?php if ($niverCount > 0): ?>
                <div class="status-indicator" style="background: #ec4899; box-shadow: 0 0 0 4px #fce7f3;"></div>
            <?php endif; ?>

            <div>
                <div class="card-icon-bg" style="background: #fdf2f8; color: #db2777;">
                    <i data-lucide="cake" style="width: 24px;"></i>
                </div>
                <div class="card-title">Aniversários</div>
                <div class="card-value">
                    <?= $niverCount > 0 ? "$niverCount celebrações" : "Ninguém este mês" ?>
                </div>
            </div>

            <div class="card-subtext">
                Em <?= strtolower(date('M')) ?>
            </div>
        </a>

        <!-- Minhas Escalas (Se não tiver destaque acima ou para ver todas) -->
        <a href="escalas.php?mine=1" class="dashboard-card ripple">
            <div>
                <div class="card-icon-bg" style="background: #eff6ff; color: #2563eb;">
                    <i data-lucide="calendar-check" style="width: 24px;"></i>
                </div>
                <div class="card-title">Minha Agenda</div>
                <div class="card-value">Ver escalas</div>
            </div>
            <div class="card-subtext">
                Planejamento pessoal
            </div>
        </a>

    </div>

    <!-- Quick Links / Atalhos Sutis -->
    <div style="margin-top: 32px;">
        <div class="section-title" style="font-size: 0.9rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Acesso Rápido</div>

        <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px;">
            <a href="repertorio.php" class="ripple" style="
                flex: 0 0 auto;
                background: white; border: 1px solid #e2e8f0; 
                padding: 12px 20px; border-radius: 50px; 
                color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem;
                display: flex; align-items: center; gap: 8px;
            ">
                <i data-lucide="music-2" style="width: 18px;"></i> Repertório
            </a>
            <a href="indisponibilidade.php" class="ripple" style="
                flex: 0 0 auto;
                background: white; border: 1px solid #e2e8f0; 
                padding: 12px 20px; border-radius: 50px; 
                color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem;
                display: flex; align-items: center; gap: 8px;
            ">
                <i data-lucide="calendar-off" style="width: 18px;"></i> Avisar Ausência
            </a>
        </div>
    </div>

</div>

<div style="height: 60px;"></div>
<?php renderAppFooter(); ?>