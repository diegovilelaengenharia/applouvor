<?php
// criar_admin.php
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/config/db.php';

try {
    // ----------------------------------------------------
    // MIGRAÇÃO ROBUSTA: Adiciona colunas faltantes na tabela users
    // ----------------------------------------------------
    $columns_to_add = [
        "ALTER TABLE users ADD last_login DATETIME NULL",
        "ALTER TABLE users ADD login_count INT DEFAULT 0",
        "ALTER TABLE users ADD photo VARCHAR(255) NULL",
        "ALTER TABLE users ADD avatar VARCHAR(255) NULL"
    ];

    $migrated = [];
    foreach ($columns_to_add as $sql) {
        try {
            $pdo->exec($sql);
            $migrated[] = "Sucesso: " . substr($sql, 17);
        } catch (PDOException $e) {
            // Código 1060 = Duplicate column name (a coluna já existe)
            if ($e->errorInfo[1] == 1060) {
                $migrated[] = "Já existia: " . substr($sql, 17);
            } else {
                throw $e;
            }
        }
    }

    // ----------------------------------------------------
    // CRIAÇÃO / ATUALIZAÇÃO DO USUÁRIO ADMIN
    // ----------------------------------------------------
    $name = 'Diego';
    $password = '9577';
    $role = 'admin';
    $instrument = 'Voz/Violão';
    $phone = '';

    // Gera o hash da senha
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Verifica se já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$name]);
    $user = $stmt->fetch();

    if ($user) {
        // Atualiza
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE id = ?");
        $updateStmt->execute([$hash, $role, $user['id']]);
        $status = "Usuário <b>Diego</b> já existia e teve seus privilégios de <b>Admin</b> e senha resetados para <b>9577</b> com sucesso!";
    } else {
        // Insere novo
        $insertStmt = $pdo->prepare("INSERT INTO users (name, role, instrument, phone, password) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$name, $role, $instrument, $phone, $hash]);
        $status = "Usuário <b>Diego</b> foi criado do zero como <b>Admin</b> com a senha <b>9577</b> com sucesso!";
    }

    echo "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Configurador de Admin & Banco</title>
        <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap' rel='stylesheet'>
        <style>
            :root {
                --primary: #1e40af;
                --accent: #22d3ee;
                --amber: #FFC501;
                --bg: #0f172a;
                --card-bg: #1e293b;
                --text: #f8fafc;
            }
            body {
                font-family: 'Outfit', sans-serif;
                background-color: var(--bg);
                color: var(--text);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .card {
                background-color: var(--card-bg);
                border-radius: 16px;
                padding: 32px;
                max-width: 480px;
                width: 100%;
                text-align: center;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .icon {
                font-size: 48px;
                margin-bottom: 16px;
            }
            h1 {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 12px;
                color: var(--accent);
            }
            p {
                font-size: 16px;
                line-height: 1.6;
                color: #cbd5e1;
                margin-bottom: 24px;
            }
            .db-log {
                background: rgba(0,0,0,0.2);
                border-radius: 8px;
                padding: 12px;
                font-size: 12px;
                text-align: left;
                font-family: monospace;
                color: #94a3b8;
                margin-bottom: 24px;
                max-height: 120px;
                overflow-y: auto;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .db-log span {
                display: block;
                border-bottom: 1px dashed rgba(255,255,255,0.05);
                padding: 4px 0;
            }
            .btn {
                display: inline-block;
                background-color: var(--primary);
                color: white;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.2s ease;
                border: 1px solid transparent;
            }
            .btn:hover {
                background-color: #1d4ed8;
                box-shadow: 0 0 12px rgba(34, 211, 238, 0.3);
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon'>🔧</div>
            <h1>Banco de Dados Ajustado!</h1>
            <p>{$status}</p>
            <div class='db-log'>
                <strong>Relatório de colunas do banco:</strong>
                " . implode("", array_map(function($log) { return "<span>{$log}</span>"; }, $migrated)) . "
            </div>
            <a href='index.php' class='btn'>Ir para o Login</a>
        </div>
    </body>
    </html>
    ";

} catch (Exception $e) {
    echo "Erro ao criar/atualizar usuário Admin ou ajustar estrutura do banco: " . $e->getMessage();
}
