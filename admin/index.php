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
renderPageHeader('Dashboard', 'Visão Geral');

// Definição dos atalhos rápidos táteis
$shortcuts = [
    [
        'title' => 'Escalas',
        'url' => 'escalas.php',
        'icon' => 'calendar',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Repertório',
        'url' => 'repertorio.php',
        'icon' => 'music-2',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Histórico',
        'url' => 'historico.php',
        'icon' => 'history',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Membros',
        'url' => 'membros.php',
        'icon' => 'users',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Ausências',
        'url' => 'indisponibilidade.php',
        'icon' => 'calendar-off',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Agenda',
        'url' => 'agenda.php',
        'icon' => 'calendar-range',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Metrônomo',
        'url' => 'metronomo.php',
        'icon' => 'timer',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Devocional',
        'url' => 'devocionais.php',
        'icon' => 'book-heart',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Oração',
        'url' => 'oracao.php',
        'icon' => 'heart',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Bíblia',
        'url' => 'leitura.php',
        'icon' => 'book-open',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Avisos',
        'url' => 'avisos.php',
        'icon' => 'megaphone',
        'category' => 'comunicacao',
        'color' => 'var(--amber-600)',
        'bg' => 'rgba(245, 158, 11, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Aniversários',
        'url' => 'aniversarios.php',
        'icon' => 'cake',
        'category' => 'comunicacao',
        'color' => 'var(--amber-600)',
        'bg' => 'rgba(245, 158, 11, 0.08)',
        'admin_only' => false
    ],
    [
        'title' => 'Gestão Escalas',
        'url' => 'escalas_gestao.php',
        'icon' => 'sliders',
        'category' => 'admin',
        'color' => 'var(--red-600)',
        'bg' => 'rgba(239, 68, 68, 0.08)',
        'admin_only' => true
    ],
    [
        'title' => 'Relatórios',
        'url' => 'relatorios_gerais.php',
        'icon' => 'trending-up',
        'category' => 'admin',
        'color' => 'var(--red-600)',
        'bg' => 'rgba(239, 68, 68, 0.08)',
        'admin_only' => true
    ],
    [
        'title' => 'Manutenção',
        'url' => 'manutencao.php',
        'icon' => 'database',
        'category' => 'admin',
        'color' => 'var(--red-600)',
        'bg' => 'rgba(239, 68, 68, 0.08)',
        'admin_only' => true
    ]
];
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

    /* ESTILOS DE ACESSO RÁPIDO & BOAS-VINDAS PREMIUM */
    .dashboard-hero {
        margin-bottom: var(--space-lg);
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3);
    }

    body.dark-mode .dashboard-hero {
        background: linear-gradient(135deg, #080c14 0%, #111827 100%);
        border-color: rgba(255, 255, 255, 0.04);
    }

    @keyframes pulse-border {
        0% { transform: scale(1); opacity: 0.8; }
        100% { transform: scale(1.05); opacity: 1; }
    }

    .dashboard-section-title {
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--color-text-muted);
        letter-spacing: 1.5px;
        margin: var(--space-lg) 0 var(--space-md);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: var(--space-xl);
    }

    @media (min-width: 480px) {
        .shortcuts-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (min-width: 768px) {
        .shortcuts-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (min-width: 1024px) {
        .shortcuts-grid { grid-template-columns: repeat(5, 1fr); }
    }
    @media (min-width: 1280px) {
        .shortcuts-grid { grid-template-columns: repeat(6, 1fr); }
    }

    .shortcut-btn {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: 14px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        min-height: 52px;
    }

    body.dark-mode .shortcut-btn {
        border-color: rgba(255, 255, 255, 0.04);
        box-shadow: none;
    }

    .shortcut-icon-box {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.25s;
    }

    .shortcut-icon-box i {
        width: 17px;
        height: 17px;
        stroke-width: 2.2;
    }

    .shortcut-title {
        font-size: 0.85rem;
        font-weight: 750;
        color: var(--color-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: all 0.25s;
    }

    .shortcut-hover-dot {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%) scale(0);
        width: 5px;
        height: 5px;
        border-radius: 50%;
        transition: all 0.25s ease;
        opacity: 0;
    }

    /* Hover/Tátil State */
    .shortcut-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.06);
        background: var(--color-surface-hover);
        border-color: var(--color-border-hover);
    }

    body.dark-mode .shortcut-btn:hover {
        background: rgba(255, 255, 255, 0.02);
        box-shadow: 0 10px 25px -10px rgba(0,0,0,0.5);
    }

    .shortcut-btn:hover .shortcut-icon-box {
        transform: scale(1.05);
    }

    .shortcut-btn:hover .shortcut-hover-dot {
        transform: translateY(-50%) scale(1);
        opacity: 0.8;
    }

    /* Cores específicas de borda no Hover de cada Categoria */
    .shortcut-gestao:hover { border-color: rgba(59, 130, 246, 0.35); }
    .shortcut-espiritualidade:hover { border-color: rgba(16, 185, 129, 0.35); }
    .shortcut-comunicacao:hover { border-color: rgba(245, 158, 11, 0.35); }
    .shortcut-admin:hover { border-color: rgba(239, 68, 68, 0.35); }
