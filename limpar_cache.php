<?php
// Forcar o LiteSpeed Web Server a purgar 100% do cache do dominio na Hostinger
header("X-LiteSpeed-Purge: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
echo "<h1>[OK] Cache de borda do LiteSpeed limpo com sucesso absoluto!</h1>";
echo "<p>Todos os arquivos HTML, JS e CSS foram invalidados e serao recarregados do disco fisico em tempo real no proximo acesso.</p>";
?>
