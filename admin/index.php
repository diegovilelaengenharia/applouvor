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
<?php
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
        line-height: 1.2;
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
        <div class="hero-glow-1"></div>
        <div class="hero-glow-2"></div>
        
        <div class="hero-main-content">
            <!-- Bloco Info Usuário (Esquerda) -->
            <div class="hero-user-section">
                <div class="hero-avatar-wrapper">
                    <div class="hero-avatar-ring"></div>
                    <a href="perfil.php" style="display: block;">
                        <img src="<?= $userPhoto ?>" alt="Avatar" class="hero-avatar">
                    </a>
                </div>
                <div class="hero-welcome-text">
                    <div class="hero-badge">
                        <i data-lucide="sparkles" width="12" height="12"></i> PIB Oliveira Louvor
                    </div>
                    <h2 class="hero-title"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>! 👋</h2>
                    <p class="hero-subtitle">Pronto para servir e adorar hoje?</p>
                </div>
            </div>

            <!-- Bloco Próxima Escala (Direita) -->
            <div class="hero-info-section">
                <?php if (!empty($nextSchedule)): ?>
                    <div class="hero-scale-card">
                        <div class="scale-card-header">
                            <span class="scale-label"><i data-lucide="calendar" width="13" height="13"></i> Próxima Escala</span>
                            <?php 
                            $statusClass = 'status-pending';
                            $statusText = 'Pendente';
                            if (($nextSchedule['my_status'] ?? '') === 'confirmed') {
                                $statusClass = 'status-confirmed';
                                $statusText = 'Confirmada';
                            } elseif (($nextSchedule['my_status'] ?? '') === 'declined') {
                                $statusClass = 'status-declined';
                                $statusText = 'Recusada';
                            }
                            ?>
                            <span class="scale-status <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <div class="scale-card-body">
                            <span class="scale-date"><?= date('d/m', strtotime($nextSchedule['event_date'])) ?></span>
                            <div class="scale-details">
                                <span class="scale-event"><?= htmlspecialchars($nextSchedule['event_type']) ?></span>
                                <span class="scale-role"><?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hero-scale-card empty-scale">
                        <i data-lucide="calendar-heart" width="20" height="20" style="flex-shrink: 0;"></i>
                        <div class="scale-details">
                            <span class="scale-event" style="font-size: 0.8rem; font-weight: 700;">Nenhuma escala próxima</span>
                            <span class="scale-role" style="font-size: 0.68rem; white-space: normal;">Acompanhe os cultos no menu Escalas.</span>
                        </div>
                    </div>
                <?php endif; ?>
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

</div>

<?php renderAppFooter(); ?>
