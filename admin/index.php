<?php
// admin/index.php
header('Content-Type: text/html; charset=utf-8');
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/layout/dashboard_cards.php';
require_once '../src/layout/dashboard_render.php';

// 1. Carregar Dados Completos
$renderData = require 'dashboard_data.php';
extract($renderData);

// --- LÓGICA DE AVISO URGENTE (Original) ---
$popupAviso = null;
if (!empty($avisos)) {
    foreach ($avisos as $av) {
        if (($av['priority'] ?? '') === 'urgent') {
            $popupAviso = $av;
            break;
        }
    }
}
// --- LÓGICA DE VERSÍCULO DIÁRIO PARA CARDS VAZIOS ---
$diaSemana = (int)date('w');
$versiculos = [
    0 => ["\"Servi ao Senhor com alegria, apresentai-vos a ele com cânticos.\"", "Salmo 100:2"],
    1 => ["\"Cantai-lhe um cântico novo; tocai bem e com júbilo.\"", "Salmo 33:3"],
    2 => ["\"Louvarei o nome de Deus com cântico e engrandecê-lo-ei com ação de graças.\"", "Salmo 69:30"],
    3 => ["\"O Senhor é a minha força e o meu escudo; nele o meu coração confia.\"", "Salmo 28:7"],
    4 => ["\"Grandioso é o Senhor e mui digno de ser louvado.\"", "Salmo 48:1"],
    5 => ["\"Tudo quanto tem fôlego louve ao Senhor. Louvai ao Senhor!\"", "Salmo 150:6"],
    6 => ["\"Cantarei ao Senhor enquanto eu viver; cantarei louvores ao meu Deus.\"", "Salmo 104:33"]
];
$versiculoHoje = $versiculos[$diaSemana];

renderAppHeader('Dashboard');
?>

