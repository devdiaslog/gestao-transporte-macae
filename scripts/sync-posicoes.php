<?php

/**
 * Script de sincronização de posições — chamado via cron.
 * Faz uma requisição HTTP para a rota interna de sync.
 *
 * Uso no cron do cPanel:
 *   php /home/usuario/scripts/sync-posicoes.php
 */

$url = 'https://transporte.vixplancon.com/sync/posicoes';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'CronSync/1.0',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

$timestamp = date('Y-m-d H:i:s');

if ($error) {
    echo "[{$timestamp}] ERRO cURL: {$error}" . PHP_EOL;
    exit(1);
}

if ($httpCode !== 200) {
    echo "[{$timestamp}] ERRO HTTP {$httpCode}: {$response}" . PHP_EOL;
    exit(1);
}

echo "[{$timestamp}] OK — {$response}" . PHP_EOL;
exit(0);