</style>

<div class="dashboard-container" style="padding: var(--space-md) var(--space-md) 100px;">
    
    <!-- HEADER HERO PREMIUM DE BOAS-VINDAS -->
    <div class="dashboard-hero animate-card">
        <!-- Elementos decorativos de fundo para premium feel -->
        <div style="position: absolute; top: -50px; right: -50px; width: 180px; height: 180px; background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, transparent 70%); pointer-events: none;"></div>
        <div style="position: absolute; bottom: -30px; left: 10%; width: 140px; height: 140px; background: radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 70%); pointer-events: none;"></div>
        
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; position: relative; z-index: 2;">
            <div>
                <span style="background: rgba(59,130,246,0.15); color: #93c5fd; font-size: 0.68rem; font-weight: 800; padding: 5px 10px; border-radius: 100px; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px; border: 1px solid rgba(59,130,246,0.25);">
                    <i data-lucide="sparkles" width="11" height="11"></i> PIB Oliveira Louvor
                </span>
                <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0; color: #ffffff; letter-spacing: -0.5px;"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>!</h2>
                <p style="color: #94a3b8; font-size: 0.9rem; margin: 6px 0 0 0; font-weight: 500; line-height: 1.4;">
                    Bem-vindo ao seu Painel <?= $userRole === 'admin' ? 'do Líder' : 'do Músico' ?>. Escolha um atalho abaixo para iniciar.
                </p>
            </div>
            <div style="position: relative; flex-shrink: 0;">
                <div style="position: absolute; inset: -3px; background: linear-gradient(135deg, var(--blue-500), var(--cyan-400)); border-radius: 50%; padding: 2px; animation: pulse-border 3s infinite alternate;"></div>
                <a href="perfil.php" style="display: block; position: relative; z-index: 2;">
                    <img src="<?= $userPhoto ?>" style="width: 58px; height: 58px; border-radius: 50%; border: 3px solid #0f172a; object-fit: cover; box-shadow: var(--shadow-lg);">
                </a>
            </div>
        </div>
    </div>

    <!-- SEÇÃO ATALHOS RÁPIDOS TÁTEIS -->
    <div class="dashboard-section-title animate-card">
        <i data-lucide="layout-grid" width="16" height="16" style="color: var(--primary);"></i>
        Acesso Rápido
    </div>
    <div class="shortcuts-grid">
        <?php foreach ($shortcuts as $sc): ?>
            <?php if ($sc['admin_only'] && $userRole !== 'admin') continue; ?>
            <a href="<?= $sc['url'] ?>" class="shortcut-btn shortcut-<?= $sc['category'] ?> animate-card" style="text-decoration: none;">
                <div class="shortcut-icon-box" style="background: <?= $sc['bg'] ?>; color: <?= $sc['color'] ?>;">
                    <i data-lucide="<?= $sc['icon'] ?>"></i>
                </div>
                <span class="shortcut-title"><?= htmlspecialchars($sc['title']) ?></span>
                <div class="shortcut-hover-dot" style="background: <?= $sc['color'] ?>;"></div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- SEÇÃO INFORMAÇÕES E ESTATÍSTICAS -->
    <div class="dashboard-section-title animate-card" style="margin-top: var(--space-xl);">
        <i data-lucide="activity" width="16" height="16" style="color: var(--primary);"></i>
        Visão Geral & Estatísticas
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
