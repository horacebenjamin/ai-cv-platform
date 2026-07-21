<?php
namespace App\Filament\Resources\CVS\RelationManagers;
use App\Filament\Resources\Support\CvAdmin; use Filament\Actions\CreateAction; use Filament\Resources\RelationManagers\RelationManager; use Filament\Schemas\Schema; use Filament\Tables\Table;
class ReferencesRelationManager extends RelationManager { protected static string $relationship = 'references'; public function form(Schema $schema): Schema { return CvAdmin::referenceForm($schema, false); } public function table(Table $table): Table { return CvAdmin::referenceTable($table)->headerActions([CreateAction::make()]); } }
