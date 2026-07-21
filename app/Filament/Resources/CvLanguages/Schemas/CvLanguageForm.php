<?php

namespace App\Filament\Resources\CvLanguages\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvLanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::languageForm($schema);
    }
}
