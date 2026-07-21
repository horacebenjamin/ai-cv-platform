<?php

namespace App\Filament\Resources\SavedJobs\Tables;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Tables\Table;

class SavedJobsTable
{
    public static function configure(Table $table): Table
    {
        return JobAdmin::savedJobTable($table);
    }
}
