<?php

namespace App\Filament\Resources\CvCertifications\Tables;

use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;

class CvCertificationsTable
{
    public static function configure(Table $table): Table
    {
        return CvAdmin::certificationTable($table);
    }
}
