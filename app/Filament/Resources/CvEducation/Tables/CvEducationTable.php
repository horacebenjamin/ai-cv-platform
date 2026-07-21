<?php

namespace App\Filament\Resources\CvEducation\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvEducationTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::educationTable($table);
    }
}
