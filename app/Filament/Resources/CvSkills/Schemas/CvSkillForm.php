<?php

namespace App\Filament\Resources\CvSkills\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvSkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::skillForm($schema);
    }
}
