<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Check if user is logged in
checkLogin();

// ==========================================
// BUSCAR PRÓXIMA ESCALA DO USUÁRIO
// ==========================================
$nextSchedule = null;
$scheduleSongs = [];

try {
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
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $recentAvisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $recentAvisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silently fail
}

// ==========================================
// STATUS LEITURA (Popup Logic)
// ==========================================
$showReadingPopup = false;
$readingPopupData = null;
try {
    // Verificar se já marcou o dia de hoje
    $m = (int)date('n');
    $d = min((int)date('j'), 25); // Limita a 25 do plano
    
    // Verifica se completou
    $stmt = $pdo->prepare("SELECT id FROM reading_progress WHERE user_id = ? AND month_num = ? AND day_num = ?");
    $stmt->execute([$_SESSION['user_id'], $m, $d]);
    $readingDone = $stmt->fetch();

    if (!$readingDone) {
        // Se não fez, verifica filtro de cookie/sessão para não mostrar toda hora?
        // O cliente pediu "popup que apareça na tela após abrir o aplicativo".
        // Vamos mostrar sempre que entrar na Home se não tiver feito.
        $showReadingPopup = true;
        $readingPopupData = [
            'month' => $m,
            'day' => $d
        ];
    }
} catch (Exception $e) {}

renderAppHeader('Início');
?>
<!-- Import JSON for Popup -->
<script src="../assets/js/reading_plan_data.js"></script>

