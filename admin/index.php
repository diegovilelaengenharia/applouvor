<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';





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
        padding: 20px 20px 40px 20px; 
        border-radius: 0 0 24px 24px; 
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: visible;
    ">
        <!-- Navigation Buttons (Top Right) -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 12px; gap: 12px; align-items: center;">

            <?php renderGlobalNavButtons(); ?>
                        background: #EF4444;
                        color: white;
                        font-size: 0.7rem;
                        font-weight: 700;
                        border-radius: 10px;
                        border: 2px solid #047857; /* Matches header bg for cutout effect */
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- 3. Avatar (Profile) -->
            <button onclick="openSheet('sheet-perfil')" class="ripple" style="
                width: 44px; 
                height: 44px; 
                border-radius: 14px;
                padding: 2px;
                background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.1) 100%);
                border: 1px solid rgba(255,255,255,0.3);
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <?php
                $displayAvatar = $_SESSION['user_avatar'] ?? '';

                // Specific fix for Diego
                if (($_SESSION['user_name'] ?? '') === 'Diego') {
                    // Check if specific file exists (assuming we know it's diego_avatar.jpg based on file list)
                    if (file_exists('../assets/uploads/diego_avatar.jpg')) {
                        $displayAvatar = 'diego_avatar.jpg';
                    }
                }
                ?>

                <?php if (!empty($displayAvatar)): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($displayAvatar) ?>" style="
                        width: 100%; 
                        height: 100%; 
                        border-radius: 12px; 
                        object-fit: cover;
                    ">
                <?php else: ?>
                    <div style="
                        width: 100%; 
                        height: 100%; 
                        background: rgba(255,255,255,0.9);
                        border-radius: 12px;
                        display: flex; 
                        align-items: center; 
                        justify-content: center;
                        color: #047857;
                        font-weight: 700;
                        font-size: 1.1rem;
                    ">
                        <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                    </div>
                <?php endif; ?>
            </button>

        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1; min-width: 0;">
                <h1 style="color: white; margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;">Gestão Louvor</h1>
                <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.875rem;">Painel de Liderança</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">




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
                                line-clamp: 2;
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