<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApagarDadosConvidados extends Command
{
    protected $signature = 'casamento:apagar-dados {--confirmo : executa sem perguntar}';

    protected $description = 'Apaga convidados, escolhas de presentes e recados (use depois do casamento)';

    public function handle(): int
    {
        if (! $this->option('confirmo')
            && ! $this->confirm('Isso apaga TODOS os convidados, escolhas e recados. Continuar?')) {
            return self::FAILURE;
        }

        DB::transaction(function () {
            DB::table('contribuicoes')->delete();
            // A tabela recados só existe se você fez o mural (passo 6.1).
            if (Schema::hasTable('recados')) {
                DB::table('recados')->delete();
            }

            DB::table('convidados')->delete();
        });

        $this->info('Dados apagados.');

        return self::SUCCESS;
    }
}