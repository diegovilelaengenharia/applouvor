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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .brand-logo {
            width: 80px;
            margin-bottom: 15px;
        }

        .login-header h2 {
            font-size: 1.2rem;
            color: #1a1a1a;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .form-label {
            display: block;
            text-align: left;
            font-size: 0.8rem;
            color: #555;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-input-login {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
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
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .btn-gold {
            background-color: #D4AF37;
            color: #fff;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 4px 6px rgba(212, 175, 55, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            /* Espaço entre texto e ícone */
        }

        .btn-gold:hover {
            background-color: #B59326;
            transform: translateY(-2px);
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
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="login-page">

    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/logo-black.png" alt="Logo" class="brand-logo">
            <h2>Ministério de Louvor</h2>
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

            <button type="submit" class="btn-gold">
                Entrar
            </button>
        </form>

        <div class="custom-footer">
            <p>Esqueceu a senha? Fale com o líder.</p>
            <p class="version">v1.0.0 © 2026</p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>