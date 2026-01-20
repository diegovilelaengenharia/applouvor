<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Temporary Avatar Fix (Auto-Execute v3)
if (isset($_SESSION['user_id']) && ($_SESSION['user_name'] == 'Diego' || $_SESSION['user_id'] == 1)) {
    if (empty($_SESSION['user_avatar'])) {
        $_SESSION['user_avatar'] = 'diego_avatar.jpg';
    }
    if (!isset($_SESSION['avatar_persist_v3'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET avatar = 'diego_avatar.jpg' WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $_SESSION['avatar_persist_v3'] = true;
        } catch (Exception $e) {
        }
    }
}

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
        ORDER BY s.event_date ASC, s.event_time ASC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

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
    // Silently fail if tables don't exist yet
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
        <!-- Navigation Row (Right Aligned) -->
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 24px; gap: 12px;">
            <!-- Botão WhatsApp -->
            <a href="https://chat.whatsapp.com/LmNlohl5XFiGGKQdONQMv2" target="_blank" class="ripple" style="
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #0D6EFD 0%, #0B5ED7 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
                transition: all 0.3s ease;
                position: relative;
                z-index: 10;
            " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
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
                <!-- Event Info -->
                <div class="event-info">
                    <div class="event-date-box">
                        <div class="event-day"><?= date('d', strtotime($nextSchedule['event_date'])) ?></div>
                        <div class="event-month"><?= strftime('%b', strtotime($nextSchedule['event_date'])) ?></div>
                    </div>
                    <div class="event-details">
                        <h4 class="event-type"><?= htmlspecialchars($nextSchedule['event_type']) ?></h4>
                        <div class="event-time">
                            <i data-lucide="clock" style="width: 14px;"></i>
                            <?= date('H:i', strtotime($nextSchedule['event_time'] ?? '19:00:00')) ?>
                        </div>
                    </div>
                </div>

                <!-- Músicas -->
                <?php if (!empty($scheduleSongs)): ?>
                    <div style="margin-top: 16px;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px;">
                            Músicas (<?= count($scheduleSongs) ?>)
                        </div>
                        <ul class="song-list">
                            <?php
                            $displaySongs = array_slice($scheduleSongs, 0, 3);
                            foreach ($displaySongs as $index => $song):
                            ?>
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
                        <?php if (count($scheduleSongs) > 3): ?>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">
                                + <?= count($scheduleSongs) - 3 ?> música(s)
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Team Members -->
                <?php if ($nextSchedule['team_members']): ?>
                    <div class="team-members">
                        <span class="team-label">Equipe:</span>
                        <span class="team-names"><?= htmlspecialchars($nextSchedule['team_members']) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Action Button -->
                <a href="escala_detalhe.php?id=<?= $nextSchedule['id'] ?>" class="card-action-btn">
                    <i data-lucide="arrow-right" style="width: 16px;"></i>
                    Ver Detalhes
                </a>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="calendar-x" style="width: 30px; color: var(--text-muted);"></i>
                    </div>
                    <h4 class="empty-state-title">Nenhuma escala próxima</h4>
                    <p class="empty-state-text">Você não está escalado nos próximos eventos</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Card: Avisos -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);">
                        <i data-lucide="bell" style="width: 20px; color: white;"></i>
                    </div>
                    Avisos
                </div>
                <a href="avisos.php" class="card-link">
                    Ver todos
                    <i data-lucide="chevron-right" style="width: 16px;"></i>
                </a>
            </div>

            <?php if (!empty($recentAvisos)): ?>
                <?php foreach ($recentAvisos as $aviso): ?>
                    <div class="aviso-item <?= $aviso['priority'] ?>">
                        <div class="aviso-header">
                            <h5 class="aviso-title"><?= htmlspecialchars($aviso['title']) ?></h5>
                            <span class="priority-badge priority-<?= $aviso['priority'] ?>">
                                <?php
                                $priorityLabels = [
                                    'urgent' => '🔴 Urgente',
                                    'important' => '🟡 Importante',
                                    'info' => '🔵 Info'
                                ];
                                echo $priorityLabels[$aviso['priority']] ?? 'Info';
                                ?>
                            </span>
                        </div>
                        <p class="aviso-message"><?= nl2br(htmlspecialchars($aviso['message'])) ?></p>
                        <div class="aviso-meta">
                            <i data-lucide="user" style="width: 12px;"></i>
                            <?= htmlspecialchars($aviso['author_name']) ?>
                            <span>•</span>
                            <i data-lucide="clock" style="width: 12px;"></i>
                            <?= date('d/m/Y', strtotime($aviso['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="bell-off" style="width: 30px; color: var(--text-muted);"></i>
                    </div>
                    <h4 class="empty-state-title">Nenhum aviso</h4>
                    <p class="empty-state-text">Não há avisos no momento</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php
renderAppFooter();
?>