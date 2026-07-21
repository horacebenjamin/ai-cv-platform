<?php

namespace App\Filament\Resources\CvSkills\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CvSkillInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cv.title')
                    ->label('Cv'),
                TextEntry::make('category')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('proficiency')
                    ->placeholder('-'),
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
