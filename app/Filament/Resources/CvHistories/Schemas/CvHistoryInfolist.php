<?php

namespace App\Filament\Resources\CvHistories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CvHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event')->schema([
                TextEntry::make('cv.title')->label('CV'),
                TextEntry::make('user.name')->label('User'),
                TextEntry::make('action')->badge(),
                TextEntry::make('created_at')->label('Created')->dateTime('d M Y, H:i:s'),
            ])->columns(2),
            Section::make('Notes')->schema([TextEntry::make('notes')->placeholder('No notes were recorded.')->columnSpanFull()]),
            Section::make('Snapshot')->description('Immutable JSON captured for this event.')->schema([
                TextEntry::make('snapshot')->label('')->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}')->copyable()->columnSpanFull(),
            ]),
        ]);
    }
}
