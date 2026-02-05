<?php
// admin/aniversarios.php - Redesign Premium
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

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
    /* Birthday Cards - Premium Design */
    .birthday-card {
        background: var(--bg-surface);
        border-radius: 16px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
    }
    .birthday-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .birthday-card.today {
        background: var(--yellow-100);
        border-color: #fbbf24;
    }
    
    /* Avatar */
    .birthday-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .birthday-avatar-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: var(--font-h2);
        flex-shrink: 0;
    }
    
    /* Date Badge */
    .birthday-date {
        background: #fbbf24;
        color: white;
        padding: 8px 12px;
        border-radius: 12px;
        text-align: center;
        min-width: 50px;
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
    }
    .birthday-date .day {
        font-size: var(--font-h1);
        font-weight: 800;
        line-height: 1;
    }
    .birthday-date .month {
        font-size: var(--font-caption);
        text-transform: uppercase;
        font-weight: 600;
        opacity: 0.9;
    }
    
    /* Info */
    .birthday-info {
        flex: 1;
    }
    .birthday-name {
        font-weight: 700;
        color: var(--text-main);
        font-size: var(--font-body);
        margin-bottom: 2px;
    }
    .birthday-role {
        font-size: var(--font-body-sm);
        color: var(--text-muted);
    }
    
    /* Month Section */
    .month-section {
        margin-bottom: 24px;
    }
    .month-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--border-color);
    }
    .month-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .month-title {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--text-main);
    }
    .month-count {
        background: var(--bg-body);
        color: var(--text-muted);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: var(--font-caption);
        font-weight: 600;
    }
    
    /* Filter Tabs */
    .filter-tabs {
        display: flex;
        gap: 4px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 20px;
        scrollbar-width: none;
    }
    .filter-tab {
        padding: 10px 18px;
        border-radius: 20px;
        font-size: var(--font-body-sm);
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        cursor: pointer;
    }
    .filter-tab:hover {
        background: var(--border-color);
    }
    .filter-tab.active {
        background: #fbbf24;
        color: white;
        border-color: transparent;
    }
</style>

<?php renderPageHeader('Aniversariantes', 'Celebrando a vida da nossa equipe'); ?>

