<?php

use App\Services\VfleetsService;
use Illuminate\Contracts\Http\Kernel;

/**
 * Script de sincronização de posições de veículos — Trimble DaaS
 *
 * Executar via cron:
 *   * /5 * * * * /usr/local/bin/php /caminho/para/projeto/sincronizar-posicoes.php >> /caminho/para/logs/posicoes.log 2>&1
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$app->boot();

try {
    /** @var VfleetsService $service */
    $service = $app->make(VfleetsService::class);

    $total = $service->sincronizar();

    echo '['.date('Y-m-d H:i:s').'] Sincronizadas '.$total.' posições.'.PHP_EOL;

} catch (Throwable $e) {
    echo '['.date('Y-m-d H:i:s').'] ERRO: '.$e->getMessage().PHP_EOL;
    exit(1);
}
