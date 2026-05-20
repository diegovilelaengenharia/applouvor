<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Check if user is logged in
checkLogin();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Voluntário';

// 1. Saudação
$hour = date('H');
if ($hour >= 5 && $hour < 12) $salutation = "Bom dia";
elseif ($hour >= 12 && $hour < 18) $salutation = "Boa tarde";
else $salutation = "Boa noite";

// 2. Status Leitura (Popup Logic)
$showReadingPopup = false;
$readingPopupData = null;
try {
    $m = (int)date('n');
    $d = min((int)date('j'), 25);
    
    $stmt = $pdo->prepare("SELECT id FROM reading_progress WHERE user_id = ? AND month_num = ? AND day_num = ?");
    $stmt->execute([$userId, $m, $d]);
    $readingDone = $stmt->fetch();

    if (!$readingDone) {
        $showReadingPopup = true;
        $readingPopupData = [
            'month' => $m,
            'day' => $d
        ];
    }
} catch (Exception $e) {}

// Foto do Usuário
$userPhoto = $_SESSION['user_avatar'] ?? '';
if (empty($userPhoto)) {
    $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=dbeafe&color=1e40af';
} elseif (strpos($userPhoto, 'http') === false) {
    if (strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
        $userPhoto = '../assets/uploads/' . $userPhoto;
    } else {
        $userPhoto = '../' . $userPhoto;
    }
}

// 3. Próxima Escala
$nextSchedule = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, su.status as my_status, su.role as my_role
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Definição dos atalhos específicos para voluntários
$shortcuts = [
    [
        'title' => 'Minhas Escalas',
        'url' => '../admin/escalas.php',
        'icon' => 'calendar',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Repertório',
        'url' => '../admin/repertorio.php',
        'icon' => 'music-2',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Histórico',
        'url' => '../admin/historico.php',
        'icon' => 'history',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Ausências',
        'url' => '../admin/indisponibilidade.php',
        'icon' => 'calendar-off',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Agenda',
        'url' => '../admin/agenda.php',
        'icon' => 'calendar-range',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Metrônomo',
        'url' => '../admin/metronomo.php',
        'icon' => 'timer',
        'category' => 'gestao',
        'color' => 'var(--blue-500)',
        'bg' => 'rgba(59, 130, 246, 0.08)'
    ],
    [
        'title' => 'Devocional',
        'url' => '../admin/devocionais.php',
        'icon' => 'book-heart',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)'
    ],
    [
        'title' => 'Oração',
        'url' => '../admin/oracao.php',
        'icon' => 'heart',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)'
    ],
    [
        'title' => 'Bíblia',
        'url' => 'leitura.php',
        'icon' => 'book-open',
        'category' => 'espiritualidade',
        'color' => 'var(--emerald-600)',
        'bg' => 'rgba(5, 150, 105, 0.08)'
    ],
    [
        'title' => 'Avisos',
        'url' => '../admin/avisos.php',
        'icon' => 'megaphone',
        'category' => 'comunicacao',
        'color' => 'var(--amber-600)',
        'bg' => 'rgba(245, 158, 11, 0.08)'
    ],
    [
        'title' => 'Aniversários',
        'url' => '../admin/aniversarios.php',
        'icon' => 'cake',
        'category' => 'comunicacao',
        'color' => 'var(--amber-600)',
        'bg' => 'rgba(245, 158, 11, 0.08)'
    ]
];

renderAppHeader('Início');
?>
<!-- Import JSON for Popup -->
<script src="../assets/js/reading_plan_data.js"></script>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
        gap: var(--space-md);
    }
    
    @media (min-width: 768px) {
        .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
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

<script>
    // Reading Popup Logic
    const showReadingPopup = <?= $showReadingPopup ? 'true' : 'false' ?>;
    const readingData = <?= json_encode($readingPopupData) ?>;

    if (showReadingPopup && readingData && bibleReadingPlan) {
        window.addEventListener('load', () => {
            const verses = bibleReadingPlan[readingData.month][readingData.day - 1];
            if (!verses) return;

            const modalHtml = `
            <div id="reading-modal" class="modal-overlay active" style="z-index: 2000;">
                <div class="modal-card" style="max-width: 340px; margin: 0 auto; height: auto; max-height: 90vh; border-radius: var(--radius-xl); text-align: center; position: relative;">
                    <!-- Decorative Top Border -->
                    <div style="height: 6px; background: var(--success); width: 100%;"></div>
                    
                    <div class="modal-body" style="padding: 28px 24px;">
                        <div style="width: 56px; height: 56px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                            <i data-lucide="book-open" style="width: 28px; color: var(--success);"></i>
                        </div>

                        <h3 class="modal-title" style="justify-content: center; font-size: 1.25rem; font-weight: 800; margin-bottom: 8px;">Leitura de Hoje</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                            Dia ${readingData.day}/${readingData.month}
                        </p>

                        <div style="background: var(--bg-surface-alt); padding: 14px; border-radius: var(--radius-lg); margin-bottom: 24px; text-align: left; border: 1px solid var(--border-subtle);">
                            ${verses.map(v => `<div style="font-size:0.95rem; font-weight:500; padding:6px 0; border-bottom:1px solid var(--border-subtle); color: var(--text-primary);">${v}</div>`).join('')}
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="leitura.php" class="btn-primary ripple" style="text-decoration: none; justify-content: center; width: 100%; display: flex; align-items: center;">
                                Ir para Leitura
                            </a>
                            <button onclick="document.getElementById('reading-modal').remove()" class="ripple" style="background: transparent; border: none; padding: 12px; color: var(--text-muted); font-weight: 600; cursor: pointer; transition: color 0.2s;">
                                Lembrar depois
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            lucide.createIcons();
        });
    }
</script>

<?php renderAppFooter(); ?>