<?php

namespace Tests\Feature;

use App\Models\Convidado;
use App\Models\Presente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainelTest extends TestCase
{
    use RefreshDatabase;

    public function test_noivo_cria_presente_pelo_formulario_sem_erro_405(): void
    {
        $this->noivoLogado();

        // O formulário agora envia POST (antes enviava _method=PUT para uma rota POST)
        $this->get(route('presentes.create'))->assertOk()->assertDontSee('name="_method"', false);

        $this->post(route('presentes.store'), ['nome' => 'Panelas', 'valor' => '300', 'cotas_total' => 3])
            ->assertRedirect(route('presentes.admin'));

        $this->assertDatabaseHas('presentes', ['nome' => 'Panelas', 'cotas_total' => 3]);
    }

    public function test_noivo_cria_e_edita_convidado(): void
    {
        $this->noivoLogado();

        $this->post(route('convidados.store'), ['nome' => 'Fulano', 'email' => 'f@ex.com', 'adultos' => 1, 'criancas' => 0])
            ->assertRedirect(route('convidados.index'));

        $c = Convidado::where('email', 'f@ex.com')->firstOrFail();

        // "Não vai" zera as pessoas
        $this->put(route('convidados.update', $c), [
            'nome' => 'Fulano', 'email' => 'f@ex.com', 'confirma_presenca' => '0', 'adultos' => 2, 'criancas' => 1,
        ])->assertRedirect();

        $this->assertSame(0, $c->fresh()->adultos);
        $this->assertSame(0, $c->fresh()->criancas);
    }

    public function test_todas_as_telas_do_painel_abrem(): void
    {
        $this->noivoLogado();
        $c = Convidado::factory()->create();
        $p = Presente::factory()->create();

        foreach ([
            route('painel'), route('perfil.edit'), route('secoes.index'), route('secoes.create'),
            route('convidados.index'), route('convidados.create'), route('convidados.show', $c), route('convidados.edit', $c),
            route('presentes.admin'), route('presentes.create'), route('presentes.edit', $p), route('contribuicoes.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_exportar_csv_baixa(): void
    {
        $this->noivoLogado();
        Convidado::factory()->create(['nome' => '=cmd|calc']);

        $r = $this->get(route('convidados.exportar'));
        $r->assertOk();
        $this->assertStringContainsString("'=cmd|calc", $r->streamedContent()); // protege contra CSV injection
    }

    public function test_apagar_dados_funciona_mesmo_sem_a_tabela_recados(): void
    {
        Convidado::factory()->count(2)->create();

        $this->artisan('casamento:apagar-dados', ['--confirmo' => true])->assertSuccessful();

        $this->assertDatabaseCount('convidados', 0);
    }

    public function test_lista_de_presentes_pagina_de_9_em_9(): void
    {
        Presente::factory()->count(12)->create();

        $this->get(route('presentes.index'))->assertOk()->assertSee('page=2', false);
    }
}
