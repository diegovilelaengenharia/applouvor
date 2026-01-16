<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        if (login($name, $password, $pdo)) {
            // Verifica nivel e redireciona
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: app/index.php");
            }
            exit;
        } else {
            $error = "Nome ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Louvor PIB Oliveira</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="theme-color" content="#121212">
    <link rel="icon" type="image/png" href="assets/images/logo-white.png">
</head>

<body class="login-page">

    <div class="login-card card">
        <div class="brand-header">
            <img src="assets/images/logo-white.png" alt="PIB Oliveira" class="brand-logo">
            <h2 style="margin-bottom: 5px;">Ministério de Louvor</h2>
            <p class="brand-subtitle">Primeira Igreja Batista em Oliveira</p>
        </div>

        <?php if ($error): ?>
            <div style="background-color: rgba(198, 40, 40, 0.1); color: #ef5350; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group text-left" style="text-align: left;">
                <label for="name" class="form-label">Seu Nome</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Ex: Diego" required autocomplete="off">
            </div>

            <div class="form-group text-left" style="text-align: left;">
                <label for="password" class="form-label">Senha (4 dígitos)</label>
                <input type="tel" id="password" name="password" class="form-input" placeholder="••••" maxlength="4" required pattern="[0-9]*" inputmode="numeric">
            </div>

            <button type="submit" class="btn btn-primary w-full">Entrar</button>
        </form>

        <div class="login-footer">
            <p>Esqueceu a senha? Fale com o líder.</p>
            <p style="margin-top: 10px; font-size: 0.7rem; opacity: 0.5;">v1.0.0 &copy; 2026</p>
        </div>
    </div>

</body>

</html>