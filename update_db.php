<?php
require_once 'includes/db.php';

try {
    echo "<h1>Atualizando Banco de Dados...</h1>";

    // 1. Adicionar coluna address_street
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_street VARCHAR(150) AFTER phone");
        echo "<p style='color: green'>✅ Coluna 'address_street' adicionada.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange'>⚠️ Coluna 'address_street' já existe ou erro: " . $e->getMessage() . "</p>";
    }

    // 2. Adicionar coluna address_number
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_number VARCHAR(20) AFTER address_street");
        echo "<p style='color: green'>✅ Coluna 'address_number' adicionada.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange'>⚠️ Coluna 'address_number' já existe ou erro: " . $e->getMessage() . "</p>";
    }

    // 3. Adicionar coluna address_neighborhood
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_neighborhood VARCHAR(100) AFTER address_number");
        echo "<p style='color: green'>✅ Coluna 'address_neighborhood' adicionada.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange'>⚠️ Coluna 'address_neighborhood' já existe ou erro: " . $e->getMessage() . "</p>";
    }

    // 4. Adicionar coluna avatar
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) AFTER address_neighborhood");
        echo "<p style='color: green'>✅ Coluna 'avatar' adicionada.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange'>⚠️ Coluna 'avatar' já existe ou erro: " . $e->getMessage() . "</p>";
    }

    // 5. Configurar Permissões (Thalyta Admin)
    try {
        $pdo->exec("UPDATE users SET role = 'admin' WHERE name = 'Thalyta'");
        echo "<p style='color: green'>✅ Permissão de Admin concedida para Thalyta.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red'>❌ Erro ao atualizar permissão: " . $e->getMessage() . "</p>";
    }

    // Criar pasta de uploads se não existir
    $upload_dir = 'assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p style='color: green'>✅ Pasta 'assets/uploads' criada.</p>";
    }

    echo "<h3>Atualização Concluída! Você já pode usar as configurações.</h3>";
    echo "<p>Por segurança, peça para remover este arquivo do repositório após o uso.</p>";
    echo "<a href='index.php'>Voltar para o Início</a>";
} catch (PDOException $e) {
    echo "<h2 style='color: red'>Erro Fatal: " . $e->getMessage() . "</h2>";
}
