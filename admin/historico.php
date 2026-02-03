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
        <?= $currentTab == 'laboratorio' ? 'background: #7c3aed; color: white;' : 'background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color);' ?>
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
                background: #7c3aed; color: white; padding: 12px 24px;
                border-radius: 12px; text-decoration: none; font-weight: 700;
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
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
            <div style="display: flex; align-items: flex-end; gap: 8px; height: 200px; padding-bottom: 20px; overflow-x: auto;">
                <?php 
                $maxTonUses = !empty($usoTons) ? $usoTons[0]['uses_period'] : 1;
                foreach ($usoTons as $ton):
                    $height = max(10, ($ton['uses_period'] / $maxTonUses) * 100);
                    $barColor = '#3b82f6';
                    // Cores para tons especificos
                    if (strpos($ton['tone'], '#') !== false) $barColor = '#8b5cf6'; // Sustenidos Roxo
                ?>
                <div style="flex: 1; min-width: 30px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted);"><?= $ton['uses_period'] ?></div>
                    <div style="width: 100%; height: <?= $height ?>%; background: <?= $barColor ?>; border-radius: 4px 4px 0 0; opacity: 0.8; transition: all 0.3s;" title="<?= $ton['tone'] ?>: <?= $ton['uses_period'] ?> vezes"></div>
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-main);"><?= $ton['tone'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.8rem; margin-top: 12px;">
                Frequência de tons utilizados nos últimos <?= $period ?> dias.
            </p>
        </div>

    </div>

<!-- =================================================================================
     TAB: LABORATÓRIO (BUSCA E FILTROS)
     ================================================================================= -->
<?php elseif ($currentTab == 'laboratorio'): ?>
    
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 20px; padding: 32px; color: white; margin-bottom: 32px; box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.4);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                    <i data-lucide="flask-conical" width="32" height="32"></i>
                </div>
                <h2 style="font-size: 1.8rem; font-weight: 800; margin: 0 0 8px 0;">Laboratório de Escolha</h2>
                <p style="opacity: 0.9; margin: 0;">Encontre a música perfeita para completar sua escala.</p>
            </div>

            <form action="" method="GET" style="background: white; padding: 24px; border-radius: 16px; color: var(--text-main);">
                <input type="hidden" name="tab" value="laboratorio">
                <input type="hidden" name="search" value="1">
                
                <h3 style="font-size: 1.1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="filter" width="18"></i> Estou procurando uma música...
                </h3>

                <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px;">
                    
                    <!-- Filtro Temporal -->
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="not_played" value="1" <?= isset($_GET['not_played']) ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: #7c3aed;">
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
                    background: #7c3aed; color: white; font-weight: 700; font-size: 1rem;
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

<?php renderAppFooter(); ?> 
