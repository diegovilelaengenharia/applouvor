<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;

// --- DADOS REAIS ---
// 1. Avisos (Apenas alertas não lidos/recentes)
$avisos = [];
try {
    // Busca apenas urgentes ou importantes recentes
    $stmt = $pdo->query("
        SELECT count(*) as total, 
        (SELECT title FROM avisos WHERE archived_at IS NULL ORDER BY is_pinned DESC, created_at DESC LIMIT 1) as last_title
        FROM avisos WHERE archived_at IS NULL
    ");
    $avisosData = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAvisos = $avisosData['total'] ?? 0;
    $ultimoAviso = $avisosData['last_title'] ?? 'Nenhum aviso novo';
} catch (Exception $e) {
    $totalAvisos = 0;
    $ultimoAviso = '';
}

// 2. Minha Próxima Escala
$nextSchedule = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// 3. Aniversariantes (Quantidade no mês)
$niverCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())");
    $niverCount = $stmt->fetchColumn();
} catch (Exception $e) {
}

// Saudação baseada no horário
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

renderAppHeader('Início');
?>

<!-- Estilos Específicos para a Nova Home (Moderate) -->
<style>
    /* Card Interativo (Padrão Clean) */
    .interact-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        /* Mais espaçamento */
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .interact-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    /* Icon Box (Quadrado arredondado suave) */
    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        font-size: 24px;
        transition: transform 0.3s ease;
    }

    .interact-card:hover .icon-box {
        transform: scale(1.1);
    }

    /* Typography */
    .card-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-value {
        font-size: 1.5rem;
        /* 24px */
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.2;
    }
</style>

