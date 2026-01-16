<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Louvor PIB - Área do Músico</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo-white.png">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">

        <h1 class="page-title">Olá, <?= $_SESSION['user_name'] ?>!</h1>
        <p>Prepare-se para servir com excelência.</p>

        <section class="dashboard-grid">
            <a href="escala.php" class="menu-card">
                <span class="menu-icon">📅</span>
                <span class="menu-title">Minhas Escalas</span>
            </a>

            <a href="repertorio.php" class="menu-card">
                <span class="menu-icon">🎵</span>
                <span class="menu-title">Repertório da Semana</span>
            </a>

            <!-- Funcionalidades Futuras -->
            <div class="menu-card disabled-card">
                <span class="menu-icon">📂</span>
                <span class="menu-title">Repertório Geral</span>
            </div>

            <div class="menu-card disabled-card">
                <span class="menu-icon">⛪</span>
                <span class="menu-title">Agenda Igreja</span>
            </div>
        </section>

        <!-- Área de Avisos Rapidos (Opcional) -->
        <div style="margin-top: 40px;">
            <h3 style="margin-bottom: 15px; font-size: 1.1rem;">Próximo Culto</h3>
            <div class="card">
                <p style="color: var(--text-secondary); text-align: center;">Nenhuma escala confirmada para os próximos dias.</p>
                <!-- Futuramente aqui vai puxar do banco -->
            </div>
        </div>

    </div>

</body>

</html>