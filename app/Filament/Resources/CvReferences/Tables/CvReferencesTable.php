<?php

namespace App\Filament\Resources\CvReferences\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvReferencesTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::referenceTable($table);
    }
}
