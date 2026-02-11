<?php
// admin/historico.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Configurações e Filtros
$period = $_GET['period'] ?? '90'; // 90 dias padrão para análise
$dateLimit = date('Y-m-d', strtotime("-{$period} days"));
$currentTab = $_GET['tab'] ?? 'visageral';

// Helper para Links Externos
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

// Processamento dos Dados para KPIs
$totalSongs = count($musicasXRay);
$songsPlayedPeriod = 0;
$songsNeverPlayed = 0;
$statusDist = ['em_alta' => 0, 'ok' => 0, 'geladeira' => 0, 'esquecida' => 0, 'virgem' => 0];

foreach ($musicasXRay as $m) {
    if ($m['freq_period'] > 0) $songsPlayedPeriod++;
    if ($m['freq_total'] == 0) $songsNeverPlayed++;

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
}

$renovacaoTaxa = $totalSongs > 0 ? round(($songsPlayedPeriod / $totalSongs) * 100) : 0;

// KPI Card Data
$kpiCards = [
    [
        'title' => 'Taxa de Uso',
        'value' => $renovacaoTaxa . '%',
        'desc' => 'do repertório total',
        'style' => 'green', // kpi-green
        'icon' => 'activity'
    ],
    [
        'title' => 'Super Expostas',
        'value' => $statusDist['em_alta'],
        'desc' => 'tocadas +3x recente',
        'style' => 'rose',
        'icon' => 'flame'
    ],
    [
        'title' => 'Esquecidas',
        'value' => $statusDist['esquecida'],
        'desc' => '+6 meses sem tocar',
        'style' => 'blue',
        'icon' => 'archive'
    ],
    [
        'title' => 'Nunca Tocadas',
        'value' => $statusDist['virgem'],
        'desc' => 'oportunidade',
        'style' => 'yellow',
        'icon' => 'sparkles' 
    ]
];

// =================================================================================
// 2. QUERY ANALISE TAGS
// =================================================================================
try {
    $sqlTags = "
        SELECT 
            t.id, t.name, t.color,
            COUNT(CASE WHEN sc.event_date >= :dateLimit THEN 1 END) as uses_period,
            COUNT(ss.id) as uses_total
        FROM tags t
        JOIN song_tags st ON t.id = st.tag_id
        JOIN songs s ON st.song_id = s.id
        JOIN schedule_songs ss ON s.id = ss.song_id
        JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
        GROUP BY t.id, t.name, t.color
        HAVING uses_period > 0
        ORDER BY uses_period DESC
        LIMIT 10
    ";
    $stmtTags = $pdo->prepare($sqlTags);
    $stmtTags->execute(['dateLimit' => $dateLimit]);
    $topTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $topTags = []; }

// =================================================================================
// 3. QUERY ANALISE TONS
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

<!-- Import CSS -->
<link rel="stylesheet" href="../assets/css/pages/historico.css?v=<?= time() ?>">

