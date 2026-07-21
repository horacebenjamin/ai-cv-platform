<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class EducationRelationManager extends RelationManager { protected static string $relationship = 'education'; public function form(Schema $schema): Schema { return CvAdmin::educationForm($schema, false); } public function table(Table $table): Table { return CvAdmin::educationTable($table)->headerActions([CreateAction::make()]); } }
