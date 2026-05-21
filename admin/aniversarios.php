<?php
// admin/aniversarios.php - Redesign Premium Sacred Minimalist
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkLogin();

// Mês atual e próximo
$mesAtual = (int)date('n');
$mesNomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

// Busca todos aniversariantes
try {
    $stmt = $pdo->query("SELECT *, MONTH(birth_date) as mes, DAY(birth_date) as dia, avatar FROM users WHERE birth_date IS NOT NULL ORDER BY MONTH(birth_date) ASC, DAY(birth_date) ASC");
    $todosAniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $todosAniversariantes = [];
}

// Separar por mês atual e outros
$aniversariantesMesAtual = [];
$aniversariantesOutros = [];
$hoje = (int)date('j');

foreach ($todosAniversariantes as $niver) {
    if ((int)$niver['mes'] === $mesAtual) {
        // Verificar se é hoje
        $niver['isToday'] = ((int)$niver['dia'] === $hoje);
        $aniversariantesMesAtual[] = $niver;
    } else {
        $aniversariantesOutros[] = $niver;
    }
}

// Próximos aniversários (próximos 3 meses)
$proximosAniversarios = [];
for ($i = 0; $i < 3; $i++) {
    $mes = (($mesAtual + $i - 1) % 12) + 1;
    foreach ($todosAniversariantes as $niver) {
        if ((int)$niver['mes'] === $mes && $mes !== $mesAtual) {
            $proximosAniversarios[] = $niver;
        }
    }
}

renderAppHeader('Aniversariantes');
?>

<style>
    /* Estilos Customizados para Efeitos Especiais de Aniversários */
    .birthday-card-today {
        position: relative;
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.06) 0%, rgba(255, 193, 7, 0.02) 100%);
        border: 1.5px solid rgba(255, 193, 7, 0.4) !important;
        box-shadow: 0 10px 30px -10px rgba(255, 193, 7, 0.15) !important;
    }
    
    .dark .birthday-card-today {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.09) 0%, rgba(255, 193, 7, 0.03) 100%);
        border: 1.5px solid rgba(255, 193, 7, 0.3) !important;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5) !important;
    }
    
    @keyframes gold-pulse {
        0%, 100% { 
            border-color: rgba(255, 193, 7, 0.4); 
            box-shadow: 0 10px 30px -10px rgba(255, 193, 7, 0.15) !important; 
        }
        50% { 
            border-color: rgba(255, 193, 7, 0.8); 
            box-shadow: 0 10px 30px -5px rgba(255, 193, 7, 0.3) !important; 
        }
    }
    
    @keyframes gold-pulse-dark {
        0%, 100% { 
            border-color: rgba(255, 193, 7, 0.3); 
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5) !important; 
        }
        50% { 
            border-color: rgba(255, 193, 7, 0.7); 
            box-shadow: 0 10px 30px -5px rgba(255, 193, 7, 0.2) !important; 
        }
    }
    
    .birthday-card-today {
        animation: gold-pulse 3s infinite ease-in-out;
    }
    
    .dark .birthday-card-today {
        animation: gold-pulse-dark 3s infinite ease-in-out;
    }
