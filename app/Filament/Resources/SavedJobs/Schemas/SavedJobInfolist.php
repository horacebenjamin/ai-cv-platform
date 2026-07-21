<?php

namespace App\Filament\Resources\SavedJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SavedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('company.name')
                    ->label('Company')
                    ->placeholder('-'),
                TextEntry::make('jobDescription.title')
                    ->label('Job description')
                    ->placeholder('-'),
                TextEntry::make('title'),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('salary_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_currency'),
                TextEntry::make('employment_type')
                    ->placeholder('-'),
                TextEntry::make('source_name'),
                TextEntry::make('source_url')
                    ->label('Source URL')->url(fn ($record) => $record->source_url, true)->openUrlInNewTab()->copyable()->placeholder('—'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')->badge(),
                TextEntry::make('saved_at')
                    ->dateTime(),
                TextEntry::make('applied_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
