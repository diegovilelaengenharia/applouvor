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

// KPI Card Data
$kpiCards = [
    [
        'title' => 'Taxa de Uso',
        'value' => $renovacaoTaxa . '%',
        'desc' => 'do repertório total',
        'color' => $renovacaoTaxa > 30 ? 'green' : 'amber',
        'icon' => 'activity'
    ],
    [
        'title' => 'Super Expostas',
        'value' => $statusDist['em_alta'],
        'desc' => 'tocadas +3x recente',
        'color' => 'rose',
        'icon' => 'flame'
    ],
    [
        'title' => 'Esquecidas',
        'value' => $statusDist['esquecida'],
        'desc' => '+6 meses sem tocar',
        'color' => 'blue',
        'icon' => 'archive'
    ],
    [
        'title' => 'Nunca Tocadas',
        'value' => $statusDist['virgem'],
        'desc' => 'oportunidade',
        'color' => 'yellow',
        'icon' => 'star'
    ]
];


// =================================================================================
// 2. QUERY ANALISE TAGS (O que estamos cantando?)
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

<style>
    /* Custom Styles for History Page */
    
    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        font-size: 20px;
    }

    .stat-value-lg {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
    }

    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: var(--bg-tertiary);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        border-radius: 4px;
        /* background set inline */
    }

    .ranking-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        transition: transform 0.2s;
    }
    
    .ranking-item:hover {
        transform: translateX(4px);
        border-color: var(--primary-subtle);
    }

    .ranking-position {
        width: 28px;
        height: 28px;
        background: var(--bg-body);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    
    .ranking-position.top-1 { background: var(--amber-100); color: var(--amber-700); }
    .ranking-position.top-2 { background: var(--slate-100); color: var(--slate-700); }
    .ranking-position.top-3 { background: var(--red-100); color: var(--red-700); }

    /* Table Enhancements */
    .table-container {
        background: var(--bg-surface);
        border-radius: 16px;
        border: 1px solid var(--border-subtle);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th {
        text-align: left;
        padding: 16px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: var(--bg-body);
        border-bottom: 1px solid var(--border-subtle);
    }
    
    td {
        padding: 16px;
        border-bottom: 1px solid var(--border-subtle);
        color: var(--text-primary);
        font-size: 0.95rem;
    }
    
    tr:last-child td {
        border-bottom: none;
    }
    
    tr:hover td {
        background: var(--bg-body);
    }

    .search-box {
        position: relative;
        margin-bottom: 16px;
    }
    
    .search-box input {
        width: 100%;
        padding: 14px 16px 14px 44px;
        border: 2px solid var(--border-subtle);
        border-radius: 12px;
        font-size: 0.95rem;
        background: var(--bg-surface);
        color: var(--text-primary);
        transition: all 0.2s;
    }
    
    .search-box input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        background: white;
    }
    
    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-tertiary);
    }
    
    .filter-chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }
    
    .filter-chip {
        padding: 6px 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 20px;
        border: 2px solid var(--border-subtle);
        background: var(--bg-surface);
        color: var(--text-secondary);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .filter-chip:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .filter-chip.active {
        background: var(--slate-700);
        border-color: var(--slate-700);
        color: white;
    }
    
    .results-count {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .stat-value-lg { font-size: 1.75rem; }
        .kpi-icon { width: 36px; height: 36px; font-size: 18px; }
        th, td { padding: 10px; font-size: 0.85rem; }
        .hide-mobile { display: none; }
        .ranking-position { width: 24px; height: 24px; font-size: 0.75rem; }
    }
</style>

<!-- NAVEGAÇÃO SUPERIOR (TABS) -->
<div class="container">
    <div class="tabs-container" style="margin-bottom: 24px;">
        <a href="?tab=visageral" class="tab-link <?= $currentTab == 'visageral' ? 'active' : '' ?>">
            📊 Visão Geral
        </a>
        <a href="?tab=raiox" class="tab-link <?= $currentTab == 'raiox' ? 'active' : '' ?>">
            🩺 Raio-X
        </a>
        <a href="?tab=estilo" class="tab-link <?= $currentTab == 'estilo' ? 'active' : '' ?>">
            🎨 Tags & Tons
        </a>
        <a href="?tab=laboratorio" class="tab-link <?= $currentTab == 'laboratorio' ? 'active' : '' ?>">
            🧪 Laboratório
        </a>
    </div>
</div>

<!-- =================================================================================
     TAB: VISÃO GERAL
     ================================================================================= -->
<?php if ($currentTab == 'visageral'): ?>
    
    <div class="container fade-in">
        
        <!-- KPIs de Saúde -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                    <i data-lucide="activity" class="text-primary"></i>
                    Saúde do Repertório (<?= $period ?> dias)
                </h3>
                <button onclick="openHelpModal()" class="btn-ghost-slate btn-sm">
                    <i data-lucide="help-circle" width="16"></i> Entenda as Métricas
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                <?php foreach($kpiCards as $kpi): ?>
                <div style="
                    text-align: center; 
                    display: flex; 
                    flex-direction: column; 
                    align-items: center; 
                    padding: 20px 16px;
                    background: var(--bg-surface);
                    border: 2px solid var(--<?= $kpi['color'] ?>-200);
                    border-radius: 16px;
                    transition: all 0.2s;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div class="kpi-icon" style="background: var(--<?= $kpi['color'] ?>-100); color: var(--<?= $kpi['color'] ?>-600);">
                        <i data-lucide="<?= $kpi['icon'] ?>" width="22" height="22"></i>
                    </div>
                    <div class="stat-value-lg" style="color: var(--<?= $kpi['color'] ?>-600);">
                        <?= $kpi['value'] ?>
                    </div>
                    <div class="font-bold text-primary mt-1" style="font-size: 0.9rem;"><?= $kpi['title'] ?></div>
                    <div class="text-xs text-secondary"><?= $kpi['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Músicas mais Tocadas (Top 5) -->
        <div class="card-neutral" style="padding: 16px;">
            <h4 class="card-title mb-3 flex items-center gap-2" style="font-size: 1rem;">
                <span style="background: var(--red-100); color: var(--red-600); padding: 4px 8px; border-radius: 8px; font-size: 1rem;">🔥</span>
                Top 5 Mais Tocadas
            </h4>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php 
                $top5 = array_slice($musicasXRay, 0, 5);
                foreach ($top5 as $i => $m): 
                    $percent = ($m['freq_period'] / max(1, $top5[0]['freq_period'])) * 100;
                ?>
                    <div class="ranking-item" style="padding: 10px;">
                        <div class="ranking-position top-<?= $i+1 ?>"><?= $i+1 ?></div>
                        <div style="flex: 1;">
                            <div class="font-bold text-primary" style="font-size: 0.9rem;"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="text-xs text-secondary"><?= htmlspecialchars($m['artist']) ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div class="font-bold text-primary" style="font-size: 0.95rem;"><?= $m['freq_period'] ?>x</div>
                            <div class="text-xs text-tertiary">tocadas</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Link para Timeline -->
        <div style="margin-top: 24px; text-align: center;">
            <a href="historico.php?tab=laboratorio" class="btn-primary-slate">
                <i data-lucide="flask-conical" width="18"></i>
                Laboratório de Escolha
            </a>
        </div>

    </div>

<!-- =================================================================================
     TAB: RAIO-X (TABELA COMPLETA)
     ================================================================================= -->
<?php elseif ($currentTab == 'raiox'): ?>

    <div class="container fade-in">
        
        <!-- Busca e Filtros -->
        <div class="search-box">
            <i data-lucide="search" width="18"></i>
            <input type="text" id="tableSearch" placeholder="Buscar por música ou artista..." onkeyup="filterTable()">
        </div>
        
        <!-- Filtros Rápidos -->
        <div class="filter-chips">
            <button class="filter-chip active" onclick="filterByStatus('all')" data-status="all">
                🎵 Todas
            </button>
            <button class="filter-chip" onclick="filterByStatus('em_alta')" data-status="em_alta">
                🔥 Alta Rotatividade
            </button>
            <button class="filter-chip" onclick="filterByStatus('ok')" data-status="ok">
                ✅ Saudável
            </button>
            <button class="filter-chip" onclick="filterByStatus('geladeira')" data-status="geladeira">
                ❄️ Geladeira
            </button>
            <button class="filter-chip" onclick="filterByStatus('esquecida')" data-status="esquecida">
                📦 Esquecida
            </button>
            <button class="filter-chip" onclick="filterByStatus('virgem')" data-status="virgem">
                ⭐ Nunca Tocada
            </button>
        </div>
        
        <!-- Contador de Resultados -->
        <div class="results-count" id="resultsCount"></div>
        
        <script>
            let currentStatusFilter = 'all';
            
            function filterByStatus(status) {
                currentStatusFilter = status;
                
                // Atualizar botões ativos
                document.querySelectorAll('.filter-chip').forEach(chip => {
                    chip.classList.remove('active');
                });
                document.querySelector(`[data-status="${status}"]`).classList.add('active');
                
                // Aplicar filtro
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
                        
                        const textMatch = text.includes(filter);
                        
                        if (textMatch && statusMatch) {
                            rows[i].style.display = '';
                            visibleCount++;
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
                
                // Atualizar contador
                document.getElementById('resultsCount').textContent = `Exibindo ${visibleCount} música${visibleCount !== 1 ? 's' : ''}`;
            }
            
            // Inicializar contador
            window.addEventListener('DOMContentLoaded', filterTable);
        </script>
        
        <script>
            // Script de ordenação para a tabela
            function sortTable(n) {
                var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
                table = document.getElementById("raioXTable");
                switching = true;
                dir = "asc";
                
                var headers = table.getElementsByTagName("th");
                for (var j = 0; j < headers.length; j++) {
                    headers[j].classList.remove("text-primary");
                    // Reset icon or style if needed
                }

                while (switching) {
                    switching = false;
                    rows = table.rows;
                    for (i = 1; i < (rows.length - 1); i++) {
                        shouldSwitch = false;
                        x = rows[i].getElementsByTagName("TD")[n];
                        y = rows[i + 1].getElementsByTagName("TD")[n];
                        
                        var xContent = x.innerText.toLowerCase();
                        var yContent = y.innerText.toLowerCase();
                        var isNumber = n === 3; // Coluna Freq

                        if (isNumber) {
                            xContent = parseInt(xContent) || 0;
                            yContent = parseInt(yContent) || 0;
                        }

                        if (dir == "asc") {
                            if (xContent > yContent) { shouldSwitch = true; break; }
                        } else if (dir == "desc") {
                            if (xContent < yContent) { shouldSwitch = true; break; }
                        }
                    }
                    if (shouldSwitch) {
                        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                        switching = true;
                        switchcount ++;
                    } else {
                        if (switchcount == 0 && dir == "asc") {
                            dir = "desc";
                            switching = true;
                        }
                    }
                }
            }
        </script>

        <div class="table-container">
            <table id="raioXTable">
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
                        // Determine status badge
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
                            <div class="text-sm text-secondary mt-1 flex items-center gap-2">
                                <?= htmlspecialchars($m['artist']) ?> 
                                <?php if($m['tone']): ?>
                                    <span class="badge-slate badge-sm"><?= $m['tone'] ?></span>
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
                            <span class="<?= $badgeClass ?> badge-sm">
                                <?= $badgeText ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div class="font-bold text-primary text-lg"><?= $m['freq_period'] ?></div>
                        </td>
                        <td style="text-align: center;">
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

<!-- =================================================================================
     TAB: TAGS & TONS
     ================================================================================= -->
<?php elseif ($currentTab == 'estilo'): ?>
    
    <div class="container fade-in">
        
        <!-- TAGS -->
        <div class="card-neutral" style="padding: 20px; margin-bottom: 32px;">
            <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                <i data-lucide="tag" width="20"></i>
                Tags Mais Cantadas (Últimos <?= $period ?> dias)
            </h3>
            
            <?php if (!empty($topTags)): ?>
            <div class="table-container">
                <style>
                    /* Estilo Minimalista Dedicado */
                    .minimal-table th {
                        font-weight: 600;
                        color: var(--text-secondary);
                        font-size: 0.8rem;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        border-bottom: 2px solid var(--border-subtle);
                        padding-bottom: 12px;
                    }
                    .minimal-table td {
                        padding: 16px 8px;
                        border-bottom: 1px solid var(--border-subtle);
                        color: var(--text-primary);
                    }
                    .minimal-table tr:last-child td {
                        border-bottom: none;
                    }
                    .tag-indicator {
                        width: 12px;
                        height: 12px;
                        border-radius: 4px;
                        display: inline-block;
                        margin-right: 8px;
                    }
                </style>
                <table class="minimal-table">
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
                            $percent = ($tag['uses_period'] / $maxUses) * 100;
                            $percentTotal = $totalExec > 0 ? round(($tag['uses_period'] / $totalExec) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td style="text-align: center; color: var(--text-tertiary); font-weight: 600;">
                                <?= $rank++ ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <span class="tag-indicator" style="background: <?= $tag['color'] ?>;"></span>
                                    <div>
                                        <div class="font-bold text-primary"><?= htmlspecialchars($tag['name']) ?></div>
                                        <div class="text-xs text-secondary mt-0.5"><?= $tag['uses_total'] ?? 0 ?> no histórico</div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="font-bold text-primary"><?= $tag['uses_period'] ?></div>
                            </td>
                            <td style="text-align: center;">
                                <span class="text-sm text-secondary"><?= $percentTotal ?>%</span>
                            </td>
                            <td style="text-align: center;">
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
                <div class="text-center text-tertiary p-8">
                    <i data-lucide="tag" width="48" style="opacity: 0.2; margin-bottom: 12px;"></i>
                    <p>Nenhuma tag registrada neste período.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TONS -->
        <div class="card-neutral" style="padding: 20px;">
            <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                        <i data-lucide="music" width="20"></i>
                Distribuição de Tons (Nas Execuções)
            </h3>
            
            <?php if (!empty($usoTons)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                <?php 
                $maxTonUses = !empty($usoTons) ? $usoTons[0]['uses_period'] : 1;
                $totalTonExec = array_sum(array_column($usoTons, 'uses_period'));
                $tonColors = [
                    'C' => 'var(--red-500)', 'D' => 'var(--amber-500)', 'E' => 'var(--green-500)', 
                    'F' => 'var(--blue-500)', 'G' => 'var(--purple-500)', 'A' => 'var(--pink-500)', 'B' => 'var(--teal-500)'
                ];
                foreach ($usoTons as $ton):
                    $baseTone = substr($ton['tone'], 0, 1);
                    $barColor = $tonColors[$baseTone] ?? 'var(--slate-500)';
                    $percentTotal = $totalTonExec > 0 ? round(($ton['uses_period'] / $totalTonExec) * 100, 1) : 0;
                ?>
                <div class="card-neutral" style="
                    padding: 12px; 
                    display: flex; 
                    align-items: center; 
                    gap: 12px; 
                    border-left: 3px solid <?= $barColor ?>;
                    background: linear-gradient(to right, <?= $barColor ?>08, transparent);
                ">
                    <!-- Ícone / Tom -->
                    <div style="
                        width: 40px; height: 40px; 
                        background: <?= $barColor ?>20; 
                        color: <?= $barColor ?>;
                        border-radius: 8px;
                        display: flex; flex-direction: column;
                        align-items: center; justify-content: center;
                        font-family: monospace;
                        line-height: 1;
                        flex-shrink: 0;
                    ">
                        <span style="font-size: 1.1rem; font-weight: 800;"><?= $ton['tone'] ?></span>
                    </div>

                    <!-- Dados -->
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: baseline; gap: 6px; margin-bottom: 2px;">
                            <span class="text-primary font-bold" style="font-size: 1.1rem;"><?= $ton['uses_period'] ?>x</span>
                            <span class="text-xs text-secondary">execuções</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="progress-bar-container" style="height: 4px; flex: 1; background: var(--border-subtle);">
                                <div class="progress-bar" style="width: <?= $percentTotal ?>%; background: <?= $barColor ?>;"></div>
                            </div>
                            <span class="text-xs font-bold" style="color: <?= $barColor ?>;"><?= $percentTotal ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center text-tertiary p-8">
                    <i data-lucide="music" width="48" style="opacity: 0.2; margin-bottom: 12px;"></i>
                    <p>Nenhum tom registrado neste período.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

<!-- =================================================================================
     TAB: LABORATÓRIO (BUSCA E FILTROS)
     ================================================================================= -->
<?php elseif ($currentTab == 'laboratorio'): ?>
    
    <div class="container fade-in">
        <!-- Header Compacto -->
        <div class="card-primary" style="
            background: linear-gradient(135deg, var(--purple-600) 0%, var(--purple-700) 100%);
            color: white; 
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        ">
            <div style="
                width: 56px; height: 56px; 
                background: rgba(255,255,255,0.2); 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                margin: 0 auto 12px auto;
            ">
                <i data-lucide="flask-conical" width="28" height="28" style="color: white;"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">Laboratório de Escolha</h2>
            <p style="opacity: 0.9;">Encontre a música perfeita para completar sua escala.</p>
        </div>

        <!-- Filtros -->
        <div class="card-neutral" style="padding: 20px; margin-bottom: 24px;">
            <h3 class="font-bold text-primary mb-4 flex items-center gap-2">
                <i data-lucide="filter" width="18"></i>
                Estou procurando uma música...
            </h3>
            
            <form action="" method="GET">
                <input type="hidden" name="tab" value="laboratorio">
                <input type="hidden" name="search" value="1">

                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label text-sm font-bold text-secondary mb-2 block">No Tom:</label>
                        <select name="tone_filter" class="form-select w-full p-3 rounded-lg border border-slate-200">
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

                    <div>
                        <label class="text-sm font-semibold text-primary mb-2 flex items-center gap-2">
                            <i data-lucide="tag" width="14"></i>
                            Com Tag:
                        </label>
                        <select name="tag_filter" class="w-full" style="
                            padding: 10px 12px;
                            border: 1px solid var(--border-subtle);
                            border-radius: 8px;
                            background: var(--bg-surface);
                            color: var(--text-primary);
                            font-size: 0.95rem;
                        ">
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

                <button type="submit" class="btn-primary-slate w-full justify-center">
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
            <h3 class="text-lg font-bold text-primary mt-8 mb-4">Resultados da Análise (<?= count($labResults) ?>)</h3>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 100px; max-width: 800px; margin-left: auto; margin-right: auto;">
                <?php foreach ($labResults as $res): 
                     $links = getExternalLinks($res['title'], $res['artist']);
                ?>
                <div class="card-neutral p-4 flex flex-col gap-3">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div class="font-bold text-primary text-lg"><?= htmlspecialchars($res['title']) ?></div>
                            <div class="text-sm text-secondary"><?= htmlspecialchars($res['artist']) ?></div>
                            
                            <div style="display: flex; gap: 8px; margin-top: 8px;">
                                <?php if ($res['tone']): ?>
                                    <span class="badge-slate badge-sm">
                                        <?= $res['tone'] ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="badge-blue badge-sm">
                                    <?php 
                                        if (!$res['last_played']) echo "Nunca tocada";
                                        else echo $res['days_since'] . " dias sem tocar";
                                    ?>
                                </span>
                            </div>
                        </div>
                        <a href="musica_detalhe.php?id=<?= $res['id'] ?>" class="text-primary hover:text-blue-600"><i data-lucide="chevron-right"></i></a>
                    </div>
                    
                    <!-- Botões de Ação Rápida -->
                    <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
                        <a href="<?= $links['cifraclub'] ?>" target="_blank" class="btn-ghost-lavender btn-sm flex-1 justify-center">Cifra</a>
                        <a href="<?= $links['youtube'] ?>" target="_blank" class="btn-ghost-rose btn-sm flex-1 justify-center">YouTube</a>
                        <a href="<?= $links['spotify'] ?>" target="_blank" class="btn-ghost-sage btn-sm flex-1 justify-center">Spotify</a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($labResults)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                        <i data-lucide="search-x" width="48" style="opacity: 0.3; margin-bottom: 12px;"></i>
                        <p>Nenhuma música encontrada com estes filtros.</p>
                        <p class="text-sm">Tente filtros menos específicos.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

<?php endif; ?>

<!-- MODAL DE AJUDA -->
<div id="helpModal" class="notification-overlay" style="z-index: 9999;">
    <div class="notification-modal" style="margin: auto; position: relative; top: 50%; transform: translateY(-50%); max-width: 600px;">
        <div class="modal-header">
            <h3>Entenda as Métricas</h3>
            <button class="modal-close" onclick="closeHelpModal()">
                <i data-lucide="x" width="20"></i>
            </button>
        </div>
        
        <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <div style="margin-bottom: 24px;">
                <h3 class="text-base font-bold text-primary border-b border-slate-200 pb-2 mb-3">📊 Classificação de Status</h3>
                <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li style="display: flex; gap: 12px; align-items: flex-start;">
                        <span class="badge-rose whitespace-nowrap">Alta Rotatividade</span>
                        <span class="text-sm text-secondary">Músicas tocadas <strong>3 ou mais vezes</strong> nos últimos 90 dias. Cuidado para não "cansar" a igreja.</span>
                    </li>
                    <li style="display: flex; gap: 12px; align-items: flex-start;">
                        <span class="badge-blue whitespace-nowrap">Geladeira</span>
                        <span class="text-sm text-secondary">Músicas não tocadas entre <strong>3 e 6 meses</strong>. Ótima hora para reintroduzir!</span>
                    </li>
                    <li style="display: flex; gap: 12px; align-items: flex-start;">
                        <span class="badge-slate whitespace-nowrap">Esquecida</span>
                        <span class="text-sm text-secondary">Mais de <strong>6 meses</strong> sem tocar. Talvez já tenhamos esquecido a letra ou arranjo.</span>
                    </li>
                    <li style="display: flex; gap: 12px; align-items: flex-start;">
                        <span class="badge-yellow whitespace-nowrap">Nunca Tocada</span>
                        <span class="text-sm text-secondary">Cadastradas no sistema mas nunca escaladas. Oportunidade de novidade!</span>
                    </li>
                </ul>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 class="text-base font-bold text-primary border-b border-slate-200 pb-2 mb-3">📈 KPIs de Saúde</h3>
                <p class="text-sm text-secondary mt-3">
                    <strong>Taxa de Uso:</strong> Porcentagem do nosso repertório total que foi usado nos últimos 90 dias. Uma taxa muito baixa (< 20%) indica que temos muitas músicas "mortas". Uma taxa muito alta (> 80%) indica que rodamos bem o repertório.
                </p>
            </div>

            <div>
                <h3 class="text-base font-bold text-primary border-b border-slate-200 pb-2 mb-3">🧪 Laboratório de Escolha</h3>
                <p class="text-sm text-secondary mt-3">
                    Use esta ferramenta para montar escalas equilibradas.
                    <br>Exemplo: <em>"Preciso de uma música rápida (Tag: Celebração) em Tom D para completar a escala, mas não quero repetir o que já tocamos mês passado."</em>
                </p>
            </div>
        </div>

        <div class="modal-footer">
            <button onclick="closeHelpModal()" class="btn-primary-slate w-full justify-center">Entendi!</button>
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
// Fechar ao clicar fora
document.getElementById('helpModal').addEventListener('click', function(e) {
    if (e.target === this) closeHelpModal();
});
</script>

<?php renderAppFooter(); ?>
