<?php

namespace Database\Factories;

use App\Models\Presente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presente>
 */
class PresenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(3, true),
            'descricao' => fake()->sentence(),
            'valor' => fake()->randomFloat(2, 50, 2000),
            'cotas_total' => 1,
        ];
    }
}
