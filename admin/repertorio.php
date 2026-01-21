<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório');
?>

<!-- Header Clean -->
<header style="
    background: white; 
    padding: 20px 24px; 
    border-bottom: 1px solid #e2e8f0; 
    margin: -16px -16px 24px -16px; 
    text-align: center;
    position: sticky; top: 0; z-index: 10;
">
    <h1 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Repertório</h1>
    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Louvor PIB Oliveira</p>
</header>

<!-- Tabs Navegação -->
<div style="background: #f1f5f9; padding: 4px; border-radius: 12px; display: flex; margin: 0 16px 24px 16px; max-width: 600px; margin-left: auto; margin-right: auto;">
    <a href="?tab=musicas" class="ripple" style="
        flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;
        <?= $tab == 'musicas' ? 'background: white; color: #166534; box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'color: #64748b;' ?>
    ">Músicas</a>
    <a href="?tab=pastas" class="ripple" style="
        flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;
        <?= $tab == 'pastas' ? 'background: white; color: #166534; box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'color: #64748b;' ?>
    ">Pastas</a>
    <a href="?tab=artistas" class="ripple" style="
        flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;
        <?= $tab == 'artistas' ? 'background: white; color: #166534; box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'color: #64748b;' ?>
    ">Artistas</a>
</div>

<!-- Conteúdo das Tabs -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- Busca -->
    <form style="margin-bottom: 24px;">
        <div style="position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 20px;"></i>
            <input type="text" name="q" placeholder="Buscar música, artista..." value="<?= htmlspecialchars($search) ?>" style="
                width: 100%; padding: 12px 12px 12px 48px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 1rem; outline: none; transition: border 0.2s;
            " onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
    </form>

    <?php if ($tab === 'musicas'):
        try {
            $sql = "SELECT * FROM songs WHERE title LIKE :q OR artist LIKE :q ORDER BY title ASC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['q' => "%$search%"]);
            $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $songs = [];
        }
    ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($songs as $song): ?>
                <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="ripple" style="display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; border-radius: 12px; background: white; border: 1px solid white; transition: background 0.2s;">
                    <!-- Cover Placeholder -->
                    <div style="width: 48px; height: 48px; background: url('https://ui-avatars.com/api/?name=<?= urlencode($song['title']) ?>&background=047857&color=fff&size=96') center/cover; border-radius: 8px;"></div>

                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 1rem;"><?= htmlspecialchars($song['title']) ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($song['artist']) ?></div>
                    </div>

                    <div style="text-align: right; min-width: 60px;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: #f59e0b;">Tom: <?= $song['tone'] ?: '-' ?></div>
                        <div style="font-size: 0.75rem; color: #3b82f6;">BPM: <?= $song['bpm'] ?: '-' ?></div>
                    </div>
                </a>
                <div style="height: 1px; background: #f1f5f9; margin: 0 12px;"></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Implementar Pastas e Artistas conforme necessário, seguindo o padrão acima -->
    <?php if ($tab === 'pastas'): ?>
        <div style="text-align: center; color: #94a3b8; padding: 40px;">
            <i data-lucide="folder-open" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Visualização de Pastas em desenvolvimento.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Floating Action Button -->
<a href="musica_adicionar.php" class="ripple" style="
    position: fixed;
    bottom: 80px; 
    right: 24px;
    background: #dcfce7;
    color: #166534;
    padding: 12px 24px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 101, 52, 0.2);
    text-decoration: none;
    font-weight: 600;
    z-index: 50;
">
    <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
    <span>Música</span>
</a>

<style>
    @media (min-width: 1025px) {

        /* No desktop, o botão fica no canto inferior direito padrão */
        a[href="musica_adicionar.php"] {
            bottom: 32px;
        }
    }
</style>

<?php renderAppFooter(); ?>