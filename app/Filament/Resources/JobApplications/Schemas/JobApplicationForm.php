<?php

namespace App\Filament\Resources\JobApplications\Schemas;

use App\Filament\Resources\Support\JobAdmin;
use Filament\Schemas\Schema;

class JobApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return JobAdmin::jobApplicationForm($schema);
    }
}
