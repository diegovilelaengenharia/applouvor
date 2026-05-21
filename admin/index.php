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
    
    <!-- HEADER HERO PREMIUM DE BOAS-VINDAS (Bento Card Grande) -->
    <div class="relative bg-gradient-to-br from-primary to-blue-800 text-white rounded-3xl p-6 sm:p-8 shadow-lg overflow-hidden border border-white/10 transition-all duration-300 hover:shadow-xl group">
        <!-- Decorativos sutis de fundo -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-blue-600/20 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <!-- Bloco Info Usuário (Esquerda) -->
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="relative flex-shrink-0">
                    <div class="absolute inset-0 bg-white/20 rounded-full animate-ping opacity-75 pointer-events-none scale-105"></div>
                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden border-2 border-white shadow-md">
                        <a href="perfil.php" class="block w-full h-full">
                            <img src="<?= $userPhoto ?>" alt="Avatar" class="w-full h-full object-cover">
                        </a>
                    </div>
                </div>
                <div class="space-y-1 sm:space-y-1.5">
                    <div class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-white/15 rounded-full text-[10px] font-bold tracking-wider uppercase border border-white/10">
                        <i data-lucide="sparkles" class="w-3 h-3"></i> PIB Oliveira Louvor
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight font-outfit"><?= $salutation ?>, <?= explode(' ', $userName)[0] ?>! 👋</h2>
                    <p class="text-sm text-white/80 font-medium">Pronto para servir e adorar hoje?</p>
                </div>
            </div>

            <!-- Bloco Próxima Escala (Direita) -->
            <div class="w-full md:w-auto md:min-w-[280px]">
                <?php if (!empty($nextSchedule)): ?>
                    <div class="bg-white/10 border border-white/20 rounded-2xl p-4.5 shadow-sm backdrop-blur-sm transition-all duration-300 hover:bg-white/15">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-white/90 flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> Próxima Escala</span>
                            <?php 
                            $statusClass = 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30';
                            $statusText = 'Pendente';
                            if (($nextSchedule['my_status'] ?? '') === 'confirmed') {
                                $statusClass = 'bg-green-500/20 text-green-300 border border-green-500/30';
                                $statusText = 'Confirmada';
                            } elseif (($nextSchedule['my_status'] ?? '') === 'declined') {
                                $statusClass = 'bg-red-500/20 text-red-300 border border-red-500/30';
                                $statusText = 'Recusada';
                            }
                            ?>
                            <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-md <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl font-extrabold font-outfit tracking-tight"><?= date('d/m', strtotime($nextSchedule['event_date'])) ?></span>
                            <div class="flex flex-col overflow-hidden">
                                <span class="text-sm font-bold truncate leading-tight"><?= htmlspecialchars($nextSchedule['event_type']) ?></span>
                                <span class="text-xs text-white/80 font-medium mt-0.5 truncate"><?= htmlspecialchars($nextSchedule['my_role'] ?? 'Músico') ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 flex items-center gap-3.5 backdrop-blur-sm">
                        <div class="p-2.5 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center flex-shrink-0 text-white/80">
                            <i data-lucide="calendar-heart" class="w-5 h-5"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-bold leading-tight">Nenhuma escala próxima</span>
                            <span class="text-[10px] text-white/70 font-semibold mt-0.5 leading-tight">Acompanhe os cultos no menu Escalas.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SEÇÃO ATALHOS RÁPIDOS TÁTEIS -->
    <div class="space-y-4">
        <div class="flex items-center gap-2 px-1">
            <div class="bg-primary/10 p-1.5 rounded-lg text-primary flex items-center justify-center">
                <i data-lucide="layout-grid" class="w-4 h-4"></i>
            </div>
            <h3 class="text-base font-bold text-surface-on-surface font-outfit tracking-tight">Acesso Rápido</h3>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <?php foreach ($shortcuts as $sc): ?>
                <?php if ($sc['admin_only'] && $userRole !== 'admin') continue; ?>
                
                <?php
                // Mapeamento de Cores para Tailwind
                $catColors = [
                    'gestao' => [
                        'icon' => 'bg-blue-500/10 text-blue-500 border border-blue-500/10 dark:text-blue-400',
                        'hover' => 'hover:border-blue-500/30'
                    ],
                    'espiritualidade' => [
                        'icon' => 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/10 dark:text-emerald-400',
                        'hover' => 'hover:border-emerald-500/30'
                    ],
                    'comunicacao' => [
                        'icon' => 'bg-amber-500/10 text-amber-500 border border-amber-500/10 dark:text-amber-400',
                        'hover' => 'hover:border-amber-500/30'
                    ],
                    'admin' => [
                        'icon' => 'bg-red-500/10 text-red-500 border border-red-500/10 dark:text-red-400',
                        'hover' => 'hover:border-red-500/30'
                    ]
                ];
                $colors = $catColors[$sc['category']] ?? $catColors['gestao'];
                ?>
                
                <a href="<?= $sc['url'] ?>" class="bg-surface border border-surface-container-highest rounded-2xl p-3.5 flex items-center gap-3 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md <?= $colors['hover'] ?> group select-none">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform <?= $colors['icon'] ?>">
                        <i data-lucide="<?= $sc['icon'] ?>" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-surface-on-surface font-outfit leading-tight leading-3 truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($sc['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>