<!-- MODAL URGENTE (Premium Style) -->
<?php if ($popupAviso): ?>
<div id="urgentModal" class="hidden fixed inset-0 z-[2000] bg-slate-950/60 backdrop-blur-md items-center justify-center p-4">
    <div class="bg-surface w-full max-w-sm rounded-3xl p-6 text-center shadow-2xl border-t-4 border-t-red-500 border-surface-container-highest transition-all duration-300">
        <div class="bg-red-500/10 text-red-500 w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-500/20">
            <i data-lucide="alert-triangle" class="w-7 h-7"></i>
        </div>
        <h3 class="text-lg font-black text-on-surface font-outfit">Aviso Urgente</h3>
        <p class="text-sm font-extrabold text-red-500 mt-1 mb-4"><?= htmlspecialchars($popupAviso['title']) ?></p>
        <div class="text-left bg-surface-container-lowest p-4 rounded-2xl text-xs text-on-surface leading-relaxed max-h-[180px] overflow-y-auto border border-surface-container-highest mb-5">
            <?= nl2br(htmlspecialchars(trim(strip_tags($popupAviso['message'] ?? '')))) ?>
        </div>
        <button onclick="closeUrgentModal()" class="w-full py-3 bg-red-500 hover:bg-red-600 text-white font-extrabold text-sm rounded-xl shadow-md transition-all active:scale-[0.98] cursor-pointer">Entendido</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!sessionStorage.getItem('seen_urgent_<?= $popupAviso['id'] ?>')) {
            const modal = document.getElementById('urgentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    });
    function closeUrgentModal() {
        const modal = document.getElementById('urgentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        sessionStorage.setItem('seen_urgent_<?= $popupAviso['id'] ?>', 'true');
    }
</script>
<?php endif; ?>

<?php
// 2. Organizar Cards por Categoria (Sistema Original)
$groupedCards = [
    'gestao' => ['escalas', 'repertorio', 'membros', 'agenda', 'historico', 'metronomo'],
    'espiritualidade' => ['leitura', 'devocional', 'oracao'],
    'comunicacao' => ['avisos', 'aniversariantes']
];

$categoryNames = [
    'gestao' => 'Gestão e Equipe',
    'espiritualidade' => 'Vida Cristã',
    'comunicacao' => 'Comunicação'
];
// Definição dos atalhos rápidos essenciais (Sacred Minimalist)
$shortcuts = [
    [
        'title' => 'Escalas',
        'url' => 'escalas.php',
        'icon' => 'calendar',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Repertório',
        'url' => 'repertorio.php',
        'icon' => 'music-2',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Metrônomo',
        'url' => 'metronomo.php',
        'icon' => 'timer',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Devocional',
        'url' => 'devocionais.php',
        'icon' => 'book-heart',
        'category' => 'espiritualidade',
        'admin_only' => false
    ]
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-8">
    
    <!-- HEADER HERO PREMIUM DE BOAS-VINDAS (Bento Card Grande - Bloco Sólido Minimalista) -->
    <div class="reveal-item relative bg-slate-950 border border-slate-800/40 rounded-2xl p-4 sm:p-5 overflow-hidden transition-all duration-300 group shadow-md" style="background-image: linear-gradient(135deg, #0e1d32 0%, #152d4b 50%, #2b2311 100%);">
        <!-- Decorativos sutis de fundo (Worship Blue + Altar Gold) -->
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full blur-3xl pointer-events-none group-hover:scale-110 transition-transform duration-700" style="background-color: rgba(46,126,237,0.15);"></div>
        <div class="absolute -left-20 -bottom-20 w-56 h-56 rounded-full blur-3xl pointer-events-none group-hover:scale-110 transition-transform duration-700" style="background-color: rgba(194,144,0,0.12);"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Bloco Info Usuário (Esquerda) -->
            <div class="flex items-center gap-4">
                <div class="relative flex-shrink-0">
                    <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-full overflow-hidden border-2 border-amber-400/40 shadow-md ring-4 ring-amber-400/10 transition-all duration-300 hover:ring-amber-400/20">
                        <a href="perfil.php" class="block w-full h-full">
                            <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto de <?= htmlspecialchars($userName) ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=2E7EED&color=fff&bold=true';">
                        </a>
                    </div>
                </div>
                <div class="space-y-0.5">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-amber-400/10 text-amber-400 rounded-full text-[10px] font-bold tracking-wide border border-amber-400/20 backdrop-blur-sm">
                        <i data-lucide="sparkles" class="w-3 h-3 text-amber-400/80"></i> PIB Oliveira Louvor
                    </div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight font-outfit text-white"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>! 👋</h2>
                    <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-2">
                        <p class="text-xs text-slate-300 font-medium">Pronto para servir e adorar hoje?</p>
                        <span class="hidden md:inline text-slate-300/35 text-xs">•</span>
                        <p class="text-[10px] text-amber-400/90 font-semibold italic flex items-center gap-1" title="<?= htmlspecialchars($versiculoHoje[0] . ' — ' . $versiculoHoje[1]) ?>">
                            <?= htmlspecialchars($versiculoHoje[0]) ?> 
                            <span class="font-bold not-italic text-[9px] opacity-80">(<?= htmlspecialchars($versiculoHoje[1]) ?>)</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bloco Próxima Escala (Direita) -->
            <div class="w-full md:w-auto md:min-w-[260px]">
                <?php if (!empty($nextSchedule)): ?>
                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-sm hover:bg-white/10 transition-all duration-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-300 flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i> Próxima Escala</span>
                            <?php 
                            $statusClass = 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20';
                            $statusText = 'Pendente';
                            if (($nextSchedule['my_status'] ?? '') === 'confirmed') {
                                $statusClass = 'bg-green-500/15 text-green-400 border border-green-500/25';
                                $statusText = 'Confirmada';
                            } elseif (($nextSchedule['my_status'] ?? '') === 'declined') {
                                $statusClass = 'bg-red-500/15 text-red-400 border border-red-500/25';
                                $statusText = 'Recusada';
                            }
                            ?>
                            <span class="text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl font-extrabold font-outfit tracking-tight text-white"><?= date('d/m', strtotime($nextSchedule['event_date'])) ?></span>
                            <div class="flex flex-col overflow-hidden">
                                <span class="text-xs font-bold text-white truncate leading-tight"><?= htmlspecialchars($nextSchedule['event_type']) ?></span>
                                <span class="text-[10px] text-slate-300 font-medium mt-0.5 truncate"><?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-3 flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-white/10 text-amber-400 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="calendar-heart" class="w-4 h-4 text-amber-400"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-white leading-tight">Nenhuma escala próxima</span>
                            <span class="text-[9px] text-slate-300 font-semibold mt-0.5 leading-tight">Acompanhe os cultos no menu Escalas.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BENTO STITCH: AVISOS + ANIVERSARIANTES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Card Avisos -->
        <div class="reveal-item reveal-stagger-1 lg:col-span-2 bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2 font-bold tracking-tight">
                    <span class="text-worship-blue flex items-center"><i data-lucide="megaphone" class="w-5 h-5"></i></span>
                    Avisos
                </h2>
                <a href="avisos.php" class="font-label-sm text-label-sm text-worship-blue uppercase tracking-wider hover:opacity-75 transition-opacity">Ver todos</a>
            </div>
            <div class="space-y-3.5 flex-grow">
                <?php if (!empty($avisos)): ?>
                    <?php foreach (array_slice($avisos, 0, 3) as $av): ?>
                        <?php
                        $prio = $av['priority'] ?? 'info';
                        $prioDot = $prio === 'urgent' ? 'bg-red-500' : ($prio === 'important' ? 'bg-amber-500' : 'bg-worship-blue');
                        $ts = !empty($av['created_at']) ? strtotime($av['created_at']) : time();
                        $quando = (date('Y-m-d', $ts) === date('Y-m-d'))
                            ? 'Hoje, ' . date('H:i', $ts)
                            : date('d/m', $ts) . ' às ' . date('H:i', $ts);
                        ?>
                        <div class="reveal-item pb-3 border-b border-surface-container-highest last:border-0 last:pb-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="w-2 h-2 rounded-full <?= $prioDot ?> flex-shrink-0"></span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-[10px]"><?= htmlspecialchars($quando) ?></span>
                            </div>
                            <div class="font-body-md text-body-md font-semibold text-on-surface"><?= htmlspecialchars($av['title'] ?? '') ?></div>
                            <?php
                            // Avisos vêm de editor WYSIWYG (HTML). Limpamos as tags para a prévia em texto puro.
                            $avisoPreview = trim(preg_replace('/\s+/', ' ', strip_tags($av['message'] ?? '')));
                            ?>
                            <?php if ($avisoPreview !== ''): ?>
                                <p class="font-body-md text-on-surface-variant mt-0.5 text-xs line-clamp-2 leading-relaxed"><?= htmlspecialchars($avisoPreview) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center py-8 px-4 text-on-surface-variant flex-grow space-y-2">
                        <span class="text-xs italic font-medium text-center">"<?= htmlspecialchars($versiculoHoje[0]) ?>"</span>
                        <span class="text-[10px] font-bold text-primary/80">- <?= htmlspecialchars($versiculoHoje[1]) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Aniversariantes -->
        <div class="reveal-item reveal-stagger-2 bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col">
            <div class="flex items-center mb-4">
                <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2 font-bold tracking-tight">
                    <span class="text-altar-gold flex items-center"><i data-lucide="cake" class="w-5 h-5"></i></span>
                    Aniversariantes
                </h2>
            </div>
            <?php if (!empty($aniversariantes)): ?>
                <div class="flex gap-3 overflow-x-auto pb-1.5 -mx-1 px-1 flex-grow items-start">
                    <?php foreach (array_slice($aniversariantes, 0, 8) as $niver): ?>
                        <?php
                        $nomeCurto = explode(' ', trim($niver['name']))[0];
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($niver['name']) . '&background=eef2ff&color=2E7EED&bold=true';
                        ?>
                        <div class="reveal-item flex flex-col items-center min-w-[60px] transition-transform active:scale-95 duration-150">
                            <div class="w-11 h-11 rounded-full overflow-hidden mb-1.5 border border-surface-container-highest">
                                <img alt="<?= htmlspecialchars($niver['name']) ?>" class="w-full h-full object-cover" src="<?= $avatar ?>">
                            </div>
                            <span class="font-label-sm text-label-sm text-on-surface text-center truncate w-full text-[11px]"><?= htmlspecialchars($nomeCurto) ?></span>
                            <span class="font-label-sm text-on-surface-variant text-[9px]">Dia <?= (int)$niver['dia'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center text-center py-6 px-4 text-on-surface-variant flex-grow space-y-2">
                    <div class="p-3 bg-amber-500/5 rounded-full text-amber-500 border border-amber-500/10">
                        <i data-lucide="heart" class="w-5 h-5 animate-pulse"></i>
                    </div>
                    <span class="font-body-md text-xs font-semibold text-on-surface">Nenhum aniversário este mês</span>
                    <p class="text-[10px] text-on-surface-variant leading-relaxed max-w-[200px]">Que tal aproveitar o dia de hoje para orar pela nossa liderança e equipe de louvor?</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- SEÇÃO ATALHOS RÁPIDOS TÁTEIS -->
    <div class="space-y-3">
        <div class="flex items-center gap-2 px-1">
            <div class="bg-primary/10 p-1.5 rounded-lg text-primary flex items-center justify-center">
                <i data-lucide="layout-grid" class="w-4 h-4"></i>
            </div>
            <h3 class="text-sm font-bold text-on-surface font-outfit tracking-tight">Acesso Rápido</h3>
        </div>
        
        <div class="reveal-item reveal-stagger-3 grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($shortcuts as $sc): ?>
                <?php if ($sc['admin_only'] && $userRole !== 'admin') continue; ?>
                
                <?php
                // Mapeamento de Cores para Tailwind
                $catColors = [
                    'gestao' => [
                        'icon' => 'bg-primary/10 text-primary border border-primary/10',
                    ],
                    'espiritualidade' => [
                        'icon' => 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/10 dark:text-emerald-400',
                    ],
                    'comunicacao' => [
                        'icon' => 'bg-amber-500/10 text-amber-600 border border-amber-500/10 dark:text-amber-400',
                    ],
                    'admin' => [
                        'icon' => 'bg-red-500/10 text-red-600 border border-red-500/10 dark:text-red-400',
                    ]
                ];
                $colors = $catColors[$sc['category']] ?? $catColors['gestao'];
                ?>
                
                <a href="<?= $sc['url'] ?>" class="interactive-scale bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-3 flex items-center gap-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md group select-none shadow-sm">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform <?= $colors['icon'] ?>">
                        <i data-lucide="<?= $sc['icon'] ?>" class="w-[18px] h-[18px]"></i>
                    </div>
                    <span class="text-sm font-bold text-on-surface font-outfit leading-tight truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($sc['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>
