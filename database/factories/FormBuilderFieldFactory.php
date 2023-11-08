<?php

namespace FluxErp\Database\Factories;

use FluxErp\Models\FormBuilderField;
use FluxErp\Models\FormBuilderSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FormBuilderFieldFactory extends Factory
{
    protected $model = FormBuilderField::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'ordering' => $this->faker->randomNumber(),
            'options' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
