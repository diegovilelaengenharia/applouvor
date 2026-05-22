<?php
require_once __DIR__ . '/../../src/config/db.php';
$newPass = password_hash('9577', PASSWORD_DEFAULT);
App\DB::table('users')->where('name', '=', 'Diego')->update(['password' => $newPass]);
echo "Senha de Diego resetada para 9577";
?>
