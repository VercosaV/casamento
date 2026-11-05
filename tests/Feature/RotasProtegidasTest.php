<?php

namespace Tests\Feature;

use App\Models\Noivo;
use App\Models\User; // Importe a model User se necessário
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotasProtegidasTest extends TestCase
{
    use RefreshDatabase;
    // dentro da classe, com `use RefreshDatabase;` no topo dela
    public function test_noivo_logado_acessa_o_painel(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->get(route('painel'))
            ->assertOk();
    }

    public function test_visitante_e_enviado_ao_login_ao_editar_noivo(): void
    {
        // Cria um usuário para satisfazer a chave estrangeira user_id (se houver factory de User)
        // Ou você pode usar User::create([...]) caso não use factories para User.
        $user = User::factory()->create();

        $noivo = Noivo::create([
            'user_id' => $user->id, // Associa o ID do usuário criado
            'nome' => 'Nome do Noivo',
            'email' => 'noivo@teste.com',
            'password' => bcrypt('password'),
        ]);

        $this->get("/noivos/{$noivo->id}/edit")->assertRedirect('/login');
    }

    public function test_visitante_nao_cria_presente(): void
    {
        $this->post('/presentes', ['nome' => 'Teste'])->assertRedirect('/login');
    }

    public function test_visitante_nao_exclui_presente(): void
    {
        $this->delete('/presentes/1')->assertRedirect('/login');
    }
}