<!-- Hero Section User -->
<div style="
    background: var(--primary); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 24px; gap: 12px;">
        <!-- Botão WhatsApp -->
        <a href="https://chat.whatsapp.com/LmNlohl5XFiGGKQdONQMv2" target="_blank" class="ripple" style="
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #0D6EFD;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
            </svg>
        </a>

        <!-- Avatar do Usuário -->
        <div onclick="openSheet('sheet-perfil')" class="ripple" style="
            width: 52px; 
            height: 52px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.2); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
            cursor: pointer;
            border: 2px solid rgba(255,255,255,0.3);
        ">
            <?php if (!empty($_SESSION['user_avatar'])): ?>
                <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="font-weight: 700; font-size: 0.9rem; color: white;">
                    <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Olá, <?= explode(' ', $_SESSION['user_name'])[0] ?>!</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Área do Voluntário</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">

    <!-- Card: Próxima Escala -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: var(--primary);">
                    <i data-lucide="calendar" style="width: 20px; color: white;"></i>
                </div>
                Minha Próxima Escala
            </div>
        </div>

        <?php if ($nextSchedule): ?>
            <div class="event-info">
                <div class="event-date-box">
                    <div class="event-day"><?= date('d', strtotime($nextSchedule['event_date'])) ?></div>
                    <div class="event-month"><?= date('M', strtotime($nextSchedule['event_date'])) ?></div>
                </div>
                <div class="event-details">
                    <h4 class="event-type"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                    <div class="event-time">
                        <i data-lucide="clock" style="width: 14px;"></i>
                        <?= date('H:i', strtotime($nextSchedule['event_time'] ?? '19:00:00')) ?>
                    </div>
                </div>
            </div>

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

                <div id="schedule-details" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-subtle);">
                    <?php if (!empty($scheduleSongs)): ?>
                        <div style="margin-bottom: 12px;">
                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px;">Repertório</div>
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

                    <?php if ($nextSchedule['team_members']): ?>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px;">Equipe</div>
                            <p style="font-size: 0.85rem; color: var(--text-primary); margin: 0;">
                                <?= htmlspecialchars($nextSchedule['team_members']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

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
                    <span id="toggle-text">Ver Detalhes</span>
                </button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x" style="width: 30px; color: var(--text-muted);"></i>
                </div>
                <h4 class="empty-state-title">Livre!</h4>
                <p class="empty-state-text">Você não tem escalas próximas.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card: Leitura Bíblica -->
    <div class="dashboard-card">
        <a href="leitura.php" class="ripple" style="
            display: flex; 
            align-items: center; 
            gap: 16px; 
            padding: 16px; 
            text-decoration: none; 
            color: var(--text-primary);
        ">
            <div style="
                width: 48px; 
                height: 48px; 
                border-radius: 12px; 
                background: var(--success);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            ">
                <i data-lucide="book-open" style="width: 24px; color: white;"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700;">Leitura Bíblica</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">Acompanhe seu plano</p>
            </div>
            <i data-lucide="chevron-right" style="margin-left: auto; width: 20px; color: var(--text-muted);"></i>
        </a>
    </div>

    <!-- Card: Avisos -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-icon" style="background: #FFC107;">
                    <i data-lucide="bell" style="width: 20px; color: white;"></i>
                </div>
                Avisos
            </div>
        </div>

        <?php if (!empty($recentAvisos)): ?>
            <?php foreach ($recentAvisos as $aviso): ?>
                <div class="aviso-item <?= $aviso['priority'] ?>">
                    <div class="aviso-header">
                        <h5 class="aviso-title"><?= htmlspecialchars($aviso['title']) ?></h5>
                        <span class="priority-badge priority-<?= $aviso['priority'] ?>">
                            <?php
                            $priorityLabels = ['urgent' => '🔴!', 'important' => '🟡', 'info' => '🔵'];
                            echo $priorityLabels[$aviso['priority']] ?? '•';
                            ?>
                        </span>
                    </div>
                    <p class="aviso-message"><?= nl2br(htmlspecialchars($aviso['message'])) ?></p>
                    <div class="aviso-meta">
                        <?= htmlspecialchars($aviso['author_name']) ?> • <?= date('d/m', strtotime($aviso['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="bell-off" style="width: 30px; color: var(--text-muted);"></i>
                </div>
                <p class="empty-state-text">Nenhum aviso no momento.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card: Atalhos -->
    <div class="dashboard-card">
        <a href="../admin/repertorio.php" class="ripple" style="
            display: flex; 
            align-items: center; 
            gap: 16px; 
            padding: 16px; 
            text-decoration: none; 
            color: var(--text-primary);
        ">
            <div style="
                width: 48px; 
                height: 48px; 
                border-radius: 12px; 
                background: #0D6EFD;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            ">
                <i data-lucide="music" style="width: 24px; color: white;"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700;">Repertório</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">Ver todas as músicas</p>
            </div>
            <i data-lucide="chevron-right" style="margin-left: auto; width: 20px; color: var(--text-muted);"></i>
        </a>
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
            text.textContent = 'Ocultar Detalhes';
        } else {
            details.style.display = 'none';
            icon.setAttribute('data-lucide', 'chevron-down');
            text.textContent = 'Ver Detalhes';
        }
        lucide.createIcons();
    }

    // Reading Popup Logic
    const showReadingPopup = <?= $showReadingPopup ? 'true' : 'false' ?>;
    const readingData = <?= json_encode($readingPopupData) ?>;

    if (showReadingPopup && readingData && bibleReadingPlan) {
        window.addEventListener('load', () => {
            // Check if already dismissed in session to avoid annoyance?
            // User requested "popup que apareça na tela após abrir o aplicativo".
            // Let's create the modal HTML dynamically
            
            const verses = bibleReadingPlan[readingData.month][readingData.day - 1];
            if (!verses) return;

            const modalHtml = `
            <div id="reading-modal" class="modal-overlay active" style="z-index: 2000;">
                <div class="modal-content" style="background: var(--bg-surface); padding: 24px; border-radius: 20px; max-width: 320px; width: 90%; text-align: center; position:relative; overflow:hidden;">
                    <!-- Decorative Background -->
                    <div style="position:absolute; top:0; left:0; width:100%; height:6px; background: var(--success);"></div>
                    
                    <div style="width: 56px; height: 56px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                        <i data-lucide="book-open" style="width: 28px; color: var(--success);"></i>
                    </div>

                    <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 8px;">Leitura de Hoje</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                        Dia ${readingData.day}/${readingData.month}
                    </p>

                    <div style="background: var(--bg-body); padding: 12px; border-radius: 12px; margin-bottom: 24px; text-align: left;">
                        ${verses.map(v => `<div style="font-size:0.9rem; font-weight:500; padding:4px 0; border-bottom:1px solid var(--border-color);">${v}</div>`).join('')}
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="leitura.php" class="btn-primary" style="text-decoration: none; justify-content: center;">
                            Ir para Leitura
                        </a>
                        <button onclick="document.getElementById('reading-modal').remove()" style="background: transparent; border: none; padding: 12px; color: var(--text-muted); font-weight: 600;">
                            Lembrar depois
                        </button>
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