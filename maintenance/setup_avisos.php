// setup_avisos.php - Setup protegido
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/layout.php';

// Proteção: Apenas Admin
checkAdmin();

// Verificar se tabela já existe
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM avisos LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}

if ($tableExists) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <div style='font-size:3rem; margin-bottom:10px;'>✅</div>
            <h2>Setup Já Realizado</h2>
            <p>A tabela de avisos já existe no banco de dados.</p>
            <a href='admin/avisos.php' style='background:#f97316; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Voltar para Avisos</a>
          </div>";
    exit;
}

try {
    $sql = file_get_contents('migrations/002_create_avisos_table.sql');
    $pdo->exec($sql);
    echo "Tabela 'avisos' criada com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
