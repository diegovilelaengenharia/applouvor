<?php
// admin/aniversarios.php - Redesign Premium
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


<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-8">
    
    <!-- Hero Section (Bento Card Destaque) -->
    <div class="bg-surface-container-low border border-surface-container-highest rounded-3xl p-6 sm:p-8 text-center shadow-sm relative overflow-hidden group">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-amber-500/5 rounded-full blur-xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-primary/5 rounded-full blur-xl pointer-events-none"></div>
        
        <div class="bg-amber-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg border border-amber-500/20 group-hover:scale-105 transition-transform duration-300">
            <i data-lucide="cake" class="text-white w-8 h-8"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-surface-on-surface font-outfit tracking-tight">Parabéns para Você! 🎂</h2>
        <p class="text-sm text-muted max-w-sm mx-auto mt-2 font-medium">
            Celebre com os irmãos que completam mais um ano de vida. Família em adoração!
        </p>
    </div>
    
    <?php if (empty($todosAniversariantes)): ?>
        <!-- Empty State -->
        <div class="bg-surface-container-low border border-surface-container-highest rounded-3xl p-12 text-center max-w-md mx-auto">
            <div class="bg-amber-500/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-500/20">
                <i data-lucide="calendar-heart" class="text-amber-500 w-8 h-8"></i>
            </div>
            <h3 class="text-base font-extrabold text-surface-on-surface font-outfit">Nenhuma data cadastrada</h3>
            <p class="text-xs text-muted max-w-[260px] mx-auto mt-1.5 mb-6 font-semibold">
                Cadastre a data de nascimento dos membros para visualizar os aniversários.
            </p>
            <a href="membros.php" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-2xl text-xs font-bold shadow-md transition-all active:scale-[0.98]">
                <i data-lucide="users" class="w-4 h-4"></i>
                Gerenciar Membros
            </a>
        </div>
    <?php else: ?>
        
        <!-- Aniversários do Mês Atual -->
        <?php if (!empty($aniversariantesMesAtual)): ?>
        <div class="space-y-4">
            <div class="flex items-center gap-3 bg-surface-container-low border border-surface-container-highest px-4 py-3 rounded-2xl shadow-sm">
                <div class="bg-amber-500 p-2 rounded-xl flex items-center justify-center shadow-sm">
                    <i data-lucide="sparkles" class="text-white w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-extrabold text-surface-on-surface font-outfit leading-tight"><?= $mesNomes[$mesAtual] ?></span>
                    <span class="text-[10px] text-muted font-bold tracking-wider leading-none mt-0.5"><?= count($aniversariantesMesAtual) ?> aniversariante(s)</span>
                </div>
                <span class="ml-auto text-xs font-extrabold text-primary bg-primary/10 px-2.5 py-1 rounded-full border border-primary/20">Este mês! 🎉</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($aniversariantesMesAtual as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    $gradients = [
                        'from-blue-500 to-indigo-600',
                        'from-teal-400 to-emerald-500',
                        'from-amber-400 to-orange-500',
                        'from-sky-400 to-blue-500',
                    ];
                    $gradient = $gradients[array_rand($gradients)];
                    
                    $cardBorder = $niver['isToday'] ? 'border-amber-400 bg-amber-500/5 ring-1 ring-amber-400' : 'border-surface-container-highest bg-surface-container-lowest';
                ?>
                <div class="border rounded-2xl p-4 flex items-center gap-4 transition-all duration-200 hover:shadow-md <?= $cardBorder ?> group">
                    <div class="bg-surface-container-low border border-surface-container-highest rounded-xl p-2.5 text-center min-w-[50px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-base font-black text-surface-on-surface font-outfit leading-none"><?= $niver['dia'] ?></div>
                        <div class="text-[9px] font-extrabold uppercase tracking-wider text-muted mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-11 h-11 rounded-full object-cover border border-surface-container-highest" alt="">
                        <?php else: ?>
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br <?= $gradient ?> text-white font-black text-sm flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-extrabold text-surface-on-surface font-outfit truncate flex items-center gap-1.5">
                            <?= htmlspecialchars($niver['name']) ?>
                            <?php if ($niver['isToday']): ?>
                                <span>🎂</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-muted font-bold truncate mt-0.5"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <?php if ($niver['isToday']): ?>
                        <span class="text-[9px] font-black uppercase tracking-widest bg-amber-500 text-white px-2.5 py-1 rounded-lg shadow-sm animate-pulse flex-shrink-0">
                            HOJE! 🎉
                        </span>
                    <?php else: ?>
                        <i data-lucide="party-popper" class="text-amber-500 w-5 h-5 flex-shrink-0 group-hover:scale-110 transition-transform duration-300"></i>
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
        
        foreach ($mesesOrdenados as $mes => $aniversariantes): ?>
        <div class="space-y-4">
            <div class="flex items-center gap-3 bg-surface-container-low border border-surface-container-highest px-4 py-2.5 rounded-2xl shadow-sm mt-6">
                <div class="bg-surface p-2 rounded-xl border border-surface-container-highest flex items-center justify-center shadow-sm text-muted">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-extrabold text-surface-on-surface font-outfit leading-none"><?= $mesNomes[$mes] ?></span>
                <span class="text-[9px] text-muted font-bold tracking-wider leading-none"><?= count($aniversariantes) ?> pessoa(s)</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($aniversariantes as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../uploads/' . $avatar;
                    }
                    $gradients = [
                        'from-blue-500 to-indigo-600',
                        'from-teal-400 to-emerald-500',
                        'from-amber-400 to-orange-500',
                        'from-sky-400 to-blue-500',
                    ];
                    $gradient = $gradients[array_rand($gradients)];
                ?>
                <div class="bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-4 flex items-center gap-4 transition-all duration-200 hover:shadow-md group">
                    <div class="bg-surface border border-surface-container-highest rounded-xl p-2.5 text-center min-w-[50px] shadow-sm flex flex-col justify-center flex-shrink-0">
                        <div class="text-sm font-black text-surface-on-surface font-outfit leading-none"><?= $niver['dia'] ?></div>
                        <div class="text-[8px] font-extrabold uppercase tracking-wider text-muted mt-1 leading-none"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <div class="relative flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" class="w-10 h-10 rounded-full object-cover border border-surface-container-highest" alt="">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $gradient ?> text-white font-black text-xs flex items-center justify-center shadow-sm">
                                <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-extrabold text-surface-on-surface font-outfit truncate"><?= htmlspecialchars($niver['name']) ?></div>
                        <div class="text-xs text-muted font-bold truncate mt-0.5"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <i data-lucide="gift" class="text-muted/60 w-4.5 h-4.5 flex-shrink-0 group-hover:scale-110 transition-transform duration-300"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
</div>

<?php renderAppFooter(); ?>