<?php

namespace App\Filament\Resources\AiRequests\Schemas;

use App\Filament\Resources\Support\AiAdmin;
use Filament\Schemas\Schema;

class AiRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return AiAdmin::aiRequestForm($schema);
    }
}
