<?php

namespace Tests\Feature;

use App\Models\Contribuicao;
use App\Models\Presente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContribuicaoTest extends TestCase
{
    use RefreshDatabase;

    private function envio(int $cotas): array
    {
        return [
            'quantidade_cotas' => $cotas,
            'nome'             => 'Ana',
            'email'            => 'ana@example.com',
            'mensagem'         => 'Parabéns!',
        ];
    }

    public function test_contribui_dentro_do_limite(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 4]);

        $this->post(route('presentes.contribuir', $presente), $this->envio(3))
            ->assertRedirect(route('presentes.index'))
            ->assertSessionHas('mensagem');

        $this->assertDatabaseHas('contribuicoes', [
            'presente_id'      => $presente->id,
            'quantidade_cotas' => 3,
        ]);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    public function test_nao_aceita_mais_cotas_do_que_restam(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 4]);
        Contribuicao::create(['presente_id' => $presente->id, 'quantidade_cotas' => 3, 'status' => 'paga']);

        $this->post(route('presentes.contribuir', $presente), $this->envio(2))
            ->assertSessionHas('erro');

        $this->assertSame(3, (int) Contribuicao::sum('quantidade_cotas'));
    }

    public function test_presente_esgotado_nao_aceita_mais_nada(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        Contribuicao::create(['presente_id' => $presente->id, 'quantidade_cotas' => 1, 'status' => 'paga']);

        $this->post(route('presentes.contribuir', $presente), $this->envio(1))
            ->assertSessionHas('erro');

        $this->assertDatabaseCount('contribuicoes', 1);
    }
}