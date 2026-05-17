// setup_prayers.php - Setup protegido
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/layout.php';

// Proteção: Apenas Admin
checkAdmin();

// Verificar se tabela já existe
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM prayer_requests LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}

if ($tableExists) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <div style='font-size:3rem; margin-bottom:10px;'>✅</div>
            <h2>Setup Já Realizado</h2>
            <p>As tabelas de oração já existem no banco de dados.</p>
            <a href='admin/oracao.php' style='background:#f59e0b; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Voltar para Mural</a>
          </div>";
    exit;
}

echo "<h2>Setup das Tabelas de Oração</h2>";

try {
    // Tabela de pedidos de oração
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category ENUM('health', 'family', 'work', 'spiritual', 'gratitude', 'other') DEFAULT 'other',
            is_urgent BOOLEAN DEFAULT FALSE,
            is_anonymous BOOLEAN DEFAULT FALSE,
            is_answered BOOLEAN DEFAULT FALSE,
            prayer_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            answered_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>prayer_requests</b> criada/verificada.</p>";

    // Tabela de quem orou
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prayer_id INT NOT NULL,
            user_id INT NOT NULL,
            type ENUM('pray', 'comment') DEFAULT 'pray',
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (prayer_id) REFERENCES prayer_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>prayer_interactions</b> criada/verificada.</p>";

    echo "<br><p style='color:green; font-weight:bold;'>🎉 Setup concluído com sucesso!</p>";
    echo "<p><a href='admin/oracao.php'>Ir para Mural de Oração →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
