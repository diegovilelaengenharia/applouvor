<?php
// debug_ftp.php - Test FTP Connection from Local Environment
// Run this locally to verify if the credentials and path are correct.

$ftp_server = "ftp.vilela.eng.br";
$ftp_user = "u884436813";
// $ftp_pass = "YOUR_PASSWORD"; // Do NOT hardcode password here. Pass it via env var or prompt.

echo "Teste de Conexão FTP\n";
echo "----------------------\n";

if (!function_exists('ftp_connect')) {
    die("❌ Erro: Extensão FTP do PHP não está habilitada.\n");
}

// Prompt for password securely
echo "Digite a senha do FTP: ";
$handle = fopen("php://stdin", "r");
$ftp_pass = trim(fgets($handle));

echo "\nTentando conectar a $ftp_server...\n";
$conn_id = ftp_connect($ftp_server);

if (!$conn_id) {
    die("❌ Falha ao conectar ao servidor FTP.\n");
}

echo "✅ Conectado ao servidor.\n";

echo "Tentando login como $ftp_user...\n";
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "✅ Login realizado com sucesso!\n";
    
    // Listar diretórios para confirmar o caminho
    echo "Listando arquivos no diretório raiz:\n";
    $files = ftp_nlist($conn_id, ".");
    print_r($files);

    echo "\nTentando acessar 'public_html/applouvor/'...\n";
    if (ftp_chdir($conn_id, "public_html/applouvor")) {
        echo "✅ Diretório 'public_html/applouvor' encontrado e acessível.\n";
        echo "Conteúdo de 'public_html/applouvor':\n";
        $files = ftp_nlist($conn_id, ".");
        print_r($files);
    } else {
        echo "❌ Falha ao acessar 'public_html/applouvor'. Verifique o caminho.\n";
        echo "Tentando listar 'public_html':\n";
        if (ftp_chdir($conn_id, "public_html")) {
             print_r(ftp_nlist($conn_id, "."));
        } else {
             echo "❌ Diretório 'public_html' também não encontrado.\n";
        }
    }

} else {
    echo "❌ Falha no login. Verifique usuário e senha.\n";
}

ftp_close($conn_id);
?>
