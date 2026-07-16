<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\MediaDerivative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaDerivative>
 */
class MediaDerivativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'derivative_type' => 'public',
            'filename' => $this->faker->uuid().'.jpg',
            'disk' => 'public',
            'size' => 1024,
            'mime_type' => 'image/jpeg',
            'media_id' => Media::factory(),
        ];
    }
}
