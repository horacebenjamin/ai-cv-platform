<?php

namespace App\Filament\Resources\JobDescriptions\Schemas;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Schemas\Schema;

class JobDescriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return JobAdmin::jobDescriptionForm($schema);
    }
}
