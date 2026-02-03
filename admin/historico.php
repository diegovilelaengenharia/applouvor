<?php
// admin/historico.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Filtros
$period = $_GET['period'] ?? '90'; // 30, 60, 90 dias
$eventType = $_GET['type'] ?? '';

// Data limite baseada no período
$dateLimit = date('Y-m-d', strtotime("-{$period} days"));

renderAppHeader('Histórico');
renderPageHeader('Histórico de Cultos', 'Análise de repertório');

// ========== ESTATÍSTICAS GERAIS ==========
try {
    // Total de cultos no período
    $stmtCultos = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM schedules 
        WHERE event_date < CURDATE() 
        AND event_date >= ?
    ");
    $stmtCultos->execute([$dateLimit]);
    $totalCultos = $stmtCultos->fetch()['total'];

    // Total de músicas diferentes tocadas
    $stmtMusicasDif = $pdo->prepare("
        SELECT COUNT(DISTINCT ss.song_id) as total
        FROM schedule_songs ss
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE s.event_date < CURDATE() 
        AND s.event_date >= ?
    ");
    $stmtMusicasDif->execute([$dateLimit]);
    $totalMusicasDif = $stmtMusicasDif->fetch()['total'];

    // Música mais tocada
    $stmtTop = $pdo->prepare("
        SELECT sg.title, sg.artist, COUNT(*) as vezes
        FROM schedule_songs ss
        JOIN songs sg ON ss.song_id = sg.id
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE s.event_date < CURDATE() 
        AND s.event_date >= ?
        GROUP BY ss.song_id
        ORDER BY vezes DESC
        LIMIT 1
    ");
    $stmtTop->execute([$dateLimit]);
    $topMusic = $stmtTop->fetch();

    // Tom mais usado
    $stmtTom = $pdo->prepare("
        SELECT sg.tone, COUNT(*) as vezes
        FROM schedule_songs ss
        JOIN songs sg ON ss.song_id = sg.id
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE s.event_date < CURDATE() 
        AND s.event_date >= ?
        AND sg.tone IS NOT NULL AND sg.tone != ''
        GROUP BY sg.tone
        ORDER BY vezes DESC
        LIMIT 1
    ");
    $stmtTom->execute([$dateLimit]);
    $topTom = $stmtTom->fetch();

    // Total de músicas no repertório
    $totalRepertorio = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();

    // Músicas do repertório nunca tocadas
    $stmtNunca = $pdo->query("
        SELECT COUNT(*) FROM songs s
        WHERE NOT EXISTS (SELECT 1 FROM schedule_songs ss WHERE ss.song_id = s.id)
    ");
    $nuncaTocadas = $stmtNunca->fetchColumn();

} catch (Exception $e) {
    $totalCultos = 0;
    $totalMusicasDif = 0;
    $topMusic = null;
    $topTom = null;
    $totalRepertorio = 0;
    $nuncaTocadas = 0;
}

// Função para gerar links de recursos externos
function getExternalLinks($title, $artist) {
    $searchQuery = urlencode("$title $artist");
    $searchQueryCifra = urlencode("$title $artist");
    
    return [
        'cifraclub' => "https://www.cifraclub.com.br/?q=" . $searchQueryCifra,
        'youtube' => "https://www.youtube.com/results?search_query=" . $searchQuery,
        'spotify' => "https://open.spotify.com/search/" . $searchQuery,
        'letras' => "https://www.letras.mus.br/?q=" . $searchQuery
    ];
}
?>

<!-- Filtros -->
<div style="max-width: 900px; margin: 0 auto 24px; padding: 0 16px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?period=30" class="ripple" style="
            padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
            <?= $period == '30' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
        ">30 dias</a>
        <a href="?period=60" class="ripple" style="
            padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
            <?= $period == '60' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
        ">60 dias</a>
        <a href="?period=90" class="ripple" style="
            padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
            <?= $period == '90' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
        ">90 dias</a>
        <a href="?period=365" class="ripple" style="
            padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
            <?= $period == '365' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
        ">1 ano</a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div style="max-width: 900px; margin: 0 auto 32px; padding: 0 16px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
        
        <!-- Total Cultos -->
        <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
            <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="calendar" style="width: 20px; color: #2563eb;"></i>
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $totalCultos ?></div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Cultos</div>
        </div>

        <!-- Músicas Diferentes -->
        <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
            <div style="width: 40px; height: 40px; background: #ecfdf5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="music" style="width: 20px; color: #059669;"></i>
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $totalMusicasDif ?></div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Músicas tocadas</div>
        </div>

        <!-- Nunca Tocadas -->
        <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
            <div style="width: 40px; height: 40px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="sparkles" style="width: 20px; color: #d97706;"></i>
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #d97706;"><?= $nuncaTocadas ?></div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Nunca tocadas</div>
        </div>

        <!-- Tom Mais Usado -->
        <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
            <div style="width: 40px; height: 40px; background: #f5f3ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                <i data-lucide="hash" style="width: 20px; color: #7c3aed;"></i>
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $topTom['tone'] ?? '-' ?></div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Tom popular</div>
        </div>
    </div>
</div>

<!-- Seção: Sugestões (Músicas para Explorar) -->
<div style="max-width: 900px; margin: 0 auto 32px; padding: 0 16px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="lightbulb" style="width: 16px; color: white;"></i>
        </div>
        <h2 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">💡 Sugestões para Explorar</h2>
    </div>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 16px;">
        Músicas do repertório que não foram tocadas recentemente ou nunca foram usadas.
    </p>

    <?php
    // Músicas nunca tocadas OU não tocadas há mais de 60 dias
    $stmtSugestoes = $pdo->prepare("
        SELECT 
            s.id, s.title, s.artist, s.tone, s.bpm,
            MAX(sc.event_date) as last_played,
            COUNT(ss.id) as times_played
        FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
        GROUP BY s.id
        HAVING MAX(sc.event_date) IS NULL OR MAX(sc.event_date) < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ORDER BY last_played IS NULL DESC, last_played ASC
        LIMIT 10
    ");
    $stmtSugestoes->execute();
    $sugestoes = $stmtSugestoes->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php foreach ($sugestoes as $sug): 
            $links = getExternalLinks($sug['title'], $sug['artist']);
        ?>
            <div style="
                padding: 12px; border-radius: 12px; 
                background: var(--bg-surface); border: 1px solid var(--border-color);
            ">
                <a href="musica_detalhe.php?id=<?= $sug['id'] ?>" style="
                    display: flex; align-items: center; gap: 12px; 
                    text-decoration: none;
                " class="ripple">
                    <div style="
                        width: 40px; height: 40px; border-radius: 10px;
                        background: <?= $sug['last_played'] ? '#fef3c7' : '#dcfce7' ?>;
                        color: <?= $sug['last_played'] ? '#d97706' : '#16a34a' ?>;
                        display: flex; align-items: center; justify-content: center;
                        flex-shrink: 0;
                    ">
                        <i data-lucide="<?= $sug['last_played'] ? 'clock' : 'sparkles' ?>" style="width: 18px;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($sug['title']) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            <?= htmlspecialchars($sug['artist']) ?>
                            <?php if ($sug['last_played']): ?>
                                • Última vez: <?= date('d/m/Y', strtotime($sug['last_played'])) ?>
                            <?php else: ?>
                                • <span style="color: #16a34a; font-weight: 600;">Nunca tocada!</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <?php if ($sug['tone']): ?>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); background: var(--primary-subtle); padding: 2px 6px; border-radius: 4px;">
                                <?= $sug['tone'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Links Externos -->
                <div style="display: flex; gap: 6px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                    <a href="<?= $links['cifraclub'] ?>" target="_blank" style="
                        display: flex; align-items: center; gap: 4px;
                        padding: 6px 10px; border-radius: 8px;
                        background: #f97316; color: white;
                        font-size: 0.7rem; font-weight: 600;
                        text-decoration: none;
                    ">
                        <i data-lucide="guitar" style="width: 12px;"></i> Cifra
                    </a>
                    <a href="<?= $links['youtube'] ?>" target="_blank" style="
                        display: flex; align-items: center; gap: 4px;
                        padding: 6px 10px; border-radius: 8px;
                        background: #ef4444; color: white;
                        font-size: 0.7rem; font-weight: 600;
                        text-decoration: none;
                    ">
                        <i data-lucide="youtube" style="width: 12px;"></i> YouTube
                    </a>
                    <a href="<?= $links['spotify'] ?>" target="_blank" style="
                        display: flex; align-items: center; gap: 4px;
                        padding: 6px 10px; border-radius: 8px;
                        background: #22c55e; color: white;
                        font-size: 0.7rem; font-weight: 600;
                        text-decoration: none;
                    ">
                        <i data-lucide="music" style="width: 12px;"></i> Spotify
                    </a>
                    <a href="<?= $links['letras'] ?>" target="_blank" style="
                        display: flex; align-items: center; gap: 4px;
                        padding: 6px 10px; border-radius: 8px;
                        background: #3b82f6; color: white;
                        font-size: 0.7rem; font-weight: 600;
                        text-decoration: none;
                    ">
                        <i data-lucide="file-text" style="width: 12px;"></i> Letra
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($sugestoes) >= 10): ?>
        <div style="text-align: center; margin-top: 12px;">
            <a href="historico_sugestoes.php" style="color: var(--primary); font-weight: 600; font-size: 0.85rem; text-decoration: none;">
                Ver todas as sugestões →
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Seção: Músicas Mais Tocadas -->
<div style="max-width: 900px; margin: 0 auto 32px; padding: 0 16px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="trophy" style="width: 16px; color: white;"></i>
        </div>
        <h2 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">🏆 Mais Tocadas</h2>
    </div>

    <?php
    $stmtMaisTocadas = $pdo->prepare("
        SELECT 
            sg.id, sg.title, sg.artist, sg.tone,
            COUNT(*) as vezes
        FROM schedule_songs ss
        JOIN songs sg ON ss.song_id = sg.id
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE s.event_date < CURDATE() AND s.event_date >= ?
        GROUP BY ss.song_id
        ORDER BY vezes DESC
        LIMIT 8
    ");
    $stmtMaisTocadas->execute([$dateLimit]);
    $maisTocadas = $stmtMaisTocadas->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php foreach ($maisTocadas as $i => $m): ?>
            <a href="musica_detalhe.php?id=<?= $m['id'] ?>" style="
                display: flex; align-items: center; gap: 12px; 
                padding: 12px; border-radius: 12px; 
                background: var(--bg-surface); border: 1px solid var(--border-color);
                text-decoration: none; transition: all 0.2s;
            " class="ripple">
                <div style="
                    width: 32px; height: 32px; border-radius: 50%;
                    background: <?= $i < 3 ? '#fef3c7' : 'var(--bg-body)' ?>;
                    color: <?= $i < 3 ? '#d97706' : 'var(--text-muted)' ?>;
                    display: flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 0.85rem;
                ">
                    <?= $i + 1 ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                        <?= htmlspecialchars($m['title']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                        <?= htmlspecialchars($m['artist']) ?>
                    </div>
                </div>
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">
                    <?= $m['vezes'] ?>x
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Seção: Timeline de Cultos -->
<div style="max-width: 900px; margin: 0 auto; padding: 0 16px 100px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="history" style="width: 16px; color: white;"></i>
        </div>
        <h2 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">📅 Histórico de Cultos</h2>
    </div>

    <?php
    // Buscar cultos passados com músicas
    $stmtCultosLista = $pdo->prepare("
        SELECT 
            s.*,
            COUNT(DISTINCT ss.song_id) as total_songs,
            COUNT(DISTINCT su.user_id) as total_participants
        FROM schedules s
        LEFT JOIN schedule_songs ss ON s.id = ss.schedule_id
        LEFT JOIN schedule_users su ON s.id = su.schedule_id
        WHERE s.event_date < CURDATE() AND s.event_date >= ?
        GROUP BY s.id
        ORDER BY s.event_date DESC
        LIMIT 20
    ");
    $stmtCultosLista->execute([$dateLimit]);
    $cultosLista = $stmtCultosLista->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($cultosLista as $culto):
            $date = new DateTime($culto['event_date']);
            
            // Buscar músicas deste culto
            $stmtMusicas = $pdo->prepare("
                SELECT sg.title, sg.artist, sg.tone
                FROM schedule_songs ss
                JOIN songs sg ON ss.song_id = sg.id
                WHERE ss.schedule_id = ?
                ORDER BY ss.position
            ");
            $stmtMusicas->execute([$culto['id']]);
            $musicas = $stmtMusicas->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div style="
                background: var(--bg-surface); 
                border: 1px solid var(--border-color); 
                border-radius: 16px; 
                overflow: hidden;
            ">
                <!-- Header do Culto -->
                <div style="
                    display: flex; align-items: center; gap: 12px; 
                    padding: 14px 16px; 
                    background: var(--bg-body);
                    border-bottom: 1px solid var(--border-color);
                ">
                    <div style="
                        min-width: 48px; text-align: center; 
                        padding: 6px; background: var(--bg-surface); 
                        border-radius: 10px; border: 1px solid var(--border-color);
                    ">
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main); line-height: 1;">
                            <?= $date->format('d') ?>
                        </div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">
                            <?= strtoupper(strftime('%b', $date->getTimestamp())) ?>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                            <?= htmlspecialchars($culto['event_type']) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; gap: 12px;">
                            <span><i data-lucide="music" style="width: 12px; display: inline;"></i> <?= $culto['total_songs'] ?> músicas</span>
                            <span><i data-lucide="users" style="width: 12px; display: inline;"></i> <?= $culto['total_participants'] ?> pessoas</span>
                        </div>
                    </div>
                    <a href="escala_detalhe.php?id=<?= $culto['id'] ?>" style="
                        color: var(--primary); font-size: 0.8rem; font-weight: 600; text-decoration: none;
                    ">Ver</a>
                </div>

                <!-- Lista de Músicas -->
                <?php if (!empty($musicas)): ?>
                    <div style="padding: 12px 16px;">
                        <?php foreach ($musicas as $i => $mus): ?>
                            <div style="
                                display: flex; align-items: center; gap: 10px; 
                                padding: 8px 0;
                                <?= $i > 0 ? 'border-top: 1px solid var(--border-color);' : '' ?>
                            ">
                                <div style="
                                    width: 24px; height: 24px; 
                                    background: var(--bg-body); 
                                    border-radius: 6px; 
                                    display: flex; align-items: center; justify-content: center;
                                    font-size: 0.7rem; font-weight: 600; color: var(--text-muted);
                                "><?= $i + 1 ?></div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($mus['title']) ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($mus['artist']) ?>
                                    </div>
                                </div>
                                <?php if ($mus['tone']): ?>
                                    <div style="font-size: 0.65rem; font-weight: 700; color: var(--primary); background: var(--primary-subtle); padding: 2px 6px; border-radius: 4px;">
                                        <?= $mus['tone'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                        <i data-lucide="music" style="width: 20px; opacity: 0.5;"></i>
                        <div>Nenhuma música registrada</div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($cultosLista)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <i data-lucide="calendar-x" style="width: 48px; opacity: 0.3; margin-bottom: 12px;"></i>
                <div style="font-weight: 600;">Nenhum culto encontrado</div>
                <div style="font-size: 0.85rem;">Tente aumentar o período de busca</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderAppFooter(); ?>
