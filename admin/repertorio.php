<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório');

renderPageHeader('Repertório');
?>

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
        $tagId = $_GET['tag_id'] ?? null;
        try {
            if ($tagId) {
                // Busca por Tag
                $sql = "
                    SELECT s.* 
                    FROM songs s 
                    JOIN song_tags st ON s.id = st.song_id 
                    WHERE st.tag_id = :tagId 
                    ORDER BY s.title ASC 
                    LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['tagId' => $tagId]);
            } else {
                // Busca Normal
                $sql = "SELECT * FROM songs WHERE title LIKE :q OR artist LIKE :q ORDER BY title ASC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['q' => "%$search%"]);
            }
            $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $songs = [];
        }
    ?>

        <?php if ($tagId):
            // Mostrar qual tag está filtrada
            // Precisamos buscar o nome da tag
            $stmtTag = $pdo->prepare("SELECT name, color FROM tags WHERE id = ?");
            $stmtTag->execute([$tagId]);
            $currentTag = $stmtTag->fetch(PDO::FETCH_ASSOC);
        ?>
            <?php if ($currentTag): ?>
                <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; background: <?= $currentTag['color'] ?>15; padding: 12px 16px; border-radius: 12px; border: 1px solid <?= $currentTag['color'] ?>30;">
                    <div style="display: flex; align-items: center; gap: 8px; color: <?= $currentTag['color'] ?>; font-weight: 700;">
                        <i data-lucide="folder-open" style="width: 20px;"></i>
                        Pasta: <?= htmlspecialchars($currentTag['name']) ?>
                    </div>
                    <a href="repertorio.php?tab=musicas" style="color: <?= $currentTag['color'] ?>; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                        <i data-lucide="x" style="width: 16px;"></i> Limpar
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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

    <!-- Conteúdo: Pastas (Tags) -->
    <?php if ($tab === 'pastas'):
        try {
            // Busca tags com contagem de músicas
            $sql = "
                SELECT t.*, COUNT(st.song_id) as count 
                FROM tags t 
                LEFT JOIN song_tags st ON t.id = st.tag_id 
                GROUP BY t.id 
                ORDER BY t.name ASC
            ";
            $stmt = $pdo->query($sql);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tags = [];
        }
    ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
            <?php foreach ($tags as $tag):
                // Cor de fundo mais suave baseada na cor da tag
                $bgHex = $tag['color'] ?? '#047857';
                // Converter hex para rgb para usar opacidade se necessário, ou usar direto
            ?>
                <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="ripple" style="
                background: white; 
                border-radius: 20px; 
                padding: 20px; 
                text-decoration: none; 
                border: 1px solid #f1f5f9;
                display: flex; 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 12px; 
                transition: transform 0.2s, box-shadow 0.2s;
                position: relative;
                overflow: hidden;
            " onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 10px 20px -10px rgba(0,0,0,0.1)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">

                    <!-- Barra de cor lateral -->
                    <div style="
                    position: absolute; left: 0; top: 0; bottom: 0; width: 6px; 
                    background: <?= $bgHex ?>;
                "></div>

                    <div style="
                    width: 44px; height: 44px; 
                    background: <?= $bgHex ?>20; /* 20% opacidade */
                    border-radius: 12px; 
                    color: <?= $bgHex ?>;
                    display: flex; align-items: center; justify-content: center;
                ">
                        <i data-lucide="folder-heart" style="width: 24px;"></i>
                    </div>

                    <div style="width: 100%;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 1rem; margin-bottom: 4px; line-height: 1.2;">
                            <?= htmlspecialchars($tag['name']) ?>
                        </div>
                        <?php if (!empty($tag['description'])): ?>
                            <div style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 8px;">
                                <?= htmlspecialchars($tag['description']) ?>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 0.8rem; font-weight: 600; color: #64748b; background: #f8fafc; padding: 4px 10px; border-radius: 20px; display: inline-block;">
                            <?= $tag['count'] ?> músicas
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <!-- Botão para gerenciar tags -->
            <a href="classificacoes.php" class="ripple" style="
            background: #f8fafc; 
            border-radius: 20px; 
            padding: 20px; 
            text-decoration: none; 
            border: 2px dashed #cbd5e1;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            gap: 12px; 
            color: #94a3b8;
            transition: all 0.2s;
        ">
                <div style="
                width: 44px; height: 44px; 
                background: #e2e8f0; 
                border-radius: 50%; 
                display: flex; align-items: center; justify-content: center;
            ">
                    <i data-lucide="plus" style="width: 24px;"></i>
                </div>
                <div style="font-weight: 600; font-size: 0.9rem;">Nova Pasta</div>
            </a>

            <?php if (empty($tags)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                    <p>Nenhuma pasta encontrada.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Artistas -->
    <?php if ($tab === 'artistas'):
        try {
            $sql = "SELECT artist as name, COUNT(*) as count FROM songs WHERE artist IS NOT NULL AND artist != '' GROUP BY artist ORDER BY artist ASC";
            $stmt = $pdo->query($sql);
            $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $artists = [];
        }
    ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($artists as $artist): ?>
                <a href="repertorio.php?tab=musicas&q=<?= urlencode($artist['name']) ?>" class="ripple" style="
                display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; border-radius: 12px; background: white; border: 1px solid white; transition: background 0.2s;
            ">
                    <!-- Avatar do Artista -->
                    <div style="
                    width: 48px; height: 48px; border-radius: 50%; background: #f1f5f9; 
                    background-image: url('https://ui-avatars.com/api/?name=<?= urlencode($artist['name']) ?>&background=random&color=fff&size=96');
                    background-size: cover;
                "></div>

                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 1rem;"><?= htmlspecialchars($artist['name']) ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;"><?= $artist['count'] ?> músicas</div>
                    </div>

                    <i data-lucide="chevron-right" style="width: 20px; color: #cbd5e1;"></i>
                </a>
                <div style="height: 1px; background: #f1f5f9; margin: 0 12px;"></div>
            <?php endforeach; ?>
            <?php if (empty($artists)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <p>Nenhum artista encontrado.</p>
                </div>
            <?php endif; ?>
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