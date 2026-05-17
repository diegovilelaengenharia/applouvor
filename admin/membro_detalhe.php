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
           (SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = s.id) as total_songs,
           su.status as presence_status,
           su.absence_note
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

// Calcular breakdown de presença
$totalEscalas = count($schedules);
$totalPresente = 0;
$totalFaltou = 0;
$totalJustificou = 0;

foreach ($schedules as $sc) {
    $st = $sc['presence_status'] ?? 'pending';
    if (in_array($st, ['confirmed', 'pending'])) $totalPresente++;
    elseif ($st === 'absent') $totalFaltou++;
    elseif ($st === 'absent_justified') $totalJustificou++;
}

$taxaPresenca = $totalEscalas > 0
    ? round(($totalPresente / $totalEscalas) * 100)
    : 0;

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

<link rel="stylesheet" href="../assets/css/pages/shared-pages.css">




<!-- Hero Header Compacto -->
<div style="
    background: var(--sage-600); 
    margin: -16px -16px 20px -16px; 
    padding: 16px 16px 40px 16px; 
    border-radius: 0 0 20px 20px; 
    box-shadow: var(--shadow-sm);
    position: relative;
    color: white;
">
    <!-- Navbar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <a href="membros.php" class="ripple" style="
            width: 32px; height: 32px; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; 
            color: white; background: rgba(255,255,255,0.2); 
            text-decoration: none;
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="arrow-left" style="width: 18px;"></i>
        </a>

        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="index.php" class="ripple" style="
                width: 32px; height: 32px; border-radius: 10px; 
                background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
                display: flex; align-items: center; justify-content: center;
                color: white; text-decoration: none;
            ">
                <i data-lucide="home" style="width: 16px;"></i>
            </a>
            <a href="../app/index.php" class="ripple" style="
                width: 32px; height: 32px; border-radius: 10px; 
                background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
                display: flex; align-items: center; justify-content: center;
                color: white; text-decoration: none;
            ">
                <i data-lucide="smartphone" style="width: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Member Info -->
    <div style="text-align: center;">
        <div style="
            width: 56px; height: 56px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: inline-flex; align-items: center; justify-content: center; 
            margin-bottom: 8px;
            backdrop-filter: blur(4px);
            font-size: var(--font-display); font-weight: 800; color: white;
            border: 2px solid rgba(255,255,255,0.3);
        ">
            <?= strtoupper(substr($member['name'], 0, 1)) ?>
        </div>
        <h1 style="color: white; margin: 0; font-size: var(--font-h1); font-weight: 800; line-height: 1.2;">
            <?= htmlspecialchars($member['name']) ?>
            <?php if ($member['role'] === 'admin'): ?>
                <span style="background: rgba(255,255,255,0.2); padding: 1px 6px; border-radius: 8px; font-size: var(--font-caption); vertical-align: middle; font-weight: 600;">ADMIN</span>
            <?php endif; ?>
        </h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-size: var(--font-body-sm); display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span style="display: flex; align-items: center; gap: 4px;">
                <i data-lucide="music" style="width: 12px;"></i>
                <?= htmlspecialchars($member['instrument'] ?: 'Não definido') ?>
            </span>
            <span style="display: flex; align-items: center; gap: 4px;">
                <i data-lucide="phone" style="width: 12px;"></i>
                <?= htmlspecialchars($member['phone']) ?>
            </span>
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div style="margin-top: -30px; position: relative; padding: 0 16px; margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_escalas'] ?? 0 ?></div>
            <div class="stat-label">Escalas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $frequencia_media ?></div>
            <div class="stat-label">Freq (dias)</div>
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
            <div class="stat-label">Última</div>
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
            <div class="stat-label">Próxima</div>
        </div>
    </div>
</div>

<!-- Stats de Presença -->
<div style="padding: 0 16px; margin-bottom: 20px;">
  <div class="presence-stats-card pib-card" style="padding:16px;">
    <h4 style="margin:0 0 12px;font-size:0.9rem;font-weight:700;color:var(--gray-700,#374151);">
      Histórico de Presença
    </h4>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <div style="flex:1;min-width:80px;text-align:center;padding:10px;background:#d1fae5;border-radius:8px;">
        <div style="font-size:1.4rem;font-weight:800;color:#065f46;"><?= $totalPresente ?></div>
        <div style="font-size:0.7rem;font-weight:600;color:#047857;">Presente</div>
      </div>
      <div style="flex:1;min-width:80px;text-align:center;padding:10px;background:#fee2e2;border-radius:8px;">
        <div style="font-size:1.4rem;font-weight:800;color:#991b1b;"><?= $totalFaltou ?></div>
        <div style="font-size:0.7rem;font-weight:600;color:#b91c1c;">Faltou</div>
      </div>
      <div style="flex:1;min-width:80px;text-align:center;padding:10px;background:#fef3c7;border-radius:8px;">
        <div style="font-size:1.4rem;font-weight:800;color:#92400e;"><?= $totalJustificou ?></div>
        <div style="font-size:0.7rem;font-weight:600;color:#b45309;">Justificou</div>
      </div>
      <div style="flex:1;min-width:80px;text-align:center;padding:10px;background:var(--blue-50,#eff6ff);border-radius:8px;border:2px solid var(--blue-500,#3b82f6);">
        <div style="font-size:1.4rem;font-weight:800;color:var(--blue-700,#1d4ed8);"><?= $taxaPresenca ?>%</div>
        <div style="font-size:0.7rem;font-weight:600;color:var(--blue-600,#2563eb);">Taxa</div>
      </div>
    </div>
  </div>