<div class="container">
    <!-- NAVEGAÇÃO SUPERIOR (TABS) -->
    <div class="tabs-container">
        <a href="?tab=visageral" class="tab-link <?= $currentTab == 'visageral' ? 'active' : '' ?>">
            <i data-lucide="bar-chart-2" width="18"></i> Visão Geral
        </a>
        <a href="?tab=raiox" class="tab-link <?= $currentTab == 'raiox' ? 'active' : '' ?>">
            <i data-lucide="stethoscope" width="18"></i> Raio-X
        </a>
        <a href="?tab=estilo" class="tab-link <?= $currentTab == 'estilo' ? 'active' : '' ?>">
            <i data-lucide="palette" width="18"></i> Tags & Tons
        </a>
        <a href="?tab=laboratorio" class="tab-link <?= $currentTab == 'laboratorio' ? 'active' : '' ?>">
            <i data-lucide="flask-conical" width="18"></i> Laboratório
        </a>
    </div>

    <!-- TAB: VISÃO GERAL -->
    <?php if ($currentTab == 'visageral'): ?>
        <div class="fade-in">
            <!-- Header KPIs -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                    <i data-lucide="activity" width="20" class="text-primary"></i>
                    Saúde do Repertório (<?= $period ?> dias)
                </h3>
                <button onclick="openHelpModal()" class="btn-ghost-slate btn-sm">
                    <i data-lucide="help-circle" width="16"></i> Entenda
                </button>
            </div>
            
            <div class="kpis-grid">
                <?php foreach($kpiCards as $kpi): ?>
                <div class="kpi-card kpi-<?= $kpi['style'] ?>">
                    <div class="kpi-icon">
                        <i data-lucide="<?= $kpi['icon'] ?>" width="24"></i>
                    </div>
                    <div class="stat-value-lg"><?= $kpi['value'] ?></div>
                    <div class="kpi-title"><?= $kpi['title'] ?></div>
                    <div class="kpi-desc"><?= $kpi['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Top 5 Mais Tocadas -->
            <div class="card-neutral" style="padding: 16px;">
                <h4 class="card-title mb-3 flex items-center gap-2">
                    <span style="background: var(--red-100); color: var(--red-600); padding: 4px 8px; border-radius: 8px; font-size: 1rem;">🔥</span>
                    Top 5 Mais Tocadas
                </h4>
                
                <div class="ranking-list">
                    <?php 
                    $top5 = array_slice($musicasXRay, 0, 5);
                    foreach ($top5 as $i => $m): 
                    ?>
                        <div class="ranking-item">
                            <div class="ranking-position top-<?= $i+1 ?>"><?= $i+1 ?></div>
                            <div class="ranking-info">
                                <div class="ranking-title"><?= htmlspecialchars($m['title']) ?></div>
                                <div class="ranking-subtitle"><?= htmlspecialchars($m['artist']) ?></div>
                            </div>
                            <div class="ranking-stat">
                                <div class="ranking-value"><?= $m['freq_period'] ?>x</div>
                                <div class="ranking-label">tocadas</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CTA Laboratório -->
            <div style="margin-top: 24px; text-align: center;">
                <a href="historico.php?tab=laboratorio" class="btn-primary-slate">
                    <i data-lucide="flask-conical" width="18"></i>
                    Ir para Laboratório de Escolha
                </a>
            </div>
        </div>

    <!-- TAB: RAIO-X -->
    <?php elseif ($currentTab == 'raiox'): ?>
        <div class="fade-in">
            <!-- Busca e Filtros -->
            <div class="search-box">
                <i data-lucide="search" width="18" class="search-box-icon"></i>
                <input type="text" id="tableSearch" placeholder="Buscar por música ou artista..." onkeyup="filterTable()">
            </div>
            
            <div class="filter-chips">
                <button class="filter-chip active" onclick="filterByStatus('all')" data-status="all">🎵 Todas</button>
                <button class="filter-chip" onclick="filterByStatus('em_alta')" data-status="em_alta">🔥 Alta Rot.</button>
                <button class="filter-chip" onclick="filterByStatus('ok')" data-status="ok">✅ Saudável</button>
                <button class="filter-chip" onclick="filterByStatus('geladeira')" data-status="geladeira">❄️ Geladeira</button>
                <button class="filter-chip" onclick="filterByStatus('esquecida')" data-status="esquecida">📦 Esquecida</button>
                <button class="filter-chip" onclick="filterByStatus('virgem')" data-status="virgem">⭐ Nunca Tocada</button>
            </div>
            
            <div class="results-count" id="resultsCount"></div>
            
            <div class="table-container">
                <table id="raioXTable" class="data-table">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)" style="cursor: pointer; width: 40%;">Música</th>
                            <th onclick="sortTable(1)" style="cursor: pointer; width: 20%;" class="hide-mobile">Última Vez</th>
                            <th onclick="sortTable(2)" style="cursor: pointer; width: 20%;">Status</th>
                            <th onclick="sortTable(3)" style="cursor: pointer; text-align: center; width: 10%;">Freq (90d)</th>
                            <th style="text-align: center; width: 10%;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($musicasXRay as $m): 
                            $days = $m['days_since_last'];
                            $badgeClass = 'badge-slate';
                            $badgeText = 'Normal';
                            
                            if ($m['freq_total'] == 0) {
                                $badgeClass = 'badge-yellow'; $badgeText = 'Nunca Tocada';
                            } elseif ($m['freq_period'] >= 3) {
                                $badgeClass = 'badge-rose'; $badgeText = 'Alta Rotatividade';
                            } elseif ($days > 180) {
                                $badgeClass = 'badge-slate'; $badgeText = 'Esquecida';
                            } elseif ($days > 90) {
                                $badgeClass = 'badge-blue'; $badgeText = 'Geladeira';
                            } else {
                                $badgeClass = 'badge-green'; $badgeText = 'Saudável';
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="font-bold text-primary"><?= htmlspecialchars($m['title']) ?></div>
                                <div class="text-xs text-secondary mt-1 flex items-center gap-2">
                                    <?= htmlspecialchars($m['artist']) ?> 
                                    <?php if($m['tone']): ?>
                                        <span class="badge-slate" style="font-size: 0.65rem; padding: 2px 6px;"><?= $m['tone'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="hide-mobile">
                                <?php if ($m['last_played']): ?>
                                    <div class="text-primary font-medium"><?= date('d/m/Y', strtotime($m['last_played'])) ?></div>
                                    <div class="text-xs text-tertiary"><?= $days ?> dias atrás</div>
                                <?php else: ?>
                                    <span class="text-tertiary">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= $badgeClass ?> badge-status">
                                    <?= $badgeText ?>
                                </span>
                            </td>
                            <td class="center">
                                <div class="font-bold text-primary text-lg"><?= $m['freq_period'] ?></div>
                            </td>
                            <td class="center">
                                <a href="musica_detalhe.php?id=<?= $m['id'] ?>" class="btn-ghost-slate btn-sm" title="Ver Detalhes">
                                    <i data-lucide="eye" width="18"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($musicasXRay)): ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-tertiary);">
                        Nenhuma música encontrada no raio-x.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Filter Logic
            let currentStatusFilter = 'all';
            function filterByStatus(status) {
                currentStatusFilter = status;
                document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));
                document.querySelector(`[data-status="${status}"]`).classList.add('active');
                filterTable();
            }
            
            function filterTable() {
                const input = document.getElementById('tableSearch');
                const filter = input.value.toLowerCase();
                const table = document.getElementById('raioXTable');
                const rows = table.getElementsByTagName('tr');
                let visibleCount = 0;
                
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    if (cells.length > 0) {
                        const text = cells[0].textContent.toLowerCase();
                        const statusCell = cells[2];
                        const statusClass = statusCell.querySelector('span').className;
                        
                        let statusMatch = true;
                        if (currentStatusFilter !== 'all') {
                            if (currentStatusFilter === 'em_alta') statusMatch = statusClass.includes('badge-rose');
                            else if (currentStatusFilter === 'ok') statusMatch = statusClass.includes('badge-green');
                            else if (currentStatusFilter === 'geladeira') statusMatch = statusClass.includes('badge-blue');
                            else if (currentStatusFilter === 'esquecida') statusMatch = statusClass.includes('badge-slate');
                            else if (currentStatusFilter === 'virgem') statusMatch = statusClass.includes('badge-yellow');
                        }
                        
                        if (text.includes(filter) && statusMatch) {
                            rows[i].style.display = '';
                            visibleCount++;
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
                document.getElementById('resultsCount').textContent = `Exibindo ${visibleCount} música${visibleCount !== 1 ? 's' : ''}`;
            }
            
            // Sort Logic
            function sortTable(n) {
                var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
                table = document.getElementById("raioXTable");
                switching = true;
                dir = "asc";
                while (switching) {
                    switching = false;
                    rows = table.rows;
                    for (i = 1; i < (rows.length - 1); i++) {
                        shouldSwitch = false;
                        x = rows[i].getElementsByTagName("TD")[n];
                        y = rows[i + 1].getElementsByTagName("TD")[n];
                        var xContent = x.innerText.toLowerCase();
                        var yContent = y.innerText.toLowerCase();
                        var isNumber = n === 3;
                        if (isNumber) { xContent = parseInt(xContent)||0; yContent = parseInt(yContent)||0; }
                        if (dir == "asc") { if (xContent > yContent) { shouldSwitch = true; break; } } 
                        else if (dir == "desc") { if (xContent < yContent) { shouldSwitch = true; break; } }
                    }
                    if (shouldSwitch) {
                        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                        switching = true;
                        switchcount ++;
                    } else {
                        if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; }
                    }
                }
            }
            window.addEventListener('DOMContentLoaded', filterTable);
        </script>

    <!-- TAB: TAGS & TONS -->
    <?php elseif ($currentTab == 'estilo'): ?>
        <div class="fade-in">
            <!-- TAGS -->
            <div class="card-neutral" style="padding: 20px; margin-bottom: 24px;">
                <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                    <i data-lucide="tag" width="20"></i> Tags Mais Cantadas (Últimos <?= $period ?> dias)
                </h3>
                
                <?php if (!empty($topTags)): ?>
                <div class="table-container" style="margin-bottom: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">#</th>
                                <th style="width: 40%;">Tag / Estilo</th>
                                <th style="width: 25%; text-align: center;">Execuções</th>
                                <th style="width: 15%; text-align: center;">% Total</th>
                                <th style="width: 15%; text-align: center;">Tendência</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxUses = !empty($topTags) ? $topTags[0]['uses_period'] : 1;
                            $totalExec = array_sum(array_column($topTags, 'uses_period'));
                            $rank = 1;
                            foreach ($topTags as $tag): 
                                $percentTotal = $totalExec > 0 ? round(($tag['uses_period'] / $totalExec) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="center" style="color: var(--text-tertiary); font-weight: 600;"><?= $rank++ ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span class="tag-indicator" style="background: <?= $tag['color'] ?>; margin-right: 8px;"></span>
                                        <div>
                                            <div class="font-bold text-primary"><?= htmlspecialchars($tag['name']) ?></div>
                                            <div class="text-xs text-secondary mt-0.5"><?= $tag['uses_total'] ?? 0 ?> no histórico</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="center font-bold text-primary"><?= $tag['uses_period'] ?></td>
                                <td class="center text-sm text-secondary"><?= $percentTotal ?>%</td>
                                <td class="center">
                                    <?php 
                                    $trend = $tag['uses_period'] >= 3 ? 'up' : ($tag['uses_period'] == 1 ? 'down' : 'stable');
                                    $trendColor = $trend == 'up' ? 'var(--green-500)' : ($trend == 'down' ? 'var(--red-500)' : 'var(--slate-400)');
                                    $trendIcon = $trend == 'up' ? 'trending-up' : ($trend == 'down' ? 'trending-down' : 'minus');
                                    ?>
                                    <i data-lucide="<?= $trendIcon ?>" width="18" style="color: <?= $trendColor ?>; opacity: 0.8;"></i>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-center text-tertiary p-4">Nenhuma tag registrada neste período.</p>
                <?php endif; ?>
            </div>

            <!-- TONS -->
            <div class="card-neutral" style="padding: 20px;">
                <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                    <i data-lucide="music" width="20"></i> Distribuição de Tons
                </h3>
                
                <?php if (!empty($usoTons)): ?>
                <div class="tones-grid">
                    <?php 
                    $tonColors = [
                        'C' => '#ef4444', 'D' => '#f59e0b', 'E' => '#22c55e', 
                        'F' => '#3b82f6', 'G' => '#a855f7', 'A' => '#ec4899', 'B' => '#14b8a6'
                    ];
                    foreach ($usoTons as $ton):
                        $baseTone = substr($ton['tone'], 0, 1);
                        $barColor = $tonColors[$baseTone] ?? '#64748b';
                    ?>
                    <div class="tone-card" style="border-top: 3px solid <?= $barColor ?>;">
                        <div class="tone-entry"><?= $ton['tone'] ?></div>
                        <div class="tone-count"><?= $ton['uses_period'] ?>x</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-center text-tertiary p-4">Nenhum tom registrado neste período.</p>
                <?php endif; ?>
            </div>
        </div>

    <!-- TAB: LABORATÓRIO -->
    <?php elseif ($currentTab == 'laboratorio'): ?>
        <div class="fade-in">
            <div class="lab-header">
                <i data-lucide="flask-conical" width="32" style="margin-bottom: 12px; opacity: 0.9;"></i>
                <h2 class="lab-title">Laboratório de Repertório</h2>
                <p class="lab-desc">Encontre a música perfeita baseada em critérios técnicos e histórico.</p>
            </div>

            <!-- Filtros -->
            <div class="card-neutral" style="padding: 24px; margin-bottom: 32px;">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="laboratorio">
                    <input type="hidden" name="search" value="1">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <div class="form-group">
                            <label class="form-label">Tom</label>
                            <select name="tone_filter" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach(['C', 'D', 'E', 'F', 'G', 'A', 'B'] as $t) echo "<option value='$t'>$t</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estilo / Tag</label>
                            <select name="tag_filter" class="form-select">
                                <option value="">Todos</option>
                                <?php 
                                $tagsAll = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();
                                foreach($tagsAll as $tg) echo "<option value='{$tg['id']}'>{$tg['name']}</option>";
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                             <label class="form-label">Filtro Extra</label>
                             <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                                 <input type="checkbox" name="not_played" id="not_played" value="1">
                                 <label for="not_played" style="font-size: 0.9rem; cursor: pointer;">Não tocada há 90 dias</label>
                             </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full justify-center">
                        <i data-lucide="sparkles" width="18"></i> Analisar e Buscar
                    </button>
                </form>
            </div>

            <!-- Resultados -->
            <?php if (isset($_GET['search'])): 
                // Lógica de busca simplificada para manter o arquivo limpo
                $conditions = ["1=1"];
                $params = [];
                if (!empty($_GET['not_played'])) $conditions[] = "s.id NOT IN (SELECT song_id FROM schedule_songs ss JOIN schedules sc ON ss.schedule_id = sc.id WHERE sc.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY))";
                if (!empty($_GET['tone_filter'])) { $conditions[] = "s.tone LIKE ?"; $params[] = $_GET['tone_filter'] . "%"; }
                if (!empty($_GET['tag_filter'])) { $conditions[] = "s.id IN (SELECT song_id FROM song_tags WHERE tag_id = ?)"; $params[] = $_GET['tag_filter']; }
                
                $whereSql = implode(" AND ", $conditions);
                $sqlLab = "SELECT s.*, MAX(sc.event_date) as last_played, DATEDIFF(CURDATE(), MAX(sc.event_date)) as days_since FROM songs s LEFT JOIN schedule_songs ss ON s.id = ss.song_id LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE() WHERE $whereSql GROUP BY s.id ORDER BY last_played ASC LIMIT 20";
                
                try {
                    $stmtLab = $pdo->prepare($sqlLab);
                    $stmtLab->execute($params);
                    $labResults = $stmtLab->fetchAll(PDO::FETCH_ASSOC);
                } catch(Exception $e) { $labResults = []; }
            ?>
                <h3 class="text-lg font-bold text-primary mb-4">Resultados Sugeridos (<?= count($labResults) ?>)</h3>
                
                <div style="display: grid; gap: 8px;">
                    <?php foreach ($labResults as $res): 
                         $stmtSongTags = $pdo->prepare("SELECT t.name, t.color FROM tags t JOIN song_tags st ON t.id = st.tag_id WHERE st.song_id = ?");
                         $stmtSongTags->execute([$res['id']]);
                         $songTags = $stmtSongTags->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <a href="musica_detalhe.php?id=<?= $res['id'] ?>" class="compact-card">
                        <div class="compact-card-icon">
                            <?php if ($res['tone']): ?>
                                <span style="font-weight: 800; font-size: 0.9rem;"><?= $res['tone'] ?></span>
                            <?php else: ?>
                                <i data-lucide="music" width="18" style="opacity: 0.3;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="compact-card-content">
                            <div class="compact-card-title"><?= htmlspecialchars($res['title']) ?></div>
                            <div class="compact-card-subtitle">
                                <?= htmlspecialchars($res['artist']) ?> • 
                                <span class="<?= !$res['last_played'] ? 'text-yellow-600' : 'text-slate-500' ?>">
                                    <?= !$res['last_played'] ? 'Nunca tocada' : $res['days_since'] . ' dias atrás' ?>
                                </span>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="compact-card-arrow"></i>
                    </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($labResults)): ?>
                        <p class="text-center text-tertiary p-8 border border-dashed border-slate-300 rounded-xl">Nenhuma música encontrada com estes filtros.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    <?php endif; ?>
</div>

<!-- HELP MODAL -->
<div id="helpModal" class="notification-overlay">
    <div class="notification-modal" style="margin: auto; position: relative; top: 50%; transform: translateY(-50%); max-width: 500px;">
        <div class="modal-header">
            <h3>Entenda as Métricas</h3>
            <button class="modal-close" onclick="closeHelpModal()"><i data-lucide="x" width="20"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div class="mb-4">
                <h4 class="font-bold text-primary mb-2">Classificação de Status</h4>
                <ul class="text-sm text-secondary space-y-2">
                    <li><span class="badge-rose">Alta Rotatividade</span>: Tocada 3+ vezes em 90 dias.</li>
                    <li><span class="badge-blue">Geladeira</span>: Não tocada há 3-6 meses.</li>
                    <li><span class="badge-slate">Esquecida</span>: Mais de 6 meses sem tocar.</li>
                    <li><span class="badge-yellow">Nunca Tocada</span>: Cadastrada mas nunca usada.</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-primary mb-2">Taxa de Uso</h4>
                <p class="text-sm text-secondary">Porcentagem do repertório total utilizada nos últimos 90 dias.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeHelpModal()" class="btn-primary w-full justify-center">Entendi</button>
        </div>
    </div>
</div>

<script>
function openHelpModal() {
    const modal = document.getElementById('helpModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('active'), 10);
}
function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}
</script>

<?php renderAppFooter(); ?>
