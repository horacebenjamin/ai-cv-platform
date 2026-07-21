<?php

namespace App\Filament\Resources\JobDescriptions\Tables;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Tables\Table;

class JobDescriptionsTable
{
    public static function configure(Table $table): Table
    {
        return JobAdmin::jobDescriptionTable($table);
    }
}
