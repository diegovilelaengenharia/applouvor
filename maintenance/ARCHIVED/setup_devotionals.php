// setup_devotionals.php - Setup protegido
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/layout.php';

// Proteção: Apenas Admin
checkAdmin();

// Verificar se tabela já existe
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM devotionals LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}

if ($tableExists) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <div style='font-size:3rem; margin-bottom:10px;'>✅</div>
            <h2>Setup Já Realizado</h2>
            <p>As tabelas de devocionais já existem no banco de dados.</p>
            <a href='admin/devocionais.php' style='background:#f97316; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Voltar para Devocionais</a>
          </div>";
    exit;
}

echo "<h2>Setup das Tabelas de Devocionais</h2>";

try {
    // Tabela principal de devocionais
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotionals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            media_type ENUM('text', 'video', 'audio', 'link') DEFAULT 'text',
            media_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>devotionals</b> criada/verificada.</p>";

    // Relacionamento Devocional-Tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_tags (
            devotional_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (devotional_id, tag_id),
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>devotional_tags</b> criada/verificada.</p>";

    // Comentários
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            devotional_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>devotional_comments</b> criada/verificada.</p>";

    echo "<br><p style='color:green; font-weight:bold;'>🎉 Setup concluído com sucesso!</p>";
    echo "<p><a href='admin/devocionais.php'>Ir para Devocionais →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
