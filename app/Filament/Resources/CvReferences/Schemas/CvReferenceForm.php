<?php

namespace App\Filament\Resources\CvReferences\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvReferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::referenceForm($schema);
    }
}
