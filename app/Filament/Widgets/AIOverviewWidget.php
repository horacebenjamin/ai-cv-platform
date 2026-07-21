<?php

namespace App\Filament\Widgets;

use App\Models\AiRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AIOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $requests = AiRequest::query();

        return [
            Stat::make('Total AI Requests', (clone $requests)->count())
                ->description('All recorded AI operations')
                ->icon(Heroicon::OutlinedCpuChip)
                ->color('primary'),
            Stat::make('Requests Today', (clone $requests)->whereDate('created_at', today())->count())
                ->description('Requests created today')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
            Stat::make('Credits Used', number_format((int) (clone $requests)->sum('tokens_used')))
                ->description('Recorded usage across all requests')
                ->icon(Heroicon::OutlinedBolt)
                ->color('warning'),
            Stat::make('Estimated AI Cost', '$'.number_format((float) (clone $requests)->sum('cost'), 2))
                ->description('Cumulative estimated provider cost')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),
            Stat::make('Average Processing Time', self::formatDuration((float) (clone $requests)->whereNotNull('processing_time_ms')->avg('processing_time_ms')))
                ->description('Mean duration for timed requests')
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('Failed Requests', (clone $requests)->where('status', 'failed')->count())
                ->description('Requests requiring attention')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }

    private static function formatDuration(float $milliseconds): string
    {
        return $milliseconds >= 1000
            ? number_format($milliseconds / 1000, 2).' s'
            : number_format($milliseconds).' ms';
    }
}
