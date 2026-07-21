<?php

namespace App\Filament\Resources\CvLanguages\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvLanguagesTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::languageTable($table);
    }
}
