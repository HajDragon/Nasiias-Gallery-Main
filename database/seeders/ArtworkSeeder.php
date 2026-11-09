<?php

namespace Database\Seeders;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArtworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Abstract', 'slug' => 'abstract'],
            ['name' => 'Geometric', 'slug' => 'geometric'],
            ['name' => 'Color Field', 'slug' => 'color-field'],
            ['name' => 'Expressionism', 'slug' => 'expressionism'],
            ['name' => 'Minimalism', 'slug' => 'minimalism'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $paintings = [
            [
                'title' => 'Vermilion Weather',
                'category' => 'Expressionism',
                'image_url' => 'https://images.unsplash.com/photo-1605721911519-3dfeb3be25e7?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Red, blue, and yellow abstract painting',
                'description' => 'A storm held mid-flash — reds and yellows torn across cobalt. Painted in one sitting while the sky outside turned the same bruised blue.',
            ],
            [
                'title' => 'A Quiet Current',
                'category' => 'Minimalism',
                'image_url' => 'https://images.unsplash.com/photo-1618331833071-ce81bd50d300?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Blue, white, and yellow abstract painting',
                'description' => 'Near-white space with a single slow drift of color. The piece rewards standing still — the longer you look, the more the surface moves.',
            ],
            [
                'title' => 'The Shape of Memory',
                'category' => 'Geometric',
                'image_url' => 'https://images.unsplash.com/photo-1541961017774-22349e4a1262?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Colorful geometric abstract painting',
                'description' => 'Hard edges, warm colors — the way a childhood room comes back to you in blocks rather than details.',
            ],
            [
                'title' => 'Soft Geometry',
                'category' => 'Geometric',
                'image_url' => 'https://images.unsplash.com/photo-1533208087231-c3618eab623c?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Pastel abstract painting',
                'description' => 'Pastel planes that refuse to argue with each other. A gentle exercise in order: circles, drifts, and soft corners.',
            ],
            [
                'title' => 'In Bloom',
                'category' => 'Abstract',
                'image_url' => 'https://images.unsplash.com/photo-1533157950006-c38844053d55?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'White and red abstract painting',
                'description' => 'White ground, red gestures — a garden opening fast. Each mark made in a single stroke, no corrections allowed.',
            ],
            [
                'title' => 'Blue Hour',
                'category' => 'Color Field',
                'image_url' => 'https://images.unsplash.com/photo-1531489956451-20957fab52f2?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Deep blue abstract painting',
                'description' => 'The twenty minutes after sunset when everything turns the same shade of blue. Thin layered washes until the surface holds light like water.',
            ],
            [
                'title' => 'Garden After Rain',
                'category' => 'Abstract',
                'image_url' => 'https://images.unsplash.com/photo-1618331835717-801e976710b2?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Yellow, green, and white abstract painting',
                'description' => 'Yellow, green, and white breaking through wet air. Mixed from memory of a morning walk the day after a storm.',
            ],
            [
                'title' => 'Between Two Tides',
                'category' => 'Expressionism',
                'image_url' => 'https://images.unsplash.com/photo-1586032788085-d75f745f26e0?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Red, blue, and white abstract painting',
                'description' => 'Red and white pulled against blue — the moment a wave decides which way to go. Painted quickly, before the color could settle.',
            ],
            [
                'title' => 'The Last Light',
                'category' => 'Color Field',
                'image_url' => 'https://images.unsplash.com/photo-1531913764164-f85c52e6e654?auto=format&fit=crop&w=1200&q=85',
                'alt_text' => 'Blue and red abstract painting',
                'description' => 'Warm red sinking into deep blue — the final minutes of daylight held flat on canvas. Best viewed in low light.',
            ],
        ];

        foreach ($paintings as $painting) {
            $category = Category::where('slug', Str::slug($painting['category']))->first();
            if ($category) {
                Artwork::firstOrCreate(
                    ['title' => $painting['title']],
                    [
                        'category_id' => $category->id,
                        'image_url' => $painting['image_url'],
                        'alt_text' => $painting['alt_text'],
                        'description' => $painting['description'],
                        'view_count' => fake()->numberBetween(10, 500),
                        'like_count' => fake()->numberBetween(0, 100),
                    ]
                );
            }
        }
    }
}
