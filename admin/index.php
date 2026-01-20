<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';



// ==========================================
// BUSCAR PRÓXIMA ESCALA DO USUÁRIO
// ==========================================
$nextSchedule = null;
$scheduleSongs = [];

try {
    // Query simplificada sem event_time (pode não existir)
    $stmt = $pdo->prepare("
        SELECT s.*, 
               GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') as team_members,
               COUNT(DISTINCT ss.song_id) as song_count
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        LEFT JOIN schedule_users su2 ON s.id = su2.schedule_id
        LEFT JOIN users u ON su2.user_id = u.id
        LEFT JOIN schedule_songs ss ON s.id = ss.schedule_id
        WHERE su.user_id = ? 
          AND s.event_date >= CURDATE()
        GROUP BY s.id
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Log para verificar
    error_log("User ID: " . $_SESSION['user_id']);
    error_log("Next Schedule: " . print_r($nextSchedule, true));

    // Buscar músicas da escala
    if ($nextSchedule) {
        $stmt = $pdo->prepare("
            SELECT sg.title, sg.artist, ss.position
            FROM schedule_songs ss
            JOIN songs sg ON ss.song_id = sg.id
            WHERE ss.schedule_id = ?
            ORDER BY ss.position ASC
        ");
        $stmt->execute([$nextSchedule['id']]);
        $scheduleSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error fetching schedule: " . $e->getMessage());
}

// ==========================================
// BUSCAR ÚLTIMOS AVISOS
// ==========================================
$recentAvisos = [];

try {
    $stmt = $pdo->query("
        SELECT a.*, u.name as author_name
        FROM avisos a
        JOIN users u ON a.created_by = u.id
        WHERE a.archived_at IS NULL
          AND (a.priority = 'urgent' OR a.priority = 'important')
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $recentAvisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silently fail if table doesn't exist yet
}

renderAppHeader('Início');
?>

<div class="container">

    <!-- Hero Section Admin -->
    <div style="
        background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
        margin: -24px -16px 32px -16px; 
        padding: 32px 24px 64px 24px; 
        border-radius: 0 0 32px 32px; 
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: visible;
    ">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Gestão Louvor</h1>
                <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Painel de Liderança</p>
                <div style="margin-top: 12px; font-size: 0.9rem; color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; display: inline-block;">
                    Bem-vindo, <?= $_SESSION['user_name'] ?? 'Visitante' ?>!
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Card: Próxima Escala -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #047857 0%, #065f46 100%);">
                        <i data-lucide="calendar" style="width: 20px; color: white;"></i>
                    </div>
                    Próxima Escala
                </div>
            </div>

            <?php if ($nextSchedule): ?>
                <!-- Event Info Compacto -->
                <div class="event-info">
                    <div class="event-date-box">
                        <div class="event-day"><?= date('d', strtotime($nextSchedule['event_date'])) ?></div>
                        <div class="event-month"><?= date('M', strtotime($nextSchedule['event_date'])) ?></div>
                    </div>
                    <div class="event-details">
                        <h4 class="event-type"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                        <div class="event-time">
                            <i data-lucide="calendar" style="width: 14px;"></i>
                            <?= date('d/m/Y', strtotime($nextSchedule['event_date'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Resumo Compacto -->
                <div style="margin-top: 16px; padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                            <i data-lucide="music" style="width: 14px; display: inline;"></i>
                            <?= count($scheduleSongs) ?> música(s)
                        </span>
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                            <i data-lucide="users" style="width: 14px; display: inline;"></i>
                            <?= $nextSchedule['song_count'] ?? 0 ?> pessoas
                        </span>
                    </div>

                    <!-- Detalhes Expandíveis -->
                    <div id="schedule-details" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-subtle);">
                        <!-- Músicas -->
                        <?php if (!empty($scheduleSongs)): ?>
                            <div style="margin-bottom: 12px;">
                                <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px;">
                                    Músicas
                                </div>
                                <ul class="song-list">
                                    <?php foreach ($scheduleSongs as $index => $song): ?>
                                        <li class="song-item">
                                            <div class="song-number"><?= $index + 1 ?></div>
                                            <div class="song-info">
                                                <h5 class="song-title"><?= htmlspecialchars($song['title']) ?></h5>
                                                <?php if ($song['artist']): ?>
                                                    <p class="song-artist"><?= htmlspecialchars($song['artist']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Team Members -->
                        <?php if ($nextSchedule['team_members']): ?>
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px;">
                                    Equipe
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-primary); margin: 0;">
                                    <?= htmlspecialchars($nextSchedule['team_members']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botão Saiba Mais -->
                    <button onclick="toggleScheduleDetails()" id="toggle-btn" class="ripple" style="
                        width: 100%;
                        padding: 8px;
                        background: transparent;
                        border: 1px solid var(--border-subtle);
                        border-radius: 8px;
                        color: var(--text-primary);
                        font-size: 0.8rem;
                        font-weight: 600;
                        cursor: pointer;
                        margin-top: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 4px;
                    ">
                        <i data-lucide="chevron-down" style="width: 16px;" id="toggle-icon"></i>
                        <span id="toggle-text">Saiba Mais</span>
                    </button>
                </div>

                <!-- Action Button -->
                <a href="escala_detalhe.php?id=<?= $nextSchedule['id'] ?>" class="card-action-btn">
                    <i data-lucide="arrow-right" style="width: 16px;"></i>
                    Ver Detalhes Completos
                </a>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="calendar-x" style="width: 30px;"></i>
                    </div>
                    <h4 class="empty-state-title">Nenhuma escala próxima</h4>
                    <p class="empty-state-text">Você não está escalado nos próximos eventos</p>
                </div>
            <?php endif; ?>
        </div>


    </div>

</div>

<script>
    function toggleScheduleDetails() {
        const details = document.getElementById('schedule-details');
        const icon = document.getElementById('toggle-icon');
        const text = document.getElementById('toggle-text');

        if (details.style.display === 'none') {
            details.style.display = 'block';
            icon.setAttribute('data-lucide', 'chevron-up');
            text.textContent = 'Mostrar Menos';
        } else {
            details.style.display = 'none';
            icon.setAttribute('data-lucide', 'chevron-down');
            text.textContent = 'Saiba Mais';
        }

        // Reinicializar ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Welcome Popup
    function showWelcomePopup() {
        const popup = document.getElementById('welcome-popup');
        if (popup && !sessionStorage.getItem('welcomeShown')) {
            setTimeout(() => {
                popup.classList.add('active');
                sessionStorage.setItem('welcomeShown', 'true');
            }, 500);
        }
    }

    function closeWelcomePopup() {
        document.getElementById('welcome-popup').classList.remove('active');
    }

    // Show popup on page load
    window.addEventListener('DOMContentLoaded', showWelcomePopup);
</script>

<!-- Welcome Popup (Subtle & Modern) -->
<?php if (!empty($recentAvisos)): ?>
    <style>
        @keyframes subtleFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 80px;
            animation: subtleFadeIn 0.3s ease;
        }

        .welcome-popup-overlay.active {
            display: flex;
        }

        .welcome-popup-card {
            background: var(--bg-secondary);
            border-radius: 24px;
            max-width: 420px;
            width: calc(100% - 40px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: subtleFadeIn 0.4s ease 0.1s both;
            overflow: hidden;
        }

        .notice-mini-card {
            background: var(--bg-tertiary);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 8px;
            border-left: 3px solid;
            transition: all 0.2s;
            cursor: pointer;
        }

        .notice-mini-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-sm);
        }
    </style>

    <div id="welcome-popup" class="welcome-popup-overlay" onclick="if(event.target === this) closeWelcomePopup()">
        <div class="welcome-popup-card">
            <!-- Minimalist Header -->
            <div style="padding: 24px 24px 16px; border-bottom: 1px solid var(--border-subtle);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                        width: 40px;
                        height: 40px;
                        background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.5rem;
                    ">
                            📢
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--text-primary);">Avisos Importantes</h3>
                            <p style="margin: 0; font-size: 0.8rem; color: var(--text-secondary);"><?= count($recentAvisos) ?> atualização(s) recente(s)</p>
                        </div>
                    </div>
                    <button onclick="closeWelcomePopup()" style="
                    background: transparent;
                    border: none;
                    width: 32px;
                    height: 32px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    color: var(--text-muted);
                    transition: all 0.2s;
                " onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background='transparent'">
                        <i data-lucide="x" style="width: 18px;"></i>
                    </button>
                </div>
            </div>

            <!-- Notices List (Compact) -->
            <div style="padding: 16px 24px; max-height: 400px; overflow-y: auto;">
                <?php foreach ($recentAvisos as $aviso):
                    // Priority colors
                    $priorityColors = [
                        'urgent' => '#EF4444',
                        'important' => '#F59E0B',
                        'info' => '#3B82F6'
                    ];
                    $borderColor = $priorityColors[$aviso['priority']] ?? '#3B82F6';

                    // Type emojis
                    $typeEmojis = [
                        'general' => '📢',
                        'event' => '🎉',
                        'music' => '🎵',
                        'spiritual' => '🙏',
                        'urgent' => '🚨'
                    ];
                    $emoji = $typeEmojis[$aviso['type']] ?? '📢';
                ?>
                    <div class="notice-mini-card" style="border-left-color: <?= $borderColor ?>;" onclick="window.location.href='avisos.php'">
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <span style="font-size: 1.2rem; flex-shrink: 0;"><?= $emoji ?></span>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="
                                margin: 0 0 4px;
                                font-size: 0.9rem;
                                font-weight: 600;
                                color: var(--text-primary);
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                            "><?= htmlspecialchars($aviso['title']) ?></h4>
                                <p style="
                                margin: 0 0 6px;
                                font-size: 0.8rem;
                                color: var(--text-secondary);
                                line-height: 1.4;
                                display: -webkit-box;
                                -webkit-line-clamp: 2;
                                -webkit-box-orient: vertical;
                                overflow: hidden;
                            "><?= htmlspecialchars(mb_substr(strip_tags($aviso['message']), 0, 80)) ?><?= mb_strlen(strip_tags($aviso['message'])) > 80 ? '...' : '' ?></p>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.7rem; color: var(--text-muted);">
                                    <span><?= htmlspecialchars($aviso['author_name']) ?></span>
                                    <span>•</span>
                                    <span><?= date('d/m', strtotime($aviso['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer Actions -->
            <div style="padding: 16px 24px; border-top: 1px solid var(--border-subtle); display: flex; gap: 12px;">
                <a href="avisos.php" class="btn-primary ripple" style="
                flex: 1;
                justify-content: center;
                text-decoration: none;
                padding: 12px;
                font-size: 0.9rem;
            ">
                    Ver Todos
                </a>
                <button onclick="closeWelcomePopup()" class="ripple" style="
                flex: 1;
                padding: 12px;
                background: var(--bg-tertiary);
                border: 1px solid var(--border-subtle);
                border-radius: 12px;
                color: var(--text-primary);
                font-weight: 600;
                cursor: pointer;
                font-size: 0.9rem;
            ">
                    Fechar
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
renderAppFooter();
?>
```