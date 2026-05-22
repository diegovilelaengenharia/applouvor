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
    /* Estilos Customizados para Efeitos Especiais de Aniversários - Sacred Minimalist */
    .birthday-card-today {
        position: relative;
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.08) 0%, rgba(255, 193, 7, 0.02) 100%) !important;
        border: 1.5px solid rgba(255, 193, 7, 0.4) !important;
        border-radius: 2px !important;
        animation: gold-pulse 3.5s infinite cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @keyframes gold-pulse {
        0%, 100% { 
            border-color: rgba(255, 193, 7, 0.3); 
            box-shadow: 0 4px 20px -10px rgba(255, 193, 7, 0.08) !important; 
        }
        50% { 
            border-color: rgba(255, 193, 7, 0.75); 
            box-shadow: 0 4px 30px -5px rgba(255, 193, 7, 0.2) !important; 
        }
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(24, 25, 29, 0.5);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #26272B;
        border-radius: 1px;
    }
</style>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 pb-28 space-y-8">
    
    <!-- Hero Section (Bento Card Destaque) -->
    <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-6 sm:p-8 text-center shadow-xl relative overflow-hidden group reveal-item">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-[#FFC107]/5 rounded-full blur-xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-[#2E7EED]/5 rounded-full blur-xl pointer-events-none"></div>
        
        <div class="w-16 h-16 bg-[#FFC107]/10 border border-[#FFC107]/25 text-[#FFC107] rounded-[2px] flex items-center justify-center mx-auto mb-5 shadow-lg shadow-[#FFC107]/5 group-hover:rotate-3 transition-transform duration-300">
            <i data-lucide="cake" class="w-8 h-8"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white uppercase tracking-wider">Celebrações de Vida 🎂</h2>
        <p class="text-xs text-gray-400 max-w-sm mx-auto mt-2 font-medium leading-relaxed">
            Parabenize e celebre com os irmãos do nosso ministério de louvor que completam mais um ano de vida sob a graça do Senhor!
        </p>
    </div>
    
    <?php if (empty($todosAniversariantes)): ?>
        <!-- Empty State -->
        <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-12 text-center max-w-md mx-auto reveal-item">
            <div class="bg-amber-500/10 w-14 h-14 rounded-[2px] border border-amber-500/20 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="calendar-heart" class="text-[#FFC107] w-7 h-7"></i>
            </div>
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Nenhuma data cadastrada</h3>
            <p class="text-xs text-gray-500 max-w-[260px] mx-auto mt-2 mb-6 font-medium leading-relaxed">
                Cadastre a data de nascimento dos membros para visualizar os aniversários.
            </p>
            <a href="membros.php" class="inline-flex items-center gap-2 bg-[#FFC107] hover:bg-[#E5A900] text-gray-900 px-5 py-3 rounded-[2px] text-xs font-bold shadow-md transition-all active:scale-[0.97] will-change-transform">
                <i data-lucide="users" class="w-4 h-4"></i>
                Gerenciar Membros
            </a>
        </div>
    <?php else: ?>
        
        <!-- Aniversários do Mês Atual -->
        <?php if (!empty($aniversariantesMesAtual)): ?>
        <div class="space-y-4">
            <div class="flex items-center gap-3 bg-[#18191D] border border-[#26272B] px-4 py-3 rounded-[2px] shadow-sm reveal-item">
                <div class="bg-[#FFC107]/10 border border-[#FFC107]/20 p-2 rounded-[2px] flex items-center justify-center shadow-sm">
                    <i data-lucide="sparkles" class="text-[#FFC107] w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-white uppercase tracking-wider leading-tight"><?= $mesNomes[$mesAtual] ?></span>
                    <span class="text-[10px] text-gray-500 font-semibold tracking-wider leading-none mt-1"><?= count($aniversariantesMesAtual) ?> aniversariante(s)</span>
                </div>
                <span class="ml-auto text-[9px] font-bold uppercase tracking-widest text-[#2E7EED] bg-[#2E7EED]/10 border border-[#2E7EED]/20 px-2.5 py-1.5 rounded-[2px]">Este mês! 🎉</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php 
                $index = 0;
                foreach ($aniversariantesMesAtual as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    
                    $isToday = $niver['isToday'];
                    $cardClass = $isToday ? 'birthday-card-today' : 'bg-[#18191D] border border-[#26272B]';
                    $index++;
                ?>
                <div class="p-4 flex items-center gap-4 border transition-all duration-200 hover:border-gray-500 rounded-[2px] active:scale-[0.98] will-change-transform <?= $cardClass ?> group reveal-item" style="animation-delay: <?= $index * 0.05 ?>s">
                    <div class="bg-[#121316] border border-[#26272B] rounded-[2px] p-2.5 text-center min-w-[54px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-base font-bold text-white leading-none font-mono"><?= str_pad($niver['dia'], 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-12 h-12 rounded-[2px] object-cover border border-[#26272B] shadow-sm" alt="">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-[2px] bg-[#121316] border border-[#26272B] text-gray-300 font-bold text-sm flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isToday): ?>
                            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FFC107] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#FFC107]"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate flex items-center gap-1.5">
                            <?= htmlspecialchars($niver['name']) ?>
                        </div>
                        <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider truncate mt-1"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <?php if ($isToday): ?>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest bg-[#FFC107] text-gray-900 px-2.5 py-1.5 rounded-[2px] shadow-lg shadow-[#FFC107]/10 animate-bounce flex-shrink-0">
                            HOJE! 🎉
                        </span>
                    <?php else: ?>
                        <div class="bg-[#121316] border border-[#26272B] p-2 rounded-[2px] text-[#FFC107]/80 group-hover:scale-105 transition-transform duration-200">
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
            <div class="flex items-center gap-3 bg-[#18191D] border border-[#26272B] px-4 py-2.5 rounded-[2px] shadow-sm mt-6">
                <div class="bg-[#121316] p-2 rounded-[2px] border border-[#26272B] flex items-center justify-center shadow-sm text-gray-500">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-bold text-white uppercase tracking-wider leading-none"><?= $mesNomes[$mes] ?></span>
                <span class="text-[9px] text-gray-500 font-semibold tracking-wider leading-none"><?= count($aniversariantes) ?> pessoa(s)</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php 
                $cardIndex = 0;
                foreach ($aniversariantes as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    $cardIndex++;
                ?>
                <div class="bg-[#18191D] border border-[#26272B] rounded-[2px] p-4 flex items-center gap-4 transition-all duration-200 hover:border-gray-500 active:scale-[0.98] will-change-transform group">
                    <div class="bg-[#121316] border border-[#26272B] rounded-[2px] p-2.5 text-center min-w-[50px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-sm font-bold text-white leading-none font-mono"><?= str_pad($niver['dia'], 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="text-[8px] font-bold uppercase tracking-wider text-gray-500 mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-10 h-10 rounded-[2px] object-cover border border-[#26272B]" alt="">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-[2px] bg-[#121316] border border-[#26272B] text-gray-400 font-bold text-xs flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($niver['name']) ?></div>
                        <div class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider truncate mt-1"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <div class="text-gray-600 group-hover:text-[#FFC107]/80 group-hover:scale-105 transition-all duration-200">
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