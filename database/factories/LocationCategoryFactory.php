<?php

namespace Database\Factories;

use App\Models\LocationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationCategoryFactory extends Factory
{
    protected $model = LocationCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
