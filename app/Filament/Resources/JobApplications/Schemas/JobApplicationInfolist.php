<?php

namespace App\Filament\Resources\JobApplications\Schemas;

use App\Models\CV;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JobApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('cv_id')->label('CV')->formatStateUsing(fn ($state): string => CV::query()->find($state)?->title ?? '—'),
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('jobDescription.title')
                    ->label('Job description')
                    ->placeholder('-'),
                TextEntry::make('status')->badge(),
                TextEntry::make('applied_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
