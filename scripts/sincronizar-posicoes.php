<?php

/**
 * Sincronização de posições de veículos — Trimble DaaS / Vfleets
 *
 * Executa internamente a rota GET /sync/posicoes passando a chave
 * configurada em VFLEETS_SYNC_KEY no .env do projeto.
 *
 * Exemplo de uso no cron (ajuste o caminho conforme o servidor):
 *   php /caminho/para/projeto/scripts/sincronizar-posicoes.php >> /caminho/para/logs/sync.log 2>&1
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Lê a chave diretamente da config (carregada do .env)
$key = $app->make('config')->get('services.vfleets.sync_key');

if (empty($key)) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] ERRO: VFLEETS_SYNC_KEY não configurada no .env." . PHP_EOL;
    exit(1);
}

$request = Illuminate\Http\Request::create('/sync/posicoes', 'GET', ['key' => $key]);

$response = $kernel->handle($request);

$status    = $response->getStatusCode();
$body      = $response->getContent();
$timestamp = date('Y-m-d H:i:s');

echo "[{$timestamp}] Status: {$status}" . PHP_EOL;
echo "[{$timestamp}] {$body}" . PHP_EOL;

$kernel->terminate($request, $response);

exit($status === 200 ? 0 : 1);
