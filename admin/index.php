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

renderAppHeader('Dashboard');
?>

<!-- MODAL URGENTE (Premium Style) -->
<?php if ($popupAviso): ?>
<div id="urgentModal" class="hidden fixed inset-0 z-[2000] bg-slate-950/60 backdrop-blur-md items-center justify-center p-4">
    <div class="bg-surface w-full max-w-sm rounded-3xl p-6 text-center shadow-2xl border-t-4 border-t-red-500 border-surface-container-highest transition-all duration-300">
        <div class="bg-red-500/10 text-red-500 w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-500/20">
            <i data-lucide="alert-triangle" class="w-7 h-7"></i>
        </div>
        <h3 class="text-lg font-black text-surface-on-surface font-outfit">Aviso Urgente</h3>
        <p class="text-sm font-extrabold text-red-500 mt-1 mb-4"><?= htmlspecialchars($popupAviso['title']) ?></p>
        <div class="text-left bg-surface-container-lowest p-4 rounded-2xl text-xs text-surface-on-surface leading-relaxed max-h-[180px] overflow-y-auto border border-surface-container-highest mb-5">
            <?= nl2br(htmlspecialchars($popupAviso['message'] ?? '')) ?>
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

// Definição dos atalhos rápidos táteis
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
        'title' => 'Histórico',
        'url' => 'historico.php',
        'icon' => 'history',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Membros',
        'url' => 'membros.php',
        'icon' => 'users',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Ausências',
        'url' => 'indisponibilidade.php',
        'icon' => 'calendar-off',
        'category' => 'gestao',
        'admin_only' => false
    ],
    [
        'title' => 'Agenda',
        'url' => 'agenda.php',
        'icon' => 'calendar-range',
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
    ],
    [
        'title' => 'Oração',
        'url' => 'oracao.php',
        'icon' => 'heart',
        'category' => 'espiritualidade',
        'admin_only' => false
    ],
    [
        'title' => 'Bíblia',
        'url' => 'leitura.php',
        'icon' => 'book-open',
        'category' => 'espiritualidade',
        'admin_only' => false
    ],
    [
        'title' => 'Avisos',
        'url' => 'avisos.php',
        'icon' => 'megaphone',
        'category' => 'comunicacao',
        'admin_only' => false
    ],
    [
        'title' => 'Aniversários',
        'url' => 'aniversarios.php',
        'icon' => 'cake',
        'category' => 'comunicacao',
        'admin_only' => false
    ],
    [
        'title' => 'Gestão Escalas',
        'url' => 'escalas_gestao.php',
        'icon' => 'sliders',
        'category' => 'admin',
        'admin_only' => true
    ],
    [
        'title' => 'Relatórios',
        'url' => 'relatorios_gerais.php',
        'icon' => 'trending-up',
        'category' => 'admin',
        'admin_only' => true
    ],
    [
        'title' => 'Manutenção',
        'url' => 'manutencao.php',
        'icon' => 'database',
        'category' => 'admin',
        'admin_only' => true
    ]
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-8">
    
    <!-- HEADER HERO PREMIUM DE BOAS-VINDAS (Bento Card Grande - Bloco Sólido Minimalista) -->
    <div class="reveal-item relative bg-surface-container-low border border-surface-container-highest rounded-2xl p-4 sm:p-5 overflow-hidden transition-all duration-300 group shadow-sm">
        <!-- Decorativo sutil de fundo -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Bloco Info Usuário (Esquerda) -->
            <div class="flex items-center gap-4">
                <div class="relative flex-shrink-0">
                    <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-full overflow-hidden border-2 border-surface-container-highest shadow-sm">
                        <a href="perfil.php" class="block w-full h-full">
                            <img src="<?= $userPhoto ?>" alt="Avatar" class="w-full h-full object-cover">
                        </a>
                    </div>
                </div>
                <div class="space-y-0.5">
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded-full text-[9px] font-bold tracking-wider uppercase border border-primary/15">
                        <i data-lucide="sparkles" class="w-3 h-3"></i> PIB Oliveira Louvor
                    </div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight font-outfit text-on-surface"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>! 👋</h2>
                    <p class="text-xs text-on-surface-variant font-medium">Pronto para servir e adorar hoje?</p>
                </div>
            </div>

            <!-- Bloco Próxima Escala (Direita) -->
            <div class="w-full md:w-auto md:min-w-[260px]">
                <?php if (!empty($nextSchedule)): ?>
                    <div class="bg-surface-container-lowest border border-surface-container-highest rounded-xl p-3 shadow-sm hover:bg-surface-container transition-all duration-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-on-surface-variant flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5 text-primary"></i> Próxima Escala</span>
                            <?php 
                            $statusClass = 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border border-yellow-500/20';
                            $statusText = 'Pendente';
                            if (($nextSchedule['my_status'] ?? '') === 'confirmed') {
                                $statusClass = 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20';
                                $statusText = 'Confirmada';
                            } elseif (($nextSchedule['my_status'] ?? '') === 'declined') {
                                $statusClass = 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
                                $statusText = 'Recusada';
                            }
                            ?>
                            <span class="text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl font-extrabold font-outfit tracking-tight text-on-surface"><?= date('d/m', strtotime($nextSchedule['event_date'])) ?></span>
                            <div class="flex flex-col overflow-hidden">
                                <span class="text-xs font-bold text-on-surface truncate leading-tight"><?= htmlspecialchars($nextSchedule['event_type']) ?></span>
                                <span class="text-[10px] text-on-surface-variant font-medium mt-0.5 truncate"><?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-surface-container-lowest border border-surface-container-highest rounded-xl p-3 flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-surface-container text-on-surface-variant flex items-center justify-center flex-shrink-0">
                            <i data-lucide="calendar-heart" class="w-4 h-4 text-primary"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-on-surface leading-tight">Nenhuma escala próxima</span>
                            <span class="text-[9px] text-on-surface-variant font-semibold mt-0.5 leading-tight">Acompanhe os cultos no menu Escalas.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BENTO STITCH: AVISOS + ANIVERSARIANTES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Card Avisos -->
        <div class="reveal-item reveal-stagger-1 lg:col-span-2 bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 sm:p-4.5 shadow-sm flex flex-col">
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
                            <?php if (!empty($av['message'])): ?>
                                <p class="font-body-md text-on-surface-variant mt-0.5 text-xs line-clamp-2 leading-relaxed"><?= htmlspecialchars($av['message']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center py-6 text-on-surface-variant">
                        <i data-lucide="inbox" class="w-7 h-7 mb-1.5 opacity-60"></i>
                        <span class="font-body-md text-xs">Nenhum aviso no momento.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Aniversariantes -->
        <div class="reveal-item reveal-stagger-2 bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 sm:p-4.5 shadow-sm flex flex-col">
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
                <div class="flex flex-col items-center justify-center text-center py-6 text-on-surface-variant flex-grow">
                    <i data-lucide="party-popper" class="w-7 h-7 mb-1.5 opacity-60"></i>
                    <span class="font-body-md text-xs">Nenhum aniversário este mês.</span>
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
            <h3 class="text-sm font-bold text-surface-on-surface font-outfit tracking-tight">Acesso Rápido</h3>
        </div>
        
        <div class="reveal-item reveal-stagger-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2.5">
            <?php foreach ($shortcuts as $sc): ?>
                <?php if ($sc['admin_only'] && $userRole !== 'admin') continue; ?>
                
                <?php
                // Mapeamento de Cores para Tailwind
                $catColors = [
                    'gestao' => [
                        'icon' => 'bg-blue-500/10 text-blue-600 border border-blue-500/10 dark:text-blue-400',
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
                
                <a href="<?= $sc['url'] ?>" class="interactive-scale bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-2.5 flex items-center gap-2.5 transition-all duration-200 hover:-translate-y-0.5 hover:bg-surface-container-low group select-none shadow-sm">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform <?= $colors['icon'] ?>">
                        <i data-lucide="<?= $sc['icon'] ?>" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs font-bold text-surface-on-surface font-outfit leading-tight truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($sc['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>
