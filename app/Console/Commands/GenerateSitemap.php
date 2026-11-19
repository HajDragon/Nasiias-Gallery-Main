<?php

namespace App\Console\Commands;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

#[Signature('app:generate-sitemap')]
#[Description('Generate the sitemap for the website')]
class GenerateSitemap extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create()
            ->add(Url::create(url('/'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(1.0))
            ->add(Url::create(url('/gallery'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.9));

        $artworks = Artwork::query()->with('category')->get();
        foreach ($artworks as $artwork) {
            $sitemap->add(Url::create(url("/gallery/{$artwork->id}"))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8)
                ->setLastModificationDate($artwork->updated_at ?? $artwork->created_at));
        }

        $categories = Category::query()->whereHas('artworks')->get();
        foreach ($categories as $category) {
            if ($category->slug) {
                $sitemap->add(Url::create(url("/gallery?category={$category->slug}"))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7)
                    ->setLastModificationDate($category->updated_at));
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully at '.public_path('sitemap.xml'));

        return self::SUCCESS;
    }
}
