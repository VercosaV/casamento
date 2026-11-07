<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotasProtegidasTest extends TestCase
{
    use RefreshDatabase;

    public function test_noivo_logado_acessa_o_painel(): void
    {
        $this->noivoLogado();

        $this->get(route('painel'))->assertOk();
    }

    public function test_usuario_sem_registro_de_noivo_leva_403(): void
    {
        // Existe em users (ex.: conta de teste esquecida), mas não é noivo
        $this->actingAs(User::factory()->create(), 'api');

        $this->get(route('painel'))->assertForbidden();
        $this->get(route('secoes.index'))->assertForbidden();
    }

    public function test_visitante_vai_para_o_login_em_todas_as_areas_dos_noivos(): void
    {
        foreach (['/painel', '/perfil', '/secoes', '/convidados', '/presentes-admin', '/presentes/create', '/pedidos'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_visitante_nao_cria_nem_exclui_presente(): void
    {
        $this->post('/presentes', ['nome' => 'Teste'])->assertRedirect('/login');
        $this->delete('/presentes/1')->assertRedirect('/login');
    }

    public function test_visitante_nao_altera_secoes(): void
    {
        $this->put('/secoes/1', ['titulo' => 'Hackeado'])->assertRedirect('/login');
        $this->post('/secoes/1/visibilidade')->assertRedirect('/login');
        $this->delete('/secoes/2')->assertRedirect('/login');
    }
}
