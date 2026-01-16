<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
checkAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Louvor PIB</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/images/logo-white.png">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">

        <h1 class="page-title">Painel do Líder</h1>
        <p>Bem-vindo, <?= $_SESSION['user_name'] ?>. O que vamos gerenciar hoje?</p>

        <section class="dashboard-grid">
            <!-- Funcionalidades Principais -->
            <a href="escala.php" class="menu-card">
                <span class="menu-icon">📅</span>
                <span class="menu-title">Escalas</span>
            </a>

            <a href="repertorio.php" class="menu-card">
                <span class="menu-icon">🎵</span>
                <span class="menu-title">Repertório da Semana</span>
            </a>

            <!-- Funcionalidades Futuras / Em Branco -->
            <div class="menu-card disabled-card">
                <span class="menu-icon">📂</span>
                <span class="menu-title">Repertório Geral</span>
            </div>

            <div class="menu-card disabled-card">
                <span class="menu-icon">⛪</span>
                <span class="menu-title">Agenda Igreja</span>
            </div>

            <div class="menu-card disabled-card">
                <span class="menu-icon">🙏</span>
                <span class="menu-title">Oração</span>
            </div>

            <div class="menu-card disabled-card">
                <span class="menu-icon">📖</span>
                <span class="menu-title">Devocionais</span>
            </div>

            <!-- Leitura Bíblica (Exemplo de placeholder anterior) -->
            <div class="menu-card disabled-card">
                <span class="menu-icon">📜</span>
                <span class="menu-title">Leitura Bíblica</span>
            </div>

            <!-- Novos Botões -->
            <a href="membros.php" class="menu-card">
                <span class="menu-icon">👥</span>
                <span class="menu-title">Membros Cadastrados</span>
            </a>

            <a href="perfil.php" class="menu-card">
                <span class="menu-icon">⚙️</span>
                <span class="menu-title">Configurações</span>
            </a>
        </section>

    </div>

</body>

</html>