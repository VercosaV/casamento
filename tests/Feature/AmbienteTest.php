<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmbienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sem_jwt_secret_da_mensagem_clara_em_vez_de_erro_500(): void
    {
        User::factory()->create(['email' => 'a@ex.com', 'password' => 'senha12345']);
        config(['jwt.secret' => null, 'app.debug' => true]);

        $this->post('/login', ['email' => 'a@ex.com', 'password' => 'senha12345'])
            ->assertRedirect()
            ->assertSessionHasErrors(['email' => 'JWT_SECRET não configurado. Rode no terminal: php artisan jwt:secret (depois: php artisan config:clear)']);
    }

    public function test_em_producao_a_mensagem_nao_revela_configuracao(): void
    {
        config(['jwt.secret' => null, 'app.debug' => false]);

        $this->post('/login', ['email' => 'a@ex.com', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Não foi possível entrar agora. Tente novamente mais tarde.']);
    }

    public function test_criar_noivo_avisa_quando_falta_o_jwt_secret(): void
    {
        config(['jwt.secret' => null]);

        $this->artisan('casamento:criar-noivo', ['--nome' => 'Ana', '--email' => 'ana@ex.com', '--senha' => 'segredo123'])
            ->expectsOutputToContain('falta o JWT_SECRET')
            ->assertSuccessful();

        $this->assertDatabaseHas('noivos', ['email' => 'ana@ex.com']); // o perfil foi criado mesmo assim
    }

    public function test_verificar_aponta_jwt_ausente_e_falta_de_noivo(): void
    {
        config(['jwt.secret' => null]);

        $this->artisan('casamento:verificar')
            ->expectsOutputToContain('JWT_SECRET ausente')
            ->expectsOutputToContain('Nenhum noivo cadastrado')
            ->assertFailed();
    }

    public function test_verificar_passa_quando_o_essencial_esta_configurado(): void
    {
        config(['jwt.secret' => 'x', 'stripe.sk' => null]);
        $this->artisan('casamento:criar-noivo', ['--nome' => 'Ana', '--email' => 'ana@ex.com', '--senha' => 'segredo123']);

        $this->artisan('casamento:verificar')
            ->expectsOutputToContain('JWT_SECRET configurado')
            ->expectsOutputToContain('1 noivo(s) cadastrado(s)')
            ->assertSuccessful();
    }

    public function test_verificar_avisa_quando_faltam_pix_e_endereco_de_entrega(): void
    {
        config(['jwt.secret' => 'x', 'casamento.pix.chave' => null, 'casamento.entrega.endereco' => null]);

        $this->artisan('casamento:verificar')
            ->expectsOutputToContain('PIX_CHAVE vazia')
            ->expectsOutputToContain('ENDERECO_ENTREGA vazio');
    }
}
