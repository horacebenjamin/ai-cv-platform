<?php
namespace App\Filament\Resources\CVS\Tables;
use App\Filament\Resources\Support\CvAdmin;
use Filament\Tables\Table;
class CVSTable { public static function configure(Table $table): Table { return CvAdmin::cvTable($table); } }
