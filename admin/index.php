<?php
// admin/index.php
header('Content-Type: text/html; charset=utf-8');
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/dashboard_cards.php';
require_once '../includes/dashboard_render.php';

// 1. Carregar Dados Completos
$renderData = require 'dashboard_data.php';
extract($renderData);

// --- LÓGICA DE AVISO URGENTE (Original) ---
$popupAviso = null;
if (!empty($avisos)) {
    foreach ($avisos as $av) {
        if (($av['priority'] ?? '') === 'urgent') {
            $popupAviso = $av;
            break;
        }
    }
}

renderAppHeader('Dashboard');
?>

<!-- MODAL URGENTE (Premium Style) -->
<?php if ($popupAviso): ?>
<div id="urgentModal" style="display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px;">
    <div class="animate-card" style="background: var(--color-surface); width: 100%; max-width: 400px; border-radius: var(--radius-xl); padding: 24px; text-align: center; box-shadow: var(--shadow-xl); border-top: 6px solid #ef4444;">
        <div style="background: #fee2e2; color: #dc2626; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <i data-lucide="alert-triangle" width="28"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.25rem; font-weight: 800; color: var(--color-text);">Aviso Urgente</h3>
        <p style="margin: 0 0 16px 0; font-weight: 700; color: #b91c1c;"><?= htmlspecialchars($popupAviso['title']) ?></p>
        <div style="text-align: left; background: var(--color-surface-alt); padding: 16px; border-radius: var(--radius-md); font-size: 0.9rem; color: var(--color-text); margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
            <?= nl2br(htmlspecialchars($popupAviso['message'] ?? '')) ?>
        </div>
        <button onclick="closeUrgentModal()" class="btn-hero btn-hero-confirm" style="width: 100%; background: #ef4444;">Entendido</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!sessionStorage.getItem('seen_urgent_<?= $popupAviso['id'] ?>')) {
            document.getElementById('urgentModal').style.display = 'flex';
        }
    });
    function closeUrgentModal() {
        document.getElementById('urgentModal').style.display = 'none';
        sessionStorage.setItem('seen_urgent_<?= $popupAviso['id'] ?>', 'true');
    }
</script>
<?php endif; ?>

<?php
// 2. Organizar Cards por Categoria (Sistema Original)
$groupedCards = [
    'gestao' => ['escalas', 'repertorio', 'membros', 'agenda', 'historico', 'metronomo'],
    'espiritualidade' => ['leitura', 'devocional', 'oracao'],
    'comunicacao' => ['avisos', 'aniversariantes']
];

$categoryNames = [
    'gestao' => 'Gestão e Equipe',
    'espiritualidade' => 'Vida Cristã',
    'comunicacao' => 'Comunicação'
];
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
        gap: var(--space-md);
    }
    
    @media (min-width: 768px) {
        .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .category-section { margin-bottom: var(--space-xl); }
    .category-title {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--color-text-muted);
        letter-spacing: 1.5px;
        margin-bottom: var(--space-md);
        padding-left: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .category-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--color-border);
    }
</style>

<div class="dashboard-container" style="padding: var(--space-md) var(--space-md) 100px;">
    
    <!-- HEADER PREMIUM -->
    <div style="margin-bottom: var(--space-lg); display: flex; align-items: center; justify-content: space-between;" class="animate-card">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0;"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>!</h2>
            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin: 0;">Bem-vindo ao Painel <?= $userRole === 'admin' ? 'do Líder' : 'do Músico' ?></p>
        </div>
        <a href="perfil.php">
            <img src="<?= $userPhoto ?>" style="width: 52px; height: 52px; border-radius: 50%; border: 3px solid var(--color-primary); object-fit: cover; box-shadow: var(--shadow-md);">
        </a>
    </div>

    <!-- BADGE: Sugestões de Música Pendentes (admin only) -->
    <?php if ($pendingSuggestions > 0 && $_SESSION['user_role'] === 'admin'): ?>
    <a href="sugestoes_musicas.php" style="
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;margin-bottom:var(--space-md);
        background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;
        text-decoration:none;color:#92400e;">
        <span style="background:#f97316;color:#fff;font-size:.75rem;font-weight:800;
                     padding:3px 9px;border-radius:20px;">
            <?= $pendingSuggestions ?>
        </span>
        <span style="font-size:.875rem;font-weight:600;">
            sugestão<?= $pendingSuggestions > 1 ? 'ões' : '' ?> de música pendente<?= $pendingSuggestions > 1 ? 's' : '' ?>
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="#f97316" stroke-width="2" style="margin-left:auto;">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
    </a>
    <?php endif; ?>

    <!-- RENDERIZAÇÃO DINÂMICA POR CATEGORIAS (Original Logic) -->
    <?php foreach ($groupedCards as $catId => $cards): ?>
        <section class="category-section">
            <div class="category-title"><?= $categoryNames[$catId] ?></div>
            <div class="dashboard-grid">
                <?php 
                foreach ($cards as $cardId) {
                    renderDashboardCard($cardId, $renderData);
                }
                ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>

<?php if ($userRole === 'admin'): ?>
<?php
// Buscar escalas nos próximos 2 dias com participantes pending
$upcomingWithPending = [];
try {
    $stmtReminder = $pdo->prepare("
        SELECT s.id, s.event_type, s.event_date, s.event_time,
               COUNT(su.user_id) as pending_count
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE s.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
          AND su.status = 'pending'
        GROUP BY s.id
        HAVING pending_count > 0
    ");
    $stmtReminder->execute();
    $upcomingWithPending = $stmtReminder->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<?php if (!empty($upcomingWithPending)): ?>
<div style="margin: 0 var(--space-md) var(--space-md);">
    <div class="pib-card" style="border-left: 4px solid #f97316; padding: 16px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <i data-lucide="bell" width="20" style="color: #f97316;"></i>
            <span style="font-weight: 800; font-size: 0.95rem;">Lembretes Pendentes</span>
        </div>
        <?php foreach ($upcomingWithPending as $upcoming): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border);">
            <div>
                <div style="font-weight: 700; font-size: 0.9rem;"><?= htmlspecialchars($upcoming['event_type']) ?></div>
                <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                    <?= date('d/m', strtotime($upcoming['event_date'])) ?> as <?= substr($upcoming['event_time'], 0, 5) ?>
                    &mdash; <?= (int)$upcoming['pending_count'] ?> sem confirmar
                </div>
            </div>
            <button
                onclick="sendReminder(<?= (int)$upcoming['id'] ?>, this)"
                style="background: #f97316; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; font-size: 0.8rem; cursor: pointer; min-height: 44px; white-space: nowrap;">
                <i data-lucide="send" width="14"></i> Lembrar
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function sendReminder(scheduleId, btn) {
    btn.disabled = true;
    btn.textContent = 'Enviando...';
    fetch('../api/send_reminders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ schedule_id: scheduleId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.textContent = 'Enviado!';
            btn.style.background = '#16a34a';
        } else {
            btn.textContent = 'Erro';
            btn.style.background = '#dc2626';
            btn.disabled = false;
            alert('Erro: ' + (data.message || 'Tente novamente.'));
        }
    })
    .catch(function() {
        btn.textContent = 'Erro';
        btn.disabled = false;
    });
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php renderAppFooter(); ?>
