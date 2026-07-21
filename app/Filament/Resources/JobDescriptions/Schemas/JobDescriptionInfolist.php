<?php

namespace App\Filament\Resources\JobDescriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobDescriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Job Details')->schema([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('title')->label('Job Title'),
                TextEntry::make('location')
                    ->placeholder('—'),
                TextEntry::make('salary')
                    ->placeholder('—'),
                TextEntry::make('employment_type')
                    ->label('Employment Type')->badge()->placeholder('—'),
                TextEntry::make('source_url')
                    ->label('Source URL')->url(fn ($record) => $record->source_url, true)->openUrlInNewTab()->copyable()->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime('d M Y, H:i'),
                TextEntry::make('updated_at')
                    ->dateTime('d M Y, H:i'),
            ])->columns(2),
            Section::make('Original Job Description')->schema([
                TextEntry::make('description')->html()->columnSpanFull(),
            ]),
        ]);
    }
}
