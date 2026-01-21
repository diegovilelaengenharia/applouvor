<?php
// admin/escalas.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Deletar Escala
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$id]);
    header('Location: escalaa.php');
    exit;
}

// Buscar Escalas Futuras
$stmt = $pdo->query("
    SELECT s.*, 
    (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as song_count,
    (SELECT COUNT(*) FROM schedule_users WHERE schedule_id = s.id) as user_count
    FROM schedules s 
    WHERE event_date >= CURDATE() 
    ORDER BY event_date ASC
");
$upcomingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar Escalas Passadas (Histórico)
$stmtHistory = $pdo->query("
    SELECT s.* 
    FROM schedules s 
    WHERE event_date < CURDATE() 
    ORDER BY event_date DESC 
    LIMIT 10
");
$pastSchedules = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Escalas');
?>

<style>
    .schedule-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        border: 1px solid #f1f5f9;
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .schedule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        border-color: #e2e8f0;
    }

    .date-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        min-width: 70px;
        height: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .date-day {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .date-month {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    .info-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    .section-title {
        font-size: 0.9rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 32px 0 16px 8px;
    }
</style>

<!-- Hero Section -->
<div style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%); margin: -24px -16px 32px -16px; padding: 32px 24px 64px 24px; border-radius: 0 0 32px 32px; color: white; position: relative; box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.5);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px;">Escalas</h1>
            <p style="margin-top: 8px; font-size: 1rem; opacity: 0.8;">Gerencie as datas e a equipe do louvor.</p>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 14px; backdrop-filter: blur(5px);">
            <i data-lucide="calendar" style="color: white; width: 24px; height: 24px;"></i>
        </div>
    </div>
</div>

<div style="margin-top: -40px; padding: 0 8px; padding-bottom: 80px;">

    <?php if (empty($upcomingSchedules)): ?>
        <div style="background: white; border-radius: 20px; padding: 40px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="background: #f1f5f9; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="calendar-off" style="width: 32px; color: #94a3b8;"></i>
            </div>
            <h3 style="color: #334155; font-size: 1.1rem; margin-bottom: 8px;">Nenhuma escala agendada</h3>
            <p style="color: #64748b; font-size: 0.95rem;">Clique no botão abaixo para criar a primeira escala.</p>
        </div>
    <?php else: ?>

        <div class="section-title">Próximos Eventos</div>

        <?php foreach ($upcomingSchedules as $schedule):
            $dateObj = new DateTime($schedule['event_date']);
        ?>
            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="schedule-card ripple">
                <div class="date-box">
                    <div class="date-day"><?= $dateObj->format('d') ?></div>
                    <div class="date-month"><?= strftime('%b', $dateObj->getTimestamp()) ?></div>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 6px;">
                        <?= htmlspecialchars($schedule['title'] ?: 'Culto de Louvor') ?>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if ($schedule['song_count'] > 0): ?>
                            <span class="info-badge">
                                <i data-lucide="music" style="width: 14px;"></i> <?= $schedule['song_count'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($schedule['user_count'] > 0): ?>
                            <span class="info-badge">
                                <i data-lucide="users" style="width: 14px;"></i> <?= $schedule['user_count'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if (empty($schedule['song_count']) && empty($schedule['user_count'])): ?>
                            <span class="info-badge" style="background: #fef2f2; color: #dc2626;">Pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
                <i data-lucide="chevron-right" style="width: 20px; color: #cbd5e1;"></i>
            </a>
        <?php endforeach; ?>

    <?php endif; ?>

    <?php if (!empty($pastSchedules)): ?>
        <div class="section-title">Histórico Recente</div>
        <?php foreach ($pastSchedules as $schedule):
            $dateObj = new DateTime($schedule['event_date']);
        ?>
            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="schedule-card ripple" style="opacity: 0.8; background: #f8fafc;">
                <div class="date-box" style="background: #e2e8f0; border-color: #cbd5e1;">
                    <div class="date-day" style="color: #64748b;"><?= $dateObj->format('d') ?></div>
                    <div class="date-month" style="color: #94a3b8;"><?= strftime('%b', $dateObj->getTimestamp()) ?></div>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #64748b; font-size: 1rem; margin-bottom: 4px;">
                        <?= htmlspecialchars($schedule['title'] ?: 'Culto Passado') ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- FAB Adicionar -->
<a href="escala_criar.php" class="ripple" style="
    position: fixed; 
    bottom: 24px; 
    right: 24px; 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #0f172a 0%, #334155 100%); 
    color: white; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.4);
    z-index: 100;
    transition: transform 0.2s;
">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</a>

<?php renderAppFooter(); ?>