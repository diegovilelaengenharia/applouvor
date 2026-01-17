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
            background: linear-gradient(-45deg, #1E5A3A, #2D7A4F, #4A5568, #2D3748);
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
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }

        .music-note svg {
            fill: white;
            stroke: white;
        }

        .music-note:nth-child(1) {
            top: 5%;
            left: 8%;
            animation-delay: 0s;
            width: 120px;
        }

        .music-note:nth-child(2) {
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
            border-color: #2D7A4F;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(45, 122, 79, 0.1);
            transform: translateY(-2px);
        }

        .btn-gold {
            background: linear-gradient(135deg, #2D7A4F 0%, #1E5A3A 100%);
            color: #fff;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(45, 122, 79, 0.4);
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
    <!-- Elementos Musicais Decorativos SVG -->

    <!-- Clave de Sol -->
    <div class="music-note">
        <svg viewBox="0 0 100 150" xmlns="http://www.w3.org/2000/svg">
            <path d="M50,10 Q60,20 55,35 Q50,50 45,65 Q40,80 42,95 Q44,110 50,115 Q56,120 60,115 Q64,110 62,100 Q60,90 55,85 Q50,80 45,85 Q40,90 42,100 Q44,110 50,115 M50,35 Q55,30 58,25 Q61,20 60,15 Q59,10 55,8 Q51,6 48,8 Q45,10 46,15 L50,115 Q52,125 48,135 Q44,145 38,145 Q32,145 30,140 Q28,135 30,130" stroke-width="2" fill="none" />
            <circle cx="50" cy="95" r="8" fill="white" />
        </svg>
    </div>

    <!-- Clave de Fá -->
    <div class="music-note">
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M30,30 Q20,35 20,45 Q20,55 30,60 Q40,65 50,60 Q60,55 60,45 Q60,35 50,30 Q40,25 30,30 M25,40 Q25,35 30,32 Q35,29 40,32 Q45,35 45,40 Q45,45 40,48 Q35,51 30,48 Q25,45 25,40" stroke-width="2" fill="none" />
            <circle cx="70" cy="35" r="4" fill="white" />
            <circle cx="70" cy="55" r="4" fill="white" />
            <line x1="10" y1="30" x2="90" y2="30" stroke-width="1.5" />
            <line x1="10" y1="40" x2="90" y2="40" stroke-width="1.5" />
            <line x1="10" y1="50" x2="90" y2="50" stroke-width="1.5" />
            <line x1="10" y1="60" x2="90" y2="60" stroke-width="1.5" />
        </svg>
    </div>

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