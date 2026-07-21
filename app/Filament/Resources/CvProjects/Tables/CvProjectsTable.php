<?php

namespace App\Filament\Resources\CvProjects\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvProjectsTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::projectTable($table);
    }
}
