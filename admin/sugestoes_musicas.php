<?php
// admin/sugestoes_musicas.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// Apenas admin
checkAdmin();

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

<style>
    .bento-suggestion-card {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-suggestion-card {
        background: var(--bg-surface, #1A1B1F);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .bento-suggestion-card:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        transform: translateY(-1.5px);
    }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<main class="max-w-[800px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 font-hanken">
    
    <!-- Feedbacks de erro ou sucesso -->
    <?php if (isset($error)): ?>
        <div class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/30 text-rose-500 p-4 rounded-2xl mb-6 animate-fade-in">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <span class="text-xs font-semibold"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 p-4 rounded-2xl mb-6 animate-fade-in">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <span class="text-xs font-semibold">Ação processada e registrada com sucesso!</span>
        </div>
    <?php endif; ?>

    <!-- Navegação por Abas (Pílulas Modernas) -->
    <div class="flex bg-ghost-gray dark:bg-surface-variant/10 p-1.5 rounded-full border border-outline-variant/30 w-fit mb-8 overflow-x-auto max-w-full gap-1 hide-scrollbar shadow-sm">
        <a href="?tab=pending" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'pending' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Pendentes
        </a>
        <a href="?tab=approved" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'approved' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Aprovadas
        </a>
        <a href="?tab=rejected" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'rejected' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Rejeitadas
        </a>
    </div>

    <!-- Lista de Sugestões -->
    <div class="flex flex-col gap-4">
        <?php if (empty($suggestions)): ?>
            <div class="flex flex-col items-center justify-center text-center p-12 bg-white dark:bg-deep-navy border border-outline-variant/20 rounded-2xl shadow-sm">
                <div class="w-16 h-16 rounded-full bg-ghost-gray dark:bg-surface-variant/10 flex items-center justify-center mb-4 text-secondary/40">
                    <i data-lucide="<?= $tab == 'pending' ? 'inbox' : ($tab == 'approved' ? 'check-circle' : 'x-circle') ?>" class="w-8 h-8"></i>
                </div>
                <h3 class="font-extrabold text-sm text-on-background">Nenhuma sugestão encontrada</h3>
                <p class="text-xs text-secondary mt-1 max-w-xs">Não existem sugestões marcadas como <?= $tab == 'pending' ? 'pendentes de moderação' : ($tab == 'approved' ? 'aprovadas pelo ministério' : 'rejeitadas atualmente') ?>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $sug): 
                $userName = $sug['user_name'] ?: 'Membro';
                $userPhoto = $sug['user_photo'];
                if ($userPhoto) {
                    if (strpos($userPhoto, 'http') === false && strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
                        $userPhoto = '../uploads/' . $userPhoto;
                    }
                } else {
                    $userPhoto = 'https://ui-avatars.com/api/?name='.urlencode($userName).'&background=dbeafe&color=1e40af';
                }
            ?>
                <div class="bento-suggestion-card rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
                    
                    <div class="flex items-start gap-4 flex-1 min-w-0">
                        <!-- Avatar do Proponente -->
                        <div class="w-12 h-12 rounded-full overflow-hidden shrink-0 border border-outline-variant/30 shadow-sm relative">
                            <img src="<?= $userPhoto ?>" alt="<?= htmlspecialchars($userName) ?>" class="w-full h-full object-cover">
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <!-- Título e Tom -->
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-extrabold text-base text-on-background leading-tight truncate">
                                    <?= htmlspecialchars($sug['title']) ?>
                                </h3>
                                <?php if ($sug['tone']): ?>
                                    <span class="px-2.5 py-0.5 rounded-md bg-ghost-gray dark:bg-surface-variant/20 text-altar-gold border border-outline-variant/20 text-[10px] font-extrabold tracking-tight shrink-0 shadow-sm">
                                        <?= htmlspecialchars($sug['tone']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-xs text-secondary mt-1 font-bold">
                                por <?= htmlspecialchars($sug['artist']) ?>
                            </p>
                            
                            <!-- Proponente e data -->
                            <p class="text-[10px] text-secondary/60 mt-1">
                                Sugerida por <span class="font-bold text-on-background/70"><?= htmlspecialchars($userName) ?></span> em <?= date('d/m/Y', strtotime($sug['created_at'])) ?>
                            </p>

                            <!-- Justificativa (Reason) -->
                            <?php if ($sug['reason']): ?>
                                <div class="mt-4 text-xs italic text-secondary bg-ghost-gray/40 dark:bg-surface-variant/5 border border-outline-variant/10 rounded-xl p-3 leading-relaxed relative pl-8">
                                    <i data-lucide="quote-left" class="w-3.5 h-3.5 text-worship-blue/40 absolute left-3 top-3.5"></i>
                                    "<?= htmlspecialchars($sug['reason']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap md:flex-col items-start md:items-end gap-3 shrink-0 border-t md:border-t-0 border-outline-variant/10 pt-4 md:pt-0">
                        <!-- Links Externos de Mídia -->
                        <?php if ($sug['youtube_link']): 
                            $mediaLink = $sug['youtube_link'];
                            $isYoutube = strpos(strtolower($mediaLink), 'youtube.com') !== false || strpos(strtolower($mediaLink), 'youtu.be') !== false;
                            $isSpotify = strpos(strtolower($mediaLink), 'spotify.com') !== false;
                        ?>
                            <a href="<?= htmlspecialchars($mediaLink) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase transition-all duration-200 active:scale-95 border shadow-sm <?php
                                if ($isYoutube) {
                                    echo 'bg-rose-500/10 border-rose-500/30 text-rose-500 hover:bg-rose-500/20';
                                } elseif ($isSpotify) {
                                    echo 'bg-emerald-500/10 border-emerald-500/30 text-emerald-500 hover:bg-emerald-500/20';
                                } else {
                                    echo 'bg-slate-500/10 border-slate-500/30 text-slate-500 hover:bg-slate-500/20';
                                }
                            ?>">
                                <?php if ($isYoutube): ?>
                                    <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                    <span>YouTube</span>
                                <?php elseif ($isSpotify): ?>
                                    <i data-lucide="music" class="w-3.5 h-3.5"></i>
                                    <span>Spotify</span>
                                <?php else: ?>
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span>Mídia</span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                        <!-- Botões de Ação de Moderação -->
                        <?php if ($tab == 'pending'): ?>
                            <div class="flex items-center gap-2 mt-1 w-full md:w-auto">
                                <!-- Aprovar (Emerald) -->
                                <form method="POST" class="inline-block flex-1 md:flex-initial">
                                    <input type="hidden" name="id" value="<?= $sug['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 h-9 bg-emerald-500 hover:bg-emerald-600 active:scale-[0.97] text-white text-[11px] font-extrabold uppercase tracking-wider rounded-xl transition-all duration-200 shadow-sm">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>Aprovar</span>
                                    </button>
                                </form>
                                
                                <!-- Rejeitar (Rose) -->
                                <form method="POST" class="inline-block flex-1 md:flex-initial">
                                    <input type="hidden" name="id" value="<?= $sug['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 h-9 border border-rose-500/30 hover:bg-rose-500/10 active:scale-[0.97] text-rose-500 text-[11px] font-extrabold uppercase tracking-wider rounded-xl transition-all duration-200">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        <span>Rejeitar</span>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Tag de Status (Aprovada/Rejeitada) -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border <?php
                                if ($tab == 'approved') {
                                    echo 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500';
                                } else {
                                    echo 'bg-rose-500/10 border-rose-500/20 text-rose-500';
                                }
                            ?>">
                                <i data-lucide="<?= $tab == 'approved' ? 'check' : 'x' ?>" class="w-3 h-3"></i>
                                <span><?= $tab == 'approved' ? 'Aprovada' : 'Rejeitada' ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php renderAppFooter(); ?>
