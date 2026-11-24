<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GalleryMetric;

class BuildMetricGrowthSeries
{
    /**
     * Build a day-by-day series for a metric.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public function execute(GalleryMetric $metric, int $days = 30): array
    {
        $days = max(1, min($days, 365));
        $start = now()->subDays($days - 1)->startOfDay();
        $column = $metric->dateColumn();

        // Both expressions are literals so the query builder can label the
        // grouped rows without interpolating untrusted input.
        $expression = $column === 'viewed_at'
            ? 'date(viewed_at) as day, count(*) as total'
            : 'date(created_at) as day, count(*) as total';

        /** @var array<string, int> $counts */
        $counts = $metric->model()::query()
            ->where($column, '>=', $start)
            ->toBase()
            ->selectRaw($expression)
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $labels = [];
        $data = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);

            $labels[] = $date->format('M j');
            $data[] = (int) ($counts[$date->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
