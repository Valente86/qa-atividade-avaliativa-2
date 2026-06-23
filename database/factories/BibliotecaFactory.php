<?php

namespace Database\Factories;

use App\Models\Biblioteca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Biblioteca>
 */
class BibliotecaFactory extends Factory
{
    /**
     * O nome da model correspondente a esta factory.
     *
     * @var string
     */
    protected $model = Biblioteca::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'nome' => fake()->company(),
        // Adicione EXATAMENTE esta linha abaixo:
        'created_by' => \App\Models\User::factory(), 
    ];
}
}