<?php

namespace App\Console\Commands;

use App\Models\Noivo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CriarNoivo extends Command
{
    protected $signature = 'casamento:criar-noivo
                            {--nome= : Nome completo}
                            {--email= : E-mail de login}
                            {--senha= : Senha (se omitida, será pedida sem aparecer na tela)}';

    protected $description = 'Cria (ou atualiza a senha de) um perfil de noivo para entrar no painel';

    public function handle(): int
    {
        $nome  = $this->option('nome') ?? $this->ask('Nome completo');
        $email = $this->option('email') ?? $this->ask('E-mail de login');
        $senha = $this->option('senha') ?? $this->secret('Senha (mínimo 8 caracteres)');

        $validador = Validator::make(
            ['nome' => $nome, 'email' => $email, 'senha' => $senha],
            ['nome' => 'required|string|max:255', 'email' => 'required|email|max:255', 'senha' => 'required|string|min:8']
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($nome, $email, $senha) {
            // updateOrCreate: rodar de novo com o mesmo e-mail redefine nome e senha
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $nome, 'password' => $senha]
            );

            Noivo::updateOrCreate(
                ['user_id' => $user->id],
                ['nome' => $nome, 'email' => $email]
            );
        });

        $this->info("Perfil pronto: {$email}. Já pode entrar em /login.");

        return self::SUCCESS;
    }
}
