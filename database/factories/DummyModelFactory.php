<?php

namespace Database\Factories;

use App\Models\DummyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DummyModel>
 */
class DummyModelFactory extends Factory
{
    protected $model = DummyModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
