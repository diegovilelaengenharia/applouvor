<?php
require_once 'includes/no-cache.php';
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
    <!-- Elementos Musicais Decorativos SVG Sofisticados -->

    <!-- Clave de Sol Elaborada -->
    <div class="music-note" style="top: 8%; left: 10%; width: 140px;">
        <svg viewBox="0 0 120 180" xmlns="http://www.w3.org/2000/svg">
            <path d="M60,20 Q75,25 72,45 Q69,65 64,85 Q59,105 62,125 Q65,145 72,152 Q79,159 85,152 Q91,145 88,130 Q85,115 78,108 Q71,101 64,108 Q57,115 60,130 Q63,145 72,152 M72,45 Q78,38 82,30 Q86,22 84,15 Q82,8 76,5 Q70,2 65,5 Q60,8 62,15 L72,152 Q75,165 70,178 Q65,191 56,191 Q47,191 44,183 Q41,175 44,167" stroke="white" stroke-width="3" fill="none" opacity="0.2" />
        </svg>
    </div>

    <!-- Notas Musicais Agrupadas -->
    <div class="music-note" style="top: 55%; right: 8%; width: 160px;">
        <svg viewBox="0 0 140 100" xmlns="http://www.w3.org/2000/svg">
            <!-- Pauta -->
            <line x1="10" y1="30" x2="130" y2="30" stroke="white" stroke-width="1.5" opacity="0.15" />
            <line x1="10" y1="40" x2="130" y2="40" stroke="white" stroke-width="1.5" opacity="0.15" />
            <line x1="10" y1="50" x2="130" y2="50" stroke="white" stroke-width="1.5" opacity="0.15" />
            <line x1="10" y1="60" x2="130" y2="60" stroke="white" stroke-width="1.5" opacity="0.15" />
            <line x1="10" y1="70" x2="130" y2="70" stroke="white" stroke-width="1.5" opacity="0.15" />

            <!-- Nota 1 -->
            <ellipse cx="35" cy="55" rx="8" ry="6" fill="white" opacity="0.2" transform="rotate(-20 35 55)" />
            <line x1="43" y1="55" x2="43" y2="15" stroke="white" stroke-width="2.5" opacity="0.2" />

            <!-- Nota 2 -->
            <ellipse cx="65" cy="45" rx="8" ry="6" fill="white" opacity="0.2" transform="rotate(-20 65 45)" />
            <line x1="73" y1="45" x2="73" y2="5" stroke="white" stroke-width="2.5" opacity="0.2" />

            <!-- Nota 3 -->
            <ellipse cx="95" cy="50" rx="8" ry="6" fill="white" opacity="0.2" transform="rotate(-20 95 50)" />
            <line x1="103" y1="50" x2="103" y2="10" stroke="white" stroke-width="2.5" opacity="0.2" />
        </svg>
    </div>

    <!-- Símbolo de Pausa -->
    <div class="music-note" style="bottom: 20%; left: 15%; width: 80px;">
        <svg viewBox="0 0 60 80" xmlns="http://www.w3.org/2000/svg">
            <rect x="15" y="10" width="8" height="30" fill="white" opacity="0.18" rx="2" />
            <rect x="37" y="10" width="8" height="30" fill="white" opacity="0.18" rx="2" />
        </svg>
    </div>

    <!-- Notas com Haste Dupla -->
    <div class="music-note" style="top: 35%; left: 5%; width: 120px;">
        <svg viewBox="0 0 100 120" xmlns="http://www.w3.org/2000/svg">
            <!-- Pauta de fundo -->
            <line x1="5" y1="60" x2="95" y2="60" stroke="white" stroke-width="1" opacity="0.1" />
            <line x1="5" y1="70" x2="95" y2="70" stroke="white" stroke-width="1" opacity="0.1" />
            <line x1="5" y1="80" x2="95" y2="80" stroke="white" stroke-width="1" opacity="0.1" />

            <!-- Nota dupla -->
            <ellipse cx="30" cy="75" rx="7" ry="5" fill="white" opacity="0.2" transform="rotate(-25 30 75)" />
            <line x1="37" y1="75" x2="37" y2="25" stroke="white" stroke-width="2.5" opacity="0.2" />
            <ellipse cx="60" cy="65" rx="7" ry="5" fill="white" opacity="0.2" transform="rotate(-25 60 65)" />
            <line x1="67" y1="65" x2="67" y2="15" stroke="white" stroke-width="2.5" opacity="0.2" />
            <!-- Barra conectando -->
            <rect x="37" y="23" width="30" height="4" fill="white" opacity="0.2" rx="2" />
        </svg>
    </div>

    <!-- Símbolo de Sustenido -->
    <div class="music-note" style="bottom: 25%; right: 20%; width: 70px;">
        <svg viewBox="0 0 50 70" xmlns="http://www.w3.org/2000/svg">
            <line x1="15" y1="10" x2="15" y2="60" stroke="white" stroke-width="2" opacity="0.18" />
            <line x1="35" y1="10" x2="35" y2="60" stroke="white" stroke-width="2" opacity="0.18" />
            <line x1="10" y1="25" x2="40" y2="20" stroke="white" stroke-width="3" opacity="0.18" />
            <line x1="10" y1="45" x2="40" y2="40" stroke="white" stroke-width="3" opacity="0.18" />
        </svg>
    </div>

    <!-- Clave de Fá -->
    <div class="music-note" style="top: 65%; left: 25%; width: 100px;">
        <svg viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M20,40 Q15,45 15,50 Q15,55 20,60 Q25,65 35,65 Q45,65 50,60 Q55,55 55,50 Q55,45 50,40 Q45,35 40,35 Q35,35 30,38" stroke="white" stroke-width="3" fill="none" opacity="0.2" />
            <circle cx="58" cy="45" r="3" fill="white" opacity="0.2" />
            <circle cx="58" cy="55" r="3" fill="white" opacity="0.2" />
        </svg>
    </div>

    <!-- Notas Pequenas Flutuantes -->
    <div class="music-note" style="top: 15%; right: 25%; width: 60px;">
        <svg viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="20" cy="35" rx="6" ry="4" fill="white" opacity="0.15" transform="rotate(-20 20 35)" />
            <line x1="26" y1="35" x2="26" y2="10" stroke="white" stroke-width="2" opacity="0.15" />
        </svg>
    </div>

    <div class="music-note" style="bottom: 35%; right: 12%; width: 55px;">
        <svg viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="20" cy="35" rx="6" ry="4" fill="white" opacity="0.15" transform="rotate(-20 20 35)" />
            <line x1="26" y1="35" x2="26" y2="10" stroke="white" stroke-width="2" opacity="0.15" />
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