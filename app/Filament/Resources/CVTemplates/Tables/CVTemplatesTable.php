<?php

namespace App\Filament\Resources\CVTemplates\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CVTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::templateTable($table);
    }
}
