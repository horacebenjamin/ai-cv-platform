<?php

namespace App\Filament\Resources\CVS\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CVInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('title'),
                TextEntry::make('professional_summary')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('template.name')
                    ->label('Template')
                    ->placeholder('-'),
                TextEntry::make('status'),
                IconEntry::make('is_default')
                    ->boolean(),
                IconEntry::make('is_master')
                    ->boolean(),
                TextEntry::make('parent_cv_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('variant_name')
                    ->placeholder('-'),
                TextEntry::make('last_used_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('target_job_title')
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
