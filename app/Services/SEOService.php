<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class SEOService
{
    /**
     * @return array<string, mixed>
     */
    public function getSEOData(Model|SEOData|null $source = null): array
    {
        if ($source instanceof Model && method_exists($source, 'getDynamicSEOData')) {
            $seoData = $source->getDynamicSEOData();
        } elseif ($source instanceof SEOData) {
            $seoData = $source;
        } else {
            $seoData = new SEOData;
        }

        // Apply the same defaults as the TagManager
        $seoData = $this->applyDefaults($seoData);

        return [
            'title' => $seoData->title,
            'description' => $seoData->description,
            'author' => $seoData->author,
            'image' => $seoData->image,
            'url' => $seoData->url ?? url()->current(),
            'site_name' => $seoData->site_name,
            'type' => $seoData->type ?? 'website',
            'twitter_username' => $seoData->twitter_username,
            'published_time' => $seoData->published_time?->toISOString(),
            'modified_time' => $seoData->modified_time?->toISOString(),
            'section' => $seoData->section,
            'tags' => $seoData->tags,
            'robots' => $seoData->robots,
            'canonical_url' => $seoData->canonical_url,
            'locale' => $seoData->locale ?? app()->getLocale(),
            'favicon' => $seoData->favicon,
        ];
    }

    private function applyDefaults(SEOData $seoData): SEOData
    {
        $defaults = [
            'title' => config('seo.title.infer_title_from_url') ? $this->inferTitleFromUrl() : null,
            'description' => config('seo.description.fallback'),
            'image' => config('seo.image.fallback'),
            'site_name' => config('seo.site_name'),
            'author' => config('seo.author.fallback'),
            'twitter_username' => config('seo.twitter.@username') ? '@'.config('seo.twitter.@username') : null,
            'favicon' => config('seo.favicon'),
        ];

        foreach ($defaults as $property => $defaultValue) {
            if ($seoData->{$property} === null) {
                $seoData->{$property} = $defaultValue;
            }
        }

        if ($seoData->enableTitleSuffix && $seoData->title) {
            $seoData->title .= config('seo.title.suffix');

            if ($seoData->openGraphTitle) {
                $seoData->openGraphTitle .= config('seo.title.suffix');
            }
        }

        if ($seoData->image && ! $this->isAbsoluteUrl($seoData->image)) {
            $seoData->image = secure_url($seoData->image);
        }

        if ($seoData->favicon && ! $this->isAbsoluteUrl($seoData->favicon)) {
            $seoData->favicon = secure_url($seoData->favicon);
        }

        if (! $seoData->url) {
            $seoData->url = url()->current();
        }

        if ($seoData->url === url('/') && ($homepageTitle = config('seo.title.homepage_title'))) {
            $seoData->title = $homepageTitle;
        }

        return $seoData;
    }

    private function inferTitleFromUrl(): string
    {
        return Str::of(url()->current())
            ->afterLast('/')
            ->headline()
            ->toString();
    }

    private function isAbsoluteUrl(string $url): bool
    {
        return (bool) parse_url($url, PHP_URL_SCHEME);
    }
}
