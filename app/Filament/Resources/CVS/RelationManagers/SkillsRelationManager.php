<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class SkillsRelationManager extends RelationManager { protected static string $relationship = 'skills'; public function form(Schema $schema): Schema { return CvAdmin::skillForm($schema, false); } public function table(Table $table): Table { return CvAdmin::skillTable($table)->headerActions([CreateAction::make()]); } }
