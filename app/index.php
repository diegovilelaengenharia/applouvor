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

// 4. Último Aviso Recente do Banco de Dados
$latestAviso = null;
try {
    $stmt = $pdo->query("SELECT * FROM avisos ORDER BY created_at DESC LIMIT 1");
    $latestAviso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 5. Pedidos de Oração Recentes (Para o Bento Card "Orando em Unidade")
$prayerRequests = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as author_name, u.avatar as author_avatar,
               (SELECT COUNT(*) FROM prayer_interactions pi WHERE pi.prayer_id = p.id AND pi.user_id = ? AND pi.type = 'pray') as already_prayed
        FROM prayer_requests p
        JOIN users u ON p.user_id = u.id
        WHERE p.is_answered = 0
        ORDER BY p.created_at DESC
        LIMIT 2
    ");
    $stmt->execute([$userId]);
    $prayerRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<style>
@media (min-width: 768px) {
    .bento-prayers { grid-column: span 6; grid-row: span 2; }
    .bento-birthdays { grid-column: span 6; grid-row: span 2; }
}

/* Estilos do Widget de Oração */
.prayer-card {
    background-color: var(--surface-container-low);
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-md);
    padding: 16px;
    margin-bottom: 12px;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.2s;
}
.prayer-card:hover {
    border-color: var(--primary);
}
.prayer-badge {
    font-family: var(--font-display);
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: var(--radius-full);
    text-transform: uppercase;
}
.prayer-badge-urgent {
    background-color: rgba(239, 68, 68, 0.08);
    color: var(--color-danger);
    border: 1px solid rgba(239, 68, 68, 0.15);
}
.prayer-badge-normal {
    background-color: rgba(6, 182, 212, 0.08);
    color: var(--color-espiritual);
    border: 1px solid rgba(6, 182, 212, 0.15);
}

/* Estilo do botão de interceder ativo/inativo */
.btn-pray-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: var(--radius-full);
    font-size: 0.72rem;
    font-weight: 700;
    font-family: var(--font-body);
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid var(--outline-variant);
}
.btn-pray-toggle.active {
    background-color: rgba(34, 197, 94, 0.08);
    color: var(--green-600);
    border: 1px solid rgba(34, 197, 94, 0.15);
}
.btn-pray-toggle.inactive {
    background-color: var(--surface-container-low);
    color: var(--on-surface-variant);
}
.btn-pray-toggle:hover {
    transform: scale(1.03);
}

/* Confirmação de Escala Rápida */
.confirm-status-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: var(--radius-md);
    margin-top: 16px;
    font-size: 0.825rem;
    font-family: var(--font-body);
    font-weight: 600;
}
.confirm-status-confirmed {
    background-color: rgba(34, 197, 94, 0.08);
    color: var(--green-700);
    border: 1px solid rgba(34, 197, 94, 0.15);
}
.confirm-status-declined {
    background-color: rgba(239, 68, 68, 0.08);
    color: var(--red-700);
    border: 1px solid rgba(239, 68, 68, 0.15);
}
</style>
<?php

// Definição dos atalhos específicos para voluntários
$shortcuts = [
    [
        'title' => 'Minhas Escalas',
        'url' => '../admin/escalas.php',
        'icon' => 'calendar',
        'color' => 'var(--worship-blue)'
    ],
    [
        'title' => 'Repertório',
        'url' => '../admin/repertorio.php',
        'icon' => 'music-2',
        'color' => 'var(--worship-blue)'
    ],
    [
        'title' => 'Histórico',
        'url' => '../admin/historico.php',
        'icon' => 'history',
        'color' => 'var(--worship-blue)'
    ],
    [
        'title' => 'Ausências',
        'url' => '../admin/indisponibilidade.php',
        'icon' => 'calendar-off',
        'color' => 'var(--worship-blue)'
    ]
];

