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

<!-- Estilos Específicos para a Nova Home -->
<style>
    /* Card Hover Effect */
    .interact-card {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        transform: translateY(0);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid transparent;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .interact-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 10;
    }

    /* Gradient Backgrounds & Text */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .bg-gradient-danger {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    /* Glass Effect Element */
    .glass-pill {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Icon Circle */
    .icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 12px;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .interact-card:hover .icon-circle {
        transform: scale(1.1) rotate(5deg);
    }

    /* Typography */
    .card-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 4px;
    }

    .card-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: white;
        line-height: 1.2;
    }
</style>

<div style="max-width: 900px; margin: 0 auto; padding: 16px;">

    <!-- 1. HERO SECTION: Saudação e Próxima Escala -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 4px;">
            <?= $saudacao ?>, <span style="color: #4f46e5;"><?= $nomeUser ?></span>! 👋
        </h1>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 24px;">
            Aqui está o que está rolando no ministério hoje.
        </p>

        <?php if ($nextSchedule):
            $date = new DateTime($nextSchedule['event_date']);
            $isToday = $date->format('Y-m-d') === date('Y-m-d');
        ?>
            <!-- CARD HERO -->
            <a href="escalas.php?mine=1" class="interact-card" style="
                display: block;
                background: linear-gradient(120deg, #4f46e5, #ec4899);
                border-radius: 24px;
                padding: 28px;
                text-decoration: none;
                color: white;
                position: relative;
            ">
                <!-- Decorative Circle -->
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.1);"></div>

                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div class="glass-pill" style="display: inline-flex; align-items: center; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 12px; color: white;">
                                <?php if ($isToday): ?>
                                    <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 10px #4ade80;"></span>
                                    É HOJE!
                                <?php else: ?>
                                    PRÓXIMA ESCALA
                                <?php endif; ?>
                            </div>

                            <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0 0 8px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <?= htmlspecialchars($nextSchedule['event_type']) ?>
                            </h2>

                            <div style="display: flex; gap: 16px; font-size: 1rem; opacity: 0.95; font-weight: 500;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="calendar" style="width: 18px;"></i>
                                    <?= $date->format('d/m') ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="clock" style="width: 18px;"></i>
                                    19:00
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <i data-lucide="mic-2" style="width: 32px; height: 32px; color: white;"></i>
                        </div>
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div style="background: #f1f5f9; border-radius: 20px; padding: 24px; text-align: center; color: #64748b;">
                <i data-lucide="coffee" style="width: 32px; height: 32px; margin-bottom: 8px; color: #94a3b8;"></i>
                <p>Nenhuma escala agendada para os próximos dias. Descanse!</p>
            </div>
        <?php endif; ?>
    </div>


    <!-- 2. GRID INFO: Cards Coloridos -->
    <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 16px; letter-spacing: 0.5px; text-transform: uppercase;">Visão Geral</h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px;">

        <!-- Minhas Escalas (Agora o 1º) -->
        <a href="escalas.php?mine=1" class="interact-card bg-gradient-info" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #2563eb;">
                <i data-lucide="calendar-check" style="width: 24px;"></i>
            </div>
            <div class="card-label">Minha Agenda</div>
            <div class="card-value">Ver tudo</div>
        </a>

        <!-- Avisos -->
        <a href="avisos.php" class="interact-card bg-gradient-warning" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #d97706;">
                <i data-lucide="bell" style="width: 24px;"></i>
            </div>
            <div class="card-label">Mural</div>
            <div class="card-value">
                <?= $totalAvisos > 0 ? $totalAvisos . ' novos' : 'Em dia' ?>
            </div>
        </a>

        <!-- Aniversários -->
        <a href="aniversarios.php" class="interact-card bg-gradient-danger" style="border-radius: 20px; padding: 20px; text-decoration: none;">
            <div class="icon-circle" style="color: #db2777;">
                <i data-lucide="cake" style="width: 24px;"></i>
            </div>
            <div class="card-label">Nivers de <?= strtolower(strftime('%b')) ?></div>
            <div class="card-value">
                <?= $niverCount > 0 ? $niverCount . ' festa(s)' : 'Nenhum' ?>
            </div>
        </a>

    </div>

    <!-- 3. ACTIONS: Customizável -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin: 0; letter-spacing: 0.5px; text-transform: uppercase;">Acesso Rápido</h3>
        <button onclick="openSheet('quickAccessSheet')" class="ripple" style="
            background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer;
        ">
            <i data-lucide="settings-2" style="width: 16px;"></i>
        </button>
    </div>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
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

        // Definição de todos os itens disponíveis
        $allItems = [
            'repertorio' => [
                'label' => 'Repertório',
                'icon' => 'music-2',
                'color' => '#64748b',
                'bg' => '#f1f5f9',
                'url' => 'repertorio.php'
            ],
            'ausencia' => [
                'label' => 'Avisar Ausência',
                'icon' => 'calendar-off',
                'color' => '#be123c',
                'bg' => '#fff1f2',
                'url' => 'indisponibilidade.php'
            ],
            'escalas' => [
                'label' => 'Escalas',
                'icon' => 'calendar',
                'color' => '#059669',
                'bg' => '#ecfdf5',
                'url' => 'escalas.php'
            ],
            'membros' => [
                'label' => 'Membros',
                'icon' => 'users',
                'color' => '#4f46e5',
                'bg' => '#eef2ff',
                'url' => 'membros.php'
            ],
            'perfil' => [
                'label' => 'Meu Perfil',
                'icon' => 'user',
                'color' => '#d97706',
                'bg' => '#fffbeb',
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
                background: white; border: 1px solid #e2e8f0;
                padding: 16px; border-radius: 16px;
                text-decoration: none; color: #475569;
                display: flex; align-items: center; gap: 12px;
                font-weight: 600; font-size: 0.95rem;
                transition: all 0.2s;
            ">
                    <div style="background: <?= $item['bg'] ?>; padding: 8px; border-radius: 10px;">
                        <i data-lucide="<?= $item['icon'] ?>" style="width: 20px; color: <?= $item['color'] ?>;"></i>
                    </div>
                    <?= $item['label'] ?>
                </a>
        <?php endif;
        endforeach; ?>

        <!-- Botão Adicionar se estiver vazio -->
        <?php if (empty($quickAccessItems)): ?>
            <button onclick="openSheet('quickAccessSheet')" style="
                flex: 1; min-width: 140px; padding: 16px; border: 1px dashed #cbd5e1; 
                background: transparent; color: #94a3b8; border-radius: 16px; font-weight: 600; cursor: pointer;
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
        background: white; border-radius: 20px 20px 0 0;
        padding: 24px; padding-bottom: 40px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        animation: slideUp 0.3s ease-out;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Personalizar Atalhos</h3>
            <button onclick="closeSheet('quickAccessSheet')" style="background: none; border: none; padding: 4px; cursor: pointer; color: #64748b;">
                <i data-lucide="x" style="width: 24px;"></i>
            </button>
        </div>

        <form id="formQuickAccess">
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                <?php foreach ($allItems as $key => $item):
                    $isChecked = in_array($key, $quickAccessItems);
                ?>
                    <label style="
                        display: flex; align-items: center; justify-content: space-between;
                        padding: 12px 16px; background: #f8fafc; border-radius: 12px;
                        border: 1px solid #e2e8f0; cursor: pointer;
                    ">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: <?= $item['bg'] ?>; padding: 6px; border-radius: 8px;">
                                <i data-lucide="<?= $item['icon'] ?>" style="width: 18px; color: <?= $item['color'] ?>;"></i>
                            </div>
                            <span style="font-weight: 600; color: #334155;"><?= $item['label'] ?></span>
                        </div>
                        <input type="checkbox" name="quick_access[]" value="<?= $key ?>" <?= $isChecked ? 'checked' : '' ?>
                            style="width: 18px; height: 18px; accent-color: #166534;">
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="button" onclick="saveQuickAccess()" style="
                width: 100%; padding: 14px; border: none; background: #166534; 
                color: white; font-weight: 700; border-radius: 12px; font-size: 1rem;
                cursor: pointer;
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