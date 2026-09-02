<?php

namespace Database\Seeders;

use App\Models\Noivo;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoivoSeeder extends Seeder
{
    public function run(): void
    {
        $noivos = [
            [
                'name' => 'Victor Hugo de Verçosa Geraldo',
                'email' => env('NOIVO_EMAIL'),
                'password' => env('NOIVO_PASSWORD'),
            ],
            [
                'name' => 'Kamilly da Silva Diouse',
                'email' => env('NOIVA_EMAIL'),
                'password' => env('NOIVA_PASSWORD'),
            ],
        ];

        foreach ($noivos as $dados) {
            $user = User::firstOrCreate(
                ['email' => $dados['email']],
                [
                    'name' => $dados['name'],
                    'password' => bcrypt($dados['password']),
                ]
            );

            Noivo::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nome' => $dados['name'],
                    'email' => $dados['email'],
                ]
            );
        }
    }
}