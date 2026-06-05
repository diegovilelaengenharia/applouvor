<?php
// TEMP - remover apos debug
$f = __DIR__.'/src/config/db_credentials.php';
echo 'creds_file: '.(file_exists($f)?'SIM':'NAO')."\n";
if(file_exists($f)){
    $c=require $f;
    echo 'HOST: '.($c['DB_HOST']??'vazio')."\n";
    echo 'NAME: '.($c['DB_NAME']??'vazio')."\n";
    echo 'USER: '.($c['DB_USER']??'vazio')."\n";
    echo 'PASS: '.(!empty($c['DB_PASS'])?'definida':'VAZIA')."\n";
    try{
        new PDO("mysql:host={$c['DB_HOST']};dbname={$c['DB_NAME']};charset=utf8mb4",$c['DB_USER'],$c['DB_PASS'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        echo 'CONEXAO: OK';
    }catch(PDOException $e){
        echo 'ERRO: '.$e->getCode().' — '.$e->getMessage();
    }
}
