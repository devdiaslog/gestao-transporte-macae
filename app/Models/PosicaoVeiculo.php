<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosicaoVeiculo extends Model
{
    protected $table = 'posicao_veiculos';

    protected $fillable = [
        'license_plate',
        'prefix',
        'chassis',
        'position_at',
        'latitude',
        'longitude',
        'speed',
        'ignition',
        'direction',
        'hodometer',
        'gps_status',
        'gsm_status',
        'event_type',
        'transmission_way',
        'rfid',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'position_at' => 'datetime',
            'synced_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'ignition' => 'boolean',
            'gsm_status' => 'boolean',
            'speed' => 'integer',
            'direction' => 'integer',
            'hodometer' => 'integer',
        ];
    }
}
