<?php

namespace App\Filament\Resources\JobApplications\Tables;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Tables\Table;

class JobApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return JobAdmin::jobApplicationTable($table);
    }
}
