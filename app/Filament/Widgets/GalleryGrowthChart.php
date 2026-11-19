<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\BuildMetricGrowthSeries;
use App\Enums\GalleryMetric;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class GalleryGrowthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?bool $isLazyLoaded = false;

    /**
     * Set by clicking one of the overview cards, e.g. ?metric=likes.
     */
    public ?string $metric = null;

    protected ?string $maxHeight = '320px';

    public function mount(): void
    {
        $this->metric = GalleryMetric::tryFrom((string) request()->query('metric'))?->value;

        parent::mount();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            7 => 'Last 7 days',
            30 => 'Last 30 days',
            90 => 'Last 90 days',
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->currentMetric()->label().' growth';
    }

    public function getDescription(): string|Htmlable|null
    {
        return 'New '.strtolower($this->currentMetric()->label()).' per day.';
    }

    public function getColor(): string
    {
        return $this->currentMetric()->color();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $metric = $this->currentMetric();
        $days = (int) ($this->filter ?? 30);

        if (! in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $series = app(BuildMetricGrowthSeries::class)->execute($metric, $days);
        $color = $metric->chartColor();

        return [
            'datasets' => [
                [
                    'label' => $metric->label(),
                    'data' => $series['data'],
                    'borderColor' => $color,
                    'backgroundColor' => $color.'33',
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => $color,
                    'fill' => true,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    /**
     * @return array<string, mixed>|RawJs|null
     */
    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    private function currentMetric(): GalleryMetric
    {
        return GalleryMetric::tryFrom((string) $this->metric) ?? GalleryMetric::Artworks;
    }
}
