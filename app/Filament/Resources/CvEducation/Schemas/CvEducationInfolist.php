<?php

namespace App\Filament\Resources\CvEducation\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CvEducationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cv.title')
                    ->label('Cv'),
                TextEntry::make('institution'),
                TextEntry::make('qualification'),
                TextEntry::make('field_of_study')
                    ->placeholder('-'),
                TextEntry::make('grade')
                    ->placeholder('-'),
                TextEntry::make('start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
