<?php
// admin/escalas.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// --- Lógica de Backend ---
try {
    // Escalas Futuras
    $stmtFuture = $pdo->query("
        SELECT * FROM schedules 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC
    ");
    $futureSchedules = $stmtFuture->fetchAll(PDO::FETCH_ASSOC);

    // Escalas Passadas (Histórico)
    $stmtPast = $pdo->query("
        SELECT * FROM schedules 
        WHERE event_date < CURDATE() 
        ORDER BY event_date DESC 
        LIMIT 10
    ");
    $pastSchedules = $stmtPast->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas Rápidas
    $totalFuture = count($futureSchedules);
    $nextEvent = $totalFuture > 0 ? new DateTime($futureSchedules[0]['event_date']) : null;
} catch (PDOException $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

renderAppHeader('Escalas');
?>

<!-- Hero Header com Design System -->
<div style="
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
    margin: -16px -16px 32px -16px; 
    padding: 32px 24px 48px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: relative;
    color: white;
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.02em;">Escalas</h1>
            <p style="margin: 8px 0 0 0; opacity: 0.8; font-size: 1rem;">Gerencie as datas e a equipe do louvor.</p>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 16px; backdrop-filter: blur(4px);">
            <i data-lucide="calendar" style="width: 24px; height: 24px; color: white;"></i>
        </div>
    </div>
</div>

<!-- Container Principal -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- Seção: Próximos Eventos -->
    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 16px; font-weight: 700;">Próximos Eventos</h2>

        <?php if (empty($futureSchedules)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 16px; border: 2px dashed #e2e8f0;">
                <i data-lucide="calendar-off" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <p style="color: #64748b; margin: 0; font-weight: 500;">Nenhuma escala agendada.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Buscar contagens
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $teamCount = $stmtCount->fetchColumn();

                    $stmtSongs = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                    $stmtSongs->execute([$schedule['id']]);
                    $songCount = $stmtSongs->fetchColumn();
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="
                    display: flex; 
                    background: white; 
                    border-radius: 16px; 
                    text-decoration: none; 
                    color: inherit; 
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                    transition: transform 0.2s, box-shadow 0.2s;
                    overflow: hidden;
                    border: 1px solid #f1f5f9;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">

                        <!-- Calendar Box -->
                        <div style="
                        padding: 16px 24px; 
                        background: <?= $isToday ? '#ecfdf5' : '#f8fafc' ?>; 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                        justify-content: center;
                        border-right: 1px solid #f1f5f9;
                        min-width: 90px;
                    ">
                            <span style="font-size: 1.5rem; font-weight: 800; color: <?= $isToday ? '#047857' : '#1e293b' ?>; line-height: 1;">
                                <?= $date->format('d') ?>
                            </span>
                            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: <?= $isToday ? '#059669' : '#64748b' ?>; margin-top: 4px;">
                                <?= strtoupper($date->format('M')) ?>
                            </span>
                        </div>

                        <!-- Content -->
                        <div style="padding: 16px 20px; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                            <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">
                                <?= htmlspecialchars($schedule['event_type']) ?>
                            </h3> <!-- CORRIGIDO: Usando event_type em vez de title -->

                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <?php if ($isToday): ?>
                                    <span style="background: #be123c; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">HOJE</span>
                                <?php endif; ?>

                                <span style="display: flex; align-items: center; gap: 4px; font-size: 0.85rem; color: #64748b;">
                                    <i data-lucide="music" style="width: 14px;"></i> <?= $songCount ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 4px; font-size: 0.85rem; color: #64748b;">
                                    <i data-lucide="users" style="width: 14px;"></i> <?= $teamCount ?>
                                </span>

                                <!-- Status Badge (Opcional, lógica simples) -->
                                <?php if ($songCount == 0): ?>
                                    <span style="background: #fef2f2; color: #ef4444; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;">Pendente</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <div style="padding: 0 20px; display: flex; align-items: center; color: #cbd5e1;">
                            <i data-lucide="chevron-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seção: Histórico -->
    <?php if (!empty($pastSchedules)): ?>
        <div style="margin-bottom: 80px;">
            <h2 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 16px; font-weight: 700;">Histórico Recente</h2>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="
                display: flex; 
                background: white; 
                border-radius: 12px; 
                text-decoration: none; 
                align-items: center;
                padding: 12px 16px;
                opacity: 0.8;
                border: 1px solid #f1f5f9;
            ">
                        <div style="
                    background: #f1f5f9; 
                    border-radius: 8px; 
                    padding: 8px 12px; 
                    text-align: center;
                    margin-right: 16px;
                ">
                            <div style="font-weight: 700; color: #64748b; font-size: 1rem;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.65rem; text-transform: uppercase; color: #94a3b8;"><?= strtoupper($date->format('M')) ?></div>
                        </div>

                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($schedule['event_type']) ?></div> <!-- CORRIGIDO -->
                            <div style="font-size: 0.8rem; color: #94a3b8;">Culto Passado</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Floating Action Button -->
<a href="escala_adicionar.php" class="ripple" style="
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    background: #0f172a;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3);
    z-index: 50;
    text-decoration: none;
    transition: transform 0.2s;
" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</a>

<?php renderAppFooter(); ?>