<div class="container" style="padding-top: 16px; max-width: 700px; margin: 0 auto;">
    
    <!-- Hero Section -->
    <div style="text-align: center; padding: 20px 0 30px;">
        <div style="background: #fbbf24; width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 8px 25px rgba(251, 191, 36, 0.3);">
            <i data-lucide="cake" style="color: white; width: 36px; height: 36px;"></i>
        </div>
        <h2 style="font-size: var(--font-h1); font-weight: 800; color: var(--text-main); margin: 0 0 6px;">Parabéns para Você! 🎂</h2>
        <p style="color: var(--text-muted); font-size: var(--font-body); max-width: 400px; margin: 0 auto;">
            Celebre com os irmãos que fazem aniversário. Uma família abençoada!
        </p>
    </div>
    
    <?php if (empty($todosAniversariantes)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: var(--yellow-100); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i data-lucide="calendar-heart" style="color: var(--yellow-500); width: 40px; height: 40px;"></i>
            </div>
            <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhuma data cadastrada</h3>
            <p style="color: var(--text-muted); font-size: var(--font-body); max-width: 300px; margin: 0 auto 20px;">
                Cadastre a data de nascimento dos membros para ver os aniversários.
            </p>
            <a href="membros.php" style="display: inline-flex; align-items: center; gap: 8px; background: #fbbf24; color: white; padding: 12px 24px; border-radius: 24px; font-weight: 600; text-decoration: none;">
                <i data-lucide="users" style="width: 18px;"></i>
                Gerenciar Membros
            </a>
        </div>
    <?php else: ?>
        
        <!-- Aniversários do Mês Atual -->
        <?php if (!empty($aniversariantesMesAtual)): ?>
        <div class="month-section">
            <div class="month-header">
                <div class="month-icon" style="background: #fbbf24;">
                    <i data-lucide="sparkles" style="color: white; width: 18px;"></i>
                </div>
                <span class="month-title"><?= $mesNomes[$mesAtual] ?></span>
                <span class="month-count"><?= count($aniversariantesMesAtual) ?> pessoa(s)</span>
                <span style="margin-left: auto; font-size: var(--font-body-sm); color: var(--primary); font-weight: 600;">Este mês! 🎉</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($aniversariantesMesAtual as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../assets/uploads/' . $avatar;
                    }
                    $gradients = [
                        '#667eea',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b',
                        '#fa709a',
                    ];
                    $gradient = $gradients[array_rand($gradients)];
                ?>
                <div class="birthday-card <?= $niver['isToday'] ? 'today' : '' ?>">
                    <div class="birthday-date">
                        <div class="day"><?= $niver['dia'] ?></div>
                        <div class="month"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" class="birthday-avatar" alt="">
                    <?php else: ?>
                        <div class="birthday-avatar-placeholder" style="background: <?= $gradient ?>;">
                            <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="birthday-info">
                        <div class="birthday-name">
                            <?= htmlspecialchars($niver['name']) ?>
                            <?php if ($niver['isToday']): ?>
                                <span style="font-size: var(--font-h3);">🎂</span>
                            <?php endif; ?>
                        </div>
                        <div class="birthday-role"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <?php if ($niver['isToday']): ?>
                        <div style="background: white; padding: 6px 12px; border-radius: 20px; font-size: var(--font-caption); font-weight: 700; color: var(--yellow-500); box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            HOJE! 🎉
                        </div>
                    <?php else: ?>
                        <i data-lucide="party-popper" style="color: #fbbf24; width: 20px;"></i>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Próximos Meses -->
        <?php 
        // Agrupar por mês
        $porMes = [];
        foreach ($aniversariantesOutros as $niver) {
            $mes = (int)$niver['mes'];
            if (!isset($porMes[$mes])) $porMes[$mes] = [];
            $porMes[$mes][] = $niver;
        }
        
        // Ordenar começando pelo próximo mês
        $mesesOrdenados = [];
        for ($i = 1; $i <= 12; $i++) {
            $mes = (($mesAtual + $i - 1) % 12) + 1;
            if ($mes !== $mesAtual && isset($porMes[$mes])) {
                $mesesOrdenados[$mes] = $porMes[$mes];
            }
        }
        
        foreach ($mesesOrdenados as $mes => $aniversariantes): ?>
        <div class="month-section">
            <div class="month-header">
                <div class="month-icon" style="background: var(--bg-body);">
                    <i data-lucide="calendar" style="color: var(--text-muted); width: 18px;"></i>
                </div>
                <span class="month-title"><?= $mesNomes[$mes] ?></span>
                <span class="month-count"><?= count($aniversariantes) ?> pessoa(s)</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($aniversariantes as $niver): 
                    $avatar = !empty($niver['avatar']) ? $niver['avatar'] : null;
                    if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false) {
                        $avatar = '../assets/uploads/' . $avatar;
                    }
                    $gradients = [
                        '#667eea',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b',
                    ];
                    $gradient = $gradients[array_rand($gradients)];
                ?>
                <div class="birthday-card">
                    <div class="birthday-date" style="background: var(--slate-400);">
                        <div class="day"><?= $niver['dia'] ?></div>
                        <div class="month"><?= substr($mesNomes[$niver['mes']], 0, 3) ?></div>
                    </div>
                    
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" class="birthday-avatar" alt="">
                    <?php else: ?>
                        <div class="birthday-avatar-placeholder" style="background: <?= $gradient ?>;">
                            <?= strtoupper(substr($niver['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="birthday-info">
                        <div class="birthday-name"><?= htmlspecialchars($niver['name']) ?></div>
                        <div class="birthday-role"><?= htmlspecialchars($niver['instrument'] ?? 'Membro') ?></div>
                    </div>
                    
                    <i data-lucide="gift" style="color: var(--text-muted); width: 18px;"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
    
    <div style="height: 80px;"></div>
</div>

<?php renderAppFooter(); ?>