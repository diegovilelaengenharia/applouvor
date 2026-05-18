<?php
// admin/db_test.php — DIAGNÓSTICO TEMPORÁRIO (REMOVER APÓS USO)
// Acesso: /admin/db_test.php?token=louvor2026

if (($_GET['token'] ?? '') !== 'louvor2026') {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>DB Test</title>';
echo '<style>body{font-family:monospace;padding:20px;max-width:900px;margin:auto;background:#0f172a;color:#e2e8f0}';
echo 'h2{color:#3b82f6;border-bottom:1px solid #334155;padding-bottom:8px}';
echo '.ok{color:#10b981} .err{color:#ef4444} .warn{color:#f59e0b}';
echo 'table{border-collapse:collapse;width:100%;margin:10px 0}';
echo 'td,th{padding:6px 12px;border:1px solid #334155;text-align:left}';
echo 'th{background:#1e293b}</style></head><body>';
echo '<h1>🩺 Diagnóstico App Louvor</h1>';

// === 1. Ambiente ===
echo '<h2>1. Detecção de Ambiente</h2>';
$envPath = __DIR__ . '/../.env';
$envExists = file_exists($envPath);
echo '<table>';
echo '<tr><th>Item</th><th>Valor</th></tr>';
echo '<tr><td>Working dir</td><td>' . __DIR__ . '</td></tr>';
echo '<tr><td>.env existe?</td><td>' . ($envExists ? '<span class="warn">SIM (local)</span>' : '<span class="ok">NÃO (produção)</span>') . '</td></tr>';
echo '<tr><td>PHP version</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>Server software</td><td>' . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '-') . '</td></tr>';
echo '</table>';

// === 2. .htaccess SetEnv ===
echo '<h2>2. Variáveis de Ambiente (.htaccess SetEnv)</h2>';
$vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'VAPID_PUBLIC_KEY', 'VAPID_PRIVATE_KEY'];
echo '<table>';
echo '<tr><th>Variável</th><th>getenv()</th><th>$_SERVER</th></tr>';
foreach ($vars as $v) {
    $g = getenv($v);
    $s = $_SERVER[$v] ?? null;
    $gDisp = $g === false ? '<span class="err">false (não setada)</span>' : ($v === 'DB_PASS' || strpos($v, 'PRIVATE') !== false ? '<span class="ok">' . strlen($g) . ' chars</span>' : '<span class="ok">' . htmlspecialchars($g) . '</span>');
    $sDisp = $s === null ? '<span class="err">null</span>' : ($v === 'DB_PASS' || strpos($v, 'PRIVATE') !== false ? '<span class="ok">' . strlen($s) . ' chars</span>' : '<span class="ok">' . htmlspecialchars($s) . '</span>');
    echo "<tr><td>$v</td><td>$gDisp</td><td>$sDisp</td></tr>";
}
echo '</table>';

