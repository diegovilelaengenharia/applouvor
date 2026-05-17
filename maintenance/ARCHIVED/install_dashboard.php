<?php
// admin/install_dashboard.php
require_once '../includes/db.php';

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Ler arquivo SQL
        $sqlFile = '../migrations/001_dashboard_tables.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Arquivo SQL não encontrado: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);

        // Executar SQL
        $pdo->exec($sql);

        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Dashboard - App Louvor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container" style="max-width: 600px; margin: 40px auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: var(--text-primary); margin-bottom: 8px;">🚀 Instalação do Dashboard</h1>
            <p style="color: var(--text-secondary);">Clique no botão abaixo para criar as tabelas necessárias</p>
        </div>

        <?php if ($success): ?>
            <div style="
                background: rgba(5, 150, 105, 0.1);
                border: 2px solid var(--primary);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            ">
                <h3 style="color: var(--primary); margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
                    ✅ Instalação Concluída!
                </h3>
                <p style="color: var(--text-primary); margin: 0;">
                    As tabelas foram criadas com sucesso. Você já pode usar o dashboard!
                </p>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="index.php" style="
                    flex: 1;
                    padding: 14px;
                    background: #047857;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 700;
                    text-align: center;
                    text-decoration: none;
                ">
                    Ir para Dashboard
                </a>
                <a href="avisos.php" style="
                    flex: 1;
                    padding: 14px;
                    background: #FFC107;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 700;
                    text-align: center;
                    text-decoration: none;
                ">
                    Ver Avisos
                </a>
            </div>

        <?php elseif ($error): ?>
            <div style="
                background: rgba(220, 53, 69, 0.1);
                border: 2px solid #DC3545;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            ">
                <h3 style="color: #DC3545; margin: 0 0 8px 0;">❌ Erro na Instalação</h3>
                <p style="color: var(--text-primary); margin: 0; font-family: monospace; font-size: 0.85rem;">
                    <?= htmlspecialchars($error) ?>
                </p>
            </div>

            <button onclick="location.reload()" style="
                width: 100%;
                padding: 14px;
                background: var(--bg-tertiary);
                color: var(--text-primary);
                border: 1px solid var(--border-subtle);
                border-radius: 8px;
                font-weight: 700;
                cursor: pointer;
            ">
                Tentar Novamente
            </button>

        <?php else: ?>
            <div style="
                background: var(--bg-secondary);
                border: 1px solid var(--border-subtle);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            ">
                <h3 style="color: var(--text-primary); margin: 0 0 12px 0;">O que será instalado:</h3>
                <ul style="color: var(--text-secondary); margin: 0; padding-left: 20px;">
                    <li>Tabela <code>avisos</code> - Sistema de avisos com prioridades</li>
                    <li>Tabela <code>user_widgets</code> - Widgets personalizáveis</li>
                    <li>Coluna <code>event_time</code> em schedules (se não existir)</li>
                    <li>Coluna <code>position</code> em schedule_songs (se não existir)</li>
                    <li>3 avisos de exemplo para teste</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="install" value="1" style="
                    width: 100%;
                    padding: 16px;
                    background: #047857;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 1.1rem;
                    cursor: pointer;
                    transition: all 0.2s;
                " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    🚀 Instalar Agora
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">
                    ← Voltar para Dashboard
                </a>
            </div>
        <?php endif; ?>

        <div style="
            margin-top: 40px;
            padding: 16px;
            background: rgba(13, 110, 253, 0.1);
            border-radius: 8px;
            border-left: 4px solid #0D6EFD;
        ">
            <h4 style="color: #0D6EFD; margin: 0 0 8px 0; font-size: 0.9rem;">💡 Dica</h4>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.85rem;">
                Se você preferir instalar manualmente, acesse
                <a href="http://localhost/phpmyadmin" target="_blank" style="color: #0D6EFD; font-weight: 600;">
                    phpMyAdmin
                </a>
                e execute o SQL do arquivo <code>migrations/001_dashboard_tables.sql</code>
            </p>
        </div>
    </div>
</body>

</html>