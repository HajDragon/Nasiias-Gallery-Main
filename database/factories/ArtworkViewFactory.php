<?php

namespace Database\Factories;

use App\Models\Artwork;
use App\Models\ArtworkView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtworkView>
 */
class ArtworkViewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artwork_id' => Artwork::factory(),
            'user_id' => User::factory(),
            'viewed_at' => now(),
        ];
    }
}
