<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['password' => '55019401', 'name' => 'Jessica Pereira da Silva',              'email' => 'jessicap@vix.com.br',           'role' => UserRole::Administrador],
            ['password' => '55020614', 'name' => 'Guilherme Cerqueira de Moraes Santana', 'email' => 'guilhermecerqueira@vix.com.br', 'role' => UserRole::Operador],
            ['password' => '55022032', 'name' => 'Robson Ennes Costa',                    'email' => 'robsonennes@vix.com.br',        'role' => UserRole::Operador],
            ['password' => '55026035', 'name' => 'Joel de Oliveira de Souza',             'email' => 'joelo@vix.com.br',              'role' => UserRole::Operador],
            ['password' => '55028701', 'name' => 'Rafael Santos Martins',                 'email' => 'rafaelsmartins@vix.com.br',     'role' => UserRole::Operador],
            ['password' => '55028775', 'name' => 'James Barboza da Silva Martins',        'email' => 'james@vix.com.br',              'role' => UserRole::Operador],
            ['password' => '4059733',  'name' => 'Jorge Luiz Dias Leão',                  'email' => 'jorgeluiz@vix.com.br',          'role' => UserRole::Operador],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'status' => 'active',
                    'role' => $data['role'],
                ],
            );
        }
    }
}
