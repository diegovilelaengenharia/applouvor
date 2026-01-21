<?php
// admin/musica_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: repertorio.php');
    exit;
}

// Buscar Música
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Música não encontrada.");
}

// Buscar Tags
$stmtTags = $pdo->prepare("
    SELECT t.* 
    FROM tags t 
    JOIN song_tags st ON st.tag_id = t.id 
    WHERE st.song_id = ?
");
$stmtTags->execute([$id]);
$tags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Detalhes da Música');
?>
<style>
    :root {
        --bg-tertiary: #f8fafc;
        --border-subtle: #e2e8f0;
        --text-secondary: #64748b;
        --text-primary: #1e293b;
        --accent-interactive: #047857;
        --bg-secondary: #f1f5f9;
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --text-muted: #94a3b8;
    }

    .song-header {
        text-align: center;
        padding: 24px 16px;
        background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        color: white;
    }

    .info-section {
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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

// Header com Botão Voltar (já incluído no layout, mas podemos personalizar se quiser)
// Vamos usar o renderPageHeader padrão
renderPageHeader('Detalhes da Música', $song['artist']);

// Ícone Principal (separado do header agora, como destaque no topo do corpo)
?>
<div style="text-align: center; margin-bottom: 24px;">
    <div style="
        width: 64px; 
        height: 64px; 
        background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
        border-radius: 16px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
    ">
        <i data-lucide="music" style="width: 32px; height: 32px; color: white;"></i>
    </div>
    <h1 style="color: #1e293b; margin: 16px 0 4px 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($song['title']) ?></h1>
    <p style="color: #64748b; margin: 0; font-weight: 500; font-size: 1rem;"><?= htmlspecialchars($song['artist']) ?></p>
</div>
<?php

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