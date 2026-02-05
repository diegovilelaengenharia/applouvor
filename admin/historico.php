<?php
// admin/historico.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Configurações e Filtros
$period = $_GET['period'] ?? '90'; // 90 dias padrão para análise
$dateLimit = date('Y-m-d', strtotime("-{$period} days"));
$currentTab = $_GET['tab'] ?? 'visageral';

// Função Helper para Links Externos
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

renderAppHeader('Inteligência de Repertório');
renderPageHeader('Laboratório de Repertório', 'Análise estratégica e histórico');

// =================================================================================
// 1. QUERY DE ANALISE COMPLETA (RAIO-X)
// =================================================================================
// Esta query é a base para várias métricas. Ela retorna TODAS as músicas com suas métricas.
try {
    $sqlXRay = "
        SELECT 
            s.id, s.title, s.artist, s.tone, s.bpm,
            MAX(sc.event_date) as last_played,
            COUNT(CASE WHEN sc.event_date >= :dateLimit THEN 1 END) as freq_period,
            COUNT(ss.id) as freq_total,
            DATEDIFF(CURDATE(), MAX(sc.event_date)) as days_since_last
        FROM songs s
        LEFT JOIN schedule_songs ss ON s.id = ss.song_id
        LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
        GROUP BY s.id, s.title, s.artist, s.tone, s.bpm
        ORDER BY last_played DESC
    ";
    $stmtXRay = $pdo->prepare($sqlXRay);
    $stmtXRay->execute(['dateLimit' => $dateLimit]);
    $musicasXRay = $stmtXRay->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $musicasXRay = [];
}

// Processamento dos Dados para KPIs e Gráficos
$totalSongs = count($musicasXRay);
$songsPlayedPeriod = 0;
$songsNeverPlayed = 0;
$statusDist = ['em_alta' => 0, 'ok' => 0, 'geladeira' => 0, 'esquecida' => 0, 'virgem' => 0];
$tonsDist = [];
$artistasDist = [];

foreach ($musicasXRay as $m) {
    // KPIs Gerais
    if ($m['freq_period'] > 0) $songsPlayedPeriod++;
    if ($m['freq_total'] == 0) $songsNeverPlayed++;

    // Classificação de Status
    // Em Alta: Tokou 3+ vezes no período
    // OK: Tocou 1-2 vezes no período
    // Geladeira: Não toca há 3-6 meses (90-180 dias)
    // Esquecida: Não toca há > 6 meses (>180 dias)
    // Virgem: Nunca tocou
    $status = 'ok';
    $days = $m['days_since_last'];
    
    if ($m['freq_total'] == 0) {
        $status = 'virgem';
    } elseif ($m['freq_period'] >= 3) {
        $status = 'em_alta';
    } elseif ($days > 180) {
        $status = 'esquecida';
    } elseif ($days > 90) {
        $status = 'geladeira';
    } else {
        $status = 'ok';
    }
    $statusDist[$status]++;
    
    // Distribuição de Tons (apenas se tiver tom e foi tocada recentemente ou é popular)
    if ($m['tone'] && $m['freq_total'] > 0) {
        if (!isset($tonsDist[$m['tone']])) $tonsDist[$m['tone']] = 0;
        $tonsDist[$m['tone']]++; 
        // Nota: Idealmente contaríamos EXECUÇÕES por tom, não MÚSICAS por tom.
        // Vamos ajustar isso em uma query separada para ser mais preciso sobre o que é TOCADO.
    }
}

$renovacaoTaxa = $totalSongs > 0 ? round(($songsPlayedPeriod / $totalSongs) * 100) : 0;

// =================================================================================
// 2. QUERY ANALISE TAGS (O que estamos cantando?)
// =================================================================================
try {
    $sqlTags = "
        SELECT 
            t.name, t.color,
            COUNT(ss.id) as uses_period
        FROM tags t
        JOIN song_tags st ON t.id = st.tag_id
        JOIN schedule_songs ss ON st.song_id = ss.song_id
        JOIN schedules sc ON ss.schedule_id = sc.id
        WHERE sc.event_date >= :dateLimit AND sc.event_date < CURDATE()
        GROUP BY t.id, t.name, t.color
        ORDER BY uses_period DESC
        LIMIT 10
    ";
    $stmtTags = $pdo->prepare($sqlTags);
    $stmtTags->execute(['dateLimit' => $dateLimit]);
    $topTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $topTags = []; }

