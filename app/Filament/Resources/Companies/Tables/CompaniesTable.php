<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return JobAdmin::companyTable($table);
    }
}