<div style="max-width: 900px; margin: 0 auto;">

    <!-- 1. HERO SECTION: Saudação -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            <?= $saudacao ?>, <span style="color: var(--primary);"><?= $nomeUser ?></span>!
        </h1>
        <p style="color: var(--text-muted); font-size: 1rem;">
            Confira o que temos para hoje.
        </p>

        <?php if ($nextSchedule):
            $date = new DateTime($nextSchedule['event_date']);
            $isToday = $date->format('Y-m-d') === date('Y-m-d');
        ?>
            <!-- CARD HERO (Moderate Emerald) -->
            <a href="escalas.php?mine=1" class="interact-card" style="
                background: var(--primary); 
                border: none; 
                margin-top: 24px;
                color: white;
                min-height: 180px;
                display: block;
            ">
                <!-- Background Pattern sutil -->
                <div style="position: absolute; right: -20px; top: -20px; opacity: 0.1;">
                    <svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M9 18V5l12-2v13"></path>
                        <circle cx="6" cy="18" r="3"></circle>
                        <circle cx="18" cy="16" r="3"></circle>
                    </svg>
                </div>

                <div style="position: relative; z-index: 2;">
                    <!-- Badge -->
                    <div style="
                        display: inline-flex; align-items: center; 
                        padding: 6px 12px; border-radius: 20px; 
                        background: rgba(255,255,255,0.2); 
                        backdrop-filter: blur(4px);
                        font-size: 0.8rem; font-weight: 700; 
                        margin-bottom: 16px; color: white;
                    ">
                        <?php if ($isToday): ?>
                            <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 8px #4ade80;"></span>
                            É HOJE
                        <?php else: ?>
                            PRÓXIMA ESCALA
                        <?php endif; ?>
                    </div>

                    <h2 style="font-size: 1.75rem; font-weight: 700; margin: 0 0 8px 0; color: white; letter-spacing: -0.5px;">
                        <?= htmlspecialchars($nextSchedule['event_type']) ?>
                    </h2>

                    <div style="display: flex; gap: 20px; font-size: 1.05rem; opacity: 0.9;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="calendar" style="width: 20px;"></i>
                            <?= $date->format('d/m') ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="clock" style="width: 20px;"></i>
                            19:00
                        </div>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div style="
                background: var(--bg-surface); 
                border: 1px dashed var(--border-color); 
                border-radius: var(--radius-lg); 
                padding: 32px; 
                text-align: center; 
                margin-top: 24px;
                color: var(--text-muted);
            ">
                <i data-lucide="coffee" style="width: 40px; height: 40px; margin-bottom: 12px; color: var(--text-muted); opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 1rem;">Tudo tranquilo por aqui. Nenhuma escala próxima.</p>
            </div>
        <?php endif; ?>
    </div>


    <!-- 2. GRID INFO (Clean White Cards) -->
    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 16px;">Visão Geral</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 40px;">

        <!-- Minha Agenda -->
        <a href="escalas.php?mine=1" class="interact-card">
            <div class="icon-box" style="background: #eff6ff; color: #2563eb;"> <!-- Blue -->
                <i data-lucide="calendar-check"></i>
            </div>
            <div>
                <div class="card-label">Minha Agenda</div>
                <div class="card-value" style="font-size: 1.25rem;">Ver Tudo</div>
            </div>
        </a>

        <!-- Avisos -->
        <a href="avisos.php" class="interact-card">
            <div class="icon-box" style="background: #fff7ed; color: #ea580c;"> <!-- Orange -->
                <i data-lucide="bell"></i>
            </div>
            <div>
                <div class="card-label">Mural</div>
                <div class="card-value" style="font-size: 1.25rem;">
                    <?= $totalAvisos > 0 ? $totalAvisos . ' novos' : 'Em dia' ?>
                </div>
            </div>
        </a>

        <!-- Aniversários -->
        <a href="aniversarios.php" class="interact-card">
            <div class="icon-box" style="background: #fdf2f8; color: #db2777;"> <!-- Pink -->
                <i data-lucide="cake"></i>
            </div>
            <div>
                <div class="card-label">Aniversários</div>
                <div class="card-value" style="font-size: 1.25rem;">
                    <?= $niverCount > 0 ? $niverCount : 'Nenhum' ?>
                </div>
            </div>
        </a>

    </div>

    <!-- 3. ACTIONS: Customizável -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">Acesso Rápido</h3>
        <button onclick="openSheet('quickAccessSheet')" class="ripple" style="
            background: var(--bg-surface); border: 1px solid var(--border-color);
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; 
            color: var(--text-muted); cursor: pointer;
        ">
            <i data-lucide="settings-2" style="width: 18px;"></i>
        </button>
    </div>

    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <?php
        // Buscar Preferências
        $stmtPrefs = $pdo->prepare("SELECT dashboard_prefs FROM users WHERE id = ?");
        $stmtPrefs->execute([$userId]);
        $userPrefsJson = $stmtPrefs->fetchColumn();
        $userPrefs = json_decode($userPrefsJson ?? '', true);

        // Default Items se não houver prefs
        if (!$userPrefs || !isset($userPrefs['quick_access'])) {
            $quickAccessItems = ['repertorio', 'ausencia'];
        } else {
            $quickAccessItems = $userPrefs['quick_access'];
        }

        // Definição Modificada - Cores mais suaves
        $allItems = [
            'repertorio' => [
                'label' => 'Repertório',
                'icon' => 'music-2',
                'color' => '#047857', // Primary
                'bg' => '#d1fae5',    // Primary Light
                'url' => 'repertorio.php'
            ],
            'ausencia' => [
                'label' => 'Avisar Ausência',
                'icon' => 'calendar-off',
                'color' => '#be123c', // Red 700
                'bg' => '#ffe4e6',    // Red 100
                'url' => 'indisponibilidade.php'
            ],
            'escalas' => [
                'label' => 'Escalas',
                'icon' => 'calendar',
                'color' => '#0369a1', // Sky 700
                'bg' => '#e0f2fe',    // Sky 100
                'url' => 'escalas.php'
            ],
            'membros' => [
                'label' => 'Membros',
                'icon' => 'users',
                'color' => '#4338ca', // Indigo 700
                'bg' => '#e0e7ff',    // Indigo 100
                'url' => 'membros.php'
            ],
            'perfil' => [
                'label' => 'Meu Perfil',
                'icon' => 'user',
                'color' => '#b45309', // Amber 700
                'bg' => '#fef3c7',    // Amber 100
                'url' => 'perfil.php'
            ]
        ];
        ?>

        <?php foreach ($quickAccessItems as $key):
            if (isset($allItems[$key])):
                $item = $allItems[$key];
        ?>
                <a href="<?= $item['url'] ?>" class="ripple" style="
                    flex: 1; min-width: 140px;
                    background: var(--bg-surface); 
                    border: 1px solid var(--border-color);
                    padding: 20px; 
                    border-radius: var(--radius-lg);
                    text-decoration: none; 
                    color: var(--text-main);
                    display: flex; align-items: center; gap: 16px;
                    font-weight: 600; font-size: 0.95rem;
                    transition: all 0.2s;
                    box-shadow: var(--shadow-sm);
                ">
                    <div style="background: <?= $item['bg'] ?>; padding: 10px; border-radius: 10px;">
                        <i data-lucide="<?= $item['icon'] ?>" style="width: 20px; color: <?= $item['color'] ?>;"></i>
                    </div>
                    <?= $item['label'] ?>
                </a>
        <?php endif;
        endforeach; ?>

        <!-- Botão Adicionar se estiver vazio -->
        <?php if (empty($quickAccessItems)): ?>
            <button onclick="openSheet('quickAccessSheet')" style="
                flex: 1; min-width: 140px; padding: 20px; 
                border: 2px dashed var(--border-color); 
                background: transparent; color: var(--text-muted); 
                border-radius: var(--radius-lg); font-weight: 600; cursor: pointer;
            ">
                + Personalizar
            </button>
        <?php endif; ?>

    </div>

