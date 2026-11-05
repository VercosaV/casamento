<?php

namespace Tests\Feature;

use App\Models\Noivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NoivosPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_cria_o_perfil_e_o_noivo_consegue_logar(): void
    {
        $this->artisan('casamento:criar-noivo', ['--nome' => 'Ana', '--email' => 'ana@ex.com', '--senha' => 'segredo123'])
            ->assertSuccessful();

        $this->assertDatabaseHas('noivos', ['email' => 'ana@ex.com']);

        $this->post('/login', ['email' => 'ana@ex.com', 'password' => 'segredo123'])
            ->assertRedirect(route('painel'));
    }

    public function test_comando_recusa_senha_curta(): void
    {
        $this->artisan('casamento:criar-noivo', ['--nome' => 'Ana', '--email' => 'ana@ex.com', '--senha' => '123'])
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rodar_o_comando_de_novo_nao_duplica_e_troca_a_senha(): void
    {
        $args = ['--nome' => 'Ana', '--email' => 'ana@ex.com'];
        $this->artisan('casamento:criar-noivo', $args + ['--senha' => 'primeira123']);
        $this->artisan('casamento:criar-noivo', $args + ['--senha' => 'segunda1234']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('noivos', 1);
        $this->assertTrue(Hash::check('segunda1234', User::first()->password));
    }

    public function test_seeder_nao_cria_conta_de_teste_nem_conta_sem_credenciais(): void
    {
        $this->seed();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertDatabaseCount('noivos', 0); // .env de teste não tem NOIVO_*
    }

    public function test_noivo_edita_o_proprio_perfil_e_o_login_acompanha(): void
    {
        $user = $this->noivoLogado(['email' => 'velho@ex.com']);

        $this->put(route('perfil.update'), ['nome' => 'Novo Nome', 'email' => 'novo@ex.com'])
            ->assertRedirect(route('perfil.edit'));

        $this->assertSame('novo@ex.com', $user->fresh()->email);
        $this->assertSame('novo@ex.com', Noivo::first()->email); // os dois e-mails andam juntos
        $this->assertSame('Novo Nome', Noivo::first()->nome);
    }

    public function test_trocar_senha_exige_a_senha_atual(): void
    {
        $user = $this->noivoLogado(['password' => 'senhaantiga1']);
        $dados = ['nome' => $user->name, 'email' => $user->email, 'senha' => 'novasenha99', 'senha_confirmation' => 'novasenha99'];

        $this->put(route('perfil.update'), $dados + ['senha_atual' => 'errada'])
            ->assertSessionHasErrors('senha_atual');
        $this->assertTrue(Hash::check('senhaantiga1', $user->fresh()->password));

        $this->put(route('perfil.update'), $dados + ['senha_atual' => 'senhaantiga1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('novasenha99', $user->fresh()->password));
    }

    public function test_nao_pode_usar_o_email_de_outra_pessoa(): void
    {
        User::factory()->create(['email' => 'outra@ex.com']);
        $user = $this->noivoLogado();

        $this->put(route('perfil.update'), ['nome' => 'X', 'email' => 'outra@ex.com'])
            ->assertSessionHasErrors('email');
    }
}
