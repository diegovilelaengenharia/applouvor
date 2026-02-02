<?php
require_once 'includes/db.php';

echo "<h2>Diagnóstico de Contagem de Membros</h2>";

// 1. Total de usuários
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
echo "<p><strong>Total de usuários:</strong> " . $stmt->fetchColumn() . "</p>";

// 2. Usuários ativos
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
echo "<p><strong>Usuários ativos (status='active'):</strong> " . $stmt->fetchColumn() . "</p>";

// 3. Usuários com qualquer status
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM users GROUP BY status");
echo "<p><strong>Usuários por status:</strong></p><ul>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>" . ($row['status'] ?: 'NULL') . ": " . $row['total'] . "</li>";
}
echo "</ul>";

// 4. Usuários com instrument preenchido
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE instrument IS NOT NULL AND instrument != ''");
echo "<p><strong>Usuários com coluna 'instrument' preenchida:</strong> " . $stmt->fetchColumn() . "</p>";

// 5. Sample de instruments
$stmt = $pdo->query("SELECT id, name, instrument FROM users WHERE instrument IS NOT NULL AND instrument != '' LIMIT 10");
echo "<p><strong>Exemplo de instrumentos (primeiros 10):</strong></p><ul>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>ID: {$row['id']}, Nome: {$row['name']}, Instrumento: {$row['instrument']}</li>";
}
echo "</ul>";

// 6. Usuários com roles
$stmt = $pdo->query("SELECT COUNT(DISTINCT ur.user_id) FROM user_roles ur");
echo "<p><strong>Usuários com roles na user_roles:</strong> " . $stmt->fetchColumn() . "</p>";

// 7. Roles disponíveis
$stmt = $pdo->query("SELECT r.id, r.name, COUNT(ur.user_id) as total FROM roles r LEFT JOIN user_roles ur ON r.id = ur.role_id GROUP BY r.id, r.name ORDER BY total DESC");
echo "<p><strong>Roles disponíveis e quantidade de usuários:</strong></p><ul>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>{$row['name']}: {$row['total']} usuários</li>";
}
echo "</ul>";

// 8. Testar query de vocais (user_roles)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    WHERE EXISTS (
        SELECT 1 FROM user_roles ur
        INNER JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = u.id
        AND (r.name LIKE '%Vocal%' OR r.name LIKE '%Ministro%' OR r.name LIKE '%Voz%')
    )
");
echo "<p><strong>Vocais (via user_roles):</strong> " . $stmt->fetchColumn() . "</p>";

// 9. Testar query de vocais (instrument)
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users u
    WHERE u.instrument IS NOT NULL 
    AND u.instrument != ''
    AND (u.instrument LIKE '%Voz%' OR u.instrument LIKE '%Vocal%' OR u.instrument LIKE '%Ministro%')
");
echo "<p><strong>Vocais (via coluna instrument):</strong> " . $stmt->fetchColumn() . "</p>";

// 10. Testar query de instrumentistas (user_roles)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    WHERE EXISTS (
        SELECT 1 FROM user_roles ur
        INNER JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = u.id
        AND r.name NOT LIKE '%Vocal%' 
        AND r.name NOT LIKE '%Ministro%' 
        AND r.name NOT LIKE '%Voz%'
    )
");
echo "<p><strong>Instrumentistas (via user_roles):</strong> " . $stmt->fetchColumn() . "</p>";

// 11. Testar query de instrumentistas (instrument)
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM users u
    WHERE u.instrument IS NOT NULL 
    AND u.instrument != ''
    AND u.instrument NOT LIKE '%Voz%' 
    AND u.instrument NOT LIKE '%Vocal%' 
    AND u.instrument NOT LIKE '%Ministro%'
");
echo "<p><strong>Instrumentistas (via coluna instrument):</strong> " . $stmt->fetchColumn() . "</p>";

// 12. Query COMPLETA de vocais (como está no index.php)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    WHERE u.status = 'active' 
    AND (
        EXISTS (
            SELECT 1 FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = u.id
            AND (r.name LIKE '%Vocal%' OR r.name LIKE '%Ministro%' OR r.name LIKE '%Voz%')
        )
        OR (
            u.instrument IS NOT NULL 
            AND u.instrument != ''
            AND (u.instrument LIKE '%Voz%' OR u.instrument LIKE '%Vocal%' OR u.instrument LIKE '%Ministro%')
        )
    )
");
echo "<p><strong>VOCAIS TOTAL (query completa com status='active'):</strong> " . $stmt->fetchColumn() . "</p>";

// 13. Query COMPLETA de instrumentistas (como está no index.php)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT u.id) 
    FROM users u
    WHERE u.status = 'active' 
    AND (
        EXISTS (
            SELECT 1 FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = u.id
            AND r.name NOT LIKE '%Vocal%' 
            AND r.name NOT LIKE '%Ministro%' 
            AND r.name NOT LIKE '%Voz%'
        )
        OR (
            u.instrument IS NOT NULL 
            AND u.instrument != ''
            AND u.instrument NOT LIKE '%Voz%' 
            AND u.instrument NOT LIKE '%Vocal%' 
            AND u.instrument NOT LIKE '%Ministro%'
        )
    )
");
echo "<p><strong>INSTRUMENTISTAS TOTAL (query completa com status='active'):</strong> " . $stmt->fetchColumn() . "</p>";
