<?php
namespace App\Filament\Resources\CVS\Schemas;
use App\Filament\Resources\Support\CvAdmin;
use Filament\Schemas\Schema;
class CVForm { public static function configure(Schema $schema): Schema { return CvAdmin::cvForm($schema); } }
