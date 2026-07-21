<?php

namespace App\Filament\Resources\AiRequests\Schemas;

use App\Filament\Resources\Support\AiAdmin;
use Filament\Schemas\Schema;

class AiRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return AiAdmin::aiRequestInfolist($schema);
    }
}