</div>

<div style="padding: 0 16px;">
    <!-- Tabs -->
    <div class="tabs-nav">
        <a href="?id=<?= $id ?>&tab=historico" class="tab-btn <?= $activeTab === 'historico' ? 'active' : '' ?>">Histórico</a>
        <a href="?id=<?= $id ?>&tab=dados" class="tab-btn <?= $activeTab === 'dados' ? 'active' : '' ?>">Dados</a>
    </div>

    <!-- Tab: Histórico -->
    <div style="display: <?= $activeTab === 'historico' ? 'block' : 'none' ?>;">
        <?php if (empty($schedules)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <i data-lucide="calendar-x" style="width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.5;"></i>
                <p style="font-size: var(--font-body-sm);">Nenhuma escala encontrada.</p>
            </div>
        <?php else: ?>
            <?php foreach ($schedules as $schedule):
                $date = new DateTime($schedule['event_date']);
            ?>
                <?php
                $pStatus = $schedule['presence_status'] ?? 'pending';
                $statusLabel = match($pStatus) {
                    'confirmed'        => ['✅', 'Presente',  '#d1fae5', '#065f46'],
                    'absent'           => ['❌', 'Faltou',    '#fee2e2', '#991b1b'],
                    'absent_justified' => ['⚠️', 'Justificou','#fef3c7', '#92400e'],
                    'declined'         => ['🚫', 'Recusou',   '#f3f4f6', '#6b7280'],
                    default            => ['🕐', 'Pendente',  '#f3f4f6', '#6b7280'],
                };
                [$icon, $label, $bg, $color] = $statusLabel;
                ?>
                <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="history-item">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex:1;">
                            <div style="font-weight: 600; color: var(--text-primary); font-size: var(--font-body); margin-bottom: 2px;">
                                <?= htmlspecialchars($schedule['event_type']) ?>
                            </div>
                            <div style="font-size: var(--font-caption); color: var(--text-secondary); margin-bottom: 4px;">
                                <?= $date->format('d/m/Y') ?> • <?= $schedule['total_songs'] ?> música<?= $schedule['total_songs'] != 1 ? 's' : '' ?>
                            </div>
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:12px;background:<?= $bg ?>;color:<?= $color ?>;">
                              <?= $icon ?> <?= $label ?>
                            </span>
                            <?php if (in_array($pStatus, ['absent','absent_justified']) && !empty($schedule['absence_note'])): ?>
                            <p style="font-size:0.72rem;color:var(--gray-500,#6b7280);margin:4px 0 0;font-style:italic;">
                              <?= htmlspecialchars($schedule['absence_note']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="chevron-right" style="width: 16px; color: var(--text-muted); flex-shrink:0; margin-top:2px;"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Tab: Dados -->
    <div style="display: <?= $activeTab === 'dados' ? 'block' : 'none' ?>;">
        <?php if (isset($_GET['updated'])): ?>
            <div style="background: var(--sage-100); color: var(--sage-800); padding: 10px; border-radius: 8px; margin-bottom: 16px; text-align: center; font-size: var(--font-body-sm); font-weight: 600; border: 1px solid var(--sage-200);">
                ✓ Dados atualizados!
            </div>
        <?php endif; ?>

        <div style="background: var(--bg-card); padding: 16px; border-radius: 12px; border: 1px solid var(--border-subtle);">
            <form method="POST">
                <input type="hidden" name="action" value="update">

                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($member['name']) ?>" required style="font-weight: 600;">
                </div>

                <div class="form-group">
                    <label class="form-label">Instrumento / Função</label>
                    <input type="text" name="instrument" class="form-input" value="<?= htmlspecialchars($member['instrument'] ?? '') ?>" placeholder="Ex: Voz">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($member['email'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="ripple" style="
                    width: 100%; margin-top: 12px; padding: 12px; border: none; border-radius: 10px;
                    background: var(--primary); color: white; font-weight: 700; font-size: var(--font-body);
                    display: flex; align-items: center; justify-content: center; gap: 8px;
                ">
                    <i data-lucide="save" style="width: 16px;"></i> Salvar Alterações
                </button>
            </form>
        </div>
    </div>
</div>

<?php renderAppFooter(); ?>