<?php
// admin/membro_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['id'])) {
    header('Location: membros.php');
    exit;
}

$id = $_GET['id'];

// Processar atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, instrument = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([
        $_POST['name'],
        $_POST['instrument'],
        $_POST['phone'],
        $_POST['email'] ?? null,
        $id
    ]);
    header("Location: membro_detalhe.php?id=$id&updated=1");
    exit;
}

// Buscar dados do membro
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: membros.php');
    exit;
}

// Buscar histórico de escalas
$stmtHistory = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ?
    ORDER BY s.event_date DESC
");
$stmtHistory->execute([$id]);
$schedules = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Calcular estatísticas
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_escalas,
        MIN(s.event_date) as primeira_escala,
        MAX(s.event_date) as ultima_escala
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ?
");
$stmtStats->execute([$id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Calcular frequência média
$frequencia_media = 0;
if ($stats['total_escalas'] > 1 && $stats['primeira_escala'] && $stats['ultima_escala']) {
    $primeira = new DateTime($stats['primeira_escala']);
    $ultima = new DateTime($stats['ultima_escala']);
    $dias_total = $primeira->diff($ultima)->days;
    $frequencia_media = $dias_total > 0 ? round($dias_total / ($stats['total_escalas'] - 1), 1) : 0;
}

// Buscar próxima escala
$stmtNext = $pdo->prepare("
    SELECT s.*
    FROM schedules s
    JOIN schedule_users su ON s.id = su.schedule_id
    WHERE su.user_id = ? AND s.event_date >= CURDATE()
    ORDER BY s.event_date ASC
    LIMIT 1
");
$stmtNext->execute([$id]);
$nextSchedule = $stmtNext->fetch(PDO::FETCH_ASSOC);

$activeTab = $_GET['tab'] ?? 'historico';

renderAppHeader('Detalhes do Membro');
?>

<style>
    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        padding: 16px;
        text-align: center;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--accent-interactive);
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 600;
    }

    .history-item {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-left: 4px solid var(--accent-interactive);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .history-item:hover {
        box-shadow: var(--shadow-md);
        transform: translateX(4px);
    }

    .tabs-nav {
        display: flex;
        background: var(--bg-tertiary);
        padding: 4px;
        border-radius: 16px;
        margin-bottom: 24px;
    }

    .tab-btn {
        flex: 1;
        text-align: center;
        padding: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: transparent;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab-btn.active {
        color: var(--text-primary);
        background: var(--bg-secondary);
        box-shadow: var(--shadow-sm);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
</style>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="membros.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; align-items: center;">
            <?php renderGlobalNavButtons(); ?>
        </div>
    </div>

    <!-- Member Info -->
    <div style="text-align: center;">
        <div style="
            width: 80px; 
            height: 80px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 16px;
            backdrop-filter: blur(4px);
            font-size: 2rem;
            font-weight: 800;
            color: white;
        ">
            <?= strtoupper(substr($member['name'], 0, 1)) ?>
        </div>
        <h1 style="color: white; margin: 0; font-size: 1.8rem; font-weight: 800;">
            <?= htmlspecialchars($member['name']) ?>
            <?php if ($member['role'] === 'admin'): ?>
                <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; margin-left: 8px;">ADMIN</span>
            <?php endif; ?>
        </h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 8px; font-size: 1rem;">
            <i data-lucide="music" style="width: 16px; display: inline;"></i>
            <?= htmlspecialchars($member['instrument'] ?: 'Não definido') ?>
        </p>
        <p style="color: rgba(255,255,255,0.9); margin-top: 4px;">
            <i data-lucide="phone" style="width: 16px; display: inline;"></i>
            <?= htmlspecialchars($member['phone']) ?>
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total_escalas'] ?? 0 ?></div>
        <div class="stat-label">Total de Escalas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $frequencia_media ?></div>
        <div class="stat-label">Frequência (dias)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">
            <?php
            if ($stats['ultima_escala']) {
                $ultima = new DateTime($stats['ultima_escala']);
                echo $ultima->format('d/m');
            } else {
                echo '-';
            }
            ?>
        </div>
        <div class="stat-label">Última Escala</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">
            <?php
            if ($nextSchedule) {
                $proxima = new DateTime($nextSchedule['event_date']);
                echo $proxima->format('d/m');
            } else {
                echo '-';
            }
            ?>
        </div>
        <div class="stat-label">Próxima Escala</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs-nav">
    <a href="?id=<?= $id ?>&tab=historico" class="tab-btn <?= $activeTab === 'historico' ? 'active' : '' ?>">Histórico</a>
    <a href="?id=<?= $id ?>&tab=dados" class="tab-btn <?= $activeTab === 'dados' ? 'active' : '' ?>">Dados</a>
</div>

<!-- Tab: Histórico -->
<div class="tab-content <?= $activeTab === 'historico' ? 'active' : '' ?>">
    <?php if (empty($schedules)): ?>
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            <i data-lucide="calendar-x" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Nenhuma participação em escalas ainda.</p>
        </div>
    <?php else: ?>
        <?php foreach ($schedules as $schedule):
            $date = new DateTime($schedule['event_date']);
        ?>
            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="history-item" style="display: block; text-decoration: none;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                    <div>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 1rem; margin-bottom: 4px;">
                            <?= htmlspecialchars($schedule['event_type']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <i data-lucide="calendar" style="width: 14px; display: inline;"></i>
                            <?= $date->format('d/m/Y') ?> •
                            <i data-lucide="music" style="width: 14px; display: inline;"></i>
                            <?= $schedule['total_songs'] ?> música<?= $schedule['total_songs'] != 1 ? 's' : '' ?>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Tab: Dados -->
<div class="tab-content <?= $activeTab === 'dados' ? 'active' : '' ?>">
    <?php if (isset($_GET['updated'])): ?>
        <div style="background: #DCFCE7; color: #166534; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
            ✓ Dados atualizados com sucesso!
        </div>
    <?php endif; ?>

    <form method="POST" class="card-clean">
        <input type="hidden" name="action" value="update">

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($member['name']) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Instrumento/Função</label>
            <input type="text" name="instrument" class="form-input" value="<?= htmlspecialchars($member['instrument'] ?? '') ?>" placeholder="Ex: Voz, Violão, Bateria">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Telefone</label>
            <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($member['email'] ?? '') ?>">
        </div>

        <button type="submit" class="btn-action-save ripple" style="width: 100%; justify-content: center;">
            <i data-lucide="save"></i> Salvar Alterações
        </button>
    </form>
</div>

<?php renderAppFooter(); ?>