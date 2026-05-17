// setup_reading_db.php - Setup protegido
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/layout.php';

// Proteção: Apenas Admin
checkAdmin();

// Verificar se tabela já existe
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM reading_progress LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}

if ($tableExists) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <div style='font-size:3rem; margin-bottom:10px;'>✅</div>
            <h2>Setup Já Realizado</h2>
            <p>As tabelas de leitura bíblica já existem no banco de dados.</p>
            <a href='admin/leitura.php' style='background:#3b82f6; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Voltar para Leitura</a>
          </div>";
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            month_num INT NOT NULL,
            day_num INT NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            comment TEXT,
            UNIQUE KEY unique_user_reading (user_id, month_num, day_num),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela 'reading_progress' criada ou já existente.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_settings (
            user_id INT NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, setting_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela 'user_settings' criada ou já existente.<br>";

    // Add notification_time column if not using user_settings table? 
    // The user_settings table is more flexible.

    echo "Configuração de Banco de Dados concluída com sucesso.";

} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
?>
