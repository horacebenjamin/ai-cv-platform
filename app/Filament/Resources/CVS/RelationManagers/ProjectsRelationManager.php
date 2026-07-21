<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class ProjectsRelationManager extends RelationManager { protected static string $relationship = 'projects'; public function form(Schema $schema): Schema { return CvAdmin::projectForm($schema, false); } public function table(Table $table): Table { return CvAdmin::projectTable($table)->headerActions([CreateAction::make()]); } }
