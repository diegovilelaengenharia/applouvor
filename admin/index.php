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
?>

<!-- Estilos Específicos para a Nova Home -->
<style>
    /* Card Hover Effect */
    .interact-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        transform: translateY(0);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid transparent;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .interact-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 10;
    }

    /* Gradient Backgrounds & Text */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .bg-gradient-danger {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    /* Glass Effect Element */
    .glass-pill {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Icon Circle */
    .icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 12px;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .interact-card:hover .icon-circle {
        transform: scale(1.1) rotate(5deg);
    }

    /* Typography */
    .card-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 4px;
    }

    .card-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: white;
        line-height: 1.2;
    }
</style>

<div style="max-width: 900px; margin: 0 auto; padding: 16px;">

    <!-- 1. HERO SECTION: Saudação e Próxima Escala -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 4px;">
            <?= $saudacao ?>, <span style="color: #4f46e5;"><?= $nomeUser ?></span>! 👋
        </h1>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 24px;">
            Aqui está o que está rolando no ministério hoje.
        </p>

        <?php if ($nextSchedule):
            $date = new DateTime($nextSchedule['event_date']);
            $isToday = $date->format('Y-m-d') === date('Y-m-d');
        ?>
            <!-- CARD HERO -->
            <a href="escalas.php?mine=1" class="interact-card" style="
                display: block;
                background: linear-gradient(120deg, #4f46e5, #ec4899);
                border-radius: 24px;
                padding: 28px;
                text-decoration: none;
                color: white;
                position: relative;
            ">
                <!-- Decorative Circle -->
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.1);"></div>

                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div class="glass-pill" style="display: inline-flex; align-items: center; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 12px; color: white;">
                                <?php if ($isToday): ?>
                                    <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 10px #4ade80;"></span>
                                    É HOJE!
                                <?php else: ?>
                                    PRÓXIMA ESCALA
                                <?php endif; ?>
                            </div>

                            <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0 0 8px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <?= htmlspecialchars($nextSchedule['event_type']) ?>
                            </h2>

                            <div style="display: flex; gap: 16px; font-size: 1rem; opacity: 0.95; font-weight: 500;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="calendar" style="width: 18px;"></i>
                                    <?= $date->format('d/m') ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="clock" style="width: 18px;"></i>
                                    19:00
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <i data-lucide="mic-2" style="width: 32px; height: 32px; color: white;"></i>
                        </div>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div style="background: #f1f5f9; border-radius: 20px; padding: 24px; text-align: center; color: #64748b;">
                <i data-lucide="coffee" style="width: 32px; height: 32px; margin-bottom: 8px; color: #94a3b8;"></i>
                <p>Nenhuma escala agendada para os próximos dias. Descanse!</p>
            </div>
        <?php endif; ?>
    </div>


    <!-- 2. GRID INFO: Cards Coloridos -->
    <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 16px; letter-spacing: 0.5px; text-transform: uppercase;">Visão Geral</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px;">

        <!-- Avisos -->
        <a href="avisos.php" class="interact-card bg-gradient-warning" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #d97706;">
                <i data-lucide="bell" style="width: 24px;"></i>
            </div>
            <div class="card-label">Mural</div>
            <div class="card-value">
                <?= $totalAvisos > 0 ? $totalAvisos . ' novos' : 'Em dia' ?>
            </div>
        </a>

        <!-- Aniversários -->
        <a href="aniversarios.php" class="interact-card bg-gradient-danger" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #db2777;">
                <i data-lucide="cake" style="width: 24px;"></i>
            </div>
            <div class="card-label">Nivers de <?= strtolower(strftime('%b')) ?></div>
            <div class="card-value">
                <?= $niverCount > 0 ? $niverCount . ' festa(s)' : 'Nenhum' ?>
            </div>
        </a>

        <!-- Minhas Escalas -->
        <a href="escalas.php?mine=1" class="interact-card bg-gradient-info" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #2563eb;">
                <i data-lucide="calendar-check" style="width: 24px;"></i>
            </div>
            <div class="card-label">Minha Agenda</div>
            <div class="card-value">Ver tudo</div>
        </a>

    </div>

    <!-- 3. ACTIONS: Botões Rápidos e Limpos -->
    <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 16px; letter-spacing: 0.5px; text-transform: uppercase;">Acesso Rápido</h3>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">

        <a href="repertorio.php" class="ripple" style="
            flex: 1; min-width: 140px;
            background: white; border: 1px solid #e2e8f0;
            padding: 16px; border-radius: 16px;
            text-decoration: none; color: #475569;
            display: flex; align-items: center; gap: 12px;
            font-weight: 600; font-size: 0.95rem;
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'">
            <div style="background: #f1f5f9; padding: 8px; border-radius: 10px;">
                <i data-lucide="music-2" style="width: 20px; color: #64748b;"></i>
            </div>
            Repertório
        </a>

        <a href="indisponibilidade.php" class="ripple" style="
            flex: 1; min-width: 140px;
            background: white; border: 1px solid #e2e8f0;
            padding: 16px; border-radius: 16px;
            text-decoration: none; color: #475569;
            display: flex; align-items: center; gap: 12px;
            font-weight: 600; font-size: 0.95rem;
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#cbd5e1'" onmouseout="this.style.borderColor='#e2e8f0'">
            <div style="background: #fff1f2; padding: 8px; border-radius: 10px;">
                <i data-lucide="calendar-off" style="width: 20px; color: #be123c;"></i>
            </div>
            Avisar Ausência
        </a>

    </div>

</div>

<div style="height: 60px;"></div>

<?php renderAppFooter(); ?>