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
            <h2 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); margin: 0; color: var(--text-primary);"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>!</h2>
            <p style="color: var(--text-muted); font-size: var(--font-size-sm); margin: 0;">Bem-vindo ao Painel <?= $userRole === 'admin' ? 'do Líder' : 'do Músico' ?></p>
        </div>
        <a href="perfil.php">
            <img src="<?= $userPhoto ?>" style="width: 52px; height: 52px; border-radius: 50%; border: 3px solid var(--primary); object-fit: cover; box-shadow: var(--shadow-md);">
        </a>
    </div>

    <!-- BADGE: Sugestões de Música Pendentes (admin only) -->
    <?php if ($pendingSuggestions > 0 && $_SESSION['user_role'] === 'admin'): ?>
    <a href="sugestoes_musicas.php" class="pib-card" style="border-left: 5px solid var(--orange-500); margin-bottom: var(--space-md); flex-direction: row; align-items: center; justify-content: space-between; padding: var(--space-md);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="pib-badge" style="background: var(--orange-500); color: white;">
                <?= $pendingSuggestions ?>
            </span>
            <span style="font-size: var(--text-sm); font-weight: var(--font-weight-semibold); color: var(--text-primary);">
                sugestão<?= $pendingSuggestions > 1 ? 'ões' : '' ?> de música pendente<?= $pendingSuggestions > 1 ? 's' : '' ?>
            </span>
        </div>
        <i data-lucide="chevron-right" style="color: var(--orange-500); width: 18px;"></i>
    </a>
    <?php endif; ?>

    <!-- WIDGET: Versículo da Semana (mais recente aviso type=versiculo) -->
    <?php
    $weeklyVerse = null;
    try {
        $stmtV = $pdo->query("SELECT title, message FROM avisos WHERE type = 'versiculo' AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 1");
        $weeklyVerse = $stmtV->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    if ($weeklyVerse):
    ?>
    <div class="pib-card" style="border-left: 5px solid var(--lavender-600); margin-bottom: var(--space-md); background: var(--color-surface);">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <i data-lucide="book" style="color: var(--lavender-600); width: 18px;"></i>
            <span style="font-size: 0.7rem; font-weight: var(--font-weight-bold); text-transform: uppercase; letter-spacing: 0.08em; color: var(--lavender-600);">Versículo da Semana</span>
        </div>
        <p style="margin:0 0 6px; font-size: 0.95rem; font-weight: var(--font-weight-bold); color: var(--text-primary); line-height: 1.4;"><?= htmlspecialchars($weeklyVerse['title']) ?></p>
        <p style="margin:0; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; font-style: italic;"><?= nl2br(htmlspecialchars($weeklyVerse['message'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- WIDGET: Pedidos de Oração da Equipe (3 mais recentes, não respondidos) -->
    <?php
    $prayerRequests = [];
    try {
        $stmtP = $pdo->query("
            SELECT pr.title, pr.is_urgent, pr.is_anonymous, pr.prayer_count, u.name as author
            FROM prayer_requests pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.is_answered = 0
            ORDER BY pr.is_urgent DESC, pr.created_at DESC
            LIMIT 3
        ");
        $prayerRequests = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    if (!empty($prayerRequests)):
    ?>
    <a href="oracao.php" class="pib-card" style="border-left: 5px solid var(--red-500); margin-bottom: var(--space-md); text-decoration: none;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <i data-lucide="heart" style="color: var(--red-500); width: 18px; fill: var(--red-500);"></i>
            <span style="font-size: 0.7rem; font-weight: var(--font-weight-bold); text-transform: uppercase; letter-spacing: 0.08em; color: var(--red-600);">Orando juntos</span>
            <span style="margin-left:auto; font-size: 0.7rem; color: var(--red-500); font-weight: var(--font-weight-semibold);">Ver todos &rarr;</span>
        </div>
        <?php foreach ($prayerRequests as $pr): ?>
        <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-top:1px solid var(--border-subtle);">
            <?php if ($pr['is_urgent']): ?>
            <span style="font-size: 0.8rem; color: var(--red-500);">🔥</span>
            <?php endif; ?>
            <span style="flex:1; font-size: 0.85rem; font-weight: var(--font-weight-semibold); color: var(--text-primary);"><?= htmlspecialchars($pr['title']) ?></span>
            <span style="font-size: 0.75rem; color: var(--red-500); font-weight: var(--font-weight-medium);"><?= (int)$pr['prayer_count'] ?> 🙏</span>
        </div>
        <?php endforeach; ?>
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
    <div class="pib-card" style="border-left: 5px solid var(--orange-500); padding: var(--space-md);">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <i data-lucide="bell" style="color: var(--orange-500); width: 20px;"></i>
            <span style="font-weight: var(--font-weight-bold); font-size: 0.95rem; color: var(--text-primary);">Lembretes Pendentes</span>
        </div>
        <?php foreach ($upcomingWithPending as $upcoming): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-subtle);">
            <div>
                <div style="font-weight: var(--font-weight-semibold); font-size: 0.9rem; color: var(--text-primary);"><?= htmlspecialchars($upcoming['event_type']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                    <?= date('d/m', strtotime($upcoming['event_date'])) ?> as <?= substr($upcoming['event_time'], 0, 5) ?>
                    &mdash; <?= (int)$upcoming['pending_count'] ?> sem confirmar
                </div>
            </div>
            <button
                onclick="sendReminder(<?= (int)$upcoming['id'] ?>, this)"
                style="background: var(--orange-500); color: white; border: none; border-radius: var(--radius-sm); padding: 8px 14px; font-weight: var(--font-weight-bold); font-size: 0.8rem; cursor: pointer; min-height: 44px; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="send" style="width: 14px;"></i> Lembrar
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
