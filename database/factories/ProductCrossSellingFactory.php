<?php

namespace FluxErp\Database\Factories;

use FluxErp\Models\ProductCrossSelling;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCrossSellingFactory extends Factory
{
    protected $model = ProductCrossSelling::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
