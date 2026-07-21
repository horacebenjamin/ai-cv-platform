<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\SavedJob;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JobManagementStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Companies', Company::query()->count())
                ->description('Employers being tracked')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('primary'),
            Stat::make('Saved Jobs', SavedJob::query()->count())
                ->description('Opportunities on the shortlist')
                ->icon(Heroicon::OutlinedBookmarkSquare)
                ->color('info'),
            Stat::make('Applications', JobApplication::query()->count())
                ->description('Applications across all stages')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary'),
            Stat::make('Interviews', JobApplication::query()->whereIn('status', ['interview', 'technical_test', 'final_interview'])->count())
                ->description('Active interview processes')
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('warning'),
            Stat::make('Offers', JobApplication::query()->whereIn('status', ['offer', 'accepted'])->count())
                ->description('Offers received or accepted')
                ->icon(Heroicon::OutlinedTrophy)
                ->color('success'),
            Stat::make('Rejected', JobApplication::query()->where('status', 'rejected')->count())
                ->description('Applications marked rejected')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger'),
        ];
    }
}
