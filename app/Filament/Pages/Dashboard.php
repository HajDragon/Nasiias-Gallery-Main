<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * Stack the widgets in a single column so the statistics and message
     * tables use the full width of the content area.
     */
    public function getColumns(): int|array
    {
        return 1;
    }
}
