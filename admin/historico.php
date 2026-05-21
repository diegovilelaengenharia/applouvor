<?php
// admin/historico.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

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

// Hero Bento Premium customizado integrado
?>

<!-- Estilos Customizados Sacred Minimalist (Transições GPU & Bento Grid) -->
<style>
    /* Animações e Efeitos Sacred Minimalist */
    @keyframes revealStagger {
        0% {
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .reveal-item {
        opacity: 0;
        animation: revealStagger 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        will-change: opacity, transform;
    }

    /* Delays dinâmicos em stagger */
    .reveal-item:nth-child(1) { animation-delay: 0.03s; }
    .reveal-item:nth-child(2) { animation-delay: 0.06s; }
    .reveal-item:nth-child(3) { animation-delay: 0.09s; }
    .reveal-item:nth-child(4) { animation-delay: 0.12s; }
    .reveal-item:nth-child(5) { animation-delay: 0.15s; }
    .reveal-item:nth-child(6) { animation-delay: 0.18s; }
    .reveal-item:nth-child(7) { animation-delay: 0.21s; }

    /* Micro-movimento de toque */
    .interactive-scale {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .interactive-scale:active {
        transform: scale(0.97);
    }

    /* Estilo ativo para as pills de filtro */
    .filter-chip.active {
        background-color: rgba(46, 126, 237, 0.1) !important;
        border-color: #2E7EED !important;
        color: #2E7EED !important;
    }
</style>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 mb-32 space-y-8 animate-fade-in" id="historico-container">
    <!-- Hero / Header Section Bento Premium -->
    <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-[#1A1B1F] text-gray-800 dark:text-white p-8 shadow-sm dark:shadow-xl border border-gray-100 dark:border-white/5 reveal-item">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#2E7EED]/10 dark:bg-[#2E7EED]/20 border border-[#2E7EED]/20 dark:border-[#2E7EED]/30 text-[#2E7EED] text-xs font-bold uppercase tracking-wider mb-3">
                    📊 Inteligência de Repertório
                </span>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight font-sans">Laboratório de Repertório</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-xl text-sm font-body">Análise estratégica e histórico completo do repertório musical para seleção inteligente e balanceada.</p>
            </div>
        </div>
    </div>

    <!-- TABS Flutuantes Bento Premium -->
    <div class="flex items-center gap-1.5 p-1 bg-gray-100 dark:bg-[#121316] rounded-2xl w-fit shadow-sm border border-transparent dark:border-white/5 reveal-item overflow-x-auto max-w-full">
        <a href="?tab=visageral" class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn flex items-center gap-2 <?= $currentTab == 'visageral' ? 'bg-white dark:bg-[#1A1B1F] text-[#2E7EED] shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
            <i data-lucide="bar-chart-2" class="w-4 h-4"></i> Visão Geral
        </a>
        <a href="?tab=raiox" class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn flex items-center gap-2 <?= $currentTab == 'raiox' ? 'bg-white dark:bg-[#1A1B1F] text-[#2E7EED] shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
            <i data-lucide="stethoscope" class="w-4 h-4"></i> Raio-X
        </a>
        <a href="?tab=estilo" class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn flex items-center gap-2 <?= $currentTab == 'estilo' ? 'bg-white dark:bg-[#1A1B1F] text-[#2E7EED] shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
            <i data-lucide="palette" class="w-4 h-4"></i> Tags & Tons
        </a>
        <a href="?tab=laboratorio" class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn flex items-center gap-2 <?= $currentTab == 'laboratorio' ? 'bg-white dark:bg-[#1A1B1F] text-[#2E7EED] shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
            <i data-lucide="flask-conical" class="w-4 h-4"></i> Laboratório
        </a>
    </div>

    <!-- TAB: VISÃO GERAL -->
    <?php if ($currentTab == 'visageral'): ?>
        <div class="space-y-6">
            <!-- Header KPIs -->
            <div class="flex justify-between items-center reveal-item">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2 uppercase tracking-wider">
                    <i data-lucide="activity" class="text-[#2E7EED] w-4 h-4"></i>
                    Saúde do Repertório (<?= $period ?> dias)
                </h3>
                <button onclick="openHelpModal()" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-250 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] text-gray-600 dark:text-gray-300 flex items-center gap-1.5 transition-colors cursor-pointer interactive-scale border border-transparent dark:border-white/5">
                    <i data-lucide="help-circle" class="w-4 h-4"></i> Entenda
                </button>
            </div>
            
            <!-- KPIs Grid Bento -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 reveal-item">
                <?php foreach($kpiCards as $kpi): 
                    $cardColorClass = '';
                    $iconColorClass = '';
                    $valueColorClass = '';
                    
                    if ($kpi['style'] === 'green') {
                        $cardColorClass = 'bg-emerald-50/50 dark:bg-emerald-950/10 border-emerald-100 dark:border-emerald-900/20';
                        $iconColorClass = 'bg-emerald-100/80 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400';
                        $valueColorClass = 'text-emerald-600 dark:text-emerald-400';
                    } elseif ($kpi['style'] === 'rose') {
                        $cardColorClass = 'bg-red-50/50 dark:bg-red-950/10 border-red-100 dark:border-red-900/20';
                        $iconColorClass = 'bg-red-100/80 dark:bg-red-900/30 text-red-650 dark:text-red-400';
                        $valueColorClass = 'text-red-650 dark:text-red-400';
                    } elseif ($kpi['style'] === 'blue') {
                        $cardColorClass = 'bg-blue-50/50 dark:bg-blue-950/10 border-blue-100 dark:border-blue-900/20';
                        $iconColorClass = 'bg-blue-100/80 dark:bg-blue-900/30 text-blue-600 dark:text-blue-450';
                        $valueColorClass = 'text-blue-600 dark:text-blue-450';
                    } elseif ($kpi['style'] === 'yellow') {
                        $cardColorClass = 'bg-amber-50/50 dark:bg-amber-950/10 border-amber-100 dark:border-amber-900/20';
                        $iconColorClass = 'bg-amber-100/80 dark:bg-amber-900/30 text-[#D97706] dark:text-[#FFC107]';
                        $valueColorClass = 'text-[#D97706] dark:text-[#FFC107]';
                    }
                ?>
                <div class="flex flex-col items-center text-center p-5 bg-white dark:bg-[#1A1B1F] rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm <?= $cardColorClass ?> transition-all duration-300 hover:shadow-md dark:hover:shadow-black/10 hover:scale-[1.02]">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center mb-3 text-lg font-bold <?= $iconColorClass ?>">
                        <i data-lucide="<?= $kpi['icon'] ?>" class="w-5 h-5"></i>
                    </div>
                    <div class="text-3xl font-extrabold tracking-tight <?= $valueColorClass ?>"><?= $kpi['value'] ?></div>
                    <div class="text-xs font-bold text-gray-800 dark:text-gray-250 mt-1 leading-tight"><?= $kpi['title'] ?></div>
                    <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold mt-0.5 leading-tight"><?= $kpi['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Top 5 Mais Tocadas Bento Box -->
            <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm space-y-4 reveal-item">
                <h4 class="text-base font-extrabold text-gray-850 dark:text-white flex items-center gap-2">
                    <span class="bg-red-50 dark:bg-red-950/20 text-red-500 dark:text-red-400 p-2 rounded-xl text-base flex items-center justify-center shrink-0">🔥</span>
                    Top 5 Mais Tocadas
                </h4>
                
                <div class="flex flex-col gap-3">
                    <?php 
                    $top5 = array_slice($musicasXRay, 0, 5);
                    foreach ($top5 as $i => $m): 
                        $rankNum = $i + 1;
                        $rankBadgeClass = '';
                        if ($rankNum === 1) {
                            $rankBadgeClass = 'bg-[#FFC107]/10 text-[#D97706] dark:text-[#FFC107]';
                        } elseif ($rankNum === 2) {
                            $rankBadgeClass = 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-350';
                        } elseif ($rankNum === 3) {
                            $rankBadgeClass = 'bg-amber-100/60 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400';
                        } else {
                            $rankBadgeClass = 'bg-gray-50 dark:bg-[#2C2C2E] text-gray-400 dark:text-gray-550';
                        }
                    ?>
                        <div class="flex items-center gap-4 p-3 bg-gray-50/50 dark:bg-[#2C2C2E]/40 border border-gray-100 dark:border-[#3A3A3C] rounded-2xl transition-all hover:translate-x-1 duration-200">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-extrabold text-sm <?= $rankBadgeClass ?> shrink-0">
                                <?= $rankNum ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-gray-800 dark:text-gray-150 truncate leading-tight"><?= htmlspecialchars($m['title']) ?></div>
                                <div class="text-xs text-gray-400 dark:text-gray-550 font-semibold truncate leading-tight mt-0.5"><?= htmlspecialchars($m['artist']) ?></div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-extrabold text-gray-800 dark:text-gray-100 leading-none"><?= $m['freq_period'] ?>x</div>
                                <div class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-1 leading-none">tocadas</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CTA Laboratório Bento Style -->
            <div class="text-center pt-2 reveal-item">
                <a href="historico.php?tab=laboratorio" class="inline-flex items-center gap-2 px-6 py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-extrabold text-sm rounded-2xl shadow-sm transition-all duration-200 active:scale-95 cursor-pointer interactive-scale">
                    <i data-lucide="flask-conical" class="w-4 h-4"></i>
                    Ir para Laboratório de Escolha
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB: RAIO-X -->
    <?php if ($currentTab == 'raiox'): ?>
        <div class="space-y-6">
            <!-- Busca e Filtros Bento -->
            <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-[#2C2C2E] space-y-4 reveal-item">
                <div class="relative w-full">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                    <input 
                        type="text" 
                        id="tableSearch" 
                        placeholder="Buscar por música ou artista..." 
                        class="w-full h-12 pl-12 pr-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 text-gray-800 dark:text-white font-medium"
                    >
                </div>

                <!-- Filtros Rápidos (Pills) -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider pl-1 mr-1">Status:</span>
                    
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95 active" onclick="filterByStatus('all')" data-status="all">🎵 Todas</button>
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95" onclick="filterByStatus('em_alta')" data-status="em_alta">🔥 Alta Rot.</button>
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95" onclick="filterByStatus('ok')" data-status="ok">✅ Saudável</button>
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95" onclick="filterByStatus('geladeira')" data-status="geladeira">❄️ Geladeira</button>
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95" onclick="filterByStatus('esquecida')" data-status="esquecida">📦 Esquecida</button>
                    <button class="filter-chip px-3 py-1.5 rounded-full text-xs font-bold border border-gray-150 dark:border-[#3A3A3C] bg-white dark:bg-[#2C2C2E] text-gray-600 dark:text-gray-300 hover:border-[#2E7EED] hover:text-[#2E7EED] transition-all cursor-pointer active:scale-95" onclick="filterByStatus('virgem')" data-status="virgem">⭐ Nunca Tocada</button>
                </div>
            </div>

            <!-- Contador de Resultados -->
            <div class="text-sm font-bold text-gray-500 dark:text-gray-400 pl-1 reveal-item" id="resultsCount"></div>

            <div id="raioXList" class="flex flex-col gap-3">
                <!-- Cabeçalho de Ordenação Simplificado -->
                <div class="flex justify-between items-center px-4 text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider reveal-item">
                    <span>Música e Status</span>
                    <span>Frequência</span>
                </div>

                <?php foreach ($musicasXRay as $m): 
                    $days = $m['days_since_last'];
                    $badgeClass = 'badge-slate';
                    $badgeText = 'Normal';
                    
                    if ($m['freq_total'] == 0) {
                        $badgeClass = 'badge-yellow bg-amber-500/10 text-[#D97706] dark:text-[#FFC107] border border-amber-500/20'; 
                        $badgeText = 'Nunca Tocada';
                    } elseif ($m['freq_period'] >= 3) {
                        $badgeClass = 'badge-rose bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20'; 
                        $badgeText = 'Alta Rotatividade';
                    } elseif ($days > 180) {
                        $badgeClass = 'badge-slate bg-gray-500/10 text-gray-500 dark:text-gray-400 border border-gray-500/20'; 
                        $badgeText = 'Esquecida';
                    } elseif ($days > 90) {
                        $badgeClass = 'badge-blue bg-blue-500/10 text-blue-600 dark:text-blue-450 border border-blue-500/20'; 
                        $badgeText = 'Geladeira';
                    } else {
                        $badgeClass = 'badge-green bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/20'; 
                        $badgeText = 'Saudável';
                    }
                ?>
                <a href="musica_detalhe.php?id=<?= $m['id'] ?>" class="compact-card flex items-center justify-between p-4 bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-[#2C2C2E] hover:border-[#2E7EED]/30 rounded-2xl hover:shadow-sm transition-all duration-200 active:scale-[0.995] reveal-item" data-title="<?= strtolower(htmlspecialchars($m['title'])) ?>" data-artist="<?= strtolower(htmlspecialchars($m['artist'])) ?>" data-status-class="<?= $badgeClass ?>">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-[#2C2C2E] flex items-center justify-center shrink-0 border border-gray-100 dark:border-white/5">
                            <?php if($m['tone']): ?>
                                <span class="font-extrabold text-sm text-[#2E7EED]"><?= $m['tone'] ?></span>
                            <?php else: ?>
                                <i data-lucide="music" class="w-4 h-4 text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-gray-800 dark:text-gray-150 truncate leading-tight"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="text-xs text-gray-450 dark:text-gray-500 font-semibold truncate leading-tight mt-0.5"><?= htmlspecialchars($m['artist']) ?></div>
                            
                            <div class="flex items-center flex-wrap gap-2 mt-2">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wide <?= $badgeClass ?>">
                                    <?= $badgeText ?>
                                </span>
                                <?php if ($m['last_played']): ?>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold">
                                        Há <?= $days ?> dias
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-end justify-center shrink-0 pl-2">
                        <span class="text-lg font-extrabold text-[#2E7EED]"><?= $m['freq_period'] ?>x</span>
                        <span class="text-[9px] text-gray-450 dark:text-gray-500 font-bold uppercase tracking-wider mt-0.5">Execs.</span>
                    </div>
                </a>
                <?php endforeach; ?>
                
                <?php if (empty($musicasXRay)): ?>
                    <div class="p-12 text-center text-gray-400 dark:text-gray-550 bg-gray-50 dark:bg-[#1A1B1F] border border-dashed border-gray-250 dark:border-[#2C2C2E] rounded-3xl reveal-item">
                        Nenhuma música encontrada no raio-x.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Filter Logic (Adaptado para class compact-card)
            let currentStatusFilter = 'all';
            
            function filterByStatus(status) {
                currentStatusFilter = status;
                document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));
                document.querySelector(`[data-status="${status}"]`).classList.add('active');
                filterCards();
            }
            
            function filterCards() {
                const input = document.getElementById('tableSearch');
                const filter = input.value.toLowerCase();
                const cards = document.querySelectorAll('#raioXList .compact-card');
                let visibleCount = 0;
                
                cards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const artist = card.getAttribute('data-artist') || '';
                    const statusClass = card.getAttribute('data-status-class') || '';
                    
                    let statusMatch = true;
                    if (currentStatusFilter !== 'all') {
                        if (currentStatusFilter === 'em_alta') statusMatch = statusClass.includes('badge-rose');
                        else if (currentStatusFilter === 'ok') statusMatch = statusClass.includes('badge-green');
                        else if (currentStatusFilter === 'geladeira') statusMatch = statusClass.includes('badge-blue');
                        else if (currentStatusFilter === 'esquecida') statusMatch = statusClass.includes('badge-slate');
                        else if (currentStatusFilter === 'virgem') statusMatch = statusClass.includes('badge-yellow');
                    }
                    
                    if ((title.includes(filter) || artist.includes(filter)) && statusMatch) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                document.getElementById('resultsCount').textContent = `Exibindo ${visibleCount} música${visibleCount !== 1 ? 's' : ''}`;
            }

            // Atrelar o keyup do input de busca ao filterCards()
            document.getElementById('tableSearch').addEventListener('keyup', filterCards);
            window.addEventListener('DOMContentLoaded', filterCards);
        </script>
    <?php endif; ?>

    <!-- TAB: TAGS & TONS -->
    <?php if ($currentTab == 'estilo'): ?>
        <div class="space-y-6">
            <!-- TAGS Bento -->
            <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm space-y-4 reveal-item">
                <h3 class="text-base font-extrabold text-gray-850 dark:text-white flex items-center gap-2">
                    <i data-lucide="tag" class="text-[#2E7EED] w-5 h-5"></i> Tags Mais Cantadas (Últimos <?= $period ?> dias)
                </h3>
                
                <?php if (!empty($topTags)): ?>
                <div class="flex flex-col gap-3">
                    <!-- Cabeçalho simplificado para Mobile -->
                    <div class="flex justify-between items-center px-4 text-[10px] font-bold text-gray-400 dark:text-gray-555 uppercase tracking-wider">
                        <span>Tag e Frequência</span>
                        <span>% e Tendência</span>
                    </div>

                    <?php 
                    $maxUses = !empty($topTags) ? $topTags[0]['uses_period'] : 1;
                    $totalExec = array_sum(array_column($topTags, 'uses_period'));
                    $rank = 1;
                    foreach ($topTags as $tag): 
                        $percentTotal = $totalExec > 0 ? round(($tag['uses_period'] / $totalExec) * 100, 1) : 0;
                        
                        $trend = $tag['uses_period'] >= 3 ? 'up' : ($tag['uses_period'] == 1 ? 'down' : 'stable');
                        $trendColor = $trend == 'up' ? 'text-emerald-550 dark:text-emerald-400' : ($trend == 'down' ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500');
                        $trendIcon = $trend == 'up' ? 'trending-up' : ($trend == 'down' ? 'trending-down' : 'minus');
                    ?>
                    <div class="flex items-center justify-between p-3.5 bg-gray-50/50 dark:bg-[#2C2C2E]/40 border border-gray-100 dark:border-[#3A3A3C] rounded-2xl hover:translate-x-1 transition-all duration-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-6 font-extrabold text-xs text-gray-400 dark:text-gray-500">
                                <?= $rank++ ?>º
                            </div>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border border-transparent shadow-sm" style="background: <?= $tag['color'] ?>18; color: <?= $tag['color'] ?>;">
                                <i data-lucide="tag" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 ml-1">
                                <div class="text-sm font-bold text-gray-800 dark:text-gray-150 truncate leading-tight">#<?= htmlspecialchars($tag['name']) ?></div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold leading-tight mt-0.5"><?= $tag['uses_total'] ?? 0 ?> execuções totais</div>
                            </div>
                        </div>
                        
                        <!-- Lado Direito do Card -->
                        <div class="flex items-center gap-4 shrink-0 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-sm font-extrabold text-gray-800 dark:text-gray-100 leading-none"><?= $tag['uses_period'] ?>x</span>
                                <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold mt-1 leading-none flex items-center gap-0.5 select-none">
                                    <?= $percentTotal ?>% 
                                    <i data-lucide="<?= $trendIcon ?>" class="w-3 h-3 <?= $trendColor ?>"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-center text-gray-400 dark:text-gray-550 p-8 border border-dashed border-gray-250 dark:border-[#2C2C2E] rounded-2xl">Nenhuma tag registrada neste período.</p>
                <?php endif; ?>
            </div>

            <!-- TONS Bento -->
            <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm space-y-4 reveal-item">
                <h3 class="text-base font-extrabold text-gray-850 dark:text-white flex items-center gap-2">
                    <i data-lucide="music" class="text-[#2E7EED] w-5 h-5"></i> Distribuição de Tons
                </h3>
                
                <?php if (!empty($usoTons)): ?>
                <div class="grid grid-cols-3 sm:grid-cols-6 md:grid-cols-7 gap-3">
                    <?php 
                    $tonColors = [
                        'C' => '#ef4444', 'D' => '#f59e0b', 'E' => '#22c55e', 
                        'F' => '#3b82f6', 'G' => '#a855f7', 'A' => '#ec4899', 'B' => '#14b8a6'
                    ];
                    foreach ($usoTons as $ton):
                        $baseTone = substr($ton['tone'], 0, 1);
                        $barColor = $tonColors[$baseTone] ?? '#2E7EED';
                    ?>
                    <div class="flex flex-col items-center p-3 bg-gray-50/50 dark:bg-[#2C2C2E]/40 border border-gray-100 dark:border-[#3A3A3C] rounded-2xl transition-all duration-300 hover:scale-[1.03] hover:border-[#2E7EED]/30 cursor-default" style="border-top: 3px solid <?= $barColor ?>;">
                        <div class="text-base font-extrabold text-[#2E7EED]"><?= $ton['tone'] ?></div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-550 font-bold uppercase tracking-wider mt-1"><?= $ton['uses_period'] ?>x</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-center text-gray-400 dark:text-gray-550 p-8 border border-dashed border-gray-250 dark:border-[#2C2C2E] rounded-2xl">Nenhum tom registrado neste período.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB: LABORATÓRIO -->
    <?php if ($currentTab == 'laboratorio'): ?>
        <div class="space-y-6">
            <!-- Header Bento Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#2E7EED]/10 to-[#FFC107]/5 dark:from-[#2E7EED]/20 dark:to-[#FFC107]/10 p-8 shadow-sm border border-[#2E7EED]/20 dark:border-white/5 reveal-item">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#FFC107]/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-[#1A1B1F] text-[#2E7EED] flex items-center justify-center shadow-sm border border-gray-100 dark:border-white/5 mb-4 shrink-0">
                        <i data-lucide="flask-conical" class="w-6 h-6"></i>
                    </div>
                    <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Laboratório de Repertório</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-lg font-body leading-relaxed">Combine filtros técnicos avançados, estilos, tags e o histórico de uso para encontrar a música perfeita e equilibrada para o seu próximo culto.</p>
                </div>
            </div>

            <!-- Filtros Form Bento -->
            <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm reveal-item">
                <form action="" method="GET" class="space-y-6 m-0">
                    <input type="hidden" name="tab" value="laboratorio">
                    <input type="hidden" name="search" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Tom -->
                        <div class="flex flex-col">
                            <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1 mb-2">Tom da Música</label>
                            <div class="relative">
                                <select name="tone_filter" class="w-full h-11 pl-4 pr-10 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-xs font-bold text-gray-600 dark:text-gray-300 focus:outline-none focus:border-[#2E7EED] cursor-pointer appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_14px_center]">
                                    <option value="">🎵 Todos os Tons</option>
                                    <?php foreach(['C', 'D', 'E', 'F', 'G', 'A', 'B'] as $t): ?>
                                        <option value="<?= $t ?>" <?= ($_GET['tone_filter'] ?? '') == $t ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Estilo / Tag -->
                        <div class="flex flex-col">
                            <label class="text-xs font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider pl-1 mb-2">Estilo / Tag</label>
                            <div class="relative">
                                <select name="tag_filter" class="w-full h-11 pl-4 pr-10 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-xs font-bold text-gray-600 dark:text-gray-300 focus:outline-none focus:border-[#2E7EED] cursor-pointer appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_14px_center]">
                                    <option value="">🏷️ Todos os Estilos</option>
                                    <?php 
                                    $tagsAll = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();
                                    foreach($tagsAll as $tg):
                                    ?>
                                        <option value="<?= $tg['id'] ?>" <?= ($_GET['tag_filter'] ?? '') == $tg['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tg['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Filtro Extra Checkbox -->
                        <div class="flex flex-col justify-center">
                            <label class="text-xs font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider pl-1 mb-2">Filtro Extra</label>
                            <label class="relative flex items-center gap-3 p-3 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl cursor-pointer hover:border-[#2E7EED]/30 transition-all select-none">
                                <input type="checkbox" name="not_played" id="not_played" value="1" <?= isset($_GET['not_played']) ? 'checked' : '' ?> class="w-4 h-4 rounded text-[#2E7EED] focus:ring-[#2E7EED] border-gray-300 dark:border-[#3A3A3C] dark:bg-[#1A1B1F]">
                                <span class="text-xs font-extrabold text-gray-700 dark:text-gray-300 leading-tight">Não tocada há 90 dias</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full h-12 rounded-2xl bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-extrabold text-sm flex items-center justify-center gap-2 shadow-sm transition-all duration-200 active:scale-[0.99] cursor-pointer interactive-scale">
                        <i data-lucide="sparkles" class="w-4 h-4"></i> Analisar e Buscar Sugestões
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
                <div class="space-y-4 reveal-item">
                    <h3 class="text-sm font-bold text-gray-505 dark:text-gray-400 pl-1 uppercase tracking-wider">Resultados Sugeridos (<?= count($labResults) ?>)</h3>
                    
                    <div class="grid gap-3">
                        <?php foreach ($labResults as $res): ?>
                        <a href="musica_detalhe.php?id=<?= $res['id'] ?>" class="compact-card flex items-center justify-between p-4 bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-[#2C2C2E] hover:border-[#2E7EED]/30 rounded-2xl hover:shadow-sm transition-all duration-200 active:scale-[0.995]">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-[#2C2C2E] flex items-center justify-center shrink-0 border border-gray-100 dark:border-white/5">
                                    <?php if ($res['tone']): ?>
                                        <span class="font-extrabold text-sm text-[#2E7EED]"><?= $res['tone'] ?></span>
                                    <?php else: ?>
                                        <i data-lucide="music" class="w-4 h-4 text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-150 truncate leading-tight"><?= htmlspecialchars($res['title']) ?></div>
                                    <div class="text-xs text-gray-450 dark:text-gray-500 font-semibold truncate leading-tight mt-0.5"><?= htmlspecialchars($res['artist']) ?></div>
                                    
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-[10px] font-bold text-gray-450 dark:text-gray-500 uppercase tracking-wide">
                                            ⌛ <?= !$res['last_played'] ? 'Nunca tocada' : 'Tocada há ' . $res['days_since'] . ' dias' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400 hover:text-[#2E7EED] transition-colors shrink-0"></i>
                        </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($labResults)): ?>
                            <div class="p-12 text-center text-gray-400 dark:text-gray-550 bg-gray-50 dark:bg-[#1A1B1F] border border-dashed border-gray-250 dark:border-[#2C2C2E] rounded-3xl">
                                Nenhuma música encontrada com estes filtros.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<!-- HELP MODAL (Bento Premium com Backdrop Blur) -->
<div id="helpModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#1A1B1F] rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-2xl w-full max-w-md overflow-hidden transform scale-95 opacity-0 transition-all duration-300 modal-body-container">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-50 dark:border-[#2C2C2E]">
            <h3 class="text-base font-extrabold text-gray-850 dark:text-white">Entenda as Métricas</h3>
            <button class="w-8 h-8 rounded-full bg-gray-50 dark:bg-[#2C2C2E] text-gray-450 hover:text-gray-650 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer interactive-scale" onclick="closeHelpModal()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6 space-y-6">
            <div class="space-y-3">
                <h4 class="text-xs font-black text-[#FFC107] uppercase tracking-wider pl-1">Classificação de Status</h4>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20 text-[9px] font-extrabold uppercase shrink-0 mt-0.5">Alta Rotatividade</span>
                        <p class="text-xs text-gray-550 dark:text-gray-450 leading-relaxed font-body">Tocada 3 ou mais vezes nos últimos 90 dias. Monitorar para evitar saturação.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-650 dark:text-blue-450 border border-blue-500/20 text-[9px] font-extrabold uppercase shrink-0 mt-0.5">Geladeira</span>
                        <p class="text-xs text-gray-550 dark:text-gray-450 leading-relaxed font-body">Não tocada há mais de 90 dias, mas há menos de 180 dias. Momento ideal para reavaliar.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="px-2 py-0.5 rounded bg-gray-500/10 text-gray-500 dark:text-gray-450 border border-gray-500/20 text-[9px] font-extrabold uppercase shrink-0 mt-0.5">Esquecida</span>
                        <p class="text-xs text-gray-550 dark:text-gray-450 leading-relaxed font-body">Mais de 180 dias sem tocar no repertório ativo. Requer esforço da equipe para reviver.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="px-2 py-0.5 rounded bg-amber-500/10 text-[#D97706] dark:text-[#FFC107] border border-amber-500/20 text-[9px] font-extrabold uppercase shrink-0 mt-0.5">Nunca Tocada</span>
                        <p class="text-xs text-gray-550 dark:text-gray-450 leading-relaxed font-body">Músicas já catalogadas no sistema mas que nunca integraram um roteiro oficial. Uma excelente oportunidade para novidades.</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-2 pt-4 border-t border-gray-50 dark:border-[#2C2C2E]">
                <h4 class="text-xs font-black text-[#2E7EED] uppercase tracking-wider pl-1">Taxa de Uso</h4>
                <p class="text-xs text-gray-550 dark:text-gray-450 leading-relaxed font-body">Mede a eficiência e renovação do repertório. É a porcentagem das músicas do acervo total que foram tocadas ao menos uma vez nos últimos 90 dias.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="p-6 border-t border-gray-50 dark:border-[#2C2C2E]">
            <button onclick="closeHelpModal()" class="w-full h-11 rounded-2xl bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-extrabold text-xs flex items-center justify-center shadow-sm cursor-pointer active:scale-95 transition-all">Entendi as Métricas</button>
        </div>
    </div>
</div>

<script>
function openHelpModal() {
    const modal = document.getElementById('helpModal');
    const body = modal.querySelector('.modal-body-container');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        body.classList.add('scale-100', 'opacity-100');
    }, 10);
}
function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    const body = modal.querySelector('.modal-body-container');
    modal.classList.remove('opacity-100');
    body.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}
</script>

<?php renderAppFooter(); ?>