// === 3. Tenta carregar config.php ===
echo '<h2>3. config.php</h2>';
try {
    require_once __DIR__ . '/../includes/config.php';
    echo '<p class="ok">✓ config.php carregado</p>';
    echo '<table>';
    echo '<tr><td>DB_HOST</td><td>' . htmlspecialchars(DB_HOST) . '</td></tr>';
    echo '<tr><td>DB_NAME</td><td>' . htmlspecialchars(DB_NAME) . '</td></tr>';
    echo '<tr><td>DB_USER</td><td>' . htmlspecialchars(DB_USER) . '</td></tr>';
    echo '<tr><td>DB_PASS</td><td>' . (empty(DB_PASS) ? '<span class="err">VAZIO ❌</span>' : '<span class="ok">' . strlen(DB_PASS) . ' chars ✓</span>') . '</td></tr>';
    echo '<tr><td>APP_ENV</td><td>' . (defined('APP_ENV') ? APP_ENV : '-') . '</td></tr>';
    echo '<tr><td>APP_VERSION</td><td>' . APP_VERSION . '</td></tr>';
    echo '</table>';
} catch (Throwable $e) {
    echo '<p class="err">✗ Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// === 4. Conexão PDO ===
echo '<h2>4. Conexão MySQL (PDO)</h2>';
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '<p class="ok">✓ Conectou com sucesso</p>';

    // Test query
    $stmt = $pdo->query("SELECT VERSION() as v");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo '<p>MySQL version: <span class="ok">' . htmlspecialchars($row['v']) . '</span></p>';

    // Tabelas principais
    echo '<h3>Tabelas detectadas</h3><ul>';
    foreach (['users', 'schedules', 'schedule_users', 'songs', 'avisos', 'prayer_requests', 'song_suggestions', 'schedule_roteiro'] as $t) {
        try {
            $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "<li class='ok'>✓ <code>$t</code> — $c registros</li>";
        } catch (Exception $e) {
            echo "<li class='err'>✗ <code>$t</code> — " . htmlspecialchars($e->getMessage()) . '</li>';
        }
    }
    echo '</ul>';

    // Verifica migration 004
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM schedule_users LIKE 'absence_note'");
        $hasMigration004 = (bool)$stmt->fetch();
        echo '<p>Migration 004 (absence_note): ' . ($hasMigration004 ? '<span class="ok">✓ aplicada</span>' : '<span class="warn">⚠ NÃO aplicada — rodar database/migrations/004_schedule_users_absences.sql</span>') . '</p>';
    } catch (Exception $e) {}

} catch (PDOException $e) {
    echo '<p class="err">✗ Falha: <code>' . htmlspecialchars($e->getMessage()) . '</code></p>';
    echo '<p>Código: <code>' . $e->getCode() . '</code></p>';
    echo '<div style="background:#1e293b;padding:14px;border-radius:8px;border-left:3px solid #ef4444">';
    if ($e->getCode() == 1045) echo '<strong>Diagnóstico:</strong> Senha errada (DB_PASS no .htaccess). Resetar no painel Hostinger e atualizar .htaccess.';
    elseif ($e->getCode() == 2002) echo '<strong>Diagnóstico:</strong> MySQL inacessível (DB_HOST errado ou serviço fora).';
    elseif ($e->getCode() == 1044) echo '<strong>Diagnóstico:</strong> Usuário sem permissão neste DB.';
    elseif (strpos($e->getMessage(), 'Unknown database') !== false) echo '<strong>Diagnóstico:</strong> Database não existe (DB_NAME errado).';
    else echo '<strong>Diagnóstico:</strong> Erro genérico — investigar mensagem acima.';
    echo '</div>';
}

// === 5. Migration runner ===
if (isset($pdo)) {
    echo '<h2>5. Migrations</h2>';
    $migrationsDir = realpath(__DIR__ . '/../database/migrations');
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    $runMode = ($_GET['migrate'] ?? '') === '1';

    echo '<table><tr><th>Arquivo</th><th>Status</th>' . ($runMode ? '<th>Execução</th>' : '') . '</tr>';
    foreach ($files as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        $applied = false;
        // heurística: tenta detectar se já foi aplicada por meio de CREATE TABLE IF NOT EXISTS
        if (preg_match('/CREATE TABLE\s+(IF NOT EXISTS\s+)?`?(\w+)`?/i', $sql, $m)) {
            $table = $m[2];
            try {
                $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
                $applied = true;
            } catch (Exception $e) {}
        } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?[\s\S]*ADD COLUMN\s+`?(\w+)`?/i', $sql, $m)) {
            $table = $m[1]; $col = $m[2];
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                $applied = (bool)$stmt->fetch();
            } catch (Exception $e) {}
        }

        echo "<tr><td><code>$name</code></td>";
        echo '<td>' . ($applied ? '<span class="ok">✓ aplicada</span>' : '<span class="warn">⚠ pendente</span>') . '</td>';

        if ($runMode) {
            if ($applied) {
                echo '<td><span class="ok">skip</span></td>';
            } else {
                try {
                    $pdo->exec($sql);
                    echo '<td class="ok">✓ executada</td>';
                } catch (Exception $e) {
                    echo '<td class="err">✗ ' . htmlspecialchars($e->getMessage()) . '</td>';
                }
            }
        }
        echo '</tr>';
    }
    echo '</table>';

    if (!$runMode) {
        echo '<p><a href="?token=' . htmlspecialchars($_GET['token']) . '&migrate=1" style="display:inline-block;padding:10px 18px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">▶ Rodar migrations pendentes</a></p>';
    }
}

echo '<hr style="margin-top:40px"><p style="opacity:.6;font-size:.8rem">⚠ Esta página é temporária. Apagar após diagnóstico via <code>git rm admin/db_test.php</code></p>';
echo '</body></html>';
