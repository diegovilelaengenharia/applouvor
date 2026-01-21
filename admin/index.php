<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Configurações do Usuário
$userId = $_SESSION['user_id'] ?? 1;

// 1. BUSCAR AVISOS (Recentes)
$recentAvisos = [];
try {
    $stmtA = $pdo->query("
        SELECT a.*, u.name as author_name
        FROM avisos a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.archived_at IS NULL
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 3
    ");
    $recentAvisos = $stmtA->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Silêncio se tabela não existir */
}

// 2. BUSCAR MINHAS ESCALAS (Futuras)
$mySchedules = [];
try {
    $stmtS = $pdo->prepare("
        SELECT s.* 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 5
    ");
    $stmtS->execute([$userId]);
    $mySchedules = $stmtS->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Silêncio */
}

// 3. BUSCAR ANIVERSARIANTES DO MÊS
$birthdays = [];
try {
    // Busca usuários que fazem aniversário no mês atual
    // birth_date deve ser DATE ou VARCHAR YYYY-MM-DD
    $stmtB = $pdo->prepare("
        SELECT name, photo, birth_date, DAY(birth_date) as day
        FROM users 
        WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())
        ORDER BY DAY(birth_date) ASC
    ");
    $stmtB->execute();
    $birthdays = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Silêncio */
}

renderAppHeader('Início');
?>

<!-- Hero Section Minimalista -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 24px -16px; 
    padding: 32px 24px 48px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: relative;
">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">LouveApp</h1>
            <p style="color: rgba(255,255,255,0.8); margin: 4px 0 0 0; font-size: 0.9rem;">Visão Geral</p>
        </div>

        <?php renderGlobalNavButtons(); ?>
    </div>
</div>

<div style="max-width: 800px; margin: -30px auto 0 auto; padding: 0 16px; position: relative; z-index: 10;">

    <!-- SEÇÃO 1: AVISOS -->
    <div style="background: white; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="bell" style="width: 18px; color: #f59e0b;"></i> Avisos
            </h3>
            <a href="avisos.php" style="font-size: 0.8rem; color: #059669; text-decoration: none; font-weight: 600;">Ver todos</a>
        </div>

        <?php if (empty($recentAvisos)): ?>
            <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 0.9rem; background: #f8fafc; border-radius: 12px;">
                <i data-lucide="inbox" style="width: 24px; margin-bottom: 8px; opacity: 0.5;"></i>
                <br>Nenhum aviso no momento.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($recentAvisos as $aviso):
                    $priorityColor = ($aviso['priority'] == 'urgent') ? '#fee2e2' : (($aviso['priority'] == 'important') ? '#fef3c7' : '#f1f5f9');
                    $iconColor = ($aviso['priority'] == 'urgent') ? '#ef4444' : (($aviso['priority'] == 'important') ? '#d97706' : '#64748b');
                ?>
                    <div style="
                        background: <?= $priorityColor ?>; 
                        border-radius: 12px; 
                        padding: 16px; 
                        display: flex; 
                        gap: 12px;
                        transition: transform 0.2s;
                    " class="ripple">
                        <div style="min-width: 24px; color: <?= $iconColor ?>;">
                            <i data-lucide="<?= ($aviso['type'] == 'event') ? 'calendar' : 'info' ?>" style="width: 20px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px;">
                                <?= htmlspecialchars($aviso['title']) ?>
                            </div>
                            <div style="color: #475569; font-size: 0.85rem; line-height: 1.4;">
                                <?= mb_strimwidth(strip_tags($aviso['message']), 0, 100, '...') ?>
                            </div>
                            <div style="margin-top: 8px; font-size: 0.75rem; color: #64748b; font-weight: 500;">
                                <?= date('d/m', strtotime($aviso['created_at'])) ?> • <?= htmlspecialchars($aviso['author_name']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SEÇÃO 2: MINHAS ESCALAS -->
    <div style="background: white; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="calendar" style="width: 18px; color: #2563eb;"></i> Minhas Escalas
            </h3>
            <a href="escalas.php?mine=1" style="font-size: 0.8rem; color: #2563eb; text-decoration: none; font-weight: 600;">Ver todas</a>
        </div>

        <?php if (empty($mySchedules)): ?>
            <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 0.9rem; background: #f8fafc; border-radius: 12px;">
                <i data-lucide="calendar-off" style="width: 24px; margin-bottom: 8px; opacity: 0.5;"></i>
                <br>Você não está escalado(a) em breve.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($mySchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" style="text-decoration: none;">
                        <div style="
                            background: white; 
                            border: 1px solid #e2e8f0; 
                            border-radius: 12px; 
                            padding: 12px; 
                            display: flex; 
                            align-items: center; 
                            gap: 16px;
                            transition: background 0.2s;
                        " class="ripple">
                            <div style="text-align: center; min-width: 45px;">
                                <div style="font-weight: 800; font-size: 1.1rem; color: #1e293b; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase;"><?= strtoupper($date->format('M')) ?></div>
                            </div>
                            <div style="width: 2px; height: 32px; background: <?= $isToday ? '#166534' : '#f1f5f9' ?>;"></div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #334155; font-size: 0.9rem;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                    <?php if ($isToday): ?>
                                        <span style="background: #166534; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.6rem; margin-left: 6px;">HOJE</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">
                                    <?= ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')] ?>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" style="width: 16px; color: #cbd5e1;"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SEÇÃO 3: ANIVERSARIANTES -->
    <div style="background: white; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="cake" style="width: 18px; color: #ec4899;"></i> Aniversariantes do Mês
            </h3>
            <a href="aniversarios.php" style="font-size: 0.8rem; color: #ec4899; text-decoration: none; font-weight: 600;">Ver todos</a>
        </div>

        <?php if (empty($birthdays)): ?>
            <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 0.9rem; background: #f8fafc; border-radius: 12px;">
                <i data-lucide="calendar-heart" style="width: 24px; margin-bottom: 8px; opacity: 0.5;"></i>
                <br>Nenhum aniversariante encontrado.
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; scroll-behavior: smooth;">
                <?php foreach ($birthdays as $bday):
                    $photo = $bday['photo'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($bday['name']) . '&background=fce7f3&color=be185d';
                    if (strpos($photo, 'http') === false && strpos($photo, 'assets') === false) {
                        $photo = '../assets/img/' . $photo;
                    }
                ?>
                    <div style="
                        flex: 0 0 auto; 
                        text-align: center; 
                        width: 80px;
                        background: #fff;
                    ">
                        <div style="position: relative; display: inline-block;">
                            <img src="<?= htmlspecialchars($photo) ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div style="
                                position: absolute; bottom: -4px; right: -4px; 
                                background: #ec4899; color: white; 
                                font-size: 0.65rem; font-weight: bold; 
                                padding: 2px 6px; border-radius: 10px;
                                border: 2px solid white;
                            ">
                                <?= $bday['day'] ?>
                            </div>
                        </div>
                        <div style="
                            font-size: 0.75rem; font-weight: 600; color: #334155; 
                            margin-top: 6px; 
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                        ">
                            <?= explode(' ', $bday['name'])[0] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Ajuste para padding final -->
<div style="height: 40px;"></div>

<?php renderAppFooter(); ?>
```