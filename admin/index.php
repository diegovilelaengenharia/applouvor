<?php
// admin/index.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Estatísticas Rápidas
$stats = [
    'musicas' => $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn(),
    'escalas' => $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn(),
    'membros' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'proxima_escala' => $pdo->query("SELECT event_date FROM schedules WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1")->fetchColumn()
];

// Formatar data da próxima escala
$proximaEscalaTexto = $stats['proxima_escala']
    ? date('d/m', strtotime($stats['proxima_escala']))
    : '--/--';

// Header com Saudação
$hora = date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

renderAppHeader('Painel de Controle');
?>

<style>
    /* Estilos Específicos do Dashboard */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 140px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s;
        text-decoration: none;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .stat-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .stat-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .stat-decoration {
        position: absolute;
        right: -10px;
        bottom: -10px;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    /* Seção de Acesso Rápido */
    .quick-actions {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 16px;
        margin: 0 -16px 24px -16px;
        padding-left: 16px;
        padding-right: 16px;
    }

    .action-btn {
        min-width: 100px;
        height: 100px;
        background: white;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: #475569;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    .action-btn:active {
        background: #f1f5f9;
        transform: scale(0.95);
    }

    .action-icon {
        width: 40px;
        height: 40px;
        background: #f8fafc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #047857;
    }

    /* Hero Section Personalizada */
    .dashboard-hero {
        background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
        margin: -24px -16px 24px -16px;
        padding: 32px 24px 48px 24px;
        border-radius: 0 0 32px 32px;
        color: white;
        position: relative;
    }

    .dashboard-hero h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .dashboard-hero p {
        opacity: 0.8;
        font-size: 0.95rem;
    }
</style>

<!-- Substitui o Header Padrão por um Hero Customizado (Hack visual) -->
<script>
    // Remove o header padrão gerado pelo layout.php para usar o customizado
    document.addEventListener('DOMContentLoaded', () => {
        const defaultHeader = document.querySelector('.app-header');
        if (defaultHeader) defaultHeader.style.display = 'none';

        // Ajusta padding do body
        document.body.style.paddingTop = '0';
    });
</script>

<div class="dashboard-hero">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2><?= $saudacao ?>, Time!</h2>
            <p>Vamos organizar o louvor hoje?</p>
        </div>
        <div style="background: rgba(255,255,255,0.2); padding: 8px; border-radius: 12px;">
            <i data-lucide="sparkles" style="color: white;"></i>
        </div>
    </div>
</div>

<div style="padding: 0 8px; margin-top: -30px;">
    <!-- Grid de Estatísticas -->
    <div class="dashboard-grid">
        <!-- Card Próxima Escala -->
        <a href="escalas.php" class="stat-card ripple">
            <div>
                <div class="stat-card-icon" style="background: #ecfdf5; color: #047857;">
                    <i data-lucide="calendar"></i>
                </div>
                <div class="stat-value"><?= $proximaEscalaTexto ?></div>
                <div class="stat-label">Próxima Escala</div>
            </div>
            <i data-lucide="calendar" class="stat-decoration" style="width: 80px; height: 80px;"></i>
        </a>

        <!-- Card Músicas -->
        <a href="repertorio.php" class="stat-card ripple">
            <div>
                <div class="stat-card-icon" style="background: #eff6ff; color: #2563eb;">
                    <i data-lucide="music"></i>
                </div>
                <div class="stat-value"><?= $stats['musicas'] ?></div>
                <div class="stat-label">Músicas</div>
            </div>
            <i data-lucide="music" class="stat-decoration" style="width: 80px; height: 80px;"></i>
        </a>

        <!-- Card Membros -->
        <a href="membros.php" class="stat-card ripple">
            <div>
                <div class="stat-card-icon" style="background: #fefce8; color: #ca8a04;">
                    <i data-lucide="users"></i>
                </div>
                <div class="stat-value"><?= $stats['membros'] ?></div>
                <div class="stat-label">Membros</div>
            </div>
            <i data-lucide="users" class="stat-decoration" style="width: 80px; height: 80px;"></i>
        </a>

        <!-- Card Avisos (Placeholder para futuro) -->
        <a href="avisos.php" class="stat-card ripple">
            <div>
                <div class="stat-card-icon" style="background: #fef2f2; color: #dc2626;">
                    <i data-lucide="bell"></i>
                </div>
                <div class="stat-value">--</div>
                <div class="stat-label">Avisos</div>
            </div>
            <i data-lucide="bell" class="stat-decoration" style="width: 80px; height: 80px;"></i>
        </a>
    </div>

    <!-- Ações Rápidas -->
    <h3 style="margin-left: 8px; margin-bottom: 12px; font-size: 1rem; color: #334155; font-weight: 700;">Acesso Rápido</h3>
    <div class="quick-actions">
        <a href="musica_adicionar.php" class="action-btn ripple">
            <div class="action-icon"><i data-lucide="plus"></i></div>
            <span>Add Música</span>
        </a>
        <a href="escala_criar.php" class="action-btn ripple">
            <div class="action-icon"><i data-lucide="calendar-plus"></i></div>
            <span>Nova Escala</span>
        </a>
        <a href="classificacoes.php" class="action-btn ripple">
            <div class="action-icon"><i data-lucide="tags"></i></div>
            <span>Tags</span>
        </a>
        <a href="membros.php" class="action-btn ripple">
            <div class="action-icon"><i data-lucide="user-plus"></i></div>
            <span>Equipe</span>
        </a>
    </div>

    <!-- Lista Recente (Exemplo: Últimas Músicas) -->
    <h3 style="margin-left: 8px; margin-bottom: 12px; font-size: 1rem; color: #334155; font-weight: 700;">Recém Adicionadas</h3>
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php
        $recentSongs = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recentSongs as $song):
        ?>
            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="ripple" style="
            background: white; 
            padding: 16px; 
            border-radius: 16px; 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            text-decoration: none; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        ">
                <div style="width: 40px; height: 40px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                    <i data-lucide="music-2" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #334155; font-size: 0.95rem;"><?= htmlspecialchars($song['title']) ?></div>
                    <div style="font-size: 0.8rem; color: #94a3b8;"><?= htmlspecialchars($song['artist']) ?></div>
                </div>
                <i data-lucide="chevron-right" style="width: 18px; color: #cbd5e1;"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div style="height: 80px;"></div> <!-- Espaço para footer -->

<?php renderAppFooter(); ?>