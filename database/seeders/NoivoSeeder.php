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
            ['name' => 'Victor Hugo de Verçosa Geraldo', 'email' => env('NOIVO_EMAIL'), 'password' => env('NOIVO_PASSWORD')],
            ['name' => 'Kamilly da Silva Diouse',        'email' => env('NOIVA_EMAIL'), 'password' => env('NOIVA_PASSWORD')],
        ];

        foreach ($noivos as $dados) {
            // Sem e-mail ou senha no .env não dá para criar a conta com segurança.
            if (! $dados['email'] || ! $dados['password']) {
                $this->command?->warn("Pulei {$dados['name']}: faltam e-mail/senha no .env (ou use php artisan casamento:criar-noivo).");

                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $dados['email']],
                ['name' => $dados['name'], 'password' => $dados['password']] // o cast "hashed" faz o hash
            );

            Noivo::firstOrCreate(
                ['user_id' => $user->id],
                ['nome' => $dados['name'], 'email' => $dados['email']]
            );
        }
    }
}
