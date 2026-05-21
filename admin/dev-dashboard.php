<?php
/**
 * dev-dashboard.php
 * Painel Administrativo de Desenvolvimento Premium (MySQL, JSON e HTML Sandbox).
 * EXCLUSIVO para ambiente local de desenvolvimento.
 */

// Silenciar erros de exibição direta no output em caso de AJAX para não quebrar o JSON
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
          || isset($_GET['ajax']);

if ($isAjax) {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// 1. Carrega a infraestrutura do sistema (banco de dados e ambiente)
try {
    $dbConfigPath = __DIR__ . '/../src/config/db.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception("Configurações do banco de dados não localizadas.");
    }
    require_once $dbConfigPath;
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Falha ao iniciar ambiente: ' . $e->getMessage()]);
        exit;
    }
    die("<div style='font-family:sans-serif;padding:40px;background:#18181b;color:#f87171;text-align:center;border-radius:12px;margin:50px auto;max-width:600px;'><h2>Erro de Inicialização</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>");
}

// 2. Trava de Segurança Crítica (P0)
if (!defined('APP_ENV') || APP_ENV !== 'local') {
    header("HTTP/1.1 403 Forbidden");
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas ambiente local (APP_ENV=local) é permitido.']);
        exit;
    }
    die("<div style='font-family:sans-serif;padding:60px;text-align:center;background:#09090b;color:#ef4444;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;'>
            <div style='background:#18181b;border:1px solid #27272a;padding:40px;border-radius:24px;max-width:500px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.5)'>
                <span style='font-size:64px;'>🔒</span>
                <h2 style='margin-top:20px;font-size:24px;font-weight:700;color:#f3f4f6;'>Acesso Restrito</h2>
                <p style='color:#a1a1aa;line-height:1.6;margin-top:10px;'>Este painel de desenvolvimento contém ferramentas administrativas altamente sensíveis e está permanentemente bloqueado em ambientes de produção por motivos de segurança.</p>
                <div style='margin-top:20px;background:#27272a;padding:12px;border-radius:8px;font-family:monospace;font-size:13px;color:#f87171;'>APP_ENV ativo: " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "</div>
            </div>
         </div>");
}

