<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\BuildMetricGrowthSeries;
use App\Enums\GalleryMetric;
use App\Filament\Pages\Dashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GalleryStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'Gallery overview';

    protected ?string $description = 'Totals across the whole gallery. Click a card to see its growth over time.';

    protected function getStats(): array
    {
        $growth = app(BuildMetricGrowthSeries::class);

        return array_map(
            function (GalleryMetric $metric) use ($growth): Stat {
                $series = $growth->execute($metric, 7);

                return Stat::make($metric->label(), number_format($metric->model()::query()->count()))
                    ->description($metric->description())
                    ->icon($metric->icon())
                    ->color($metric->color())
                    ->chart(collect($series['data']))
                    ->chartColor($metric->color())
                    ->url(Dashboard::getUrl(['metric' => $metric->value]));
            },
            GalleryMetric::cases(),
        );
    }
}