</style>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-8">
    
    <!-- Hero Section (Bento Card Destaque) -->
    <div class="bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-white/5 rounded-3xl p-6 sm:p-8 text-center shadow-sm relative overflow-hidden group reveal-item">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-[#FFC107]/5 dark:bg-[#FFC107]/10 rounded-full blur-xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-[#2E7EED]/5 dark:bg-[#2E7EED]/10 rounded-full blur-xl pointer-events-none"></div>
        
        <div class="bg-gradient-to-br from-[#FFC107] to-[#FFA000] w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg border border-[#FFC107]/20 group-hover:rotate-6 transition-transform duration-300">
            <i data-lucide="cake" class="text-white w-8 h-8"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-gray-800 dark:text-white font-outfit tracking-tight">Celebrações de Vida 🎂</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto mt-2 font-medium">
            Parabenize e celebre com os irmãos do nosso ministério de louvor que completam mais um ano de vida sob a graça do Senhor!
        </p>
    </div>
    
    <?php if (empty($todosAniversariantes)): ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-white/5 rounded-3xl p-12 text-center max-w-md mx-auto reveal-item">
            <div class="bg-amber-500/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-500/20">
                <i data-lucide="calendar-heart" class="text-[#FFC107] w-8 h-8"></i>
            </div>
            <h3 class="text-base font-extrabold text-gray-800 dark:text-white font-outfit">Nenhuma data cadastrada</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 max-w-[260px] mx-auto mt-1.5 mb-6 font-semibold">
                Cadastre a data de nascimento dos membros para visualizar os aniversários.
            </p>
            <a href="membros.php" class="inline-flex items-center gap-2 bg-[#FFC107] hover:bg-[#E5A900] text-gray-900 px-5 py-3 rounded-2xl text-xs font-bold shadow-md transition-all active:scale-[0.98] interactive-scale">
                <i data-lucide="users" class="w-4 h-4"></i>
                Gerenciar Membros
            </a>
        </div>
    <?php else: ?>
        
        <!-- Aniversários do Mês Atual -->
        <?php if (!empty($aniversariantesMesAtual)): ?>
        <div class="space-y-4">
            <div class="flex items-center gap-3 bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-white/5 px-4 py-3 rounded-2xl shadow-sm reveal-item">
                <div class="bg-gradient-to-br from-[#FFC107] to-[#FFA000] p-2.5 rounded-xl flex items-center justify-center shadow-sm">
                    <i data-lucide="sparkles" class="text-white w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-extrabold text-gray-800 dark:text-white font-outfit leading-tight"><?= $mesNomes[$mesAtual] ?></span>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 font-bold tracking-wider leading-none mt-0.5"><?= count($aniversariantesMesAtual) ?> aniversariante(s)</span>
                </div>
                <span class="ml-auto text-xs font-extrabold text-[#2E7EED] bg-[#2E7EED]/10 dark:bg-[#2E7EED]/20 px-2.5 py-1 rounded-full border border-[#2E7EED]/20">Este mês! 🎉</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php 
                $index = 0;
                foreach ($aniversariantesMesAtual as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    $gradients = [
                        'from-[#2E7EED] to-[#1A5BB8]',
                        'from-emerald-400 to-teal-600',
                        'from-[#FFC107] to-[#E5A900]',
                        'from-sky-400 to-[#2E7EED]',
                    ];
                    $gradient = $gradients[$index % count($gradients)];
                    
                    $isToday = $niver['isToday'];
                    $cardClass = $isToday ? 'birthday-card-today' : 'bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-[#2C2C2E]';
                    $index++;
                ?>
                <div class="rounded-2xl p-4 flex items-center gap-4 transition-all duration-300 hover:shadow-lg dark:hover:shadow-black/30 hover:scale-[1.01] <?= $cardClass ?> group reveal-item interactive-scale" style="animation-delay: <?= $index * 0.05 ?>s">
                    <div class="bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-white/5 rounded-xl p-2.5 text-center min-w-[54px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-base font-black text-gray-800 dark:text-white font-outfit leading-none"><?= $niver['dia'] ?></div>
                        <div class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-[#1A1B1F] shadow-sm" alt="">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br <?= $gradient ?> text-white font-black text-sm flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isToday): ?>
                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FFC107] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-[#FFC107]"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-extrabold text-gray-800 dark:text-white font-outfit truncate flex items-center gap-1.5">
                            <?= htmlspecialchars($niver['name']) ?>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold truncate mt-0.5"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <?php if ($isToday): ?>
                        <span class="text-[9px] font-black uppercase tracking-widest bg-[#FFC107] text-gray-900 px-2.5 py-1 rounded-lg shadow-sm animate-bounce flex-shrink-0">
                            HOJE! 🎉
                        </span>
                    <?php else: ?>
                        <div class="bg-amber-50 dark:bg-amber-500/10 p-2 rounded-xl text-[#FFC107] border border-amber-100 dark:border-amber-500/20 group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="party-popper" class="w-4 h-4 flex-shrink-0"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Outros Meses -->
        <?php 
        $porMes = [];
        foreach ($aniversariantesOutros as $niver) {
            $mes = (int)$niver['mes'];
            if (!isset($porMes[$mes])) $porMes[$mes] = [];
            $porMes[$mes][] = $niver;
        }
        
        $mesesOrdenados = [];
        for ($i = 1; $i <= 12; $i++) {
            $mes = (($mesAtual + $i - 1) % 12) + 1;
            if ($mes !== $mesAtual && isset($porMes[$mes])) {
                $mesesOrdenados[$mes] = $porMes[$mes];
            }
        }
        
        $mesIndex = 0;
        foreach ($mesesOrdenados as $mes => $aniversariantes): 
            $mesIndex++;
        ?>
        <div class="space-y-4 reveal-item" style="animation-delay: <?= 0.1 + ($mesIndex * 0.05) ?>s">
            <div class="flex items-center gap-3 bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-white/5 px-4 py-2.5 rounded-2xl shadow-sm mt-6">
                <div class="bg-gray-50 dark:bg-[#121316] p-2 rounded-xl border border-gray-100 dark:border-white/5 flex items-center justify-center shadow-sm text-gray-400 dark:text-gray-500">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-extrabold text-gray-800 dark:text-white font-outfit leading-none"><?= $mesNomes[$mes] ?></span>
                <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold tracking-wider leading-none"><?= count($aniversariantes) ?> pessoa(s)</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php 
                $cardIndex = 0;
                foreach ($aniversariantes as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    $gradients = [
                        'from-[#2E7EED] to-[#1A5BB8]',
                        'from-emerald-400 to-teal-600',
                        'from-[#FFC107] to-[#E5A900]',
                        'from-sky-400 to-[#2E7EED]',
                    ];
                    $gradient = $gradients[$cardIndex % count($gradients)];
                    $cardIndex++;
                ?>
                <div class="bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-[#2C2C2E] rounded-2xl p-4 flex items-center gap-4 transition-all duration-300 hover:shadow-md dark:hover:shadow-black/20 hover:scale-[1.008] group interactive-scale">
                    <div class="bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-white/5 rounded-xl p-2.5 text-center min-w-[50px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-sm font-black text-gray-800 dark:text-white font-outfit leading-none"><?= $niver['dia'] ?></div>
                        <div class="text-[8px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 dark:border-white/5" alt="">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $gradient ?> text-white font-black text-xs flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-extrabold text-gray-800 dark:text-white font-outfit truncate"><?= htmlspecialchars($niver['name']) ?></div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-bold truncate mt-0.5"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <div class="text-gray-300 dark:text-gray-600 group-hover:text-[#FFC107]/80 group-hover:scale-110 transition-all duration-300">
                        <i data-lucide="gift" class="w-4.5 h-4.5 flex-shrink-0"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
</div>

<?php renderAppFooter(); ?>