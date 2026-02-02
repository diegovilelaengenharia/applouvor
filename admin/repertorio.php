<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php';

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório');



renderPageHeader('Repertório', 'Gestão de Músicas');
?>

<!-- Tabs Navegação com Menu -->
<div style="display: flex; align-items: center; gap: 12px; margin: 0 16px 24px 16px; max-width: 800px; margin-left: auto; margin-right: auto;">
    <div style="background: var(--bg-body); padding: 4px; border-radius: 12px; display: flex; flex: 1;">
        <a href="?tab=musicas" class="ripple" style="
            flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: var(--font-body); font-weight: 600; transition: all 0.2s;
            <?= $tab == 'musicas' ? 'background: var(--bg-surface); color: var(--primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>
        ">Músicas</a>
        <a href="?tab=pastas" class="ripple" style="
            flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: var(--font-body); font-weight: 600; transition: all 0.2s;
            <?= $tab == 'pastas' ? 'background: var(--bg-surface); color: var(--primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>
        ">TAG's</a>
        <a href="?tab=artistas" class="ripple" style="
            flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: var(--font-body); font-weight: 600; transition: all 0.2s;
            <?= $tab == 'artistas' ? 'background: var(--bg-surface); color: var(--primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>
        ">Artistas</a>
        <a href="?tab=tons" class="ripple" style="
            flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: var(--font-body); font-weight: 600; transition: all 0.2s;
            <?= $tab == 'tons' ? 'background: var(--bg-surface); color: var(--primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>
        ">Tons</a>
    </div>
    
    <!-- Botão de 3 Pontinhos -->
    <div style="position: relative;">
        <button onclick="toggleOptionsMenu()" id="options-menu-btn" class="ripple" style="
            width: 48px; height: 48px; 
            background: white; 
            border: 1px solid #e2e8f0; 
            color: #64748b; 
            border-radius: 14px; 
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="12" cy="5" r="1"/>
                <circle cx="12" cy="19" r="1"/>
            </svg>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="options-menu" style="
            display: none;
            position: absolute;
            top: 56px;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1000;
            overflow: hidden;
        ">


        </div>
    </div>
</div>

<script>
    function toggleOptionsMenu() {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            btn.style.background = '#f1f5f9';
        } else {
            menu.style.display = 'none';
            btn.style.background = 'white';
        }
    }
    
    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (menu && btn && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
            btn.style.background = 'white';
        }
    });
</script>

