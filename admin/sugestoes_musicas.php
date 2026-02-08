<?php
// admin/sugestoes_musicas.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Apenas admin
if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: repertorio.php');
    exit;
}

// Filtros
$tab = $_GET['tab'] ?? 'pending'; // pending, approved, rejected

// Processar Ações (Aprovar/Rejeitar) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($id && in_array($action, ['approve', 'reject'])) {
        try {
            $pdo->beginTransaction();
            
            if ($action === 'approve') {
                // 1. Get Suggestion
                $stmt = $pdo->prepare("SELECT * FROM song_suggestions WHERE id = ?");
                $stmt->execute([$id]);
                $sug = $stmt->fetch();
                
                if ($sug) {
                    // 2. Insert Song
                    $stmtIns = $pdo->prepare("INSERT INTO songs (title, artist, tone) VALUES (?, ?, ?)");
                    $stmtIns->execute([$sug['title'], $sug['artist'], $sug['tone']]);
                    
                    // 3. Update Suggestion
                    $stmtUpd = $pdo->prepare("UPDATE song_suggestions SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
                    $stmtUpd->execute([$id]);
                }
            } else {
                // Reject
                $stmtUpd = $pdo->prepare("UPDATE song_suggestions SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
                $stmtUpd->execute([$id]);
            }
            
            $pdo->commit();
            header("Location: sugestoes_musicas.php?tab=$tab&success=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao processar: " . $e->getMessage();
        }
    }
}

// Buscar Dados
$sql = "SELECT s.*, u.name as user_name, u.photo as user_photo 
        FROM song_suggestions s
        JOIN users u ON s.user_id = u.id
        WHERE s.status = :status
        ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['status' => $tab]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Gestão de Sugestões');
renderPageHeader('Gestão de Sugestões', 'Aprove ou rejeite músicas sugeridas');
?>

<!-- Estilos Compartilhados com Repertório (Compact Cards) -->
<style>
    .timeline-card.compact .card-content-wrapper { padding: 8px 12px; gap: 10px; }
    .compact-card {
        display: flex; align-items: center; gap: 12px;
        background: var(--bg-surface); border: 1px solid var(--border-color);
        padding: 12px; border-radius: 12px; text-decoration: none; color: inherit;
        transition: all 0.2s; position: relative; margin-bottom: 8px;
    }
    .compact-card:hover { transform: translateX(2px); border-color: var(--primary); }
    .compact-card-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-direction: column; flex-shrink: 0;
    }
    .compact-card-content { flex: 1; min-width: 0; }
    .compact-card-title { font-weight: 700; color: var(--text-primary); font-size: 0.95rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .compact-card-subtitle { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; }
    
    /* Sugestões Específico */
    .user-avatar-mini { width: 16px; height: 16px; border-radius: 50%; object-fit: cover; }
    .btn-action-group { display: flex; gap: 8px; margin-top: 8px; }
    .btn-xs { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 4px; }
    .btn-approve { background: #dcfce7; color: #166534; }
    .btn-reject { background: #fee2e2; color: #991b1b; }
</style>

<div style="max-width: 800px; margin: 0 auto; padding: 16px;">

    <!-- Tabs Navegação (Igual Repertório) -->
    <div class="repertorio-controls" style="margin-bottom: 24px;">
        <div class="tabs-container">
            <a href="?tab=pending" class="tab-link <?= $tab == 'pending' ? 'active' : '' ?>">Pendentes</a>
            <a href="?tab=approved" class="tab-link <?= $tab == 'approved' ? 'active' : '' ?>">Aprovadas</a>
            <a href="?tab=rejected" class="tab-link <?= $tab == 'rejected' ? 'active' : '' ?>">Rejeitadas</a>
        </div>
    </div>

    <!-- Lista de Sugestões -->
    <div class="results-list">
        <?php if (empty($suggestions)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                <i data-lucide="<?= $tab == 'pending' ? 'inbox' : ($tab == 'approved' ? 'check-circle' : 'x-circle') ?>" 
                   style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.2;"></i>
                <p>Nenhuma sugestão <?= $tab == 'pending' ? 'pendente' : ($tab == 'approved' ? 'aprovada' : 'rejeitada') ?>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $sug): 
                $userPhoto = $sug['user_photo'] ?: 'https://ui-avatars.com/api/?name='.urlencode($sug['user_name']).'&background=random';
            ?>
                <div class="compact-card" style="display: block;"> <!-- Block to allow multiline content -->
                    <div style="display: flex; align-items: start; gap: 12px;">
                        
                        <!-- Ícone / Avatar -->
                        <div class="compact-card-icon" style="background: var(--bg-surface-active);">
                            <?php if ($sug['youtube_link']): ?>
                                <i data-lucide="youtube" style="width: 20px; color: #ef4444;"></i>
                            <?php else: ?>
                                <i data-lucide="music" style="width: 20px; color: var(--text-tertiary);"></i>
                            <?php endif; ?>
                        </div>

                        <div class="compact-card-content">
                            <div class="compact-card-title"><?= htmlspecialchars($sug['title']) ?></div>
                            <div class="compact-card-subtitle">
                                <span><?= htmlspecialchars($sug['artist']) ?></span>
                                <?php if($sug['tone']): ?>
                                    <span style="background: var(--bg-surface-active); padding: 1px 6px; border-radius: 4px; font-weight: 700; font-size: 0.7rem;"><?= htmlspecialchars($sug['tone']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 6px; font-size: 0.75rem; color: var(--text-tertiary); display: flex; limit-items: center; gap: 6px;">
                                <img src="<?= $userPhoto ?>" class="user-avatar-mini">
                                <?= htmlspecialchars($sug['user_name']) ?> • <?= date('d/m/Y', strtotime($sug['created_at'])) ?>
                            </div>

                            <!-- User Reason -->
                            <?php if ($sug['reason']): ?>
                                <div style="margin-top: 6px; font-style: italic; font-size: 0.8rem; color: var(--text-secondary); background: var(--bg-main); padding: 6px 10px; border-radius: 6px;">
                                    "<?= htmlspecialchars($sug['reason']) ?>"
                                </div>
                            <?php endif; ?>

                            <!-- Botões de Ação (Apenas Pendentes) -->
                            <?php if ($tab == 'pending'): ?>
                                <div class="btn-action-group">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $sug['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-xs btn-approve">
                                            <i data-lucide="check" width="14"></i> Aprovar
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $sug['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-xs btn-reject">
                                            <i data-lucide="x" width="14"></i> Rejeitar
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Links Externos -->
                         <div style="display: flex; flex-direction: column; gap: 4px;">
                            <?php if($sug['youtube_link']): ?>
                                <a href="<?= $sug['youtube_link'] ?>" target="_blank" style="color: var(--text-tertiary);"><i data-lucide="external-link" width="16"></i></a>
                            <?php endif; ?>
                         </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php renderAppFooter(); ?>
