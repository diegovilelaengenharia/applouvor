<?php
// admin/artista_detalhe.php - Redirecionamento de Compatibilidade
require_once '../src/helpers/auth.php';
checkLogin();

if (!isset($_GET['name'])) {
    header('Location: repertorio.php?tab=artistas');
    exit;
}

$artistName = urldecode($_GET['name']);
header("Location: artista_perfil.php?artist=" . urlencode($artistName));
exit;