// =================================================================================
// 3. QUERY ANALISE TONS (O que estamos tocando?) - Frequência de uso real
// =================================================================================
try {
    $sqlTons = "
        SELECT 
            s.tone,
            COUNT(ss.id) as uses_period
        FROM songs s
        JOIN schedule_songs ss ON s.id = ss.song_id
        JOIN schedules sc ON ss.schedule_id = sc.id
        WHERE sc.event_date >= :dateLimit AND sc.event_date < CURDATE()
        AND s.tone IS NOT NULL AND s.tone != ''
        GROUP BY s.tone
        ORDER BY uses_period DESC
    ";
    $stmtTons = $pdo->prepare($sqlTons);
    $stmtTons->execute(['dateLimit' => $dateLimit]);
    $usoTons = $stmtTons->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $usoTons = []; }

?>

<div style="display: flex; justify-content: flex-end; padding: 0 16px; margin-bottom: -10px; position: relative; z-index: 10;">
    <button onclick="openHelpModal()" class="ripple" style="
        background: transparent; border: none; color: #376ac8; font-weight: 600; font-size: 0.85rem;
        display: flex; align-items: center; gap: 6px; cursor: pointer;
    ">
        <i data-lucide="help-circle" width="16"></i> Entenda as Métricas
    </button>
</div>

<!-- NAVEGAÇÃO SUPERIOR (TABS) -->
<div style="
    display: flex; gap: 8px; overflow-x: auto; padding: 4px 16px 16px 16px; 
    margin: 0 -16px 24px -16px; border-bottom: 1px solid var(--border-color);
    -webkit-overflow-scrolling: touch;
">
    <a href="?tab=visageral" class="ripple" style="
        padding: 10px 16px; border-radius: 20px; white-space: nowrap; font-weight: 600; font-size: 0.9rem; text-decoration: none;
        <?= $currentTab == 'visageral' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
    ">📊 Visão Geral</a>
    
    <a href="?tab=raiox" class="ripple" style="
        padding: 10px 16px; border-radius: 20px; white-space: nowrap; font-weight: 600; font-size: 0.9rem; text-decoration: none;
        <?= $currentTab == 'raiox' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
    ">🩺 Raio-X</a>
    
    <a href="?tab=estilo" class="ripple" style="
        padding: 10px 16px; border-radius: 20px; white-space: nowrap; font-weight: 600; font-size: 0.9rem; text-decoration: none;
        <?= $currentTab == 'estilo' ? 'background: var(--primary); color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
    ">🎨 Tags & Tons</a>
    
    <a href="?tab=laboratorio" class="ripple" style="
        padding: 10px 16px; border-radius: 20px; white-space: nowrap; font-weight: 600; font-size: 0.9rem; text-decoration: none;
        <?= $currentTab == 'laboratorio' ? 'background: #376ac8; color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
    ">🧪 Laboratório</a>
</div>

<!-- =================================================================================
     TAB: VISÃO GERAL
     ================================================================================= -->
