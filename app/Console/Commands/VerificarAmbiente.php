<?php

namespace App\Console\Commands;

use App\Models\Noivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Diagnóstico: confere de uma vez o que costuma dar errado na primeira execução.
// Cada linha mostra OK, AVISO ou ERRO e diz exatamente o que fazer.
class VerificarAmbiente extends Command
{
    protected $signature = 'casamento:verificar';

    protected $description = 'Confere se o ambiente está pronto (chaves, banco, noivos, storage, Stripe)';

    private int $erros = 0;

    private function ok(string $texto): void
    {
        $this->line("  <fg=green>OK</>     {$texto}");
    }

    private function aviso(string $texto, string $dica): void
    {
        $this->line("  <fg=yellow>AVISO</>  {$texto}");
        $this->line("         → {$dica}");
    }

    private function erro(string $texto, string $dica): void
    {
        $this->erros++;
        $this->line("  <fg=red>ERRO</>   {$texto}");
        $this->line("         → {$dica}");
    }

    public function handle(): int
    {
        $this->info('Verificando o ambiente...');
        $this->newLine();

        // 1) Chaves
        blank(config('app.key'))
            ? $this->erro('APP_KEY vazia', 'php artisan key:generate')
            : $this->ok('APP_KEY configurada');

        blank(config('jwt.secret'))
            ? $this->erro('JWT_SECRET ausente: o login vai falhar', 'php artisan jwt:secret   e depois   php artisan config:clear')
            : $this->ok('JWT_SECRET configurado');

        // 2) Banco
        try {
            DB::connection()->getPdo();
            $this->ok('Conexão com o banco ('.config('database.default').')');

            $faltando = collect(['users', 'noivos', 'convidados', 'presentes', 'contribuicoes', 'secoes'])
                ->reject(fn ($tabela) => Schema::hasTable($tabela));

            $faltando->isEmpty()
                ? $this->ok('Tabelas criadas')
                : $this->erro('Faltam tabelas: '.$faltando->implode(', '), 'php artisan migrate');

            if ($faltando->isEmpty()) {
                Noivo::count() >= 1
                    ? $this->ok(Noivo::count().' noivo(s) cadastrado(s)')
                    : $this->erro('Nenhum noivo cadastrado: ninguém consegue logar', 'php artisan casamento:criar-noivo');
            }
        } catch (\Throwable $e) {
            $this->erro('Não conectou ao banco: '.str($e->getMessage())->limit(90), 'confira DB_* no .env e se o MySQL está ligado');
        }

        // 3) Fotos
        file_exists(public_path('storage'))
            ? $this->ok('Link public/storage existe (upload de fotos)')
            : $this->aviso('Falta o link public/storage: as fotos enviadas não aparecem', 'php artisan storage:link');

        // 4) Testes
        extension_loaded('pdo_sqlite')
            ? $this->ok('Extensão pdo_sqlite carregada (necessária para php artisan test)')
            : $this->aviso('Extensão pdo_sqlite não está ligada: php artisan test não roda', 'ative extension=pdo_sqlite no php.ini');

        $xml = @file_get_contents(base_path('phpunit.xml')) ?: '';
        if (preg_match('/DB_CONNECTION"\s+value="mysql"/', $xml) && str_contains($xml, ':memory:')) {
            $this->erro('phpunit.xml usa mysql com banco ":memory:" (combinação inválida)', 'volte DB_CONNECTION para sqlite, ou use um banco MySQL SÓ de testes');
        } else {
            $this->ok('phpunit.xml com configuração de banco coerente');
        }

        // 5) Stripe
        $sk = (string) config('stripe.sk');
        if ($sk === '') {
            $this->aviso('Stripe desligado: o site só deixa "reservar" presentes', 'defina STRIPE_SK (sk_test_...) quando for ligar o pagamento');
        } else {
            str_starts_with($sk, 'sk_test_')
                ? $this->ok('Stripe em MODO DE TESTE (nenhum dinheiro real se move)')
                : $this->aviso('Stripe com chave que não é de teste', 'confirme que é isso mesmo e que o ambiente é produção');

            blank(config('stripe.webhook_secret'))
                ? $this->aviso('STRIPE_WEBHOOK_SECRET vazio: pagamentos não serão confirmados', 'stripe listen --forward-to localhost:8000/stripe/webhook  e copie o whsec_...')
                : $this->ok('STRIPE_WEBHOOK_SECRET configurado');
        }

        // 6) Produção
        if (app()->isProduction()) {
            config('app.debug')
                ? $this->erro('APP_DEBUG=true em produção expõe detalhes internos', 'APP_DEBUG=false')
                : $this->ok('APP_DEBUG desligado');

            str_starts_with((string) config('app.url'), 'https://')
                ? $this->ok('APP_URL em https')
                : $this->erro('APP_URL sem https: o Stripe devolve o convidado para o endereço errado', 'APP_URL=https://seu-dominio');
        }

        $this->newLine();
        $this->erros === 0
            ? $this->info('Tudo certo.')
            : $this->error("{$this->erros} problema(s) a resolver (linhas ERRO acima).");

        return $this->erros === 0 ? self::SUCCESS : self::FAILURE;
    }
}
