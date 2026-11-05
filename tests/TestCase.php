<?php

namespace Tests;

use App\Models\Noivo;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Cria um usuário COM registro em noivos e o deixa logado no guard "api".
    protected function noivoLogado(array $atributos = []): User
    {
        $user = User::factory()->create($atributos);
        Noivo::create(['user_id' => $user->id, 'nome' => $user->name, 'email' => $user->email]);
        $this->actingAs($user, 'api');

        return $user;
    }
}