<?php if ($currentTab == 'visageral'): ?>
    
    <div style="max-width: 1000px; margin: 0 auto;">
        
        <!-- KPIs de Saúde -->
        <h3 class="section-title" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="activity" style="width: 20px; color: var(--primary);"></i>
            Saúde do Repertório (<?= $period ?> dias)
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 32px;">
            
            <!-- Taxa de Renovação -->
            <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 2rem; font-weight: 800; color: <?= $renovacaoTaxa > 30 ? '#10b981' : '#f59e0b' ?>;">
                    <?= $renovacaoTaxa ?>%
                </div>
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Taxa de Uso</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">do repertório total</div>
            </div>

            <!-- Músicas em Alta -->
            <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444;">
                    <?= $statusDist['em_alta'] ?>
                </div>
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Super Expostas</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">tocadas +3x recente</div>
            </div>

            <!-- Esquecidas -->
            <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 2rem; font-weight: 800; color: #6366f1;">
                    <?= $statusDist['esquecida'] ?>
                </div>
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Esquecidas</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">+6 meses sem tocar</div>
            </div>

            <!-- Nunca Tocadas -->
            <div style="background: var(--bg-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 2rem; font-weight: 800; color: #d97706;">
                    <?= $statusDist['virgem'] ?>
                </div>
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Nunca Tocadas</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">oportunidade</div>
            </div>
        </div>

        <!-- Músicas mais Tocadas (Top 5) -->
        <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
            <div style="background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-color); padding: 20px;">
                <h4 style="margin: 0 0 16px 0; font-size: 1rem; color: var(--text-main);">🔥 Top 5 Mais Tocadas</h4>
                
                <?php 
                $top5 = array_slice($musicasXRay, 0, 5);
                foreach ($top5 as $i => $m): 
                    $percent = ($m['freq_period'] / max(1, $top5[0]['freq_period'])) * 100;
                ?>
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                            <span style="font-weight: 600; color: var(--text-main);"><?= $i+1 ?>. <?= htmlspecialchars($m['title']) ?></span>
                            <span style="font-weight: 700; color: var(--primary);"><?= $m['freq_period'] ?>x</span>
                        </div>
                        <div style="width: 100%; height: 6px; background: var(--bg-body); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Link para Timeline -->
        <div style="margin-top: 32px; text-align: center;">
            <a href="historico.php?tab=laboratorio" class="ripple" style="
                display: inline-flex; align-items: center; gap: 8px;
                background: #376ac8; color: white; padding: 12px 24px;
                border-radius: 12px; text-decoration: none; font-weight: 700;
                box-shadow: 0 4px 12px rgba(55, 106, 200, 0.3);
            ">
                <i data-lucide="flask-conical" width="20"></i>
                Ir para o Laboratório de Escolha
            </a>
        </div>

    </div>

<!-- =================================================================================
     TAB: RAIO-X (TABELA COMPLETA)
     ================================================================================= -->
