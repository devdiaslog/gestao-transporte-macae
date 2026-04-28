<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['password' => '55019401', 'name' => 'Jessica Pereira da Silva',              'email' => 'jessicap@vix.com.br'],
            ['password' => '55020614', 'name' => 'Guilherme Cerqueira de Moraes Santana', 'email' => 'guilhermecerqueira@vix.com.br'],
            ['password' => '55022032', 'name' => 'Robson Ennes Costa',                    'email' => 'robsonennes@vix.com.br'],
            ['password' => '55026035', 'name' => 'Joel de Oliveira de Souza',             'email' => 'joelo@vix.com.br'],
            ['password' => '55028701', 'name' => 'Rafael Santos Martins',                 'email' => 'rafaelsmartins@vix.com.br'],
            ['password' => '55028775', 'name' => 'James Barboza da Silva Martins',        'email' => 'james@vix.com.br'],
            ['password' => '4059733',  'name' => 'Jorge Luiz Dias Leão',                  'email' => 'jorgeluiz@vix.com.br'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'status' => 'active',
                ],
            );
        }
    }
}
