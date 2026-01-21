<?php
// admin/escalas.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// --- Lógica de Backend ---
try {
    // Escalas Futuras
    $stmtFuture = $pdo->query("SELECT * FROM schedules WHERE event_date >= CURDATE() ORDER BY event_date ASC");
    $futureSchedules = $stmtFuture->fetchAll(PDO::FETCH_ASSOC);

    // Escalas Passadas (Histórico)
    $stmtPast = $pdo->query("SELECT * FROM schedules WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 10");
    $pastSchedules = $stmtPast->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

renderAppHeader('Escalas');
?>

<!-- Header Clean (Desktop & Mobile) -->
<header style="
    background: white; 
    padding: 20px 24px; 
    border-bottom: 1px solid #e2e8f0; 
    margin: -16px -16px 24px -16px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
">
    <div style="text-align: center; width: 100%;"> <!-- Centralizado estilo LouveApp -->
        <h1 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Escalas</h1>
        <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Louvor PIB Oliveira</p>
    </div>

    <!-- Filtros/Ações à direita (Desktop) ou Toolbar abaixo (Mobile) -->
</header>

<!-- Tabs Navegação (Estilo LouveApp - Verde) -->
<div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 32px; padding: 0 16px;">
    <button class="ripple" style="
        background: #dcfce7; 
        color: #166534; 
        border: none; 
        padding: 8px 32px; 
        border-radius: 20px; 
        font-weight: 700; 
        font-size: 0.9rem;
        box-shadow: 0 2px 6px rgba(22, 101, 52, 0.1);
    ">Próximas</button>
    <button class="ripple" style="
        background: transparent; 
        color: #64748b; 
        border: none; 
        padding: 8px 32px; 
        border-radius: 20px; 
        font-weight: 600; 
        font-size: 0.9rem;
    ">Anteriores</button>
</div>

<!-- Container Principal -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- Conteúdo Centralizado (Placeholder Empty State) -->
    <?php if (empty($futureSchedules)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div style="
                background: #f1f5f9; 
                width: 120px; height: 120px; 
                border-radius: 50%; 
                margin: 0 auto 24px auto; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="calendar" style="width: 48px; height: 48px; color: #94a3b8;"></i>
            </div>
            <h3 style="color: #334155; margin-bottom: 8px;">Lista vazia.</h3>
            <p style="color: #64748b; max-width: 300px; margin: 0 auto;">Para cadastrar uma escala, toque no botão (+).</p>
        </div>
    <?php else: ?>
        <!-- Lista de Escalas (Mantendo o card bonito de antes) -->
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
                <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="display: block; text-decoration: none; color: inherit;">
                    <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">

                        <!-- Data Box -->
                        <div style="text-align: center; min-width: 60px;">
                            <div style="font-size: 1.5rem; font-weight: 800; color: #334155; line-height: 1;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px;"><?= strtoupper($date->format('M')) ?></div>
                        </div>

                        <!-- Linha Vertical -->
                        <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>

                        <!-- Info -->
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                            <div style="display: flex; gap: 12px; color: #64748b; font-size: 0.85rem;">
                                <span><?= $songCount ?> Músicas</span>
                                <span>•</span>
                                <span><?= $teamCount ?> Membros</span>
                            </div>
                        </div>

                        <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Floating Action Button Clean -->
<a href="escala_adicionar.php" class="ripple" style="
    position: fixed;
    bottom: 80px; /* Acima da Bottom Bar no mobile */
    right: 24px;
    background: #166534; /* Verde Escuro */
    color: white;
    padding: 12px 24px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 101, 52, 0.3);
    text-decoration: none;
    font-weight: 600;
    z-index: 50;
    transition: transform 0.2s;
" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
    <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
    <span>Adicionar</span>
</a>

<style>
    @media (min-width: 1025px) {

        /* No desktop, o botão fica no canto inferior direito padrão */
        a[href="escala_adicionar.php"] {
            bottom: 32px;
        }
    }
</style>

<?php renderAppFooter(); ?>