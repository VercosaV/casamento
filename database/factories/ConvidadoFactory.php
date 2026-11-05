<?php

namespace Database\Factories;

use App\Models\Convidado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Convidado>
 */
class ConvidadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(##) 9####-####'),
            'confirma_presenca' => null,
            'adultos' => 1,
            'criancas' => 0,
        ];
    }
}
