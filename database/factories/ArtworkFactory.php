<?php

namespace Database\Factories;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artwork>
 */
class ArtworkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'image_url' => fake()->imageUrl(),
            'alt_text' => fake()->sentence(),
            'view_count' => 0,
            'like_count' => 0,
        ];
    }
}
