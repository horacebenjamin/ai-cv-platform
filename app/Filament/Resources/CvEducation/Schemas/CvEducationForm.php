<?php

namespace App\Filament\Resources\CvEducation\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvEducationForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::educationForm($schema);
    }
}
