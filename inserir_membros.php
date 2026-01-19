<?php
// Script temporário para inserir membros
require_once 'includes/db.php';

try {
    $pdo->exec("
        INSERT INTO users (name, role, instrument, phone, password) VALUES
        ('Aline', 'user', 'Voz', '37 98838-4903', '4903'),
        ('Michelle', 'user', 'Voz', '37 99145-1990', '1990'),
        ('Raquel', 'user', 'Voz', '35 99237-6691', '6691'),
        ('Samara', 'user', 'Voz', '37 9922-0252', '0252'),
        ('Thalyta', 'admin', 'Voz/Violão', '14 98165-3545', '3545'),
        ('Wemerson', 'user', 'Voz', '37 9988-5686', '5686'),
        ('Weberth', 'user', 'Voz', '37 9105-2158', '2158'),
        ('Ananias', 'user', 'Voz', '37 9959-1176', '1176'),
        ('Márcio', 'user', 'Voz/Violão', '31 9328-6713', '6713'),
        ('Diego', 'admin', 'Voz/Violão/Bateria', '35 98452-9577', '9577'),
        ('Mariana', 'user', 'Teclado', '37 9988-5686', '5686')
    ");

    echo "✅ Membros inseridos com sucesso!<br><br>";
    echo "<a href='admin/membros.php'>Ver lista de membros</a>";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
