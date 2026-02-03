<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Listar todos os instrumentos distintos para análise
$stmt = $pdo->query("SELECT DISTINCT instrument FROM users ORDER BY instrument");
$instruments = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Instrumentos Cadastrados (Distinct):</h3>";
echo "<ul>";
foreach ($instruments as $inst) {
    echo "<li>" . htmlspecialchars($inst) . "</li>";
}
echo "</ul>";

// Testar a query de filtro atual
$filterCurrent = "
    (instrument LIKE '%Vocal%' OR instrument LIKE '%Voz%' OR instrument LIKE '%Ministro%' OR instrument LIKE '%Cantor%' OR instrument LIKE '%Backing%')
";

$stmt = $pdo->query("SELECT name, instrument FROM users WHERE $filterCurrent ORDER BY name");
$vocais = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Usuários Considerados 'Vocais' (Query Atual):</h3>";
echo "Count: " . count($vocais) . "<br><ul>";
foreach ($vocais as $v) {
    echo "<li><b>" . htmlspecialchars($v['name']) . "</b>: " . htmlspecialchars($v['instrument']) . "</li>";
}
echo "</ul>";

// Listar quem NÃO está sendo pego
$stmt = $pdo->query("SELECT name, instrument FROM users WHERE NOT ($filterCurrent) ORDER BY name");
$naoVocais = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Usuários IGNORADOS (Instrumentistas/Outros):</h3>";
echo "<ul>";
foreach ($naoVocais as $v) {
    echo "<li><b>" . htmlspecialchars($v['name']) . "</b>: " . htmlspecialchars($v['instrument']) . "</li>";
}
echo "</ul>";
?>
