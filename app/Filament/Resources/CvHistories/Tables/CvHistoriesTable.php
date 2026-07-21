<?php

namespace App\Filament\Resources\CvHistories\Tables;

use App\Models\CvHistory;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CvHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cv.title')->label('CV')->searchable()->sortable(),
                TextColumn::make('user.name')->label('User')->description(fn ($record) => $record->user?->email)->searchable()->sortable(),
                TextColumn::make('action')->badge()->searchable()->sortable(),
                TextColumn::make('created_at')->label('Created')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([SelectFilter::make('action')->options(fn () => CvHistory::query()->distinct()->pluck('action', 'action')->all())])
            ->recordActions([ViewAction::make()])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No CV history')
            ->emptyStateDescription('Immutable CV snapshots will appear here when recorded.');
    }
}
