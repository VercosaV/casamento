<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Antes havia aqui um "Test User" (test@example.com / password).
        // Isso criava uma conta que conseguia logar no painel em produção.
        $this->call([
            NoivoSeeder::class,
        ]);
    }
}
