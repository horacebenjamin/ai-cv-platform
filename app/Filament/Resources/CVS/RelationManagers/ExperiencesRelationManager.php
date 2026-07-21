<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class ExperiencesRelationManager extends RelationManager { protected static string $relationship = 'experiences'; public function form(Schema $schema): Schema { return CvAdmin::experienceForm($schema, false); } public function table(Table $table): Table { return CvAdmin::experienceTable($table)->headerActions([CreateAction::make()]); } }
