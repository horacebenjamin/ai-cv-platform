<?php

namespace App\Filament\Resources\CvProjects\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::projectForm($schema);
    }
}
