<?php
// admin/lider.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

renderAppHeader('Painel do Líder');
renderPageHeader('Painel do Líder', 'Dashboard Executivo');

// --- DATA QUERIES ---
$today = date('Y-m-d');

// 1. Next Scale
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE event_date >= ? ORDER BY event_date ASC, event_time ASC LIMIT 1");
$stmt->execute([$today]);
$next_scale = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Counts
$total_songs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$total_members = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_avisos = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NULL")->fetchColumn();

?>
<link rel="stylesheet" href="../assets/css/pages/lider.css?v=<?= time() ?>">

<div class="dashboard-wrapper">

    <!-- KPI HERO SECTION -->
    <div class="kpi-hero">
        <!-- Next Event Card (Featured) -->
        <div class="kpi-card featured">
            <div class="kpi-icon-box">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Próxima Escala</div>
                <?php if ($next_scale): 
                    $date = new DateTime($next_scale['event_date']);
                ?>
                    <div class="kpi-value"><?= $date->format('d/m') ?></div>
                    <div class="kpi-sub"><?= htmlspecialchars($next_scale['event_type']) ?> • <?= substr($next_scale['event_time'], 0, 5) ?></div>
                <?php else: ?>
                    <div class="kpi-value text-muted">--</div>
                    <div class="kpi-sub">Nenhuma agendada</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="kpi-mini-grid">
            <div class="kpi-card mini">
                <div class="kpi-icon-mini text-blue">
                    <i data-lucide="music"></i>
                </div>
                <div>
                    <div class="kpi-value-mini"><?= $total_songs ?></div>
                    <div class="kpi-label-mini">Músicas</div>
                </div>
            </div>
            
            <div class="kpi-card mini">
                <div class="kpi-icon-mini text-purple">
                    <i data-lucide="users"></i>
                </div>
                <div>
                    <div class="kpi-value-mini"><?= $total_members ?></div>
                    <div class="kpi-label-mini">Membros</div>
                </div>
            </div>

            <div class="kpi-card mini">
                <div class="kpi-icon-mini text-orange">
                    <i data-lucide="bell"></i>
                </div>
                <div>
                    <div class="kpi-value-mini"><?= $total_avisos ?></div>
                    <div class="kpi-label-mini">Avisos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 1: GESTÃO -->
    <div class="dashboard-section">
        <h2 class="section-title">
            <i data-lucide="layout-grid" class="text-blue"></i> Gestão
        </h2>

        <div class="dashboard-grid">
            <a href="membros.php" class="action-card color-blue-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="users"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Equipe</div>
                    <div class="card-subtitle">Gerenciar Membros</div>
                </div>
            </a>

            <a href="notificacoes.php" class="action-card color-indigo-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="bell"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Notificações</div>
                    <div class="card-subtitle">Enviar Alertas</div>
                </div>
            </a>

            <a href="indisponibilidades_equipe.php" class="action-card color-cyan-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="calendar-off"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Ausências</div>
                    <div class="card-subtitle">Disponibilidade</div>
                </div>
            </a>

            <a href="sugestoes_musicas.php" class="action-card color-slate-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="inbox"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Sugestões</div>
                    <div class="card-subtitle">Aprovar Músicas</div>
                </div>
            </a>
            
            <a href="classificacoes.php" class="action-card color-slate-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="tags"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Tags</div>
                    <div class="card-subtitle">Categorias</div>
                </div>
            </a>
        </div>
    </div>

    <!-- SEÇÃO 2: ESTATÍSTICAS -->
    <div class="dashboard-section">
        <h2 class="section-title">
            <i data-lucide="bar-chart-2" class="text-green"></i> Estatísticas
        </h2>

        <div class="dashboard-grid">
            <a href="stats_equipe.php" class="action-card color-emerald-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="activity"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Engajamento</div>
                    <div class="card-subtitle">Atividade da Equipe</div>
                </div>
            </a>

            <a href="relatorios_gerais.php" class="action-card color-teal-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="pie-chart"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Indicadores</div>
                    <div class="card-subtitle">Relatórios Gerais</div>
                </div>
            </a>
        </div>
    </div>

    <!-- SEÇÃO 3: CRIAR NOVO -->
    <div class="dashboard-section">
        <h2 class="section-title">
            <i data-lucide="plus-circle" class="text-yellow"></i> Criar Novo
        </h2>

        <div class="dashboard-grid">
            <a href="escala_adicionar.php" class="action-card color-amber-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="calendar-plus"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Escala</div>
                    <div class="card-subtitle">Agendar Nova</div>
                </div>
            </a>

            <a href="musica_adicionar.php" class="action-card color-orange-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="music"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Música</div>
                    <div class="card-subtitle">Cadastrar</div>
                </div>
            </a>

            <a href="avisos_admin.php" class="action-card color-yellow-gradient">
                <div class="card-icon-glass">
                    <i data-lucide="megaphone"></i>
                </div>
                <div class="card-info">
                    <div class="card-title">Aviso</div>
                    <div class="card-subtitle">Novo Comunicado</div>
                </div>
            </a>
        </div>
    </div>

</div>

<?php renderAppFooter(); ?>