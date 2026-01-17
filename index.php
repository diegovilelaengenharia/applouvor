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
            background: linear-gradient(-45deg, #1E3A8A, #2563EB, #3B82F6, #60A5FA);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Notas Musicais Flutuantes */
        .music-note {
            position: absolute;
            font-size: 3rem;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
            color: white;
        }

        .music-note:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .music-note:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 2s;
            font-size: 2.5rem;
        }

        .music-note:nth-child(3) {
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
            font-size: 4rem;
        }

        .music-note:nth-child(4) {
            top: 60%;
            right: 10%;
            animation-delay: 6s;
        }

        .music-note:nth-child(5) {
            bottom: 15%;
            right: 25%;
            animation-delay: 8s;
            font-size: 3.5rem;
        }

        .music-note:nth-child(6) {
            top: 40%;
            left: 5%;
            animation-delay: 10s;
            font-size: 2rem;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            25% {
                transform: translateY(-30px) rotate(5deg);
            }

            50% {
                transform: translateY(-60px) rotate(-5deg);
            }

            75% {
                transform: translateY(-30px) rotate(3deg);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px 30px;
            border-radius: 24px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 10;
        }

        .brand-logo {
            width: 80px;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .login-header h2 {
            font-size: 1.3rem;
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            font-weight: 800;
        }

        .form-label {
            display: block;
            text-align: left;
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-input-login {
            background: #F9FAFB;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 14px 16px;
            width: 100%;
            font-size: 1rem;
            margin-bottom: 20px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input-login:focus {
            border-color: #2563EB;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .btn-gold {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            color: #fff;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-gold::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-gold:hover::before {
            left: 100%;
        }

        .btn-gold:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.5);
        }

        .btn-gold:active {
            transform: translateY(-1px) scale(0.98);
        }

        .custom-footer {
            margin-top: 25px;
            font-size: 0.85rem;
            color: #888;
        }

        .version {
            font-size: 0.7rem;
            color: #ccc;
            margin-top: 5px;
        }

        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
            animation: shake 0.5s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }
    </style>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="login-page">
    <!-- Notas Musicais Decorativas -->
    <div class="music-note">♪</div>
    <div class="music-note">♫</div>
    <div class="music-note">♪</div>
    <div class="music-note">♬</div>
    <div class="music-note">♫</div>
    <div class="music-note">♪</div>

    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/logo-black.png" alt="Logo" class="brand-logo">
            <h2>Ministério de Louvor</h2>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
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