</div>

<div style="height: 60px;"></div>

<!-- SHEET CONFIGURAÇÃO -->
<div id="quickAccessSheet" style="
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000;
">
    <div onclick="closeSheet('quickAccessSheet')" style="
        position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
    "></div>

    <div style="
        position: absolute; bottom: 0; left: 0; width: 100%;
        background: var(--bg-surface); border-radius: 20px 20px 0 0;
        padding: 24px; padding-bottom: 40px;
        box-shadow: var(--shadow-xl);
        animation: slideUp 0.3s ease-out;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Personalizar Atalhos</h3>
            <button onclick="closeSheet('quickAccessSheet')" style="background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-muted);">
                <i data-lucide="x" style="width: 24px;"></i>
            </button>
        </div>

        <form id="formQuickAccess">
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                <?php foreach ($allItems as $key => $item):
                    $isChecked = in_array($key, $quickAccessItems);
                ?>
                    <label class="ripple" style="
                        display: flex; align-items: center; justify-content: space-between;
                        padding: 12px 16px; background: var(--bg-body); border-radius: 12px;
                        border: 1px solid var(--border-color); cursor: pointer;
                        transition: all 0.2s;
                    ">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: <?= $item['bg'] ?>; padding: 6px; border-radius: 8px;">
                                <i data-lucide="<?= $item['icon'] ?>" style="width: 18px; color: <?= $item['color'] ?>;"></i>
                            </div>
                            <span style="font-weight: 600; color: var(--text-main);"><?= $item['label'] ?></span>
                        </div>
                        <input type="checkbox" name="quick_access[]" value="<?= $key ?>" <?= $isChecked ? 'checked' : '' ?>
                            style="width: 18px; height: 18px; accent-color: var(--primary);">
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="button" onclick="saveQuickAccess()" style="
                width: 100%; padding: 14px; border: none; background: var(--primary); 
                color: white; font-weight: 700; border-radius: 12px; font-size: 1rem;
                cursor: pointer; box-shadow: var(--shadow-md);
            ">
                Salvar Alterações
            </button>
        </form>
    </div>
</div>

<script>
    function openSheet(id) {
        document.getElementById(id).style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeSheet(id) {
        document.getElementById(id).style.display = 'none';
        document.body.style.overflow = '';
    }

    function saveQuickAccess() {
        const form = document.getElementById('formQuickAccess');
        const formData = new FormData(form);
        const selected = [];

        for (const entry of formData.entries()) {
            selected.push(entry[1]);
        }

        // AJAX Save
        fetch('ajax_save_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    quick_access: selected
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Erro ao salvar: ' + (data.error || 'Desconhecido'));
                }
            })
            .catch(err => alert('Erro de conexão'));
    }

    // Animation
    const styleSheet = document.createElement("style");
    styleSheet.innerText = `
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
`;
    document.head.appendChild(styleSheet);
</script>

<?php renderAppFooter(); ?>