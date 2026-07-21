<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return JobAdmin::companyForm($schema);
    }
}