// 3. Processamento de Requisições AJAX (API de Desenvolvimento)
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'run_sql':
                $sql = trim($_POST['sql'] ?? '');
                if (empty($sql)) {
                    throw new Exception("Instrução SQL vazia.");
                }
                
                $isSelect = preg_match('/^\s*(select|show|describe|explain)/i', $sql);
                $startTime = microtime(true);
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($isSelect) {
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode([
                        'success' => true,
                        'type' => 'select',
                        'elapsed_ms' => $elapsed,
                        'count' => count($rows),
                        'columns' => !empty($rows) ? array_keys($rows[0]) : [],
                        'data' => $rows
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'type' => 'write',
                        'elapsed_ms' => $elapsed,
                        'affected_rows' => $stmt->rowCount()
                    ]);
                }
                break;
                
            case 'read_log':
                $logFile = __DIR__ . '/../server.log';
                if (!file_exists($logFile)) {
                    echo json_encode(['success' => true, 'log' => "Arquivo server.log não encontrado na raiz. O log está limpo! 🌟"]);
                    break;
                }
                // Lê as últimas 100 linhas do log com segurança
                $lines = file($logFile);
                $lastLines = array_slice($lines, -100);
                echo json_encode([
                    'success' => true, 
                    'log' => implode("", $lastLines)
                ]);
                break;
                
            case 'list_files':
                // Lista arquivos JSON e HTML do projeto para carregamento fácil
                $files = [];
                $scanDirs = [
                    'raiz' => __DIR__ . '/../',
                    'database' => __DIR__ . '/../database/',
                    'admin' => __DIR__ . '/../admin/',
                ];
                
                foreach ($scanDirs as $key => $dir) {
                    if (is_dir($dir)) {
                        $dh = opendir($dir);
                        while (($file = readdir($dh)) !== false) {
                            if (preg_match('/\.(json|html)$/i', $file)) {
                                $files[] = [
                                    'name' => $file,
                                    'path' => $key . '/' . $file,
                                    'full_path' => realpath($dir . $file),
                                    'size' => filesize($dir . $file)
                                ];
                            }
                        }
                        closedir($dh);
                    }
                }
                echo json_encode(['success' => true, 'files' => $files]);
                break;
                
            case 'read_file':
                $filePath = $_POST['file_path'] ?? '';
                // Previne Directory Traversal
                if (strpos($filePath, '..') !== false) {
                    throw new Exception("Caminho de arquivo inválido.");
                }
                
                $parts = explode('/', $filePath, 2);
                $dirKey = $parts[0] ?? '';
                $fileName = $parts[1] ?? '';
                
                $allowedDirs = [
                    'raiz' => __DIR__ . '/../',
                    'database' => __DIR__ . '/../database/',
                    'admin' => __DIR__ . '/../admin/',
                ];
                
                if (!isset($allowedDirs[$dirKey])) {
                    throw new Exception("Diretório não permitido.");
                }
                
                $fullPath = $allowedDirs[$dirKey] . $fileName;
                if (!file_exists($fullPath)) {
                    throw new Exception("Arquivo não localizado.");
                }
                
                $content = file_get_contents($fullPath);
                echo json_encode([
                    'success' => true,
                    'name' => $fileName,
                    'content' => $content,
                    'extension' => pathinfo($fullPath, PATHINFO_EXTENSION)
                ]);
                break;
                
            default:
                throw new Exception("Ação desconhecida.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 4. Página Principal (Interface Web Premium)
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Workspace & DB Explorer | Antigravity</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700;800&family=Fira+Code:wght@400;500&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Styling: Vanilla CSS Premium (Glassmorphism, Dark UI, Responsive Layout) -->
    <style>
        :root {
            --bg-base: #09090b;
            --bg-surface: #18181b;
            --bg-card: rgba(24, 24, 27, 0.7);
            --border-color: #27272a;
            --border-hover: #3f3f46;
            
            --primary: #2E7EED;      /* Worship Blue */
            --primary-glow: rgba(46, 126, 237, 0.15);
            --accent: #FFC107;       /* Altar Gold */
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
            
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4 {
            font-family: 'Hanken Grotesk', sans-serif;
            font-weight: 700;
        }

        /* Glassmorphism Background Pattern */
        .glass-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(at 0% 0%, rgba(46, 126, 237, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 193, 7, 0.03) 0px, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }

        /* Header Styles */
        header {
            background: rgba(9, 9, 11, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-box {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Hanken Grotesk', sans-serif;
            font-weight: 800;
            color: #000;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(46, 126, 237, 0.3);
        }

        .header-title h1 {
            font-size: 18px;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 11px;
            color: var(--text-muted);
        }

        .env-badge {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Main Layout Grid */
        .workspace {
            display: grid;
            grid-template-columns: 280px 1fr;
            flex-grow: 1;
            height: calc(100vh - 69px);
        }

        /* Sidebar Navigation */
        .sidebar {
            background: rgba(15, 15, 17, 0.9);
            border-right: 1px solid var(--border-color);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            overflow-y: auto;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
            padding-left: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            border: none;
            text-align: left;
            width: 100%;
        }

        .menu-item:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.03);
        }

        .menu-item.active {
            color: var(--primary);
            background: var(--primary-glow);
            box-shadow: inset 0 0 0 1px rgba(46, 126, 237, 0.15);
        }

        /* Main Workspace Content Panels */
        .content {
            padding: 32px;
            overflow-y: auto;
            position: relative;
        }

        .panel {
            display: none;
            flex-direction: column;
            gap: 24px;
            animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .panel.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards and Elements */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(16px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            transition: border-color 0.2s ease;
        }

        .card:hover {
            border-color: var(--border-hover);
        }

        .card-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 18px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Forms, Buttons, Inputs */
        .sql-editor-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: #0c0c0e;
        }

        textarea.sql-input {
            width: 100%;
            height: 180px;
            background: transparent;
            border: none;
            color: #38bdf8;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            padding: 16px;
            resize: vertical;
            outline: none;
            line-height: 1.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(46, 126, 237, 0.4);
        }

        .btn-primary:hover {
            background: #256bce;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--border-hover);
        }

        .shortcut-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .shortcut-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-family: 'Fira Code', monospace;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .shortcut-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-main);
            border-color: var(--border-hover);
        }

        /* Results / Output Console */
        .output-box {
            background: #09090b;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            min-height: 100px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            overflow-x: auto;
            position: relative;
        }

        /* Custom Table Design */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        table.result-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        table.result-table th {
            background: #18181b;
            color: var(--text-main);
            padding: 12px 16px;
            font-weight: 700;
            border-bottom: 1px solid var(--border-color);
        }

        table.result-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            white-space: nowrap;
        }

        table.result-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
            color: var(--text-main);
        }

        /* JSON Tree Styling */
        .json-tree {
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            line-height: 1.6;
        }

        .json-node {
            margin-left: 16px;
        }

        .json-toggle {
            cursor: pointer;
            color: var(--primary);
            font-weight: bold;
            user-select: none;
            display: inline-block;
            transition: transform 0.15s ease;
        }

        .json-toggle.collapsed {
            transform: rotate(-90deg);
        }

        .json-key {
            color: #38bdf8; /* ciano */
        }

        .json-string {
            color: #34d399; /* verde */
        }

        .json-number {
            color: #fbbf24; /* amarelo */
        }

        .json-boolean {
            color: #c084fc; /* roxo */
        }

        .json-null {
            color: #60a5fa; /* azul */
        }

        /* HTML Sandbox Sandbox */
        .sandbox-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            height: 500px;
        }

        .sandbox-editor {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .sandbox-preview {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            height: 100%;
        }

        iframe.preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
        }

        /* File List UI */
        .file-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 350px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .file-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-hover);
        }

        .file-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .file-name {
            font-size: 13px;
            font-weight: 600;
        }

        .file-meta {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* System Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .info-icon {
            background: var(--primary-glow);
            color: var(--primary);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-detail h4 {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-detail p {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 4px;
        }

        /* Preformatted Logs */
        pre.log-content {
            background: #09090b;
            color: #e4e4e7;
            padding: 16px;
            border-radius: 10px;
            font-family: 'Fira Code', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.6;
            border: 1px solid var(--border-color);
        }

        .status-success { color: var(--success); }
        .status-danger { color: var(--danger); }
        .status-warning { color: var(--warning); }
    </style>
</head>
<body>

    <div class="glass-bg"></div>

    <!-- Header -->
    <header>
        <div class="header-logo">
            <div class="logo-box">A</div>
            <div class="header-title">
                <h1>Workspace Dev & MySQL</h1>
                <p>Louvor PIB Oliveira & Antigravity IDE</p>
            </div>
        </div>
        <div>
            <div class="env-badge">
                <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                Ambiente de Desenvolvimento Local
            </div>
        </div>
    </header>

    <!-- Workspace Grid -->
    <div class="workspace">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-title">Ferramentas de Banco</div>
                <button class="menu-item active" onclick="switchPanel('db-panel', this)">
                    <i data-lucide="database" style="width: 18px; height: 18px;"></i>
                    MySQL Console
                </button>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-title">Visualizadores</div>
                <button class="menu-item" onclick="switchPanel('json-panel', this)">
                    <i data-lucide="braces" style="width: 18px; height: 18px;"></i>
                    JSON Explorer
                </button>
                <button class="menu-item" onclick="switchPanel('html-panel', this)">
                    <i data-lucide="code-2" style="width: 18px; height: 18px;"></i>
                    HTML Sandbox
                </button>
            </div>

            <div class="sidebar-menu" style="margin-top: auto;">
                <div class="menu-title">Sistema</div>
                <button class="menu-item" onclick="switchPanel('sys-panel', this)">
                    <i data-lucide="terminal" style="width: 18px; height: 18px;"></i>
                    Logs & Servidor
                </button>
            </div>
        </aside>

        <!-- Main Content Viewport -->
        <main class="content">
            
            <!-- 1. MYSQL PANEL -->
            <section id="db-panel" class="panel active">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="terminal-square" style="color: var(--primary);"></i>
                            Console SQL
                        </h2>
                        <span style="font-size: 12px; color: var(--text-muted); font-family: monospace;">MySQL Server: localhost</span>
                    </div>
                    
                    <div class="sql-editor-container">
                        <textarea class="sql-input" id="sqlEditor" placeholder="Digite sua instrução SQL aqui..."></textarea>
                    </div>
                    
                    <div class="shortcut-grid">
                        <button class="shortcut-btn" onclick="setSql('SELECT * FROM usuarios LIMIT 10')">Listar Usuários</button>
                        <button class="shortcut-btn" onclick="setSql('SELECT * FROM musicas ORDER BY id DESC LIMIT 10')">Repertório Recente</button>
                        <button class="shortcut-btn" onclick="setSql('SELECT * FROM escalas ORDER BY data DESC LIMIT 5')">Próximas Escalas</button>
                        <button class="shortcut-btn" onclick="setSql('SHOW TABLES')">Listar Tabelas</button>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button class="btn btn-primary" id="btnRunSql" onclick="runSql()">
                            <i data-lucide="play" style="width: 16px; height: 16px;"></i>
                            Executar Query
                        </button>
                        <button class="btn btn-secondary" onclick="clearSql()">Limpar</button>
                    </div>
                </div>

                <!-- Query Result Box -->
                <div class="card" id="resultCard" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">Resultados da Consulta</h3>
                        <div style="display: flex; gap: 8px;" id="exportButtons">
                            <!-- Export Button UI populated by JS -->
                        </div>
                    </div>
                    <div class="output-box" id="sqlOutput">
                        <!-- Dynamic output table will render here -->
                    </div>
                </div>
            </section>

            <!-- 2. JSON EXPLORER PANEL -->
            <section id="json-panel" class="panel">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="braces" style="color: var(--primary);"></i>
                            Explorador de Arquivos JSON
                        </h2>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; min-height: 400px;">
                        <div>
                            <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Arquivos JSON no Projeto</h4>
                            <div class="file-list" id="jsonFileList">
                                <!-- Populated dynamically by AJAX -->
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; flex-direction: column; gap: 16px; height: 100%;">
                                <textarea class="sql-input" style="height: 120px; font-size: 13px;" id="jsonTextInput" placeholder="Cole qualquer JSON aqui ou selecione um arquivo ao lado..."></textarea>
                                <div style="display: flex; gap: 12px;">
                                    <button class="btn btn-primary" onclick="exploreCustomJson()">Analisar JSON</button>
                                    <button class="btn btn-secondary" onclick="clearJsonExplorer()">Limpar</button>
                                </div>
                                
                                <div class="output-box" style="flex-grow: 1; min-height: 250px; background: #0c0c0e;" id="jsonTreeOutput">
                                    <span style="color: var(--text-muted); font-style: italic;">Nenhum JSON analisado ainda. Cole um texto ou carregue um arquivo.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. HTML SANDBOX PANEL -->
            <section id="html-panel" class="panel">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="code-2" style="color: var(--primary);"></i>
                            HTML Sandbox & Preview
                        </h2>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; min-height: 500px;">
                        <div>
                            <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Arquivos HTML no Projeto</h4>
                            <div class="file-list" id="htmlFileList">
                                <!-- Populated dynamically by AJAX -->
                            </div>
                        </div>
                        
                        <div class="sandbox-layout">
                            <div class="sandbox-editor">
                                <textarea class="sql-input" style="height: 100%;" id="htmlCodeInput" placeholder="Escreva ou edite o código HTML aqui para ver o preview..."></textarea>
                                <button class="btn btn-primary" style="margin-top: 16px;" onclick="updateHtmlPreview()">
                                    <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                                    Atualizar Preview
                                </button>
                            </div>
                            <div class="sandbox-preview">
                                <iframe id="htmlPreviewIframe" class="preview-iframe" sandbox="allow-scripts"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. SYSTEM PANEL -->
            <section id="sys-panel" class="panel">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="terminal" style="color: var(--primary);"></i>
                            Logs do Servidor PHP (server.log)
                        </h2>
                        <button class="btn btn-secondary" style="padding: 8px 16px;" onclick="loadServerLogs()">
                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                            Atualizar
                        </button>
                    </div>
                    <pre class="log-content" id="logOutput">Carregando logs...</pre>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações de Ambiente</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-icon"><i data-lucide="cpu"></i></div>
                            <div class="info-detail">
                                <h4>PHP Versão</h4>
                                <p><?php echo PHP_VERSION; ?></p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i data-lucide="shield"></i></div>
                            <div class="info-detail">
                                <h4>APP Ambiente</h4>
                                <p><?php echo defined('APP_ENV') ? APP_ENV : 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i data-lucide="database"></i></div>
                            <div class="info-detail">
                                <h4>Banco MySQL</h4>
                                <p><?php echo defined('DB_NAME') ? DB_NAME : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Script: Interatividade Completa (SPA Feel, Dynamic AJAX e JSON Parser) -->
    <script>
        // Inicializa ícones
        lucide.createIcons();

        // Alternância de abas/painéis
        function switchPanel(panelId, btnEl) {
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.querySelectorAll('.menu-item').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(panelId).classList.add('active');
            btnEl.classList.add('active');
            
            // Ações específicas ao abrir a aba
            if (panelId === 'json-panel' || panelId === 'html-panel') {
                loadProjectFiles();
            } else if (panelId === 'sys-panel') {
                loadServerLogs();
            }
        }

        // Funções do SQL Console
        function setSql(sql) {
            document.getElementById('sqlEditor').value = sql;
        }

        function clearSql() {
            document.getElementById('sqlEditor').value = '';
            document.getElementById('resultCard').style.display = 'none';
        }

        let lastSqlResult = null;
        let lastSqlTableName = 'tabela';

        function runSql() {
            const sql = document.getElementById('sqlEditor').value.trim();
            if (!sql) return;
            
            const btn = document.getElementById('btnRunSql');
            btn.disabled = true;
            btn.innerHTML = '<span style="animation: spin 1s linear infinite; display: inline-block;">⚙️</span> Executando...';
            
            // Detecta nome de tabela para possível exportação
            const tableMatch = sql.match(/from\s+([a-zA-Z0-9_`]+)/i);
            lastSqlTableName = tableMatch ? tableMatch[1].replace(/[`]/g, '') : 'export';

            const formData = new FormData();
            formData.append('action', 'run_sql');
            formData.append('sql', sql);

            fetch('dev-dashboard.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="play" style="width: 16px; height: 16px;"></i> Executar Query';
                lucide.createIcons();
                
                const resultCard = document.getElementById('resultCard');
                const output = document.getElementById('sqlOutput');
                const exportDiv = document.getElementById('exportButtons');
                
                resultCard.style.display = 'block';
                exportDiv.innerHTML = '';
                
                if (!res.success) {
                    output.innerHTML = `<span class="status-danger">❌ ERRO: ${res.error}</span>`;
                    return;
                }
                
                lastSqlResult = res.data;

                if (res.type === 'select') {
                    if (res.count === 0) {
                        output.innerHTML = `<span class="status-warning">Nenhum resultado retornado. Tempo: ${res.elapsed_ms}ms.</span>`;
                        return;
                    }
                    
                    // Renderiza Tabela
                    let html = `<div style="margin-bottom: 12px; color: var(--text-muted); font-size: 12px;">Retornado: <b>${res.count}</b> linhas em <b>${res.elapsed_ms}ms</b></div>`;
                    html += '<div class="table-wrapper"><table class="result-table"><thead><tr>';
                    res.columns.forEach(col => {
                        html += `<th>${col}</th>`;
                    });
                    html += '</tr></thead><tbody>';
                    res.data.forEach(row => {
                        html += '<tr>';
                        res.columns.forEach(col => {
                            html += `<td>${escapeHtml(row[col])}</td>`;
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                    output.innerHTML = html;
                    
                    // Botões de Exportação
                    exportDiv.innerHTML = `
                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="exportResults('json')">Exportar JSON</button>
                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="exportResults('csv')">Exportar CSV</button>
                    `;
                } else {
                    output.innerHTML = `<span class="status-success">✅ Comando executado com sucesso! Linhas afetadas: <b>${res.affected_rows}</b>. Tempo: ${res.elapsed_ms}ms.</span>`;
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="play" style="width: 16px; height: 16px;"></i> Executar Query';
                lucide.createIcons();
                document.getElementById('resultCard').style.display = 'block';
                document.getElementById('sqlOutput').innerHTML = `<span class="status-danger">Erro de rede ao executar a query.</span>`;
            });
        }

        // Exportar resultados da query localmente na máquina do desenvolvedor
        function exportResults(format) {
            if (!lastSqlResult) return;
            
            let dataStr = "";
            let mimeType = "";
            let filename = `${lastSqlTableName}_export.${format}`;
            
            if (format === 'json') {
                dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(lastSqlResult, null, 2));
                mimeType = "application/json";
            } else {
                // CSV
                const headers = Object.keys(lastSqlResult[0]);
                let csvContent = headers.join(",") + "\n";
                lastSqlResult.forEach(row => {
                    let rowData = headers.map(header => {
                        let val = row[header] === null ? '' : String(row[header]);
                        // Escapa aspas duplas
                        val = val.replace(/"/g, '""');
                        return `"${val}"`;
                    });
                    csvContent += rowData.join(",") + "\n";
                });
                dataStr = "data:text/csv;charset=utf-8," + encodeURIComponent(csvContent);
                mimeType = "text/csv";
            }
            
            const dlAnchorElem = document.createElement('a');
            dlAnchorElem.setAttribute("href", dataStr);
            dlAnchorElem.setAttribute("download", filename);
            dlAnchorElem.click();
        }

        // Carrega logs de erro
        function loadServerLogs() {
            const logBox = document.getElementById('logOutput');
            logBox.innerText = "Carregando últimas linhas de server.log...";
            
            fetch('dev-dashboard.php?ajax=1&action=read_log')
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    logBox.innerText = res.log;
                } else {
                    logBox.innerText = "Falha ao carregar logs: " + res.error;
                }
            })
            .catch(() => {
                logBox.innerText = "Erro ao buscar logs do servidor.";
            });
        }

        // Carrega arquivos do projeto para o JSON Explorer e HTML Sandbox
        function loadProjectFiles() {
            fetch('dev-dashboard.php?ajax=1&action=list_files')
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const jsonList = document.getElementById('jsonFileList');
                    const htmlList = document.getElementById('htmlFileList');
                    
                    jsonList.innerHTML = '';
                    htmlList.innerHTML = '';
                    
                    const jsonFiles = res.files.filter(f => f.extension === 'json');
                    const htmlFiles = res.files.filter(f => f.extension === 'html');
                    
                    if (jsonFiles.length === 0) {
                        jsonList.innerHTML = '<span style="font-size:12px;color:var(--text-muted);">Nenhum arquivo JSON encontrado.</span>';
                    } else {
                        jsonFiles.forEach(f => {
                            jsonList.innerHTML += `
                                <div class="file-item" onclick="loadFile('${f.path}', 'json')">
                                    <div class="file-info">
                                        <span class="file-name">${f.name}</span>
                                        <span class="file-meta">Tamanho: ${formatBytes(f.size)}</span>
                                    </div>
                                    <i data-lucide="chevron-right" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                                </div>
                            `;
                        });
                    }
                    
                    if (htmlFiles.length === 0) {
                        htmlList.innerHTML = '<span style="font-size:12px;color:var(--text-muted);">Nenhum arquivo HTML encontrado.</span>';
                    } else {
                        htmlFiles.forEach(f => {
                            htmlList.innerHTML += `
                                <div class="file-item" onclick="loadFile('${f.path}', 'html')">
                                    <div class="file-info">
                                        <span class="file-name">${f.name}</span>
                                        <span class="file-meta">Tamanho: ${formatBytes(f.size)}</span>
                                    </div>
                                    <i data-lucide="chevron-right" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                                </div>
                            `;
                        });
                    }
                    lucide.createIcons();
                }
            });
        }

        // Carrega dados do arquivo no editor
        function loadFile(filePath, type) {
            const formData = new FormData();
            formData.append('action', 'read_file');
            formData.append('file_path', filePath);

            fetch('dev-dashboard.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (type === 'json') {
                        document.getElementById('jsonTextInput').value = res.content;
                        exploreCustomJson();
                    } else {
                        document.getElementById('htmlCodeInput').value = res.content;
                        updateHtmlPreview();
                    }
                } else {
                    alert("Erro ao ler arquivo: " + res.error);
                }
            });
        }

        // JSON Explorer - Gera a árvore recursiva e colapsável de JSON
        function exploreCustomJson() {
            const input = document.getElementById('jsonTextInput').value.trim();
            const output = document.getElementById('jsonTreeOutput');
            
            if (!input) {
                output.innerHTML = '<span style="color:var(--text-muted);">Por favor, insira um JSON para analisar.</span>';
                return;
            }
            
            try {
                const parsed = JSON.parse(input);
                output.innerHTML = '';
                
                const treeContainer = document.createElement('div');
                treeContainer.className = 'json-tree';
                treeContainer.appendChild(renderJsonNode(null, parsed, true));
                
                output.appendChild(treeContainer);
            } catch (err) {
                output.innerHTML = `<span class="status-danger">❌ JSON Inválido: ${err.message}</span>`;
            }
        }

        function clearJsonExplorer() {
            document.getElementById('jsonTextInput').value = '';
            document.getElementById('jsonTreeOutput').innerHTML = '<span style="color:var(--text-muted); font-style: italic;">Nenhum JSON analisado ainda.</span>';
        }

        // Função recursiva de renderização de árvore JSON
        function renderJsonNode(key, value, isLast = true) {
            const container = document.createElement('div');
            container.className = 'json-node';
            
            const keySpan = document.createElement('span');
            keySpan.className = 'json-key';
            if (key !== null) {
                keySpan.innerHTML = `"${key}": `;
            }
            
            if (value === null) {
                const nullSpan = document.createElement('span');
                nullSpan.className = 'json-null';
                nullSpan.innerText = 'null' + (isLast ? '' : ',');
                container.appendChild(keySpan);
                container.appendChild(nullSpan);
            } else if (typeof value === 'object') {
                const isArray = Array.isArray(value);
                const openBracket = isArray ? '[' : '{';
                const closeBracket = isArray ? ']' : '}';
                
                const toggle = document.createElement('span');
                toggle.className = 'json-toggle';
                toggle.innerText = '▼';
                
                const bracketSpan = document.createElement('span');
                bracketSpan.innerText = openBracket;
                
                const header = document.createElement('div');
                header.style.display = 'inline-block';
                if (key !== null) {
                    header.appendChild(keySpan);
                }
                header.appendChild(toggle);
                header.appendChild(bracketSpan);
                container.appendChild(header);
                
                const childrenContainer = document.createElement('div');
                childrenContainer.style.paddingLeft = '16px';
                
                const keys = Object.keys(value);
                keys.forEach((k, idx) => {
                    childrenContainer.appendChild(renderJsonNode(isArray ? null : k, value[k], idx === keys.length - 1));
                });
                
                container.appendChild(childrenContainer);
                
                const footer = document.createElement('div');
                footer.innerText = closeBracket + (isLast ? '' : ',');
                container.appendChild(footer);
                
                // Toggle expand/collapse logic
                toggle.onclick = function() {
                    if (childrenContainer.style.display === 'none') {
                        childrenContainer.style.display = 'block';
                        toggle.innerText = '▼';
                        toggle.classList.remove('collapsed');
                    } else {
                        childrenContainer.style.display = 'none';
                        toggle.innerText = '▶';
                        toggle.classList.add('collapsed');
                    }
                };
            } else if (typeof value === 'string') {
                const strSpan = document.createElement('span');
                strSpan.className = 'json-string';
                strSpan.innerText = `"${value}"` + (isLast ? '' : ',');
                container.appendChild(keySpan);
                container.appendChild(strSpan);
            } else if (typeof value === 'number') {
                const numSpan = document.createElement('span');
                numSpan.className = 'json-number';
                numSpan.innerText = value + (isLast ? '' : ',');
                container.appendChild(keySpan);
                container.appendChild(numSpan);
            } else if (typeof value === 'boolean') {
                const boolSpan = document.createElement('span');
                boolSpan.className = 'json-boolean';
                boolSpan.innerText = (value ? 'true' : 'false') + (isLast ? '' : ',');
                container.appendChild(keySpan);
                container.appendChild(boolSpan);
            }
            
            return container;
        }

        // HTML Sandbox - Atualiza o iframe com o código do editor
        function updateHtmlPreview() {
            const code = document.getElementById('htmlCodeInput').value;
            const iframe = document.getElementById('htmlPreviewIframe');
            
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(code);
            iframeDoc.close();
        }

        // Utilitários de escape e formatação
        function escapeHtml(text) {
            if (text === null || text === undefined) return '<span style="color:var(--text-muted);font-style:italic">NULL</span>';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