renderAppHeader('Início');
?>
<!-- Import JSON for Popup -->
<script src="../assets/js/reading_plan_data.js"></script>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8" style="padding-top: calc(var(--spacing-gutter) + 64px);">
    <!-- Hero Banner -->
    <div class="mb-8 p-8 bg-surface-container rounded-2xl reveal-item">
        <h1 class="font-display-lg-mobile md:font-display-lg text-on-surface"><?= $salutation ?>, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! 👋</h1>
        <p class="font-body-lg text-on-surface-variant mt-2">Pronto para servir e adorar hoje?</p>
    </div>

    <div class="bento-grid">
        <!-- Schedule Card -->
        <div class="bento-schedule bg-surface border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col hover:shadow-md transition-shadow reveal-stagger-1">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue fill">event</span>
                    Próxima Escala
                </h2>
                <?php if (!empty($nextSchedule)): ?>
                    <span class="font-label-sm text-xs text-worship-blue bg-[#E9F2FF] px-3 py-1 rounded-full uppercase tracking-wider font-bold">
                        <?= date('d/m', strtotime($nextSchedule['event_date'])) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($nextSchedule)): ?>
                <div class="space-y-0 flex-grow">
                    <div class="flex items-start gap-4 py-3 border-b border-outline-variant last:border-0">
                        <div class="text-altar-gold font-bold text-sm w-16 pt-0.5" style="text-transform: uppercase;">Evento</div>
                        <div>
                            <div class="font-bold text-on-surface"><?= htmlspecialchars($nextSchedule['event_type']) ?></div>
                            <div class="text-sm text-on-surface-variant">Função: <?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></div>
                        </div>
                    </div>

                    <!-- Lógica de Confirmação -->
                    <?php if ($nextSchedule['my_status'] === 'pending'): ?>
                        <div class="mt-4 p-4 rounded-xl" style="background-color: rgba(46, 126, 237, 0.04); border: 1px solid var(--outline-variant);">
                            <div class="font-bold text-on-surface text-sm mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-worship-blue text-[18px]">waving_hand</span>
                                Confirme sua participação, adorador!
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 mt-2">
                                <button onclick="confirmScale(<?= $nextSchedule['id'] ?>, 'confirmed')" class="flex-1 bg-worship-blue text-on-primary font-bold py-2 px-4 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    Confirmar Presença
                                </button>
                                <button onclick="openAbsenceModal(<?= $nextSchedule['id'] ?>)" class="bg-surface-container text-error font-bold py-2 px-4 rounded-lg border border-outline-variant hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                    Recusar / Justificar
                                </button>
                            </div>
                        </div>
                    <?php elseif ($nextSchedule['my_status'] === 'confirmed'): ?>
                        <div class="mt-4 flex items-center gap-3 p-3 rounded-lg" style="background-color: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.15);">
                            <span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span>
                            <div class="flex-grow text-green-800 font-bold text-sm">Presença confirmada! Louve com alegria!</div>
                            <button onclick="openAbsenceModal(<?= $nextSchedule['id'] ?>)" class="text-xs font-bold underline text-on-surface-variant hover:text-error transition-colors">Alterar</button>
                        </div>
                    <?php elseif ($nextSchedule['my_status'] === 'declined'): ?>
                        <div class="mt-4 flex items-center gap-3 p-3 rounded-lg" style="background-color: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15);">
                            <span class="material-symbols-outlined text-[20px] text-error">cancel</span>
                            <div class="flex-grow text-error font-bold text-sm">Ausência justificada. Sentiremos sua falta!</div>
                            <button onclick="confirmScale(<?= $nextSchedule['id'] ?>, 'confirmed')" class="text-xs font-bold underline text-on-surface-variant hover:text-worship-blue transition-colors">Reverter</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-6">
                    <a href="../admin/escalas.php" class="w-full bg-worship-blue text-on-primary font-bold py-3 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2" style="text-decoration: none;">
                        Ver Escala Completa
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-8 opacity-60 flex-grow">
                    <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant">calendar_month</span>
                    <span class="font-body-md font-bold text-on-surface-variant">Nenhuma escala próxima</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Shortcuts Card -->
        <div class="bento-announcements bg-surface border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col reveal-stagger-2">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue">apps</span>
                    Acesso Rápido
                </h2>
            </div>
            <div class="grid grid-cols-2 gap-3 flex-grow">
                <?php foreach (array_slice($shortcuts, 0, 4) as $sc): ?>
                    <a href="<?= $sc['url'] ?>" class="flex flex-col items-center justify-center p-4 bg-surface-container rounded-xl hover:bg-surface-container-high transition-colors text-center" style="border: 1px solid var(--outline-variant); text-decoration: none;">
                        <div class="w-10 h-10 rounded-full mb-2 flex items-center justify-center" style="background-color: rgba(46, 126, 237, 0.08); color: var(--worship-blue);">
                            <i data-lucide="<?= $sc['icon'] ?>" stroke-width="2" style="width: 20px; height: 20px;"></i>
                        </div>
                        <span class="text-xs font-bold text-on-surface"><?= htmlspecialchars($sc['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Avisos Card -->
        <div class="bento-birthdays bg-surface border border-outline-variant rounded-xl p-6 shadow-sm reveal-stagger-3">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-altar-gold">campaign</span>
                    Avisos Recentes
                </h2>
                <a href="../admin/avisos.php" class="text-xs font-bold text-worship-blue flex items-center gap-1 hover:underline" style="text-decoration: none;">
                    Ver todos <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>
            
            <?php if (!empty($latestAviso)): ?>
                <div class="py-2">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center border border-outline-variant flex-shrink-0" style="color: var(--altar-gold);">
                            <span class="material-symbols-outlined">notifications_active</span>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-on-surface-variant mb-1 uppercase tracking-tight"><?= date('d/m/Y H:i', strtotime($latestAviso['created_at'])) ?></div>
                            <div class="font-bold text-on-surface text-lg leading-tight"><?= htmlspecialchars($latestAviso['titulo']) ?></div>
                            <div class="text-sm text-on-surface-variant mt-2 line-clamp-2">
                                <?= htmlspecialchars(strip_tags($latestAviso['conteudo'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-6 opacity-60">
                    <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant">campaign</span>
                    <span class="font-bold text-sm text-on-surface-variant">Nenhum aviso cadastrado</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Orando em Unidade -->
        <div class="bento-birthdays bg-surface border border-outline-variant rounded-xl p-6 shadow-sm reveal-stagger-4" style="grid-column: span 12;">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-worship-blue">volunteer_activism</span>
                    Orando em Unidade
                </h2>
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container px-3 py-1 rounded-full">Pedidos</span>
            </div>

            <?php if (!empty($prayerRequests)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($prayerRequests as $pr): 
                        $isUrgent = (bool)$pr['is_urgent'];
                        $prAuthor = $pr['is_anonymous'] ? 'Anônimo' : $pr['author_name'];
                        $prAvatar = $pr['is_anonymous'] ? 'https://ui-avatars.com/api/?name=A&background=e2e8f0&color=64748b' : $pr['author_avatar'];
                        if (!$pr['is_anonymous'] && !empty($prAvatar) && strpos($prAvatar, 'http') === false) {
                            $prAvatar = (strpos($prAvatar, 'assets') === false && strpos($prAvatar, 'uploads') === false) ? '../uploads/' . $prAvatar : '../' . $prAvatar;
                        }
                    ?>
                        <div class="bg-surface border border-outline-variant rounded-xl p-4 hover:border-worship-blue transition-colors">
                            <div class="flex items-start gap-3">
                                <img src="<?= htmlspecialchars($prAvatar) ?>" alt="<?= htmlspecialchars($prAuthor) ?>" class="w-10 h-10 rounded-full object-cover border border-outline-variant flex-shrink-0">
                                <div class="flex-grow">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-on-surface text-sm"><?= htmlspecialchars($prAuthor) ?></span>
                                        <?php if ($isUrgent): ?>
                                            <span class="text-[10px] font-bold text-error bg-error-container px-2 py-0.5 rounded-full uppercase">Urgente</span>
                                        <?php else: ?>
                                            <span class="text-[10px] font-bold text-worship-blue bg-[#E9F2FF] px-2 py-0.5 rounded-full uppercase">Oração</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-on-surface-variant mt-2 line-clamp-3">
                                        <?= htmlspecialchars($pr['description']) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-outline-variant border-dashed flex justify-between items-center">
                                <span class="text-xs text-on-surface-variant font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">event</span>
                                    <?= date('d/m', strtotime($pr['created_at'])) ?>
                                </span>
                                
                                <button onclick="togglePrayer(<?= $pr['id'] ?>)" 
                                        id="btn-pray-<?= $pr['id'] ?>" 
                                        class="flex items-center gap-1 px-3 py-1.5 rounded-full font-bold text-xs transition-colors <?= $pr['already_prayed'] ? 'bg-[#E6F4EA] text-green-700 border border-green-200' : 'bg-surface border border-outline-variant text-on-surface-variant hover:text-worship-blue' ?>">
                                    <span class="material-symbols-outlined text-[14px]">volunteer_activism</span>
                                    <span id="pray-label-<?= $pr['id'] ?>"><?= $pr['already_prayed'] ? 'Intercedendo' : 'Interceder' ?></span>
                                    <span class="ml-1 px-1.5 rounded-full bg-surface-container text-[10px]" id="pray-count-<?= $pr['id'] ?>">
                                        <?= (int)$pr['prayer_count'] ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-6 opacity-60">
                    <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant">volunteer_activism</span>
                    <span class="font-bold text-sm text-on-surface-variant">Nenhum pedido de oração ativo</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal de Justificativa de Ausência -->
<div id="absence-modal" class="modal-overlay" style="z-index: 2000; display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px);">
    <div class="modal-card animate-fade-in-up" style="max-width: 380px; width: 90%; background: var(--surface-bright); border: 1px solid var(--outline); border-radius: var(--radius-xl); overflow: hidden; position: relative; box-shadow: var(--shadow-lg); margin: auto;">
        <!-- Decorative Top Border -->
        <div style="height: 6px; background: var(--color-danger, #ef4444); width: 100%;"></div>
        
        <div class="modal-body" style="padding: 24px;">
            <div style="width: 52px; height: 52px; background: rgba(239, 68, 68, 0.08); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                <span class="material-symbols-outlined text-danger" style="font-size: 26px;">event_busy</span>
            </div>

            <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 800; text-align: center; color: var(--on-surface); margin-bottom: 8px;">Justificar Ausência</h3>
            <p style="color: var(--on-surface-variant); font-family: var(--font-body); font-size: 0.825rem; text-align: center; margin-bottom: 20px; line-height: 1.4;">
                Sentiremos sua falta no ministério! Por favor, insira o motivo da sua ausência para que o líder possa se organizar.
            </p>

            <form id="absence-form" onsubmit="submitAbsence(event)">
                <input type="hidden" id="absence-schedule-id" name="schedule_id" value="">
                
                <div class="mb-4">
                    <label for="absence-note" style="display: block; font-family: var(--font-body); font-size: 0.75rem; font-weight: 700; color: var(--on-surface); margin-bottom: 6px; text-align: left;">Motivo da Ausência *</label>
                    <textarea id="absence-note" name="absence_note" required rows="3" placeholder="Ex: Viagem de trabalho, motivo de saúde, etc..." 
                              style="width: 100%; padding: 12px; border: 1px solid var(--outline-variant); border-radius: var(--radius-md); background: var(--surface-container-low); color: var(--on-surface); font-family: var(--font-body); font-size: 0.85rem; resize: none; outline: none; transition: border-color 0.2s;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeAbsenceModal()" class="flex-1 py-2.5" 
                            style="background: transparent; border: 1px solid var(--outline-variant); border-radius: var(--radius-md); color: var(--on-surface-variant); font-family: var(--font-body); font-weight: 700; font-size: 0.8rem; cursor: pointer;">
                        Voltar
                    </button>
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 flex items-center justify-center gap-2 hover-scale transition-spring" 
                            style="border: none; border-radius: var(--radius-md); font-family: var(--font-body); font-weight: 700; font-size: 0.8rem; cursor: pointer; background-color: var(--color-danger, #ef4444);">
                        <span class="material-symbols-outlined text-[16px]">send</span>
                        Enviar Justificativa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Scale Confirmation Logic
    function confirmScale(scheduleId, status, absenceNote = null) {
        fetch('../api/confirm_scale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ schedule_id: scheduleId, status: status, absence_note: absenceNote })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erro ao atualizar escala: ' + (data.message || 'Tente novamente.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão. Tente novamente.');
        });
    }

    function openAbsenceModal(scheduleId) {
        document.getElementById('absence-schedule-id').value = scheduleId;
        const modal = document.getElementById('absence-modal');
        modal.style.display = 'flex';
        document.getElementById('absence-note').focus();
    }

    function closeAbsenceModal() {
        document.getElementById('absence-modal').style.display = 'none';
        document.getElementById('absence-form').reset();
    }

    function submitAbsence(event) {
        event.preventDefault();
        const scheduleId = document.getElementById('absence-schedule-id').value;
        const note = document.getElementById('absence-note').value;
        confirmScale(scheduleId, 'declined', note);
        closeAbsenceModal();
    }

    // Intercession (Prayer) Snippet Snappy Interaction (Optimistic Update)
    function togglePrayer(prayerId) {
        const btn = document.getElementById('btn-pray-' + prayerId);
        const label = document.getElementById('pray-label-' + prayerId);
        const countSpan = document.getElementById('pray-count-' + prayerId);
        if (!btn || !countSpan) return;

        const isActive = btn.classList.contains('active');
        let currentCount = parseInt(countSpan.innerText) || 0;

        // Optimistic State
        if (isActive) {
            btn.classList.remove('active');
            btn.classList.add('inactive');
            if (label) label.innerText = 'Interceder';
            currentCount = Math.max(0, currentCount - 1);
        } else {
            btn.classList.add('active');
            btn.classList.remove('inactive');
            if (label) label.innerText = 'Intercedendo';
            currentCount += 1;
        }
        countSpan.innerText = currentCount;

        // Background call
        const formData = new FormData();
        formData.append('prayer_id', prayerId);

        fetch('../api/toggle_intercession.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                // Revert state on error
                if (isActive) {
                    btn.classList.add('active');
                    btn.classList.remove('inactive');
                    if (label) label.innerText = 'Intercedendo';
                    countSpan.innerText = currentCount + 1;
                } else {
                    btn.classList.remove('active');
                    btn.classList.add('inactive');
                    if (label) label.innerText = 'Interceder';
                    countSpan.innerText = Math.max(0, currentCount - 1);
                }
                alert('Não foi possível registrar sua intercessão. Tente novamente.');
            }
        })
        .catch(err => {
            console.error(err);
            // Revert state on failure
            if (isActive) {
                btn.classList.add('active');
                btn.classList.remove('inactive');
                if (label) label.innerText = 'Intercedendo';
                countSpan.innerText = currentCount + 1;
            } else {
                btn.classList.remove('active');
                btn.classList.add('inactive');
                if (label) label.innerText = 'Interceder';
                countSpan.innerText = Math.max(0, currentCount - 1);
            }
            alert('Erro de conexão ao registrar intercessão.');
        });
    }

    // Reading Plan Popup Logic
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