<?php

namespace App\Filament\Resources\AiRequests\Tables;

use App\Filament\Resources\Support\AiAdmin;
use Filament\Tables\Table;

class AiRequestsTable
{
    public static function configure(Table $table): Table
    {
        return AiAdmin::aiRequestTable($table);
    }
}
