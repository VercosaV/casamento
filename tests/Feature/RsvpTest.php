<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return array_merge([
            'nome'              => 'Maria Silva',
            'email'             => 'maria@example.com',
            'confirma_presenca' => '1',
            'adultos'           => 2,
            'criancas'          => 1,
            'consentimento'     => '1',
        ], $extra);
    }

    public function test_confirma_presenca_e_grava_acompanhantes(): void
    {
        $this->post(route('rsvp.store'), $this->dados())
            ->assertRedirect(route('presentes.index'));

        $this->assertDatabaseHas('convidados', [
            'email'             => 'maria@example.com',
            'confirma_presenca' => true,
            'adultos'           => 2,
            'criancas'          => 1,
        ]);
    }

    public function test_responder_de_novo_atualiza_o_mesmo_registro(): void
    {
        $this->post(route('rsvp.store'), $this->dados());
        $this->post(route('rsvp.store'), $this->dados(['confirma_presenca' => '0']))
            ->assertRedirect(route('home'));

        $this->assertDatabaseCount('convidados', 1);
        $this->assertDatabaseHas('convidados', [
            'email'             => 'maria@example.com',
            'confirma_presenca' => false,
            'adultos'           => 0,
            'criancas'          => 0,
        ]);
    }

    public function test_exige_consentimento(): void
    {
        $this->post(route('rsvp.store'), $this->dados(['consentimento' => null]))
            ->assertSessionHasErrors('consentimento');

        $this->assertDatabaseCount('convidados', 0);
    }
}