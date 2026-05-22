<?php
// admin/index.php
// Redireciona o usuário autenticado diretamente para o novo Dashboard Premium em React
require_once '../src/helpers/auth.php';
checkLogin();

header("Location: ../dashboard/");
exit;