<?php elseif ($currentTab == 'raiox'): ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        
        <script>
            // Script simples de ordenação para a tabela
            function sortTable(n) {
                // Implementação simplificada ou usar DataTables se disponível
                // Por enquanto vamos fazer via PHP sort se necessário ou JS simples
                alert('Funcionalidade de ordenação será implementada em breve. Use o Laboratório para filtros avançados.');
            }
        </script>

        <div style="background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr style="background: var(--bg-body); text-align: left; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">
                            <th style="padding: 16px; font-weight: 700;">Música</th>
                            <th style="padding: 16px; font-weight: 700;">Última Vez</th>
                            <th style="padding: 16px; font-weight: 700;">Status</th>
                            <th style="padding: 16px; font-weight: 700; text-align: center;">Freq (90d)</th>
                            <th style="padding: 16px; font-weight: 700; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($musicasXRay as $m): 
                            // Determine status badge
                            $days = $m['days_since_last'];
                            $badgeColor = '#94a3b8';
                            $badgeText = 'Normal';
                            
                            if ($m['freq_total'] == 0) {
                                $badgeColor = '#d97706'; $badgeText = 'Nunca Tocada';
                            } elseif ($m['freq_period'] >= 3) {
                                $badgeColor = '#ef4444'; $badgeText = 'Alta Rotatividade';
                            } elseif ($days > 180) {
                                $badgeColor = '#6366f1'; $badgeText = 'Esquecida';
                            } elseif ($days > 90) {
                                $badgeColor = '#3b82f6'; $badgeText = 'Geladeira';
                            } else {
                                $badgeColor = '#10b981'; $badgeText = 'Saudável';
                            }
                        ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($m['title']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($m['artist']) ?> • <?= $m['tone'] ?: '-' ?></div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($m['last_played']): ?>
                                    <div style="font-size: 0.9rem; color: var(--text-main);"><?= date('d/m/Y', strtotime($m['last_played'])) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $days ?> dias atrás</div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">--</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="
                                    background: <?= $badgeColor ?>20; color: <?= $badgeColor ?>;
                                    padding: 4px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700;
                                    white-space: nowrap;
                                ">
                                    <?= $badgeText ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div style="font-weight: 700; color: var(--text-main);"><?= $m['freq_period'] ?></div>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <a href="musica_detalhe.php?id=<?= $m['id'] ?>" style="
                                    display: inline-flex; align-items: center; justify-content: center;
                                    width: 32px; height: 32px; background: var(--bg-body); border-radius: 8px;
                                    color: var(--primary); text-decoration: none;
                                ">
                                    <i data-lucide="eye" width="16"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($musicasXRay)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    Nenhuma música encontrada no raio-x.
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- =================================================================================
     TAB: TAGS & TONS
     ================================================================================= -->
<?php elseif ($currentTab == 'estilo'): ?>
    
    <div style="max-width: 1000px; margin: 0 auto;">
        
        <!-- TAGS -->
        <h3 class="section-title" style="margin-bottom: 20px;">📌 Tags Mais Cantadas (Últimos <?= $period ?> dias)</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 40px;">
            <?php foreach ($topTags as $tag): 
                $maxUses = !empty($topTags) ? $topTags[0]['uses_period'] : 1;
                $percent = ($tag['uses_period'] / $maxUses) * 100;
            ?>
            <div style="background: var(--bg-surface); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                <div style="
                    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
                    background: <?= $tag['color'] ?>20; color: <?= $tag['color'] ?>;
                    display: flex; align-items: center; justify-content: center;
                ">
                    <i data-lucide="tag" width="20"></i>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($tag['name']) ?></span>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?= $tag['uses_period'] ?>x</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: var(--bg-body); border-radius: 3px;">
                        <div style="width: <?= $percent ?>%; height: 100%; background: <?= $tag['color'] ?>; border-radius: 3px;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TONS -->
        <h3 class="section-title" style="margin-bottom: 20px;">🎵 Distribuição de Tons (Nas Execuções)</h3>
        
        <div style="background: var(--bg-surface); padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); margin-bottom: 40px;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php 
                $maxTonUses = !empty($usoTons) ? $usoTons[0]['uses_period'] : 1;
                foreach ($usoTons as $ton):
                    $width = max(5, ($ton['uses_period'] / $maxTonUses) * 100);
                    $barColor = '#3b82f6';
                    // Cores para tons especificos
                    if (strpos($ton['tone'], '#') !== false) $barColor = '#8b5cf6'; // Sustenidos Roxo
                ?>
                <div style="display: grid; grid-template-columns: 40px 1fr 40px; align-items: center; gap: 12px;">
                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; text-align: right;"><?= $ton['tone'] ?></div>
                    <div style="width: 100%; bg-body; height: 24px; background: var(--bg-body); border-radius: 6px; overflow: hidden;">
                        <div style="width: <?= $width ?>%; height: 100%; background: <?= $barColor ?>; border-radius: 6px; display: flex; align-items: center; padding-left: 8px; transition: width 0.5s ease;">
                        </div>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);"><?= $ton['uses_period'] ?>x</div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($usoTons)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 20px;">Nenhum tom registrado neste período.</div>
                <?php endif; ?>
            </div>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.8rem; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                Frequência de tons utilizados nos últimos <?= $period ?> dias.
            </p>
        </div>

    </div>

<!-- =================================================================================
     TAB: LABORATÓRIO (BUSCA E FILTROS)
     ================================================================================= -->
