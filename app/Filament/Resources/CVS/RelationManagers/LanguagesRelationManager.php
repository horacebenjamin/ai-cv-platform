<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class LanguagesRelationManager extends RelationManager { protected static string $relationship = 'languages'; public function form(Schema $schema): Schema { return CvAdmin::languageForm($schema, false); } public function table(Table $table): Table { return CvAdmin::languageTable($table)->headerActions([CreateAction::make()]); } }
