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
    <title>Louvor PIB - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo-white.png">

    <style>
        /* Estilos Exclusivos da Página de Login (Inline para garantir override) */
        body.login-page {
            background: linear-gradient(135deg, #2C2C2C, #121212);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: #FFFFFF;
            padding: 40px 30px;
            border-radius: 20px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .brand-logo {
            width: 80px;
            margin-bottom: 15px;
        }

        .login-header h2 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .login-header p {
            font-size: 0.7rem;
            color: #D4AF37;
            /* Dourado Texto */
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .form-label {
            display: block;
            text-align: left;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .form-input-login {
            background: #F5F5F5;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            padding: 12px 15px;
            width: 100%;
            font-size: 1rem;
            margin-bottom: 20px;
            outline: none;
            transition: all 0.3s;
        }

        .form-input-login:focus {
            border-color: #D4AF37;
            background: #fff;
        }

        .btn-gold {
            background-color: #D4AF37;
            color: #fff;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }

        .btn-gold:hover {
            background-color: #B59326;
        }

        .custom-footer {
            margin-top: 25px;
            font-size: 0.8rem;
            color: #888;
        }

        .version {
            font-size: 0.7rem;
            color: #ccc;
            margin-top: 5px;
        }
    </style>
</head>

<body class="login-page">

    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/logo-black.png" alt="Logo" class="brand-logo">
            <h2>Ministério de Louvor</h2>
            <p>Primeira Igreja Batista em Oliveira</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #FFEAEA; color: #D32F2F; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="text-align:left;">
                <label class="form-label">Seu Nome</label>
                <input type="text" name="name" class="form-input-login" placeholder="Ex: Diego" required>
            </div>

            <div style="text-align:left;">
                <label class="form-label">Senha (4 dígitos)</label>
                <input type="password" name="password" class="form-input-login" placeholder="••••" maxlength="4" required inputmode="numeric">
            </div>

            <button type="submit" class="btn-gold">Entrar</button>
        </form>

        <div class="custom-footer">
            <p>Esqueceu a senha? Fale com o líder.</p>
            <p class="version">v1.0.0 © 2026</p>
        </div>
    </div>

</body>

</html>