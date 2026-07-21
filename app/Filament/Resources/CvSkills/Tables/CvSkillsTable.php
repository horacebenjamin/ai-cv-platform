<?php

namespace App\Filament\Resources\CvSkills\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvSkillsTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::skillTable($table);
    }
}
