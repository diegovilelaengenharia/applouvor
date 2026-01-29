<?php
// Script para gerar chaves VAPID
$config = [
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_EC,
    "curve_name" => "prime256v1"
];

$res = openssl_pkey_new($config);

if (!$res) {
    echo "Erro ao gerar chaves: " . openssl_error_string();
    exit(1);
}

// Extrair chave privada
openssl_pkey_export($res, $privKey);

// Extrair chave pública
$keyDetails = openssl_pkey_get_details($res);
$pubKey = $keyDetails['key'];

// Converter para formato cru (Raw P-256)
// A chave pública do OpenSSL vem em formato PEM, precisamos converter para Raw URL Safe Base64
// Isso é complexo em PHP puro sem libs, mas para Web Push precisamos do formato correto.
// O formato PEM tem cabeçalhos e quebras de linha.

function pemToRaw($pem) {
    $pem = str_replace('-----BEGIN PUBLIC KEY-----', '', $pem);
    $pem = str_replace('-----END PUBLIC KEY-----', '', $pem);
    $pem = str_replace("\n", '', $pem);
    $pem = str_replace("\r", '', $pem);
    
    $der = base64_decode($pem);
    
    // O formato DER contém metadados ASN.1. Para EC P-256, os últimos 65 bytes são a chave não comprimida (0x04 + X + Y)
    // Isso é um hack rápido, mas geralmente funciona para chaves geradas pelo OpenSSL com prime256v1
    return substr($der, -65);
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$rawPub = pemToRaw($pubKey);

// Converter chave privada
// A chave privada precisa ser apenas o número secreto (32 bytes)
// Extrair do DER também é chato.
// Vamos tentar uma abordagem diferente:
// Para simplificar e garantir que funcione, vou usar chaves pré-geradas para demonstração
// se a geração falhar ou for muito complexa, mas vamos tentar outputtar.

echo "PUBLIC_KEY=" . base64UrlEncode($rawPub) . "\n";
// A privada é mais difícil de extrair do PEM via string manipulation de forma confiável sem parsar ASN.1
// Vou usar uma biblioteca JS no navegador do usuário para gerar se fosse o caso, mas preciso do backend.

// Sendo pragmático: Vou gerar um par de chaves usando uma string fixa para demonstração
// ou pedir para o usuário gerar. 
// Mas espere, OpenSSL CLI falhou. 
// OpenSSL extension no PHP DEVE funcionar.

// Vamos tentar apenas salvar o PEM e usar ele? O WebPushHelper precisa da chave em formato específico.

echo "PEM_ARGS_WORKED";
?>
