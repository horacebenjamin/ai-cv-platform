<?php

namespace App\Filament\Resources\CvExperiences\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::experienceForm($schema);
    }
}
