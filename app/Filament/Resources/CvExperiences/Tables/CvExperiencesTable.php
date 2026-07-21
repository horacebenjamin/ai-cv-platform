<?php

namespace App\Filament\Resources\CvExperiences\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvExperiencesTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::experienceTable($table);
    }
}
