<?php
// deploy.php - Custom Deployment Script (Legacy)
// Access via: https://vilela.eng.br/applouvor/deploy.php?secret=louvor2026

echo "🚀 O deploy agora é automático via GitHub Actions!\n";
echo "-------------------------------------------------\n";
echo "Motivo: O servidor bloqueia a função exec(), impedindo comandos git.\n";
echo "Solução: O código é enviado via FTP automaticamente ao fazer push no branch main.\n";
echo "Verifique a aba 'Actions' no repositório do GitHub para ver o status do último deploy.\n";
?>
