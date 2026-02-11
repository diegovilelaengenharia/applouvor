<?php
$ftp_server = "147.93.64.217";
$ftp_user = "u884436813";
$ftp_pass = "7L#t4iHw=v2s=vky";

echo "Conectando a $ftp_server...\n";
$conn = ftp_connect($ftp_server);

if ($conn && ftp_login($conn, $ftp_user, $ftp_pass)) {
    echo "✅ Login OK!\n";
    
    // Listar raiz
    echo "Raiz:\n";
    $files = ftp_nlist($conn, ".");
    print_r($files);
    
    // Tentar entrar na pasta do app
    $target = "public_html/applouvor";
    if (ftp_chdir($conn, $target)) {
        echo "\n✅ Pasta '$target' encontrada!\n";
        echo "Conteúdo:\n";
        print_r(ftp_nlist($conn, "."));
    } else {
        echo "\n❌ Pasta '$target' NÃO encontrada.\n";
        // Tenta listar public_html para ver o que tem dentro
        if (ftp_chdir($conn, "public_html")) {
             echo "Conteúdo de public_html:\n";
             print_r(ftp_nlist($conn, "."));
        }
    }
    ftp_close($conn);
} else {
    echo "❌ Falha no login.\n";
}
?>
