<?php

namespace App\Filament\Resources\CvCertifications\Schemas;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;

class CvCertificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return CvAdmin::certificationForm($schema);
    }
}
