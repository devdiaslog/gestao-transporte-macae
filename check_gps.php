<?php

use App\Http\Controllers\ControlTowerController;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$ctrl = app(ControlTowerController::class);
$ref = new ReflectionMethod($ctrl, 'fetchVehicles');
$ref->setAccessible(true);
$vehicles = $ref->invoke($ctrl);

$withCoords = array_filter($vehicles, fn ($v) => ! empty($v['Latitude']) && $v['Latitude'] !== '0' && $v['Latitude'] !== '' && $v['Latitude'] !== null
);

echo 'Veículos com coordenadas: '.count($withCoords).' / '.count($vehicles)."\n\n";

foreach (array_slice($withCoords, 0, 6) as $v) {
    echo "  CM={$v['CM']} | Status={$v['Status']} | Lat={$v['Latitude']} | Lon={$v['Longitude']}\n";
}

// Mostra valores únicos de Latitude para ver o formato
echo "\nFormato de amostra Lat/Lon:\n";
foreach (array_slice($withCoords, 0, 3) as $v) {
    echo "  Lat='".$v['Latitude']."' Lon='".$v['Longitude']."'\n";
}
