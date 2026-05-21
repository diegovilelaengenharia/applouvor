<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

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
        $userPhoto = '../uploads/' . $userPhoto;
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

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8">
    <div class="mb-8">
        <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>! 👋</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant mt-2">Pronto para servir e adorar hoje?</p>
    </div>
    
    <div class="bento-grid">
        <!-- Schedule Card -->
        <div class="bento-schedule bg-surface-container-lowest border border-surface-container-highest rounded-xl p-6 shadow-sm flex flex-col hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue fill">event</span>
                    Próxima Escala
                </h2>
                <?php if (!empty($nextSchedule)): ?>
                    <span class="font-label-sm text-label-sm text-worship-blue bg-primary-fixed px-3 py-1 rounded-full uppercase tracking-wider">
                        <?= date('d/m', strtotime($nextSchedule['event_date'])) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($nextSchedule)): ?>
                <div class="space-y-4 flex-grow">
                    <div class="flex items-start gap-4 p-4 rounded-lg bg-ghost-gray border border-surface-container-high">
                        <div class="text-altar-gold font-label-sm text-label-sm w-16 pt-1">Evento</div>
                        <div>
                            <div class="font-body-md text-body-md font-semibold text-on-surface"><?= htmlspecialchars($nextSchedule['event_type']) ?></div>
                            <div class="font-label-sm text-label-sm text-on-surface-variant mt-1">Função: <?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-surface-container-highest">
                    <a href="../admin/escalas.php" class="w-full bg-worship-blue text-on-primary font-body-md text-body-md py-3 rounded-lg hover:bg-primary-fixed-variant transition-colors flex items-center justify-center gap-2" style="text-decoration: none;">
                        Ver Escala Completa
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-8 opacity-60">
                    <span class="material-symbols-outlined text-4xl mb-2">calendar_month</span>
                    <span class="font-body-md text-body-md font-semibold">Nenhuma escala próxima</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Shortcuts Card (Acesso Rápido) -->
        <div class="bento-announcements bg-surface-container-lowest border border-surface-container-highest rounded-xl p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue">apps</span>
                    Acesso Rápido
                </h2>
            </div>
            <div class="grid grid-cols-2 gap-3 flex-grow overflow-y-auto pr-2">
                <?php foreach (array_slice($shortcuts, 0, 4) as $sc): ?>
                    <a href="<?= $sc['url'] ?>" class="flex flex-col items-center justify-center p-3 rounded-xl border border-surface-container-high hover:bg-ghost-gray transition-colors text-center" style="text-decoration: none;">
                        <i data-lucide="<?= $sc['icon'] ?>" style="color: <?= $sc['color'] ?>; margin-bottom: 8px;"></i>
                        <span class="font-label-sm text-label-sm text-on-surface"><?= htmlspecialchars($sc['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Avisos Card -->
        <div class="bento-birthdays bg-surface-container-lowest border border-surface-container-highest rounded-xl p-6 shadow-sm">
            <div class="flex items-center mb-6">
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-altar-gold">campaign</span>
                    Avisos Recentes
                </h2>
            </div>
            <div class="flex flex-col gap-4 overflow-x-auto pb-2">
                <div class="font-label-sm text-label-sm text-on-surface-variant">Confira o menu de avisos para ficar por dentro das novidades do ministério.</div>
            </div>
        </div>
    </div>
</main>

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