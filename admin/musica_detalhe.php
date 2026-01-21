<?php
// admin/musica_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['id'])) {
    header('Location: repertorio.php');
    exit;
}

$id = $_GET['id'];

// Buscar música
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    header('Location: repertorio.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_song') {
    try {
        $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: repertorio.php");
        exit;
    } catch (PDOException $e) {
        // Tratar erro de chave estrangeira se houver
        echo "<script>alert('Erro ao excluir música. Ela pode estar em uso em alguma escala.');</script>";
    }
}

renderAppHeader('Música');

?>

<style>
    .song-header {
        text-align: center;
        padding: 24px 16px;
        background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        color: white;
    }

    .song-header-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .song-header-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .song-header-artist {
        font-size: 1rem;
        opacity: 0.9;
    }

    
    .info-section {
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }


    .info-section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }

    .info-item {
        text-align: center;
        padding: 12px;
        background: var(--bg-tertiary);
        border-radius: 8px;
    }

    .info-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--accent-interactive);
    }

    .link-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: var(--bg-tertiary);
        border-radius: 10px;
        margin-bottom: 8px;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .link-item:hover {
        background: var(--bg-secondary);
        border-color: var(--accent-interactive);
    }

    .link-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .link-info {
        flex: 1;
    }

    .link-title {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 0.95rem;
    }

    .link-url {
        font-size: 0.75rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="repertorio.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; gap: 12px; align-items: center;">
            <!-- Edit Button -->
            

            <?php renderGlobalNavButtons(); ?>
        </div>
    </div>

    <!-- Song Info in Hero -->
    <div style="text-align: center;">
        <div style="
            width: 64px; 
            height: 64px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 16px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 16px;
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="music" style="width: 32px; height: 32px; color: white;"></i>
        </div>
        <h1 style="color: white; margin: 0; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;"><?= htmlspecialchars($song['title']) ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 8px; font-weight: 500; font-size: 1.1rem;"><?= htmlspecialchars($song['artist']) ?></p>
    </div>
</div>

<!-- Versão -->
<div class="info-section">
    <div class="info-section-title">Versão (Original)</div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Tom</div>
            <div class="info-value"><?= $song['tone'] ?: '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Duração</div>
            <div class="info-value"><?= $song['duration'] ?: '-' ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">BPM</div>
            <div class="info-value"><?= $song['bpm'] ?: '-' ?></div>
        </div>
    </div>
</div>

<!-- Classificações -->
<div class="info-section">
    <div class="info-section-title">Classificações</div>
    <div style="padding: 8px 12px; background: rgba(45, 122, 79, 0.1); border-radius: 8px; display: inline-block;">
        <span style="color: var(--accent-interactive); font-weight: 700;"><?= htmlspecialchars($song['category']) ?></span>
    </div>
</div>

<!-- Referências -->
<div class="info-section">
    <div class="info-section-title">Referências</div>

    <?php if ($song['link_letra']): ?>
        <a href="<?= htmlspecialchars($song['link_letra']) ?>" target="_blank" class="link-item ripple">
            <div class="link-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                <i data-lucide="file-text" style="width: 20px; color: white;"></i>
            </div>
            <div class="link-info">
                <div class="link-title">Letra</div>
                <div class="link-url"><?= htmlspecialchars($song['link_letra']) ?></div>
            </div>
            <i data-lucide="external-link" style="width: 18px; color: var(--text-muted);"></i>
        </a>
    <?php endif; ?>

    <?php if ($song['link_cifra']): ?>
        <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="link-item ripple">
            <div class="link-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                <i data-lucide="music-2" style="width: 20px; color: white;"></i>
            </div>
            <div class="link-info">
                <div class="link-title">Cifra</div>
                <div class="link-url"><?= htmlspecialchars($song['link_cifra']) ?></div>
            </div>
            <i data-lucide="external-link" style="width: 18px; color: var(--text-muted);"></i>
        </a>
    <?php endif; ?>

    <?php if ($song['link_audio']): ?>
        <a href="<?= htmlspecialchars($song['link_audio']) ?>" target="_blank" class="link-item ripple">
            <div class="link-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                <i data-lucide="headphones" style="width: 20px; color: white;"></i>
            </div>
            <div class="link-info">
                <div class="link-title">Áudio</div>
                <div class="link-url"><?= htmlspecialchars($song['link_audio']) ?></div>
            </div>
            <i data-lucide="external-link" style="width: 18px; color: var(--text-muted);"></i>
        </a>
    <?php endif; ?>

    <?php if ($song['link_video']): ?>
        <a href="<?= htmlspecialchars($song['link_video']) ?>" target="_blank" class="link-item ripple">
            <div class="link-icon" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                <i data-lucide="video" style="width: 20px; color: white;"></i>
            </div>
            <div class="link-info">
                <div class="link-title">Vídeo</div>
                <div class="link-url"><?= htmlspecialchars($song['link_video']) ?></div>
            </div>
            <i data-lucide="external-link" style="width: 18px; color: var(--text-muted);"></i>
        </a>
    <?php endif; ?>

    <?php if (!$song['link_letra'] && !$song['link_cifra'] && !$song['link_audio'] && !$song['link_video']): ?>
        <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
            <i data-lucide="link-2" style="width: 32px; height: 32px; margin-bottom: 8px; color: var(--text-muted);"></i>
            <p>Nenhuma referência cadastrada</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($song['tags']): ?>
    <!-- Tags -->
    <div class="info-section">
        <div class="info-section-title">Tags</div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach (explode(',', $song['tags']) as $tag): ?>
                <span style="padding: 6px 12px; background: rgba(139, 92, 246, 0.1); color: #8B5CF6; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                    <?= htmlspecialchars(trim($tag)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<!-- SEÇÃO DE GERENCIAMENTO -->
<div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-subtle); margin-top: 32px; margin-bottom: 40px;">
    <h3 style="font-size: 0.85rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="settings" style="width: 14px;"></i> Gerenciamento
    </h3>
    
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <!-- Botão Editar -->
        <a href="musica_editar.php?id=<?= $id ?>" class="ripple" style="
            flex: 1;
            background: #fbbf24;
            color: #78350f;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        " onmouseover="this.style.background='#f59e0b'" onmouseout="this.style.background='#fbbf24'">
            <i data-lucide="edit-3" style="width: 20px;"></i> Editar Música
        </a>

        <!-- Botão Excluir -->
        <form method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja excluir esta música?')" style="margin: 0; flex: 1;">
            <input type="hidden" name="action" value="delete_song">
            <button type="submit" class="ripple" style="
                width: 100%;
                background: #ef4444;
                color: white;
                padding: 16px;
                border-radius: 12px;
                font-weight: 700;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: all 0.2s;
                border: none;
            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <i data-lucide="trash-2" style="width: 20px;"></i> Excluir Música
            </button>
        </form>
    </div>
</div>

<?php renderAppFooter(); ?>