<!-- Conteúdo das Tabs -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- Busca -->
    <form style="margin-bottom: 24px;">
        <div style="position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 20px;"></i>
            <input type="text" name="q" placeholder="Buscar música, artista..." value="<?= htmlspecialchars($search) ?>" style="
                width: 100%; padding: 12px 14px 12px 48px; border-radius: var(--radius-md); 
                border: 1px solid var(--border-color); font-size: var(--font-body); outline: none; 
                transition: border 0.2s; background: var(--bg-surface); color: var(--text-main);
                box-shadow: var(--shadow-sm);
            " onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-color)'">
        </div>
    </form>

    <?php if ($tab === 'musicas'):
        $tagId = $_GET['tag_id'] ?? null;
        $tone = $_GET['tone'] ?? null;
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
            } elseif ($tone) {
                // Busca por Tom
                $sql = "SELECT * FROM songs WHERE tone = :tone ORDER BY title ASC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['tone' => $tone]);
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
                    <a href="repertorio.php?tab=musicas" style="color: <?= $currentTag['color'] ?>; text-decoration: none; font-size: var(--font-body-sm); font-weight: 600;">
                        <i data-lucide="x" style="width: 16px;"></i> Limpar
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($tone):
            // Cores dos tons
            $toneColors = [
                'C' => '#ef4444', 'C#' => '#f97316', 'Db' => '#f97316',
                'D' => '#f59e0b', 'D#' => '#84cc16', 'Eb' => '#84cc16',
                'E' => '#22c55e', 'F' => '#14b8a6', 'F#' => '#06b6d4', 'Gb' => '#06b6d4',
                'G' => '#3b82f6', 'G#' => '#6366f1', 'Ab' => '#6366f1',
                'A' => '#8b5cf6', 'A#' => '#a855f7', 'Bb' => '#a855f7',
                'B' => '#ec4899'
            ];
            $toneColor = $toneColors[$tone] ?? '#047857';
        ?>
            <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; background: <?= $toneColor ?>15; padding: 12px 16px; border-radius: 12px; border: 1px solid <?= $toneColor ?>30;">
                <div style="display: flex; align-items: center; gap: 8px; color: <?= $toneColor ?>; font-weight: 700;">
                    <i data-lucide="music" style="width: 20px;"></i>
                    Tom: <?= htmlspecialchars($tone) ?>
                </div>
                <a href="repertorio.php?tab=musicas" style="color: <?= $toneColor ?>; text-decoration: none; font-size: var(--font-body-sm); font-weight: 600;">
                    <i data-lucide="x" style="width: 16px;"></i> Limpar
                </a>
            </div>
        <?php endif; ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($songs as $song): 
                // Buscar tags desta música
                $stmtSongTags = $pdo->prepare("
                    SELECT t.id, t.name, t.color 
                    FROM tags t
                    JOIN song_tags st ON t.id = st.tag_id
                    WHERE st.song_id = ?
                    ORDER BY t.name
                ");
                $stmtSongTags->execute([$song['id']]);
                $songTags = $stmtSongTags->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="ripple" style="
                    display: flex; align-items: center; gap: 12px; text-decoration: none; 
                    padding: 10px; /* Padding reduzido */
                    border-radius: 12px; 
                    background: var(--bg-surface); 
                    border: 1px solid var(--border-color);
                    transition: all 0.2s;
                    box-shadow: var(--shadow-sm);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'"
                    onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)'">
                    <!-- Cover Placeholder Compact -->
                    <div style="
                        width: 40px; height: 40px; flex-shrink: 0; /* Cover reduzido */
                        background: url('https://ui-avatars.com/api/?name=<?= urlencode($song['title']) ?>&background=047857&color=fff&size=80') center/cover; 
                        border-radius: 8px;
                    "></div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: var(--font-body); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;"><?= htmlspecialchars($song['title']) ?></div>
                        <div style="color: var(--text-muted); font-size: var(--font-body-sm); margin-top: 1px;"><?= htmlspecialchars($song['artist']) ?></div>
                        
                        <!-- Tags -->
                        <?php if (!empty($songTags)): ?>
                        <div style="display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;">
                            <?php foreach ($songTags as $tag): ?>
                            <span style="background: <?= $tag['color'] ?>20; color: <?= $tag['color'] ?>; padding: 2px 6px; border-radius: 4px; font-size: 0.6875rem; font-weight: 600; border: 1px solid <?= $tag['color'] ?>40;">
                                <?= htmlspecialchars($tag['name']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: right; min-width: 50px;">
                        <div style="font-size: var(--font-caption); font-weight: 700; color: var(--primary); background: var(--primary-subtle); padding: 2px 6px; border-radius: 6px; display: inline-block;">
                            <?= $song['tone'] ?: '-' ?>
                        </div>
                        <div style="font-size: var(--font-caption); color: var(--text-muted); margin-top: 2px;">BPM: <?= $song['bpm'] ?: '-' ?></div>
                    </div>
                </a>
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
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($tags as $tag):
                $bgHex = $tag['color'] ?? '#047857';
            ?>
                <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="ripple" style="
                display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; 
                border-radius: var(--radius-lg); background: var(--bg-surface); border: 1px solid var(--border-color);
                box-shadow: var(--shadow-sm); transition: all 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'"
                onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)'">
                    
                    <!-- Ícone da TAG -->
                    <div style="
                        width: 48px; height: 48px; 
                        background: <?= $bgHex ?>20;
                        border-radius: 12px; 
                        color: <?= $bgHex ?>;
                        display: flex; align-items: center; justify-content: center;
                        flex-shrink: 0;
                    ">
                        <i data-lucide="folder-heart" style="width: 24px;"></i>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: var(--font-h3); margin-bottom: 2px;">
                            <?= htmlspecialchars($tag['name']) ?>
                        </div>
                        <?php if (!empty($tag['description'])): ?>
                            <div style="color: var(--text-muted); font-size: var(--font-body-sm); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($tag['description']) ?>
                            </div>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-size: var(--font-body-sm);">
                                <?= $tag['count'] ?> músicas
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Barra lateral colorida -->
                    <div style="
                        width: 4px; height: 48px; 
                        background: <?= $bgHex ?>; 
                        border-radius: 4px;
                        flex-shrink: 0;
                    "></div>
                </a>
            <?php endforeach; ?>

            <!-- Botão para gerenciar tags -->
            <a href="classificacoes.php" class="ripple" style="
                display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; 
                border-radius: var(--radius-lg); background: var(--bg-body); border: 2px dashed var(--border-color);
                transition: all 0.2s; color: var(--text-muted);
            ">
                <div style="
                    width: 48px; height: 48px; 
                    background: var(--bg-surface); 
                    border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center;
                    box-shadow: var(--shadow-sm);
                ">
                    <i data-lucide="plus" style="width: 24px;"></i>
                </div>
                <div style="font-weight: 600; font-size: var(--font-body);">Nova TAG</div>
            </a>

            <?php if (empty($tags)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <p>Nenhuma TAG encontrada.</p>
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
                display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; 
                border-radius: var(--radius-lg); background: var(--bg-surface); border: 1px solid var(--border-color);
                box-shadow: var(--shadow-sm); transition: all 0.2s;
            ">
                    <!-- Avatar do Artista -->
                    <div style="
                    width: 48px; height: 48px; border-radius: 50%; background: var(--bg-body); 
                    background-image: url('https://ui-avatars.com/api/?name=<?= urlencode($artist['name']) ?>&background=random&color=fff&size=96');
                    background-size: cover;
                "></div>

                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: var(--font-h3);"><?= htmlspecialchars($artist['name']) ?></div>
                        <div style="color: var(--text-muted); font-size: var(--font-body-sm);"><?= $artist['count'] ?> músicas</div>
                    </div>

                    <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
                </a>
            <?php endforeach; ?>
            <?php if (empty($artists)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <p>Nenhum artista encontrado.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Tons -->
    <?php if ($tab === 'tons'):
        // Cores dos tons
        $toneColors = [
            'C' => '#ef4444', 'C#' => '#f97316', 'Db' => '#f97316',
            'D' => '#f59e0b', 'D#' => '#84cc16', 'Eb' => '#84cc16',
            'E' => '#22c55e', 'F' => '#14b8a6', 'F#' => '#06b6d4', 'Gb' => '#06b6d4',
            'G' => '#3b82f6', 'G#' => '#6366f1', 'Ab' => '#6366f1',
            'A' => '#8b5cf6', 'A#' => '#a855f7', 'Bb' => '#a855f7',
            'B' => '#ec4899'
        ];
        
        try {
            $sql = "SELECT tone as name, COUNT(*) as count FROM songs WHERE tone IS NOT NULL AND tone != '' GROUP BY tone ORDER BY tone ASC";
            $stmt = $pdo->query($sql);
            $tones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tones = [];
        }
    ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($tones as $toneItem):
                $toneName = $toneItem['name'];
                $bgHex = $toneColors[$toneName] ?? '#047857';
            ?>
                <a href="repertorio.php?tab=musicas&tone=<?= urlencode($toneName) ?>" class="ripple" style="
                display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 12px; 
                border-radius: var(--radius-lg); background: var(--bg-surface); border: 1px solid var(--border-color);
                box-shadow: var(--shadow-sm); transition: all 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'"
                onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)'">
                    
                    <!-- Ícone do Tom -->
                    <div style="
                        width: 48px; height: 48px; 
                        background: <?= $bgHex ?>20;
                        border-radius: 12px; 
                        color: <?= $bgHex ?>;
                        display: flex; align-items: center; justify-content: center;
                        flex-shrink: 0;
                    ">
                        <i data-lucide="music" style="width: 24px;"></i>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: var(--font-h3); margin-bottom: 2px;">
                            Tom <?= htmlspecialchars($toneName) ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: var(--font-body-sm);">
                            <?= $toneItem['count'] ?> músicas
                        </div>
                    </div>

                    <!-- Barra lateral colorida -->
                    <div style="
                        width: 4px; height: 48px; 
                        background: <?= $bgHex ?>; 
                        border-radius: 4px;
                        flex-shrink: 0;
                    "></div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($tones)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <p>Nenhum tom encontrado no repertório.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php renderAppFooter(); ?>