<?php elseif ($currentTab == 'laboratorio'): ?>
    
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="
            background: #f8fafc; border: 1px solid var(--border-color); border-radius: 20px; 
            padding: 32px; color: var(--text-main); margin-bottom: 32px; 
        ">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="
                    width: 60px; height: 60px; background: white; border: 1px solid var(--border-color);
                    border-radius: 50%; display: flex; align-items: center; justify-content: center; 
                    margin: 0 auto 16px auto; color: #376ac8;
                ">
                    <i data-lucide="flask-conical" width="32" height="32"></i>
                </div>
                <h2 style="font-size: 1.8rem; font-weight: 800; margin: 0 0 8px 0; color: var(--text-main);">Laboratório de Escolha</h2>
                <p style="color: var(--text-muted); margin: 0;">Encontre a música perfeita para completar sua escala.</p>
            </div>

            <form action="" method="GET" style="background: white; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); color: var(--text-main);">
                <input type="hidden" name="tab" value="laboratorio">
                <input type="hidden" name="search" value="1">
                
                <h3 style="font-size: 1.1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="filter" width="18"></i> Estou procurando uma música...
                </h3>

                <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px;">
                    
                    <!-- Filtro Temporal -->
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="not_played" value="1" <?= isset($_GET['not_played']) ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: #376ac8;">
                        <div>
                            <div style="font-weight: 600;">Que não tocamos há muito tempo</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Pelo menos 3 meses (90 dias)</div>
                        </div>
                    </label>

                    <!-- Filtro Tom -->
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center;">
                        <label style="font-weight: 600;">No Tom:</label>
                        <select name="tone_filter" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); width: 100%;">
                            <option value="">Qualquer tom</option>
                            <?php 
                            $tonsOpcoes = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
                            foreach($tonsOpcoes as $t) {
                                $sel = ($_GET['tone_filter'] ?? '') == $t ? 'selected' : '';
                                echo "<option value='$t' $sel>$t (e variações)</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filtro Tag -->
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center;">
                        <label style="font-weight: 600;">Com Tag:</label>
                        <select name="tag_filter" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); width: 100%;">
                            <option value="">Qualquer estilo</option>
                            <?php 
                            // Buscar todas as tags
                            $tagsAll = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();
                            foreach($tagsAll as $tg) {
                                $sel = ($_GET['tag_filter'] ?? '') == $tg['id'] ? 'selected' : '';
                                echo "<option value='{$tg['id']}' $sel>{$tg['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                </div>

                <button type="submit" class="ripple" style="
                    width: 100%; padding: 14px; border: none; border-radius: 12px;
                    background: #376ac8; color: white; font-weight: 700; font-size: 1rem;
                    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
                ">
                    <i data-lucide="search" width="20"></i> Analisar e Buscar
                </button>
            </form>
        </div>

        <!-- RESULTADOS DO LABORATÓRIO -->
        <?php if (isset($_GET['search'])): 
            // Construir Query de Busca
            $conditions = ["1=1"];
            $params = [];

            if (!empty($_GET['not_played'])) {
                $conditions[] = "s.id NOT IN (SELECT song_id FROM schedule_songs ss JOIN schedules sc ON ss.schedule_id = sc.id WHERE sc.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY))";
            }

            if (!empty($_GET['tone_filter'])) {
                $tone = $_GET['tone_filter'];
                $conditions[] = "s.tone LIKE ?";
                $params[] = "$tone%";
            }

            if (!empty($_GET['tag_filter'])) {
                $tagId = $_GET['tag_filter'];
                $conditions[] = "s.id IN (SELECT song_id FROM song_tags WHERE tag_id = ?)";
                $params[] = $tagId;
            }

            $whereSql = implode(" AND ", $conditions);
            
            try {
                // Buscar resultados limitados
                $sqlLab = "
                    SELECT s.*, 
                           MAX(sc.event_date) as last_played,
                           DATEDIFF(CURDATE(), MAX(sc.event_date)) as days_since
                    FROM songs s
                    LEFT JOIN schedule_songs ss ON s.id = ss.song_id
                    LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
                    WHERE $whereSql
                    GROUP BY s.id, s.title, s.artist, s.tone, s.bpm
                    ORDER BY last_played ASC -- Prioriza as tocadas há mais tempo ou nunca tocadas (NULL)
                    LIMIT 20
                ";
                $stmtLab = $pdo->prepare($sqlLab);
                $stmtLab->execute($params);
                $labResults = $stmtLab->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $labResults = []; }
        ?>
            <h3 class="section-title">Resultados da Análise (<?= count($labResults) ?>)</h3>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 100px;">
                <?php foreach ($labResults as $res): 
                     $links = getExternalLinks($res['title'], $res['artist']);
                ?>
                <div style="background: var(--bg-surface); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);"><?= htmlspecialchars($res['title']) ?></div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($res['artist']) ?></div>
                            
                            <div style="display: flex; gap: 8px; margin-top: 6px;">
                                <?php if ($res['tone']): ?>
                                    <span style="background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                        <?= $res['tone'] ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span style="background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;">
                                    <?php 
                                        if (!$res['last_played']) echo "Nunca tocada";
                                        else echo $res['days_since'] . " dias sem tocar";
                                    ?>
                                </span>
                            </div>
                        </div>
                        <a href="musica_detalhe.php?id=<?= $res['id'] ?>" style="color: var(--primary);"><i data-lucide="chevron-right"></i></a>
                    </div>
                    
                    <!-- Botões de Ação Rápida -->
                    <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                        <a href="<?= $links['cifraclub'] ?>" target="_blank" style="flex: 1; text-align: center; background: #fff7ed; color: #c2410c; padding: 8px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">Cifra</a>
                        <a href="<?= $links['youtube'] ?>" target="_blank" style="flex: 1; text-align: center; background: #fef2f2; color: #b91c1c; padding: 8px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">YouTube</a>
                        <a href="<?= $links['spotify'] ?>" target="_blank" style="flex: 1; text-align: center; background: #f0fdf4; color: #15803d; padding: 8px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">Spotify</a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($labResults)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i data-lucide="search-x" width="48" style="opacity: 0.3; margin-bottom: 12px;"></i>
                        <p>Nenhuma música encontrada com estes filtros.</p>
                        <p style="font-size: 0.8rem;">Tente filtros menos específicos.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

<?php endif; ?>

<!-- MODAL DE AJUDA -->
<div id="helpModal" style="
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
">
    <div style="
        background: white; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;
        border-radius: 20px; padding: 24px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    ">
        <button onclick="closeHelpModal()" style="
            position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: var(--text-muted);
        "><i data-lucide="x" width="24"></i></button>

        <h2 style="margin-top: 0; color: var(--primary);">Entenda as Métricas</h2>
        
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 1rem; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">📊 Classificação de Status</h3>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
                <li style="display: flex; gap: 12px; align-items: flex-start;">
                    <span style="background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; white-space: nowrap;">Alta Rotatividade</span>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Músicas tocadas <strong>3 ou mais vezes</strong> nos últimos 90 dias. Cuidado para não "cansar" a igreja.</span>
                </li>
                <li style="display: flex; gap: 12px; align-items: flex-start;">
                    <span style="background: #dbeafe; color: #3b82f6; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; white-space: nowrap;">Geladeira</span>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Músicas não tocadas entre <strong>3 e 6 meses</strong>. Ótima hora para reintroduzir!</span>
                </li>
                <li style="display: flex; gap: 12px; align-items: flex-start;">
                    <span style="background: #e0e7ff; color: #6366f1; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; white-space: nowrap;">Esquecida</span>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Mais de <strong>6 meses</strong> sem tocar. Talvez já tenhamos esquecido a letra ou arranjo.</span>
                </li>
                <li style="display: flex; gap: 12px; align-items: flex-start;">
                    <span style="background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; white-space: nowrap;">Nunca Tocada</span>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">Cadastradas no sistema mas nunca escaladas. Oportunidade de novidade!</span>
                </li>
            </ul>
        </div>

        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 1rem; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">📈 KPIs de Saúde</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 12px;">
                <strong>Taxa de Uso:</strong> Porcentagem do nosso repertório total que foi usado nos últimos 90 dias. Uma taxa muito baixa (< 20%) indica que temos muitas músicas "mortas". Uma taxa muito alta (> 80%) indica que rodamos bem o repertório.
            </p>
        </div>

        <div>
            <h3 style="font-size: 1rem; color: #376ac8; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">🧪 Laboratório de Escolha</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 12px;">
                Use esta ferramenta para montar escalas equilibradas.
                <br>Exemplo: <em>"Preciso de uma música rápida (Tag: Celebração) em Tom D para completar a escala, mas não quero repetir o que já tocamos mês passado."</em>
            </p>
        </div>

        <div style="margin-top: 24px; text-align: center;">
            <button onclick="closeHelpModal()" style="
                background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer;
            ">Entendi!</button>
        </div>
    </div>
</div>

<script>
function openHelpModal() {
    document.getElementById('helpModal').style.display = 'flex';
}
function closeHelpModal() {
    document.getElementById('helpModal').style.display = 'none';
}
// Fechar ao clicar fora
document.getElementById('helpModal').addEventListener('click', function(e) {
    if (e.target === this) closeHelpModal();
});
</script>

<?php renderAppFooter(); ?> 
