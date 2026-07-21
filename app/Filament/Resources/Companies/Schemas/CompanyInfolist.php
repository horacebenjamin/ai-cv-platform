<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company Details')->schema([
                TextEntry::make('name')->label('Company Name'),
                TextEntry::make('website')->url(fn ($record) => $record->website, true)->openUrlInNewTab()->copyable()
                    ->placeholder('—'),
                TextEntry::make('location')
                    ->placeholder('—'),
                TextEntry::make('industry')
                    ->badge()->placeholder('—'),
                TextEntry::make('company_size')
                    ->label('Size')->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime('d M Y, H:i'),
                TextEntry::make('updated_at')
                    ->dateTime('d M Y, H:i'),
            ])->columns(2),
            Section::make('Notes')->schema([
                TextEntry::make('description')->label('')->placeholder('No notes recorded.')->columnSpanFull(),
            ]),
        ]);
    }
}
