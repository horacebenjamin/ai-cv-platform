<?php

namespace App\Filament\Resources\CvReferences\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CvReferenceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cv.title')
                    ->label('Cv'),
                TextEntry::make('name'),
                TextEntry::make('company')
                    ->placeholder('-'),
                TextEntry::make('job_title')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('relationship')
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
