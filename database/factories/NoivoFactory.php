<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NoivoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
        ];
    }
}